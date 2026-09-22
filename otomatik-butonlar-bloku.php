<?php
/**
 * Plugin Name: Otomatik Butonlar Bloku
 * Description: Seçilen kategorideki en yeni yazıları Gutenberg bloğu olarak şık kutucuklarla otomatik gösterir.
 * Plugin URI: https://bursa.mayahukuk.com
 * Version: 1.6.1
 * Author: Maya Hukuk
 * Author URI: https://bursa.mayahukuk.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Text Domain: otomatik-butonlar-bloku
 *
 * @package OtomatikButonlarBloku
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OTOBUTON_VERSION', '1.6.1' );
define( 'OTOBUTON_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTOBUTON_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once OTOBUTON_PLUGIN_DIR . 'includes/class-attributes.php';
require_once OTOBUTON_PLUGIN_DIR . 'includes/class-block-store.php';
require_once OTOBUTON_PLUGIN_DIR . 'includes/class-rest.php';
require_once OTOBUTON_PLUGIN_DIR . 'includes/class-abilities.php';

/**
 * Boot the REST layer and (when the Abilities API is present) the MCP tools.
 *
 * @return void
 */
function otobuton_boot_services(): void {
	new OTOBUTON_REST();
	new OTOBUTON_Abilities();
}
add_action( 'plugins_loaded', 'otobuton_boot_services' );

/**
 * Keep numeric block attributes inside the intended editor limits.
 *
 * @param mixed $value   Raw attribute value.
 * @param int   $min     Minimum allowed value.
 * @param int   $max     Maximum allowed value.
 * @param int   $default Fallback value.
 * @return int
 */
function otobuton_clamp_int( $value, int $min, int $max, int $default ): int {
	$value = is_numeric( $value ) ? (int) $value : $default;

	return max( $min, min( $max, $value ) );
}

/**
 * Resolve the configured post limit.
 *
 * Zero means unlimited total posts. The caller still chooses a safe page
 * size so an unlimited archive is never loaded in one request.
 *
 * @param mixed $value    Raw block attribute value.
 * @param int   $fallback Fallback value for malformed attributes.
 * @return int
 */
function otobuton_get_posts_limit( $value, int $fallback = 0 ): int {
	if ( ! is_numeric( $value ) ) {
		return $fallback;
	}

	$value = (int) $value;

	return $value >= 0 ? $value : $fallback;
}

/**
 * Build a front-end pagination URL for this block instance.
 *
 * @param string $page_query_key Query string key used by the block instance.
 * @param int    $page           Target page number.
 * @param string $section_id     Section id used as the scroll target.
 * @return string
 */
function otobuton_get_pagination_url( string $page_query_key, int $page, string $section_id ): string {
	$url = remove_query_arg( $page_query_key );

	if ( $page > 1 ) {
		$url = add_query_arg( $page_query_key, $page, $url );
	}

	return $url . '#' . rawurlencode( $section_id );
}

/**
 * Build a compact numbered pagination list.
 *
 * A zero entry represents a visual ellipsis between page ranges.
 *
 * @param int $current_page Current page number.
 * @param int $total_pages  Total page count.
 * @return array<int>
 */
function otobuton_get_pagination_pages( int $current_page, int $total_pages ): array {
	if ( $total_pages <= 7 ) {
		return range( 1, max( 1, $total_pages ) );
	}

	$pages = array( 1 );

	if ( $current_page > 3 ) {
		$pages[] = 0;
	}

	$start_page = max( 2, $current_page - 1 );
	$end_page   = min( $total_pages - 1, $current_page + 1 );

	for ( $page = $start_page; $page <= $end_page; $page++ ) {
		$pages[] = $page;
	}

	if ( $current_page < $total_pages - 2 ) {
		$pages[] = 0;
	}

	$pages[] = $total_pages;

	return $pages;
}

/**
 * Register the Gutenberg block and its assets.
 *
 * @return void
 */
