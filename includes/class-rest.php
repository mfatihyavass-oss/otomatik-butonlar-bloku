<?php
/**
 * REST layer for the block: instance inventory plus surgical updates.
 *
 * Abilities (MCP tools) dispatch to these routes, so every write path shares
 * one permission check, one normalization step and one verification step.
 *
 * @package OtomatikButonlarBloku
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers otobuton/v1 routes.
 */
final class OTOBUTON_REST {

	const NAMESPACE_V1 = 'otobuton/v1';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE_V1,
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( __CLASS__, 'can_read' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/instances',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_instances' ),
				'permission_callback' => array( __CLASS__, 'can_read' ),
				'args'                => array(
					'post_id'     => array( 'type' => 'integer', 'required' => false ),
					'category_id' => array( 'type' => 'integer', 'required' => false ),
					'post_type'   => array( 'type' => 'string', 'required' => false ),
					'post_status' => array( 'type' => 'string', 'required' => false ),
					'limit'       => array( 'type' => 'integer', 'required' => false ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/instances/update',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_instance' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => array(
					'post_id'     => array( 'type' => 'integer', 'required' => true ),
					'instance_id' => array( 'type' => 'string', 'required' => true ),
					'attributes'  => array( 'type' => 'object', 'required' => true ),
					'dry_run'     => array( 'type' => 'boolean', 'required' => false, 'default' => false ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/instances/add',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_instance' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => array(
					'post_id'    => array( 'type' => 'integer', 'required' => true ),
					'attributes' => array( 'type' => 'object', 'required' => true ),
					'position'   => array( 'type' => 'string', 'required' => false, 'default' => 'end' ),
					'dry_run'    => array( 'type' => 'boolean', 'required' => false, 'default' => false ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/instances/remove',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'remove_instance' ),
				'permission_callback' => array( __CLASS__, 'can_edit_posts' ),
				'args'                => array(
					'post_id'     => array( 'type' => 'integer', 'required' => true ),
					'instance_id' => array( 'type' => 'string', 'required' => true ),
					'dry_run'     => array( 'type' => 'boolean', 'required' => false, 'default' => false ),
				),
			)
		);
	}

	/**
	 * Read permission: any user who can edit posts.
	 *
	 * @return bool
	 */
	public static function can_read(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Write permission: the caller must be allowed to edit the target post.
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return bool|WP_Error
	 */
	public static function can_edit_posts( WP_REST_Request $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( $post_id <= 0 ) {
			return new WP_Error( 'otobuton_missing_post_id', __( 'post_id zorunludur.', 'otomatik-butonlar-bloku' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'otobuton_forbidden', __( 'Bu içeriği düzenleme yetkiniz yok.', 'otomatik-butonlar-bloku' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * GET /status
	 *
	 * @return WP_REST_Response
	 */
	public function get_status(): WP_REST_Response {
		$block_registered = WP_Block_Type_Registry::get_instance()->is_registered( OTOBUTON_Block_Store::BLOCK_NAME );
		$instances        = $this->collect_instances( array(), 500 );

		return new WP_REST_Response(
			array(
				'success'           => true,
				'plugin'            => 'otomatik-butonlar-bloku',
				'version'           => OTOBUTON_VERSION,
				'block_name'        => OTOBUTON_Block_Store::BLOCK_NAME,
				'block_registered'  => $block_registered,
				'rest_namespace'    => self::NAMESPACE_V1,
				'abilities_ready'   => function_exists( 'wp_register_ability' ),
				'instance_count'    => count( $instances ),
				'instance_posts'    => count( array_unique( wp_list_pluck( $instances, 'post_id' ) ) ),
				'writable_keys'     => otobuton_writable_attribute_keys(),
				'defaults'          => otobuton_attribute_defaults(),
				'scanned_posts'     => $this->count_block_posts(),
			),
			200
		);
	}

	/**
	 * GET /instances
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_instances( WP_REST_Request $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );

		if ( $post_id > 0 ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return new WP_Error( 'otobuton_forbidden', __( 'Bu içeriği düzenleme yetkiniz yok.', 'otomatik-butonlar-bloku' ), array( 'status' => 403 ) );
			}

			$instances = OTOBUTON_Block_Store::instances_for_post( $post_id );
		} else {
			$instances = $this->collect_instances(
				array(
					'category_id' => absint( $request->get_param( 'category_id' ) ),
					'post_type'   => sanitize_key( (string) $request->get_param( 'post_type' ) ),
					'post_status' => sanitize_key( (string) $request->get_param( 'post_status' ) ),
				),
				absint( $request->get_param( 'limit' ) ) ?: 100
			);
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'count'     => count( $instances ),
				'instances' => $instances,
			),
			200
		);
	}

	/**
	 * POST /instances/update
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_instance( WP_REST_Request $request ) {
		$post_id     = absint( $request->get_param( 'post_id' ) );
		$instance_id = (string) $request->get_param( 'instance_id' );
		$attributes  = (array) $request->get_param( 'attributes' );
		$dry_run     = (bool) $request->get_param( 'dry_run' );

		$unknown = array_diff( array_keys( $attributes ), otobuton_writable_attribute_keys() );

		if ( $unknown ) {
			return new WP_Error(
				'otobuton_unknown_attribute',
				sprintf(
					/* translators: %s: comma separated attribute names. */
					__( 'Yazılamayan nitelik(ler): %s', 'otomatik-butonlar-bloku' ),
					implode( ', ', $unknown )
				),
				array( 'status' => 400, 'unknown' => array_values( $unknown ) )
			);
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'otobuton_post_not_found', __( 'İçerik bulunamadı.', 'otomatik-butonlar-bloku' ), array( 'status' => 404 ) );
		}

		$result = OTOBUTON_Block_Store::update_instance( (string) $post->post_content, $instance_id, $attributes );

		if ( ! $result['found'] ) {
			return new WP_Error(
				'otobuton_instance_not_found',
				__( 'Bu içerikte verilen kimliğe sahip blok bulunamadı.', 'otomatik-butonlar-bloku' ),
				array(
					'status'    => 404,
					'post_id'   => $post_id,
					'available' => array_values( wp_list_pluck( OTOBUTON_Block_Store::find_instances( (string) $post->post_content ), 'attributes' ) ),
				)
			);
		}

		$payload = array(
			'success'    => true,
			'post_id'    => $post_id,
			'instance_id' => $result['attributes']['instanceId'],
			'attributes' => $result['attributes'],
			'changed'    => $result['changed'],
			'dry_run'    => $dry_run,
		);

		if ( $dry_run ) {
			$payload['saved'] = false;

			return new WP_REST_Response( $payload, 200 );
		}

		$saved = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $result['content'],
			),
			true
		);

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return new WP_REST_Response( array_merge( $payload, array( 'saved' => true ) + $this->verify_saved( $post_id, $result['attributes'] ) ), 200 );
	}

	/**
	 * POST /instances/add
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_instance( WP_REST_Request $request ) {
		$post_id    = absint( $request->get_param( 'post_id' ) );
		$attributes = (array) $request->get_param( 'attributes' );
		$position   = 'start' === (string) $request->get_param( 'position' ) ? 'start' : 'end';
		$dry_run    = (bool) $request->get_param( 'dry_run' );

		$unknown = array_diff( array_keys( $attributes ), otobuton_writable_attribute_keys() );

		if ( $unknown ) {
			return new WP_Error(
				'otobuton_unknown_attribute',
				sprintf(
					/* translators: %s: comma separated attribute names. */
					__( 'Yazılamayan nitelik(ler): %s', 'otomatik-butonlar-bloku' ),
					implode( ', ', $unknown )
				),
				array( 'status' => 400, 'unknown' => array_values( $unknown ) )
			);
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'otobuton_post_not_found', __( 'İçerik bulunamadı.', 'otomatik-butonlar-bloku' ), array( 'status' => 404 ) );
		}

		$result = OTOBUTON_Block_Store::add_instance( (string) $post->post_content, $attributes, $position );

		if ( $dry_run ) {
			return new WP_REST_Response(
				array(
					'success'     => true,
					'post_id'     => $post_id,
					'attributes'  => $result['attributes'],
					'position'    => $position,
					'dry_run'     => true,
					'saved'       => false,
				),
				200
			);
		}

		$saved = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $result['content'],
			),
			true
		);

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return new WP_REST_Response(
			array_merge(
				array(
					'success'    => true,
					'post_id'    => $post_id,
					'attributes' => $result['attributes'],
					'position'   => $position,
					'dry_run'    => false,
					'saved'      => true,
				),
				$this->verify_saved( $post_id, $result['attributes'] )
			),
			200
		);
	}

	/**
	 * POST /instances/remove
	 *
	 * @param WP_REST_Request $request Incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_instance( WP_REST_Request $request ) {
		$post_id     = absint( $request->get_param( 'post_id' ) );
		$instance_id = (string) $request->get_param( 'instance_id' );
		$dry_run     = (bool) $request->get_param( 'dry_run' );

		$post = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'otobuton_post_not_found', __( 'İçerik bulunamadı.', 'otomatik-butonlar-bloku' ), array( 'status' => 404 ) );
		}

		$result = OTOBUTON_Block_Store::remove_instance( (string) $post->post_content, $instance_id );

		if ( ! $result['removed'] ) {
			return new WP_Error( 'otobuton_instance_not_found', __( 'Bu içerikte verilen kimliğe sahip blok bulunamadı.', 'otomatik-butonlar-bloku' ), array( 'status' => 404 ) );
		}

		if ( $dry_run ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'post_id' => $post_id,
					'removed' => true,
					'dry_run' => true,
					'saved'   => false,
				),
				200
			);
		}

		$saved = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $result['content'],
			),
			true
		);

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		$still_there = wp_list_pluck( OTOBUTON_Block_Store::find_instances( (string) get_post_field( 'post_content', $post_id ) ), 'attributes' );
		$remaining   = array();

		foreach ( $still_there as $attributes ) {
			$remaining[] = $attributes['instanceId'];
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'post_id'   => $post_id,
				'removed'   => true,
				'dry_run'   => false,
				'saved'     => true,
				'verified'  => ! in_array( $instance_id, $remaining, true ),
				'remaining' => $remaining,
			),
			200
		);
	}

	/**
	 * Read back the stored content and confirm the write landed.
	 *
	 * @param int                 $post_id    Post id.
	 * @param array<string,mixed> $attributes Attributes that should be stored.
	 * @return array{verified:bool,stored:array<string,mixed>|null}
	 */
	private function verify_saved( int $post_id, array $attributes ): array {
		$content  = (string) get_post_field( 'post_content', $post_id );
		$found    = null;
		$instance = (string) $attributes['instanceId'];

		foreach ( OTOBUTON_Block_Store::find_instances( $content ) as $record ) {
			if ( $record['attributes']['instanceId'] === $instance ) {
				$found = $record['attributes'];
				break;
			}
		}

		if ( null === $found ) {
			return array(
				'verified' => false,
				'stored'   => null,
			);
		}

		return array(
			'verified' => $found === otobuton_normalize_attributes( $found ),
			'stored'   => $found,
		);
	}

	/**
	 * Collect instances across content, optionally filtered.
	 *
	 * @param array<string,mixed> $filter Keys: category_id, post_type, post_status.
	 * @param int                 $limit  Max posts to inspect.
	 * @return array<int,array<string,mixed>>
	 */
	private function collect_instances( array $filter, int $limit ): array {
		$post_ids  = $this->find_block_post_ids( $filter, $limit );
		$instances = array();

		foreach ( $post_ids as $post_id ) {
			$instances = array_merge( $instances, OTOBUTON_Block_Store::instances_for_post( (int) $post_id ) );
		}

		return $instances;
	}

	/**
	 * Post ids whose content contains the block.
	 *
	 * @param array<string,mixed> $filter Keys: category_id, post_type, post_status.
	 * @param int                 $limit  Max rows.
	 * @return array<int,int>
	 */
	private function find_block_post_ids( array $filter, int $limit ): array {
		global $wpdb;

		$post_type   = isset( $filter['post_type'] ) && '' !== $filter['post_type'] ? (string) $filter['post_type'] : '';
		$post_status = isset( $filter['post_status'] ) && '' !== $filter['post_status'] ? (string) $filter['post_status'] : '';
		$limit       = max( 1, min( 500, $limit ) );

		$sql    = "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status NOT IN ('trash','auto-draft','inherit')";
		$params = array( '%<!-- wp:' . OTOBUTON_Block_Store::BLOCK_NAME . ' %' );

		if ( '' !== $post_type ) {
			$sql     .= ' AND post_type = %s';
			$params[] = $post_type;
		}

		if ( '' !== $post_status ) {
			$sql     .= ' AND post_status = %s';
			$params[] = $post_status;
		}

		$sql     .= ' ORDER BY post_modified DESC LIMIT %d';
		$params[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

		$post_ids = array_map( 'intval', (array) $ids );

		if ( empty( $filter['category_id'] ) ) {
			return $post_ids;
		}

		$category_id = absint( $filter['category_id'] );
		$filtered    = array();

		foreach ( $post_ids as $post_id ) {
			foreach ( OTOBUTON_Block_Store::instances_for_post( $post_id ) as $record ) {
				if ( (int) $record['attributes']['categoryId'] === $category_id ) {
					$filtered[] = $post_id;
					break;
				}
			}
		}

		return $filtered;
	}

	/**
	 * How many posts carry the block (capped database count).
	 *
	 * @return int
	 */
	private function count_block_posts(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status NOT IN ('trash','auto-draft','inherit')",
				'%<!-- wp:' . OTOBUTON_Block_Store::BLOCK_NAME . ' %'
			)
		);
	}
}
