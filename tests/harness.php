<?php
/**
 * Minimal WordPress harness so the plugin's pure logic can be tested with the
 * local PHP CLI (no WordPress install required).
 *
 * Block parsing uses WordPress core's own WP_Block_Parser classes
 * (tests/lib/class-wp-block-parser*.php, taken from WordPress 6.8) and the
 * serializer mirrors wp-includes/blocks.php, so the tests exercise real block
 * markup instead of a hand-rolled approximation.
 *
 * @package OtomatikButonlarBloku
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['otobuton_test_posts']      = array();
$GLOBALS['otobuton_test_hooks']      = array();
$GLOBALS['otobuton_test_abilities']  = array();
$GLOBALS['otobuton_test_categories'] = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['otobuton_test_hooks'][] = array( $hook, $callback, $priority );
	return true;
}

function plugin_dir_path( $file ) {
	return rtrim( dirname( $file ), '/' ) . '/';
}

function plugin_dir_url( $file ) {
	return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

function __( $text, $domain = 'default' ) {
	return $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return $text;
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $text );
	$text = strip_tags( (string) $text );

	return $remove_breaks ? trim( preg_replace( '/[\r\n\t ]+/', ' ', $text ) ) : trim( $text );
}

function sanitize_hex_color( $color ) {
	if ( ! is_string( $color ) ) {
		return null;
	}

	if ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ) {
		return $color;
	}

	return null;
}

function sanitize_key( $key ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $key ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
	return substr( str_repeat( 'ab12', 10 ), 0, (int) $length );
}

function wp_list_pluck( $list, $field, $index_key = null ) {
	$result = array();

	foreach ( (array) $list as $item ) {
		$item = (array) $item;

		if ( array_key_exists( $field, $item ) ) {
			$result[] = $item[ $field ];
		}
	}

	return $result;
}

function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
}

function is_singular( $types = '' ) {
	return ! empty( $GLOBALS['otobuton_test_is_singular'] );
}

function get_queried_object_id() {
	return (int) ( $GLOBALS['otobuton_test_queried_id'] ?? 0 );
}

function get_post( $post = null ) {
	$post = is_object( $post ) ? $post->ID : (int) $post;

	return $GLOBALS['otobuton_test_posts'][ $post ] ?? null;
}

function get_post_field( $field, $post_id ) {
	$post = get_post( $post_id );

	return $post ? ( $post->{$field} ?? '' ) : '';
}

function get_the_title( $post = null ) {
	$post = get_post( is_object( $post ) ? $post->ID : $post );

	return $post ? (string) $post->post_title : '';
}

function current_user_can( $cap ) {
	return ! empty( $GLOBALS['otobuton_test_can'] );
}

function wp_register_ability_category( $name, $args ) {
	$GLOBALS['otobuton_test_ability_category'] = array( $name, $args );

	return true;
}

function wp_register_ability( $name, $args ) {
	$GLOBALS['otobuton_test_abilities'][ $name ] = $args;

	return true;
}

/**
 * Build a fake post object and register it in the harness store.
 *
 * @param int    $id      Post id.
 * @param string $content Post content.
 * @param array  $extra   Extra fields (post_title, post_type, post_status).
 * @return stdClass
 */
function otobuton_test_make_post( int $id, string $content, array $extra = array() ) {
	$post = (object) array_merge(
		array(
			'ID'           => $id,
			'post_content' => $content,
			'post_title'   => 'Test yazısı ' . $id,
			'post_type'    => 'page',
			'post_status'  => 'publish',
		),
		$extra
	);

	$GLOBALS['otobuton_test_posts'][ $id ] = $post;

	return $post;
}

/* -------------------------------------------------------------------------
 * Block parser / serializer (core behaviour)
 * ---------------------------------------------------------------------- */

require_once __DIR__ . '/lib/class-wp-block-parser-block.php';
require_once __DIR__ . '/lib/class-wp-block-parser-frame.php';
require_once __DIR__ . '/lib/class-wp-block-parser.php';

function parse_blocks( $content ) {
	$parser = new WP_Block_Parser();
	$blocks = $parser->parse( (string) $content );

	return array_map(
		function ( $block ) {
			return (array) $block;
		},
		$blocks
	);
}

function serialize_block( $block ) {
	$block_content = '';
	$index         = 0;

	foreach ( $block['innerContent'] as $chunk ) {
		$block_content .= is_string( $chunk ) ? $chunk : serialize_block( $block['innerBlocks'][ $index++ ] );
	}

	if ( ! is_array( $block['attrs'] ) ) {
		$block['attrs'] = array();
	}

	return get_comment_delimited_block_content( $block['blockName'], $block['attrs'], $block_content );
}

function get_comment_delimited_block_content( $block_name, $block_attributes, $block_content ) {
	if ( is_null( $block_name ) ) {
		return $block_content;
	}

	$serialized_block_name = 0 === strpos( $block_name, 'core/' ) ? substr( $block_name, 5 ) : $block_name;
	$serialized_attributes = empty( $block_attributes ) ? '' : ' ' . wp_json_encode( $block_attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	if ( empty( $block_content ) ) {
		return sprintf( '<!-- wp:%s%s /-->', $serialized_block_name, $serialized_attributes );
	}

	return sprintf(
		'<!-- wp:%1$s%2$s -->%3$s<!-- /wp:%1$s -->',
		$serialized_block_name,
		$serialized_attributes,
		$block_content
	);
}

function serialize_blocks( $blocks ) {
	return implode( '', array_map( 'serialize_block', $blocks ) );
}

/* -------------------------------------------------------------------------
 * Tiny assertion helpers
 * ---------------------------------------------------------------------- */

$GLOBALS['otobuton_test_passed'] = 0;
$GLOBALS['otobuton_test_failed'] = 0;
$GLOBALS['otobuton_test_group']  = '';

function otobuton_test_group( string $name ): void {
	$GLOBALS['otobuton_test_group'] = $name;
	echo "\n== {$name}\n";
}

function otobuton_test_assert( string $label, bool $condition, string $detail = '' ): void {
	if ( $condition ) {
		$GLOBALS['otobuton_test_passed']++;
		echo "  ok   {$label}\n";

		return;
	}

	$GLOBALS['otobuton_test_failed']++;
	echo "  FAIL {$label}";

	if ( '' !== $detail ) {
		echo " -- {$detail}";
	}

	echo "\n";
}

function otobuton_test_same( string $label, $expected, $actual ): void {
	$same = $expected === $actual;

	otobuton_test_assert(
		$label,
		$same,
		$same ? '' : 'beklenen: ' . var_export( $expected, true ) . ' / gerçek: ' . var_export( $actual, true )
	);
}

function otobuton_test_summary( string $suite ): void {
	printf(
		"\nRESULT[%s]: %d passed, %d failed\n",
		$suite,
		$GLOBALS['otobuton_test_passed'],
		$GLOBALS['otobuton_test_failed']
	);

	if ( $GLOBALS['otobuton_test_failed'] > 0 ) {
		exit( 1 );
	}
}

require_once dirname( __DIR__ ) . '/otomatik-butonlar-bloku.php';