function otobuton_register_category_post_buttons_block(): void {
	$block_dir = OTOBUTON_PLUGIN_DIR . 'blocks/category-post-buttons';
	$block_url = OTOBUTON_PLUGIN_URL . 'blocks/category-post-buttons/';

	wp_register_script(
		'otobuton-category-post-buttons-editor',
		$block_url . 'editor.js',
		array(
			'wp-block-editor',
			'wp-blocks',
			'wp-components',
			'wp-data',
			'wp-element',
			'wp-i18n',
			'wp-server-side-render',
		),
		filemtime( $block_dir . '/editor.js' ),
		true
	);

	wp_register_script(
		'otobuton-category-post-buttons-view',
		$block_url . 'view.js',
		array(),
		filemtime( $block_dir . '/view.js' ),
		true
	);

	wp_register_style(
		'otobuton-category-post-buttons-style',
		$block_url . 'style.css',
		array(),
		filemtime( $block_dir . '/style.css' )
	);

	wp_register_style(
		'otobuton-category-post-buttons-editor-style',
		$block_url . 'editor.css',
		array( 'otobuton-category-post-buttons-style' ),
		filemtime( $block_dir . '/editor.css' )
	);

	register_block_type(
		$block_dir,
		array(
			'render_callback' => 'otobuton_render_category_post_buttons',
		)
	);
}
add_action( 'init', 'otobuton_register_category_post_buttons_block' );

/**
 * Render selected category posts on the server so frontend content is always current.
 *
 * @param array<string,mixed> $attributes Block attributes.
 * @return string
 */
