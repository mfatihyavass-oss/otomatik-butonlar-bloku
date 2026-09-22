<?php
/**
 * Read and write otobuton/category-post-buttons instances inside post content.
 *
 * Uses WordPress' own block parser/serializer, so nested blocks (columns,
 * groups, cover …) are handled the same way the editor handles them.
 *
 * @package OtomatikButonlarBloku
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locate, update, add and remove block instances in stored content.
 */
final class OTOBUTON_Block_Store {

	/**
	 * Block name handled by this store.
	 */
	const BLOCK_NAME = 'otobuton/category-post-buttons';

	/**
	 * Collect every instance of the block in given content, nested or not.
	 *
	 * @param string $content Raw post content.
	 * @return array<int,array<string,mixed>> Instance records with path + attributes.
	 */
	public static function find_instances( string $content ): array {
		if ( '' === trim( $content ) || ! function_exists( 'parse_blocks' ) ) {
			return array();
		}

		$instances = array();
		self::walk( parse_blocks( $content ), array(), $instances );

		return $instances;
	}

	/**
	 * Recursively collect matching blocks.
	 *
	 * @param array<int,array<string,mixed>> $blocks    Parsed blocks.
	 * @param array<int,int>                 $path      Index path of the parent.
	 * @param array<int,array<string,mixed>> $instances Collector (by reference).
	 * @return void
	 */
	private static function walk( array $blocks, array $path, array &$instances ): void {
		foreach ( $blocks as $index => $block ) {
			$current_path = array_merge( $path, array( $index ) );

			if ( isset( $block['blockName'] ) && self::BLOCK_NAME === $block['blockName'] ) {
				$attributes = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

				$instances[] = array(
					'path'       => $current_path,
					'attributes' => otobuton_normalize_attributes( $attributes ),
					'raw'        => $attributes,
				);
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::walk( $block['innerBlocks'], $current_path, $instances );
			}
		}
	}

	/**
	 * Update one instance (matched by instanceId) with the given attributes.
	 *
	 * @param string              $content     Raw post content.
	 * @param string              $instance_id Target instance id.
	 * @param array<string,mixed> $attributes  Partial attribute payload (whitelisted).
	 * @return array{content:string,found:bool,attributes:array<string,mixed>,changed:array<int,string>}
	 */
	public static function update_instance( string $content, string $instance_id, array $attributes ): array {
		$instance_id = preg_replace( '/[^a-z0-9]/', '', strtolower( $instance_id ) );
		$blocks      = parse_blocks( $content );
		$result      = array(
			'content'    => $content,
			'found'      => false,
			'attributes' => array(),
			'changed'    => array(),
		);

		self::update_in_tree( $blocks, $instance_id, $attributes, $result );

		if ( $result['found'] ) {
			$result['content'] = serialize_blocks( $blocks );
		}

		return $result;
	}

