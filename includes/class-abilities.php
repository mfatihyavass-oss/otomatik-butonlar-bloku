<?php
/**
 * Abilities API registration — this is what exposes the block as MCP tools.
 *
 * Every ability is a thin wrapper around the otobuton/v1 REST routes, so the
 * same permission, validation and verification rules apply to editor, REST and
 * MCP callers alike.
 *
 * @package OtomatikButonlarBloku
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the ability category and the block management abilities.
 */
final class OTOBUTON_Abilities {

	const NAMESPACE_PREFIX = 'otobuton';
	const CATEGORY         = 'otobuton';

	/**
	 * Hook registration. Silently does nothing when the Abilities API is absent.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the "Otomatik Butonlar Bloku" ability category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Otomatik Butonlar Bloku', 'otomatik-butonlar-bloku' ),
				'description' => __( 'Otomatik yazı kutuları bloğunu bulma, ayarlarını değiştirme, ekleme ve kaldırma işlemleri.', 'otomatik-butonlar-bloku' ),
			)
		);
	}

	/**
	 * Register every ability.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( $this->ability_map() as $name => $args ) {
			wp_register_ability( self::NAMESPACE_PREFIX . '/' . $name, $args );
		}
	}

	/**
	 * Ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function ability_map(): array {
		$object_output = array(
			'type'                 => 'object',
			'additionalProperties' => true,
		);

		$attribute_properties = array(
			'categoryId'             => array( 'type' => 'integer' ),
			'title'                  => array( 'type' => 'string' ),
			'titleColor'             => array( 'type' => 'string' ),
			'postsPerPage'           => array( 'type' => 'integer' ),
			'sortBy'                 => array( 'type' => 'string', 'enum' => array( 'date', 'modified', 'title' ) ),
			'sortOrder'              => array( 'type' => 'string', 'enum' => array( 'ASC', 'DESC' ) ),
			'columns'                => array( 'type' => 'integer' ),
			'rows'                   => array( 'type' => 'integer' ),
			'showExcerpt'            => array( 'type' => 'boolean' ),
			'showDate'               => array( 'type' => 'boolean' ),
			'showLargeImage'         => array( 'type' => 'boolean' ),
			'showFeaturedBackground' => array( 'type' => 'boolean' ),
			'openInNewTab'           => array( 'type' => 'boolean' ),
			'excludeCurrent'         => array( 'type' => 'boolean' ),
			'showPagination'         => array( 'type' => 'boolean' ),
			'offset'                 => array( 'type' => 'integer' ),
			'manualIds'              => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
			'excludeIds'             => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
		);

		return array(
			'status'          => array(
				'label'               => __( 'Blok Durumu', 'otomatik-butonlar-bloku' ),
				'description'         => __( 'Eklenti sürümünü, bloğun kayıtlı olup olmadığını, sayfalarda kaç blok örneği bulunduğunu ve yazılabilir ayar adlarını döndürür.', 'otomatik-butonlar-bloku' ),
				'category'            => self::CATEGORY,
				'output_schema'       => $object_output,
				'execute_callback'    => function () {
					return self::dispatch( 'GET', '/status' );
				},
				'permission_callback' => array( __CLASS__, 'can_read' ),
				'meta'                => self::meta( true, false, true ),
			),
			'list-instances'  => array(
				'label'               => __( 'Blok Örneklerini Listele', 'otomatik-butonlar-bloku' ),
				'description'         => __( 'Sayfa ve yazılardaki otomatik yazı kutusu bloklarını ayarlarıyla listeler. post_id verilirse yalnız o içerik taranır.', 'otomatik-butonlar-bloku' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'category_id' => array( 'type' => 'integer' ),
						'post_type'   => array( 'type' => 'string' ),
						'post_status' => array( 'type' => 'string' ),
						'limit'       => array( 'type' => 'integer' ),
					),
				),
				'output_schema'       => $object_output,
				'execute_callback'    => function ( array $input ) {
					return self::dispatch(
						'GET',
						'/instances',
						array(
							'post_id'     => isset( $input['post_id'] ) ? absint( $input['post_id'] ) : null,
							'category_id' => isset( $input['category_id'] ) ? absint( $input['category_id'] ) : null,
							'post_type'   => isset( $input['post_type'] ) ? sanitize_key( (string) $input['post_type'] ) : null,
							'post_status' => isset( $input['post_status'] ) ? sanitize_key( (string) $input['post_status'] ) : null,
							'limit'       => isset( $input['limit'] ) ? absint( $input['limit'] ) : null,
						)
					);
				},
				'permission_callback' => array( __CLASS__, 'can_read' ),
				'meta'                => self::meta( true, false, true ),
			),
			'update-instance' => array(
				'label'               => __( 'Blok Ayarını Güncelle', 'otomatik-butonlar-bloku' ),
				'description'         => __( 'Tek bir blok örneğinin ayarlarını (kategori, başlık, sütun/satır, sıralama, elle seçilen yazılar, hariç tutulanlar, sayfalama) günceller. dry_run ile kaydetmeden provası yapılır.', 'otomatik-butonlar-bloku' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'instance_id' => array( 'type' => 'string' ),
						'attributes'  => array(
							'type'       => 'object',
							'properties' => $attribute_properties,
						),
						'dry_run'     => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'   => array( 'post_id', 'instance_id', 'attributes' ),
				),
				'output_schema'       => $object_output,
				'execute_callback'    => function ( array $input ) {
					return self::dispatch(
						'POST',
						'/instances/update',
						array(
							'post_id'     => absint( $input['post_id'] ?? 0 ),
							'instance_id' => (string) ( $input['instance_id'] ?? '' ),
							'attributes'  => (array) ( $input['attributes'] ?? array() ),
							'dry_run'     => (bool) ( $input['dry_run'] ?? false ),
						)
					);
				},
				'permission_callback' => array( __CLASS__, 'can_write' ),
				'meta'                => self::meta( false, false, true ),
			),
			'add-instance'    => array(
				'label'               => __( 'Blok Ekle', 'otomatik-butonlar-bloku' ),
				'description'         => __( 'Bir sayfa veya yazının başına ya da sonuna yeni bir otomatik yazı kutusu bloğu ekler. Yeni bloğun kimliğini döndürür.', 'otomatik-butonlar-bloku' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'    => array( 'type' => 'integer' ),
						'attributes' => array(
							'type'       => 'object',
							'properties' => $attribute_properties,
						),
						'position'   => array( 'type' => 'string', 'enum' => array( 'start', 'end' ), 'default' => 'end' ),
						'dry_run'    => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'   => array( 'post_id', 'attributes' ),
				),
				'output_schema'       => $object_output,
				'execute_callback'    => function ( array $input ) {
					return self::dispatch(
						'POST',
						'/instances/add',
						array(
							'post_id'    => absint( $input['post_id'] ?? 0 ),
							'attributes' => (array) ( $input['attributes'] ?? array() ),
							'position'   => in_array( $input['position'] ?? 'end', array( 'start', 'end' ), true ) ? $input['position'] : 'end',
							'dry_run'    => (bool) ( $input['dry_run'] ?? false ),
						)
					);
				},
				'permission_callback' => array( __CLASS__, 'can_write' ),
				'meta'                => self::meta( false, false, false ),
			),
			'remove-instance' => array(
				'label'               => __( 'Bloğu Kaldır', 'otomatik-butonlar-bloku' ),
				'description'         => __( 'Bir sayfa veya yazıdaki blok örneğini kimliğine göre kaldırır.', 'otomatik-butonlar-bloku' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'     => array( 'type' => 'integer' ),
						'instance_id' => array( 'type' => 'string' ),
						'dry_run'     => array( 'type' => 'boolean', 'default' => false ),
					),
					'required'   => array( 'post_id', 'instance_id' ),
				),
				'output_schema'       => $object_output,
				'execute_callback'    => function ( array $input ) {
					return self::dispatch(
						'POST',
						'/instances/remove',
						array(
							'post_id'     => absint( $input['post_id'] ?? 0 ),
							'instance_id' => (string) ( $input['instance_id'] ?? '' ),
							'dry_run'     => (bool) ( $input['dry_run'] ?? false ),
						)
					);
				},
				'permission_callback' => array( __CLASS__, 'can_write' ),
				'meta'                => self::meta( false, true, true ),
			),
		);
	}

	/**
	 * Read permission for abilities.
	 *
	 * @return bool
	 */
	public static function can_read(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Write permission for abilities.
	 *
	 * @return bool
	 */
	public static function can_write(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * MCP / REST annotations and visibility flags.
	 *
	 * `public` + `show_in_rest` are what make an ability discoverable through
	 * the WordPress Abilities API REST route
	 * (/wp-json/wp-abilities/v1/abilities) — and therefore through ability
	 * aggregators such as the miniOrange Secure MCP Server, which only grant
	 * abilities it can see there. `mcp.public` additionally exposes the ability
	 * to the MCP Adapter endpoint. All four keys are needed for both routes.
	 *
	 * @param bool $readonly    Read only ability.
	 * @param bool $destructive Destructive ability.
	 * @param bool $idempotent  Idempotent ability.
	 * @return array<string,mixed>
	 */
	private static function meta( bool $readonly, bool $destructive, bool $idempotent ): array {
		return array(
			'public'       => true,
			'show_in_rest' => true,
			'mcp'          => array( 'public' => true ),
			'annotations'  => array(
				'readonly'    => $readonly,
				'destructive' => $destructive,
				'idempotent'  => $idempotent,
			),
		);
	}

	/**
	 * Internal REST dispatch shared by every ability.
	 *
	 * @param string              $method HTTP method.
	 * @param string              $path   Route path below otobuton/v1.
	 * @param array<string,mixed> $params Request params (null values are skipped).
	 * @return array<string,mixed>|WP_Error
	 */
	private static function dispatch( string $method, string $path, array $params = array() ) {
		$request = new WP_REST_Request( $method, '/' . OTOBUTON_REST::NAMESPACE_V1 . $path );

		foreach ( $params as $key => $value ) {
			if ( null !== $value ) {
				$request->set_param( $key, $value );
			}
		}

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return $response->as_error();
		}

		return $response->get_data();
	}
}