function otobuton_render_category_post_buttons( array $attributes ): string {
	$attributes               = otobuton_normalize_attributes( $attributes );
	$category_id              = (int) $attributes['categoryId'];
	$columns                  = (int) $attributes['columns'];
	$rows                     = (int) $attributes['rows'];
	$posts_limit              = (int) $attributes['postsPerPage'];
	$posts_per_page           = otobuton_resolve_page_size( $attributes );
	$sort_by                  = (string) $attributes['sortBy'];
	$sort_order               = (string) $attributes['sortOrder'];
	$show_excerpt             = (bool) $attributes['showExcerpt'];
	$show_date                = (bool) $attributes['showDate'];
	$show_large_image         = (bool) $attributes['showLargeImage'];
	$show_featured_background = (bool) $attributes['showFeaturedBackground'];
	$open_in_new_tab          = (bool) $attributes['openInNewTab'];
	$exclude_current          = (bool) $attributes['excludeCurrent'];
	$show_pagination          = (bool) $attributes['showPagination'];
	$offset                   = (int) $attributes['offset'];
	$manual_ids               = (array) $attributes['manualIds'];
	$exclude_ids              = (array) $attributes['excludeIds'];
	$block_title              = (string) $attributes['title'];
	$title_color              = (string) $attributes['titleColor'];
	$instance_id              = (string) $attributes['instanceId'];
	$label                    = __( 'Son yazılar', 'otomatik-butonlar-bloku' );
	$manual_mode              = ! empty( $manual_ids );

	if ( ! $title_color ) {
		$title_color = '#121715';
	}

	if ( '' === $instance_id ) {
		$instance_id = substr(
			md5(
				wp_json_encode(
					array(
						'categoryId'   => $category_id,
						'columns'      => $columns,
						'postsPerPage' => $posts_limit,
						'sortBy'       => $sort_by,
						'sortOrder'    => $sort_order,
						'title'        => $block_title,
					)
				)
			),
			0,
			12
		);
	}

	$section_id     = 'otobuton-' . $instance_id;
	$page_query_key = 'otobuton_page_' . $instance_id;
	$current_page   = 1;

	if ( isset( $_GET[ $page_query_key ] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = max( 1, absint( wp_unslash( $_GET[ $page_query_key ] ) ) );
	}

	if ( ! $manual_mode && $category_id > 0 ) {
		$category = get_category( $category_id );

		if ( $category && ! is_wp_error( $category ) ) {
			$label = sprintf(
				/* translators: %s: category name. */
				__( '%s yazıları', 'otomatik-butonlar-bloku' ),
				$category->name
			);
		}
	}

	if ( $manual_mode ) {
		$label = __( 'Seçilen yazılar', 'otomatik-butonlar-bloku' );
	}

	/*
	 * Manual exclusions (excludeIds) plus the post currently being rendered,
	 * so a block placed inside an article never links to that article itself.
	 */
	$excluded_ids = $exclude_ids;

	if ( $exclude_current && is_singular() ) {
		$current_post_id = (int) get_queried_object_id();

		if ( $current_post_id > 0 ) {
			$excluded_ids[] = $current_post_id;
		}
	}

	$query_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
	);

	if ( $manual_mode ) {
		// Elle seçim: verilen sıra korunur, kategori ve sayfalama uygulanmaz.
		$query_args['post__in']       = $manual_ids;
		$query_args['orderby']        = 'post__in';
		$query_args['order']          = 'ASC';
		$query_args['posts_per_page'] = min( count( $manual_ids ), OTOBUTON_NO_PAGINATION_CAP );
		$query_args['no_found_rows']  = true;
	} else {
		$query_args['posts_per_page'] = $show_pagination ? $posts_per_page : OTOBUTON_NO_PAGINATION_CAP;
		$query_args['orderby']        = $sort_by;
		$query_args['order']          = $sort_order;
		$query_args['no_found_rows']  = ! $show_pagination;

		if ( $category_id > 0 ) {
			$query_args['cat'] = $category_id;
		}

		/*
		 * Offset only makes sense in a single, pagination-free list: WordPress
		 * rejects an offset together with paged queries, and silently ignoring
		 * the attribute would be worse than documenting the limit.
		 */
		if ( ! $show_pagination && $offset > 0 ) {
			$query_args['offset'] = $offset;
		}

		$query_args['paged'] = $current_page;
	}

	if ( $excluded_ids ) {
		$query_args['post__not_in'] = array_values( array_unique( array_map( 'intval', $excluded_ids ) ) );
	}

	$posts       = new WP_Query( $query_args );
	$total_pages = ( $manual_mode || ! $show_pagination ) ? 1 : (int) $posts->max_num_pages;

	if ( $total_pages > 0 && $current_page > $total_pages ) {
		wp_reset_postdata();

		$current_page        = $total_pages;
		$query_args['paged'] = $current_page;
		$posts               = new WP_Query( $query_args );
		$total_pages         = (int) $posts->max_num_pages;
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'id'    => $section_id,
			'class' => 'otobuton-post-buttons',
		)
	);

	ob_start();
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( '' !== $block_title ) : ?>
			<header class="otobuton-post-buttons__header">
				<h2
					class="otobuton-post-buttons__heading"
					style="<?php echo esc_attr( sprintf( '--otobuton-heading-color:%1$s;--otobuton-heading-hover-color:%1$s;', $title_color ) ); ?>"
				>
					<?php echo esc_html( $block_title ); ?>
				</h2>
			</header>
		<?php endif; ?>

		<ul
			class="<?php echo esc_attr( sprintf( 'otobuton-post-buttons__grid otobuton-post-buttons__grid--columns-%d', $columns ) ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
		>
		<?php if ( ! $posts->have_posts() ) : ?>
			<li class="otobuton-post-buttons__empty">
				<?php esc_html_e( 'Bu seçimde henüz yayınlanmış yazı yok.', 'otomatik-butonlar-bloku' ); ?>
			</li>
			<?php
			wp_reset_postdata();
			?>
		<?php else : ?>
			<?php
			while ( $posts->have_posts() ) :
				$posts->the_post();

				$post_id       = get_the_ID();
				$title         = get_the_title( $post_id );
				$title         = '' !== $title ? $title : __( 'Adsız yazı', 'otomatik-butonlar-bloku' );
				$thumbnail_id  = ( $show_large_image || $show_featured_background ) ? get_post_thumbnail_id( $post_id ) : 0;
				$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : '';
				$excerpt       = $show_excerpt ? wp_strip_all_tags( get_the_excerpt( $post_id ) ) : '';
				$excerpt       = ( $show_excerpt && ! $show_large_image ) ? wp_trim_words( $excerpt, 24, '...' ) : $excerpt;
				$item_classes  = array( 'otobuton-post-button' );
				$item_style    = '';

				if ( $show_large_image ) {
					$item_classes[] = 'has-large-image';
				}

				if ( $thumbnail_url && ! $show_large_image && $show_featured_background ) {
					$item_classes[] = 'has-image-bg';
					$item_style     = sprintf(
						'--otobuton-bg-image:url(%s);',
						esc_url( $thumbnail_url )
					);
				}
				?>
				<li class="otobuton-post-buttons__item">
					<a
						class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
						href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
						<?php if ( $open_in_new_tab ) : ?>
							target="_blank"
							rel="noopener noreferrer"
						<?php endif; ?>
						<?php if ( $item_style ) : ?>
							style="<?php echo esc_attr( $item_style ); ?>"
						<?php endif; ?>
					>
						<?php if ( $show_large_image && $thumbnail_id ) : ?>
							<span class="otobuton-post-button__media" aria-hidden="true">
								<?php
								echo wp_get_attachment_image(
									$thumbnail_id,
									'large',
									false,
									array(
										'class'    => 'otobuton-post-button__image',
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
									)
								); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</span>
						<?php endif; ?>
						<span class="otobuton-post-button__arrow" aria-hidden="true"></span>
						<span class="otobuton-post-button__content">
							<?php if ( $show_date ) : ?>
								<span class="otobuton-post-button__date"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></span>
							<?php endif; ?>
							<span class="otobuton-post-button__title"><?php echo esc_html( $title ); ?></span>
							<?php if ( '' !== $excerpt ) : ?>
								<span class="otobuton-post-button__excerpt"><?php echo esc_html( $excerpt ); ?></span>
							<?php endif; ?>
						</span>
					</a>
				</li>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		<?php endif; ?>
		</ul>

		<?php if ( $show_pagination && $total_pages > 1 ) : ?>
			<nav class="otobuton-post-buttons__pagination" aria-label="<?php esc_attr_e( 'Yazı kutuları sayfalama', 'otomatik-butonlar-bloku' ); ?>">
				<?php if ( $current_page > 1 ) : ?>
					<a class="otobuton-post-buttons__page-link is-prev" href="<?php echo esc_url( otobuton_get_pagination_url( $page_query_key, $current_page - 1, $section_id ) ); ?>" aria-label="<?php esc_attr_e( 'Önceki sayfa', 'otomatik-butonlar-bloku' ); ?>">
						<span aria-hidden="true"></span>
					</a>
				<?php else : ?>
					<span class="otobuton-post-buttons__page-link is-prev is-disabled" aria-hidden="true">
						<span></span>
					</span>
				<?php endif; ?>

				<span class="otobuton-post-buttons__page-numbers">
					<?php foreach ( otobuton_get_pagination_pages( $current_page, $total_pages ) as $page_number ) : ?>
						<?php if ( 0 === $page_number ) : ?>
							<span class="otobuton-post-buttons__page-ellipsis" aria-hidden="true">…</span>
						<?php elseif ( $page_number === $current_page ) : ?>
							<span class="otobuton-post-buttons__page-number is-current" aria-current="page">
								<?php echo esc_html( $page_number ); ?>
							</span>
						<?php else : ?>
							<a
								class="otobuton-post-buttons__page-number"
								href="<?php echo esc_url( otobuton_get_pagination_url( $page_query_key, $page_number, $section_id ) ); ?>"
								aria-label="<?php echo esc_attr( sprintf( __( '%d. sayfa', 'otomatik-butonlar-bloku' ), $page_number ) ); ?>"
							>
								<?php echo esc_html( $page_number ); ?>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</span>

				<?php if ( $current_page < $total_pages ) : ?>
					<a class="otobuton-post-buttons__page-link is-next" href="<?php echo esc_url( otobuton_get_pagination_url( $page_query_key, $current_page + 1, $section_id ) ); ?>" aria-label="<?php esc_attr_e( 'Sonraki sayfa', 'otomatik-butonlar-bloku' ); ?>">
						<span aria-hidden="true"></span>
					</a>
				<?php else : ?>
					<span class="otobuton-post-buttons__page-link is-next is-disabled" aria-hidden="true">
						<span></span>
					</span>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</section>
	<?php

	return (string) ob_get_clean();
}
