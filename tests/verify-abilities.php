<?php
/**
 * Ability (MCP tool) registration tests.
 *
 * @package OtomatikButonlarBloku
 */

require_once __DIR__ . '/harness.php';

$abilities = new OTOBUTON_Abilities();
$abilities->register_category();
$abilities->register_abilities();

$registered = $GLOBALS['otobuton_test_abilities'];
$category   = $GLOBALS['otobuton_test_ability_category'] ?? array( '', array() );

otobuton_test_group( 'Kategori kaydı' );

otobuton_test_same( 'kategori adı', 'otobuton', $category[0] );
otobuton_test_assert( 'kategori etiketi var', ! empty( $category[1]['label'] ) );
otobuton_test_assert( 'kategori açıklaması var', ! empty( $category[1]['description'] ) );

otobuton_test_group( 'Kayıtlı araçlar' );

$expected = array(
	'otobuton/status',
	'otobuton/list-instances',
	'otobuton/update-instance',
	'otobuton/add-instance',
	'otobuton/remove-instance',
);

otobuton_test_same( 'araç sayısı', 5, count( $registered ) );
otobuton_test_same( 'araç adları', $expected, array_values( array_intersect( $expected, array_keys( $registered ) ) ) );

foreach ( $registered as $name => $args ) {
	otobuton_test_assert( $name . ' etiketi var', ! empty( $args['label'] ) );
	otobuton_test_assert( $name . ' açıklaması var', ! empty( $args['description'] ) );
	otobuton_test_same( $name . ' kategorisi', 'otobuton', $args['category'] );
	otobuton_test_assert( $name . ' yürütücüsü çağrılabilir', is_callable( $args['execute_callback'] ) );
	otobuton_test_assert( $name . ' yetki kontrolü çağrılabilir', is_callable( $args['permission_callback'] ) );
	otobuton_test_same( $name . ' MCP görünürlüğü', true, $args['meta']['mcp']['public'] );
	otobuton_test_assert( $name . ' çıktı şeması nesne', isset( $args['output_schema']['type'] ) && 'object' === $args['output_schema']['type'] );
}

otobuton_test_group( 'MCP işaretleri (annotations)' );

otobuton_test_same( 'status yalnız okuma', true, $registered['otobuton/status']['meta']['annotations']['readonly'] );
otobuton_test_same( 'list-instances yalnız okuma', true, $registered['otobuton/list-instances']['meta']['annotations']['readonly'] );
otobuton_test_same( 'update-instance yıkıcı değil', false, $registered['otobuton/update-instance']['meta']['annotations']['destructive'] );
otobuton_test_same( 'update-instance tekrarlanabilir', true, $registered['otobuton/update-instance']['meta']['annotations']['idempotent'] );
otobuton_test_same( 'remove-instance yıkıcı', true, $registered['otobuton/remove-instance']['meta']['annotations']['destructive'] );

otobuton_test_group( 'Girdi şemaları' );

otobuton_test_same(
	'update-instance zorunlu alanlar',
	array( 'post_id', 'instance_id', 'attributes' ),
	$registered['otobuton/update-instance']['input_schema']['required']
);
otobuton_test_same(
	'add-instance zorunlu alanlar',
	array( 'post_id', 'attributes' ),
	$registered['otobuton/add-instance']['input_schema']['required']
);
otobuton_test_same(
	'remove-instance zorunlu alanlar',
	array( 'post_id', 'instance_id' ),
	$registered['otobuton/remove-instance']['input_schema']['required']
);
otobuton_test_assert( 'list-instances filtre alanları var', isset( $registered['otobuton/list-instances']['input_schema']['properties']['post_id'] ) );
otobuton_test_assert( 'status girdi şeması istemez', ! isset( $registered['otobuton/status']['input_schema'] ) );

otobuton_test_group( 'Şema ve yazılabilir anahtar tutarlılığı' );

$schema_props = array_keys( $registered['otobuton/update-instance']['input_schema']['properties']['attributes']['properties'] );
$writable     = otobuton_writable_attribute_keys();

sort( $schema_props );
sort( $writable );

otobuton_test_same( 'şema ile yazılabilir anahtarlar aynı', $writable, $schema_props );
otobuton_test_assert( 'instanceId şemada yok', ! in_array( 'instanceId', $schema_props, true ) );
otobuton_test_same(
	'sortBy seçenekleri',
	array( 'date', 'modified', 'title' ),
	$registered['otobuton/update-instance']['input_schema']['properties']['attributes']['properties']['sortBy']['enum']
);
otobuton_test_same(
	'sortOrder seçenekleri',
	array( 'ASC', 'DESC' ),
	$registered['otobuton/update-instance']['input_schema']['properties']['attributes']['properties']['sortOrder']['enum']
);
otobuton_test_same(
	'manualIds dizi tipinde',
	'array',
	$registered['otobuton/update-instance']['input_schema']['properties']['attributes']['properties']['manualIds']['type']
);

otobuton_test_group( 'Yetki davranışı' );

$GLOBALS['otobuton_test_can'] = false;
otobuton_test_same( 'yetkisiz okuma kapalı', false, OTOBUTON_Abilities::can_read() );
otobuton_test_same( 'yetkisiz yazma kapalı', false, OTOBUTON_Abilities::can_write() );

$GLOBALS['otobuton_test_can'] = true;
otobuton_test_same( 'yetkili okuma açık', true, OTOBUTON_Abilities::can_read() );
otobuton_test_same( 'yetkili yazma açık', true, OTOBUTON_Abilities::can_write() );

otobuton_test_group( 'Servis kaydı' );

$hooks = array_column( $GLOBALS['otobuton_test_hooks'], 0 );

otobuton_test_assert( 'REST ve abilities boot kancası kayıtlı', in_array( 'plugins_loaded', $hooks, true ) );
otobuton_test_assert( 'blok kaydı init kancasında', in_array( 'init', $hooks, true ) );

otobuton_test_summary( 'abilities' );