	/**
	 * Recursive worker for update_instance().
	 *
	 * @param array<int,array<string,mixed>> $blocks      Parsed blocks (by reference).
	 * @param string                         $instance_id Target id.
	 * @param array<string,mixed>            $attributes  Payload.
	 * @param array<string,mixed>            $result      Result (by reference).
	 * @return void
	 */
	private static function update_in_tree( array &$blocks, string $instance_id, array $attributes, array &$result ): void {
		foreach ( $blocks as $index => $block ) {
			if ( isset( $block['blockName'] ) && self::BLOCK_NAME === $block['blockName'] ) {
				$current = otobuton_normalize_attributes( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array() );

				if ( '' !== $instance_id && $current['instanceId'] === $instance_id ) {
					$merged  = array_merge( $current, array_intersect_key( $attributes, array_flip( otobuton_writable_attribute_keys() ) ) );
					$updated = otobuton_normalize_attributes( $merged, $current );

					$result['changed']    = self::diff( $current, $updated );
					$result['attributes'] = $updated;
					$result['found']      = true;
					$blocks[ $index ]['attrs'] = $updated;

					return;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::update_in_tree( $blocks[ $index ]['innerBlocks'], $instance_id, $attributes, $result );

				if ( $result['found'] ) {
					return;
				}
			}
		}
	}

	/**
	 * Append or prepend a new instance.
	 *
	 * @param string              $content    Raw post content.
	 * @param array<string,mixed> $attributes Attribute payload.
	 * @param string              $position   "start" or "end".
	 * @return array{content:string,attributes:array<string,mixed>}
	 */
	public static function add_instance( string $content, array $attributes, string $position = 'end' ): array {
		$normalized = otobuton_normalize_attributes( $attributes );

		if ( '' === $normalized['instanceId'] ) {
			$normalized['instanceId'] = otobuton_generate_instance_id( $normalized );
		}

		$block  = '<!-- wp:' . self::BLOCK_NAME . ' ' . wp_json_encode( $normalized ) . ' /-->';
		$blocks = parse_blocks( $content );

		if ( 'start' === $position ) {
			array_unshift( $blocks, parse_blocks( $block )[0] );
		} else {
			$blocks[] = parse_blocks( $block )[0];
		}

		return array(
			'content'    => serialize_blocks( $blocks ),
			'attributes' => $normalized,
		);
	}

	/**
	 * Remove one instance (matched by instanceId).
	 *
	 * @param string $content     Raw post content.
	 * @param string $instance_id Target id.
	 * @return array{content:string,removed:bool}
	 */
	public static function remove_instance( string $content, string $instance_id ): array {
		$instance_id = preg_replace( '/[^a-z0-9]/', '', strtolower( $instance_id ) );
		$blocks      = parse_blocks( $content );
		$removed     = self::remove_in_tree( $blocks, $instance_id );

		return array(
			'content' => $removed ? serialize_blocks( $blocks ) : $content,
			'removed' => $removed,
		);
	}

	/**
	 * Recursive worker for remove_instance().
	 *
	 * @param array<int,array<string,mixed>> $blocks      Parsed blocks (by reference).
	 * @param string                         $instance_id Target id.
	 * @return bool
	 */
	private static function remove_in_tree( array &$blocks, string $instance_id ): bool {
		foreach ( $blocks as $index => $block ) {
			if ( isset( $block['blockName'] ) && self::BLOCK_NAME === $block['blockName'] ) {
				$current = otobuton_normalize_attributes( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array() );

				if ( '' !== $instance_id && $current['instanceId'] === $instance_id ) {
					unset( $blocks[ $index ] );
					$blocks = array_values( $blocks );

					return true;
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				if ( self::remove_in_tree( $blocks[ $index ]['innerBlocks'], $instance_id ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Attribute-level diff between two normalized sets.
	 *
	 * @param array<string,mixed> $before Old attributes.
	 * @param array<string,mixed> $after  New attributes.
	 * @return array<int,string>
	 */
	private static function diff( array $before, array $after ): array {
		$changed = array();

		foreach ( $after as $key => $value ) {
			if ( ! array_key_exists( $key, $before ) || $before[ $key ] !== $value ) {
				$changed[] = $key;
			}
		}

		return $changed;
	}

	/**
	 * Instance records for a single post, decorated with post context.
	 *
	 * @param int $post_id Post id.
	 * @return array<int,array<string,mixed>>
	 */
	public static function instances_for_post( int $post_id ): array {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		$records = array();

		foreach ( self::find_instances( (string) $post->post_content ) as $instance ) {
			$instance['post_id']    = (int) $post->ID;
			$instance['post_type']  = $post->post_type;
			$instance['post_title'] = get_the_title( $post );
			$instance['post_status'] = $post->post_status;
			$instance['edit_link']  = admin_url( 'post.php?post=' . (int) $post->ID . '&action=edit' );

			$records[] = $instance;
		}

		return $records;
	}
}
