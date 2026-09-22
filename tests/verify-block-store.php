<?php
/**
 * Block store tests: locating, updating, adding and removing instances in
 * stored post content (nested blocks included).
 *
 * @package OtomatikButonlarBloku
 */

require_once __DIR__ . '/harness.php';

/**
 * Realistic content: one top-level instance, one nested inside a group.
 *
 * @return string
 */
function otobuton_test_content(): string {
	return '<!-- wp:paragraph --><p>Giriş metni.</p><!-- /wp:paragraph -->'
		. '<!-- wp:otobuton/category-post-buttons {"categoryId":147,"title":"Emsal Karar Kütüphanesi","columns":1,"rows":15,"showExcerpt":true,"showPagination":false,"instanceId":"18077df133f9"} /-->'
		. '<!-- wp:group {"layout":{"type":"constrained"}} --><div class="wp-block-group">'
		. '<!-- wp:otobuton/category-post-buttons {"categoryId":52,"title":"İç blok","columns":2,"rows":3,"instanceId":"abc123abc123"} /-->'
		. '</div><!-- /wp:group -->';
}

otobuton_test_group( 'Örnekleri bulma' );

$content   = otobuton_test_content();
$instances = OTOBUTON_Block_Store::find_instances( $content );

otobuton_test_same( 'iki örnek bulunur', 2, count( $instances ) );
otobuton_test_same( 'ilk örnek üst seviyede', array( 1 ), $instances[0]['path'] );
otobuton_test_same( 'ikinci örnek grubun içinde', array( 2, 0 ), $instances[1]['path'] );
otobuton_test_same( 'kategori korunur', 147, $instances[0]['attributes']['categoryId'] );
otobuton_test_same( 'satır sayısı korunur', 15, $instances[0]['attributes']['rows'] );
otobuton_test_same( 'kimlik korunur', '18077df133f9', $instances[0]['attributes']['instanceId'] );
otobuton_test_same( 'yeni nitelik varsayılana düşer', true, $instances[1]['attributes']['showPagination'] );
otobuton_test_same( 'boş içerikte örnek yok', array(), OTOBUTON_Block_Store::find_instances( '' ) );
otobuton_test_same( 'blok yoksa liste boş', array(), OTOBUTON_Block_Store::find_instances( '<!-- wp:paragraph --><p>Yalnız metin.</p><!-- /wp:paragraph -->' ) );

otobuton_test_group( 'Güncelleme' );

$update = OTOBUTON_Block_Store::update_instance( $content, '18077df133f9', array( 'rows' => 5, 'title' => 'Yeni Başlık' ) );

otobuton_test_assert( 'örnek bulundu', $update['found'] );
otobuton_test_same( 'değişen alanlar', array( 'title', 'rows' ), $update['changed'] );
otobuton_test_same( 'satır 15 -> 5', 5, $update['attributes']['rows'] );
otobuton_test_same( 'başlık güncellendi', 'Yeni Başlık', $update['attributes']['title'] );

$after = OTOBUTON_Block_Store::find_instances( $update['content'] );

otobuton_test_same( 'güncelleme sonrası iki örnek duruyor', 2, count( $after ) );
otobuton_test_same( 'hedef örnek güncel', 5, $after[0]['attributes']['rows'] );
otobuton_test_same( 'diğer örnek dokunulmamış', 3, $after[1]['attributes']['rows'] );
otobuton_test_same( 'diğer örnek başlığı korunmuş', 'İç blok', $after[1]['attributes']['title'] );
otobuton_test_assert( 'paragraf bloğu korunmuş', false !== strpos( $update['content'], '<p>Giriş metni.</p>' ) );
otobuton_test_assert( 'grup bloğu korunmuş', false !== strpos( $update['content'], '<!-- /wp:group -->' ) );

$nested = OTOBUTON_Block_Store::update_instance( $content, 'abc123abc123', array( 'columns' => 6 ) );

otobuton_test_assert( 'iç örnek güncellenebilir', $nested['found'] );
otobuton_test_same( 'iç örnek sütunu', 6, OTOBUTON_Block_Store::find_instances( $nested['content'] )[1]['attributes']['columns'] );

$missing = OTOBUTON_Block_Store::update_instance( $content, 'yokboyleid', array( 'rows' => 1 ) );

otobuton_test_same( 'olmayan örnek bulunamaz', false, $missing['found'] );
otobuton_test_same( 'bulunamayınca içerik değişmez', $content, $missing['content'] );

