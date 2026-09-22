<?php
/**
 * Block attribute normalization and defaults.
 *
 * Every write path (block editor, REST, abilities) funnels through
 * otobuton_normalize_attributes() so a malformed payload can never store a
 * value the renderer does not expect.
 *
 * @package OtomatikButonlarBloku
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical attribute defaults. Mirrors blocks/category-post-buttons/block.json.
 *
 * @return array<string,mixed>
 */
function otobuton_attribute_defaults(): array {
	return array(
		'categoryId'              => 0,
		'title'                   => 'Son Yazılar',
		'titleColor'              => '#121715',
		'postsPerPage'            => 0,
		'sortBy'                  => 'date',
		'sortOrder'               => 'DESC',
		'columns'                 => 3,
		'rows'                    => 2,
		'showExcerpt'             => false,
		'showDate'                => true,
		'showLargeImage'          => false,
		'showFeaturedBackground'  => true,
		'openInNewTab'            => false,
		'excludeCurrent'          => false,
		'showPagination'          => true,
		'offset'                  => 0,
		'manualIds'               => array(),
		'excludeIds'              => array(),
		'instanceId'              => '',
	);
}

/**
 * Hard ceiling for pagination-free listings so one block can never query
 * (and render) an unbounded number of posts.
 */
const OTOBUTON_NO_PAGINATION_CAP = 200;

/**
 * Normalize a list of post ids: unique, positive, order preserved.
 *
 * @param mixed $value Raw attribute value.
 * @return array<int>
 */
function otobuton_normalize_id_list( $value ): array {
	if ( is_string( $value ) ) {
		$value = preg_split( '/[,\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$ids = array();

	foreach ( $value as $item ) {
		if ( is_array( $item ) && isset( $item['id'] ) ) {
			$item = $item['id'];
		}

		if ( ! is_numeric( $item ) ) {
			continue;
		}

		$item = (int) $item;

		if ( $item > 0 && ! in_array( $item, $ids, true ) ) {
			$ids[] = $item;
		}
	}

	return $ids;
}

/**
 * Validate and clamp a partial attribute payload.
 *
 * @param array<string,mixed> $attributes Raw attributes (partial is fine).
 * @param array<string,mixed> $base       Values to fall back on.
 * @return array<string,mixed> Normalized full attribute set.
 */
function otobuton_normalize_attributes( array $attributes, array $base = array() ): array {
	$defaults = otobuton_attribute_defaults();
	$merged   = array_merge( $defaults, array_intersect_key( $base, $defaults ), $attributes );

	$clean = array();

	$clean['categoryId']     = absint( $merged['categoryId'] );
	$clean['title']          = trim( wp_strip_all_tags( (string) $merged['title'] ) );
	$clean['titleColor']     = sanitize_hex_color( (string) $merged['titleColor'] );
	$clean['postsPerPage']   = otobuton_clamp_int( $merged['postsPerPage'], 0, OTOBUTON_NO_PAGINATION_CAP, 0 );
	$clean['sortBy']         = in_array( $merged['sortBy'], array( 'date', 'modified', 'title' ), true ) ? $merged['sortBy'] : 'date';
	$clean['sortOrder']      = in_array( strtoupper( (string) $merged['sortOrder'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( (string) $merged['sortOrder'] ) : 'DESC';
	$clean['columns']        = otobuton_clamp_int( $merged['columns'], 1, 6, 3 );
	$clean['rows']           = otobuton_clamp_int( $merged['rows'], 1, 100, 2 );
	$clean['offset']         = otobuton_clamp_int( $merged['offset'], 0, 500, 0 );
	$clean['manualIds']      = otobuton_normalize_id_list( $merged['manualIds'] );
	$clean['excludeIds']     = otobuton_normalize_id_list( $merged['excludeIds'] );
	$clean['instanceId']     = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $merged['instanceId'] ) );

	foreach ( array( 'showExcerpt', 'showDate', 'showLargeImage', 'showFeaturedBackground', 'openInNewTab', 'excludeCurrent', 'showPagination' ) as $flag ) {
		$clean[ $flag ] = (bool) $merged[ $flag ] && ! otobuton_is_false_string( $merged[ $flag ] );
	}

	if ( ! $clean['titleColor'] ) {
		$clean['titleColor'] = $defaults['titleColor'];
	}

	if ( '' === $clean['title'] ) {
		$clean['title'] = '';
	}

	return $clean;
}

/**
 * Detect explicit false strings ("false", "0", "off", "no") coming from JSON-ish
 * payloads, where a non-empty string would otherwise cast to true.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function otobuton_is_false_string( $value ): bool {
	if ( ! is_string( $value ) ) {
		return false;
	}

	return in_array( strtolower( trim( $value ) ), array( 'false', '0', 'off', 'no', '' ), true );
}

/**
 * Resolve the number of posts shown per page for a normalized attribute set.
 *
 * @param array<string,mixed> $attributes Normalized attributes.
 * @return int
 */
function otobuton_resolve_page_size( array $attributes ): int {
	$limit = otobuton_get_posts_limit( $attributes['postsPerPage'] ?? 0 );

	if ( $limit > 0 ) {
		return min( $limit, OTOBUTON_NO_PAGINATION_CAP );
	}

	$columns = otobuton_clamp_int( $attributes['columns'] ?? 3, 1, 6, 3 );
	$rows    = otobuton_clamp_int( $attributes['rows'] ?? 2, 1, 100, 2 );

	return max( 1, $columns * $rows );
}

/**
 * Keys an external caller is allowed to write through REST/abilities.
 *
 * instanceId is intentionally excluded: it is the pagination key of a live
 * block instance and must never be rewritten from outside.
 *
 * @return array<int,string>
 */
function otobuton_writable_attribute_keys(): array {
	return array(
		'categoryId',
		'title',
		'titleColor',
		'postsPerPage',
		'sortBy',
		'sortOrder',
		'columns',
		'rows',
		'showExcerpt',
		'showDate',
		'showLargeImage',
		'showFeaturedBackground',
		'openInNewTab',
		'excludeCurrent',
		'showPagination',
		'offset',
		'manualIds',
		'excludeIds',
	);
}

/**
 * Build a stable, collision-resistant instance id.
 *
 * @param array<string,mixed> $attributes Normalized attributes.
 * @param int                 $post_id    Post the block lives in (may be 0).
 * @return string
 */
function otobuton_generate_instance_id( array $attributes, int $post_id = 0 ): string {
	$seed = wp_json_encode(
		array(
			'post'    => $post_id,
			'cat'     => $attributes['categoryId'] ?? 0,
			'title'   => $attributes['title'] ?? '',
			'columns' => $attributes['columns'] ?? 3,
			'manual'  => array_slice( (array) ( $attributes['manualIds'] ?? array() ), 0, 5 ),
			'salt'    => function_exists( 'wp_generate_password' ) ? wp_generate_password( 8, false, false ) : uniqid( '', true ),
		)
	);

	return substr( md5( (string) $seed ), 0, 12 );
}
