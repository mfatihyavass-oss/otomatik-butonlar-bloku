<?php
/**
 * Plugin Name: Otomatik Butonlar Bloku
 * Description: Seçilen kategorideki en yeni yazıları Gutenberg bloğu olarak şık kutucuklarla otomatik gösterir.
 * Plugin URI: https://bursa.mayahukuk.com
 * Version: 1.3.0
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

define( 'OTOBUTON_VERSION', '1.3.0' );
define( 'OTOBUTON_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OTOBUTON_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

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
	$category_id              = isset( $attributes['categoryId'] ) ? absint( $attributes['categoryId'] ) : 0;
	$columns                  = otobuton_clamp_int( $attributes['columns'] ?? 3, 1, 6, 3 );
	$rows                     = otobuton_clamp_int( $attributes['rows'] ?? 2, 1, 6, 2 );
	$legacy_posts_per_page    = max( 1, $columns * $rows );
	$posts_per_page           = otobuton_clamp_int( $attributes['postsPerPage'] ?? $legacy_posts_per_page, 1, 36, $legacy_posts_per_page );
	$show_excerpt             = ! empty( $attributes['showExcerpt'] );
	$show_large_image         = ! empty( $attributes['showLargeImage'] );
	$show_featured_background = ! empty( $attributes['showFeaturedBackground'] );
	$block_title              = isset( $attributes['title'] ) ? trim( wp_strip_all_tags( (string) $attributes['title'] ) ) : __( 'Son Yazılar', 'otomatik-butonlar-bloku' );
	$title_color              = isset( $attributes['titleColor'] ) ? sanitize_hex_color( (string) $attributes['titleColor'] ) : '#121715';
	$instance_id              = isset( $attributes['instanceId'] ) ? sanitize_key( (string) $attributes['instanceId'] ) : '';
	$label                    = __( 'Son yazılar', 'otomatik-butonlar-bloku' );

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
						'postsPerPage' => $posts_per_page,
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

	if ( $category_id > 0 ) {
		$category = get_category( $category_id );

		if ( $category && ! is_wp_error( $category ) ) {
			$label = sprintf(
				/* translators: %s: category name. */
				__( '%s yazıları', 'otomatik-butonlar-bloku' ),
				$category->name
			);
		}
	}

	$query_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $posts_per_page,
		'paged'               => $current_page,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	);

	if ( $category_id > 0 ) {
		$query_args['cat'] = $category_id;
	}

	$posts = new WP_Query( $query_args );
	$total_pages = (int) $posts->max_num_pages;

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
							<span class="otobuton-post-button__date"><?php echo esc_html( get_the_date( '', $post_id ) ); ?></span>
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

		<?php if ( $total_pages > 1 ) : ?>
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

				<span class="otobuton-post-buttons__page-status">
					<?php echo esc_html( sprintf( '%d / %d', $current_page, $total_pages ) ); ?>
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