$locked   = OTOBUTON_Block_Store::update_instance( $content, '18077df133f9', array( 'instanceId' => 'zzzzzzzzzzzz', 'bilinmeyen' => 'x' ) );
$locked_a = OTOBUTON_Block_Store::find_instances( $locked['content'] )[0]['attributes'];

otobuton_test_same( 'instanceId dışarıdan değiştirilemez', '18077df133f9', $locked_a['instanceId'] );
otobuton_test_assert( 'bilinmeyen anahtar yazılmaz', ! array_key_exists( 'bilinmeyen', $locked_a ) );
otobuton_test_same( 'değişiklik yoksa changed boş', array(), $locked['changed'] );

otobuton_test_group( 'Ekleme' );

$added_end = OTOBUTON_Block_Store::add_instance( $content, array( 'categoryId' => 9, 'title' => 'Son eklenen' ), 'end' );
$end_list  = OTOBUTON_Block_Store::find_instances( $added_end['content'] );

otobuton_test_same( 'sona eklenince üç örnek', 3, count( $end_list ) );
otobuton_test_same( 'son örnek yeni blok', 9, $end_list[2]['attributes']['categoryId'] );
otobuton_test_same( 'yeni kimlik 12 karakter', 12, strlen( $added_end['attributes']['instanceId'] ) );
otobuton_test_same( 'yeni blokta kalan varsayılanlar', 2, $added_end['attributes']['rows'] );

$added_start = OTOBUTON_Block_Store::add_instance( $content, array( 'categoryId' => 9 ), 'start' );
$start_list  = OTOBUTON_Block_Store::find_instances( $added_start['content'] );

otobuton_test_same( 'başa eklenince ilk blok yeni', 9, $start_list[0]['attributes']['categoryId'] );
otobuton_test_same( 'başa eklenince üç örnek', 3, count( $start_list ) );

$on_empty = OTOBUTON_Block_Store::add_instance( '', array( 'categoryId' => 4 ) );

otobuton_test_same( 'boş içeriğe ekleme', 1, count( OTOBUTON_Block_Store::find_instances( $on_empty['content'] ) ) );

otobuton_test_group( 'Kaldırma' );

$removed = OTOBUTON_Block_Store::remove_instance( $content, '18077df133f9' );
$left    = OTOBUTON_Block_Store::find_instances( $removed['content'] );

otobuton_test_assert( 'kaldırma başarılı', $removed['removed'] );
otobuton_test_same( 'kalan örnek sayısı', 1, count( $left ) );
otobuton_test_same( 'kalan örnek iç blok', 'abc123abc123', $left[0]['attributes']['instanceId'] );
otobuton_test_assert( 'içerikte hedef blok kalmadı', false === strpos( $removed['content'], '18077df133f9' ) );

$removed_missing = OTOBUTON_Block_Store::remove_instance( $content, 'yokboyleid' );

otobuton_test_same( 'olmayan örnek kaldırılamaz', false, $removed_missing['removed'] );
otobuton_test_same( 'kaldırılamayınca içerik değişmez', $content, $removed_missing['content'] );

otobuton_test_group( 'İçerik bağlamı' );

otobuton_test_make_post( 2648, $content, array( 'post_title' => 'Emsal Karar Kütüphanesi', 'post_type' => 'page' ) );

$records = OTOBUTON_Block_Store::instances_for_post( 2648 );

otobuton_test_same( 'içerikteki örnek sayısı', 2, count( $records ) );
otobuton_test_same( 'yazı kimliği eklenir', 2648, $records[0]['post_id'] );
otobuton_test_same( 'yazı başlığı eklenir', 'Emsal Karar Kütüphanesi', $records[0]['post_title'] );
otobuton_test_assert( 'düzenleme bağlantısı üretilir', false !== strpos( $records[0]['edit_link'], 'post=2648' ) );
otobuton_test_same( 'olmayan yazı için boş liste', array(), OTOBUTON_Block_Store::instances_for_post( 999999 ) );

otobuton_test_group( 'Kaydetme turu (serialize -> parse)' );

$round = OTOBUTON_Block_Store::find_instances( serialize_blocks( parse_blocks( $content ) ) );

otobuton_test_same( 'tur sonrası örnek sayısı', 2, count( $round ) );
otobuton_test_same( 'tur sonrası nitelikler aynı', $instances[0]['attributes'], $round[0]['attributes'] );
otobuton_test_same( 'tur sonrası iç örnek yolu', array( 2, 0 ), $round[1]['path'] );

otobuton_test_summary( 'block-store' );
