<?php
/**
 * Attribute normalization tests.
 *
 * @package OtomatikButonlarBloku
 */

require_once __DIR__ . '/harness.php';

otobuton_test_group( 'Varsayılanlar' );

$defaults = otobuton_normalize_attributes( array() );

otobuton_test_same( 'categoryId varsayılanı 0', 0, $defaults['categoryId'] );
otobuton_test_same( 'columns varsayılanı 3', 3, $defaults['columns'] );
otobuton_test_same( 'rows varsayılanı 2', 2, $defaults['rows'] );
otobuton_test_same( 'showDate varsayılanı açık', true, $defaults['showDate'] );
otobuton_test_same( 'showFeaturedBackground varsayılanı açık', true, $defaults['showFeaturedBackground'] );
otobuton_test_same( 'showPagination varsayılanı açık', true, $defaults['showPagination'] );
otobuton_test_same( 'excludeCurrent varsayılanı kapalı', false, $defaults['excludeCurrent'] );
otobuton_test_same( 'offset varsayılanı 0', 0, $defaults['offset'] );
otobuton_test_same( 'manualIds varsayılanı boş', array(), $defaults['manualIds'] );
otobuton_test_same( 'excludeIds varsayılanı boş', array(), $defaults['excludeIds'] );
otobuton_test_same( 'boş girdi tüm anahtarları üretir', count( otobuton_attribute_defaults() ), count( $defaults ) );

otobuton_test_group( 'Sınırlama (clamp)' );

otobuton_test_same( 'columns 99 -> 6', 6, otobuton_normalize_attributes( array( 'columns' => 99 ) )['columns'] );
otobuton_test_same( 'columns 0 -> 1', 1, otobuton_normalize_attributes( array( 'columns' => 0 ) )['columns'] );
otobuton_test_same( 'rows 0 -> 1', 1, otobuton_normalize_attributes( array( 'rows' => 0 ) )['rows'] );
otobuton_test_same( 'rows 999 -> 100', 100, otobuton_normalize_attributes( array( 'rows' => 999 ) )['rows'] );
otobuton_test_same( 'postsPerPage -5 -> 0', 0, otobuton_normalize_attributes( array( 'postsPerPage' => -5 ) )['postsPerPage'] );
otobuton_test_same( 'postsPerPage 9999 -> 200', 200, otobuton_normalize_attributes( array( 'postsPerPage' => 9999 ) )['postsPerPage'] );
otobuton_test_same( 'offset -3 -> 0', 0, otobuton_normalize_attributes( array( 'offset' => -3 ) )['offset'] );
otobuton_test_same( 'offset 9999 -> 500', 500, otobuton_normalize_attributes( array( 'offset' => 9999 ) )['offset'] );

otobuton_test_group( 'Sıralama ve başlık' );

otobuton_test_same( 'bilinmeyen sortBy -> date', 'date', otobuton_normalize_attributes( array( 'sortBy' => 'bogus' ) )['sortBy'] );
otobuton_test_same( 'sortBy title korunur', 'title', otobuton_normalize_attributes( array( 'sortBy' => 'title' ) )['sortBy'] );
otobuton_test_same( 'küçük harfli asc -> ASC', 'ASC', otobuton_normalize_attributes( array( 'sortOrder' => 'asc' ) )['sortOrder'] );
otobuton_test_same( 'geçersiz sortOrder -> DESC', 'DESC', otobuton_normalize_attributes( array( 'sortOrder' => 'sideways' ) )['sortOrder'] );
otobuton_test_same( 'HTML başlıktan ayıklanır', 'Yargıtay Kararları', otobuton_normalize_attributes( array( 'title' => '<b>Yargıtay</b> Kararları' ) )['title'] );
otobuton_test_same( 'geçersiz renk varsayılana döner', '#121715', otobuton_normalize_attributes( array( 'titleColor' => 'kırmızı' ) )['titleColor'] );
otobuton_test_same( 'geçerli renk korunur', '#6b21a8', otobuton_normalize_attributes( array( 'titleColor' => '#6b21a8' ) )['titleColor'] );

otobuton_test_group( 'Bayraklar (boolean)' );

otobuton_test_same( 'showExcerpt "false" -> kapalı', false, otobuton_normalize_attributes( array( 'showExcerpt' => 'false' ) )['showExcerpt'] );
otobuton_test_same( 'showExcerpt "true" -> açık', true, otobuton_normalize_attributes( array( 'showExcerpt' => 'true' ) )['showExcerpt'] );
otobuton_test_same( 'showExcerpt 0 -> kapalı', false, otobuton_normalize_attributes( array( 'showExcerpt' => 0 ) )['showExcerpt'] );
otobuton_test_same( 'showDate false -> kapalı', false, otobuton_normalize_attributes( array( 'showDate' => false ) )['showDate'] );
otobuton_test_same( 'showPagination false -> kapalı', false, otobuton_normalize_attributes( array( 'showPagination' => false ) )['showPagination'] );
otobuton_test_same( 'showPagination "off" -> kapalı', false, otobuton_normalize_attributes( array( 'showPagination' => 'off' ) )['showPagination'] );
otobuton_test_same( 'excludeCurrent 1 -> açık', true, otobuton_normalize_attributes( array( 'excludeCurrent' => 1 ) )['excludeCurrent'] );
otobuton_test_same( 'excludeCurrent "no" -> kapalı', false, otobuton_normalize_attributes( array( 'excludeCurrent' => 'no' ) )['excludeCurrent'] );

otobuton_test_group( 'Yazı kimliği listeleri' );

otobuton_test_same( 'virgülle ayrılmış metin', array( 12, 13, 14 ), otobuton_normalize_attributes( array( 'manualIds' => '12, 13 14' ) )['manualIds'] );
otobuton_test_same( 'tekrarlar tekilleşir, sıra korunur', array( 3, 1 ), otobuton_normalize_attributes( array( 'manualIds' => array( 3, 1, 3 ) ) )['manualIds'] );
otobuton_test_same( 'sıfır ve negatif atılır', array( 7 ), otobuton_normalize_attributes( array( 'manualIds' => array( 0, -4, 7 ) ) )['manualIds'] );
otobuton_test_same( 'nesne listesi desteklenir', array( 5 ), otobuton_normalize_attributes( array( 'excludeIds' => array( array( 'id' => 5 ) ) ) )['excludeIds'] );
otobuton_test_same( 'metin olmayan liste boş döner', array(), otobuton_normalize_attributes( array( 'excludeIds' => 'abc' ) )['excludeIds'] );
otobuton_test_same( 'instanceId sadeleştirilir', 'ab12cd', otobuton_normalize_attributes( array( 'instanceId' => 'AB12-cd!' ) )['instanceId'] );

otobuton_test_group( 'Sayfa boyutu' );

otobuton_test_same( 'sınırsızda sütun x satır', 6, otobuton_resolve_page_size( otobuton_normalize_attributes( array( 'columns' => 2, 'rows' => 3 ) ) ) );
otobuton_test_same( 'özel sayfa boyutu önceliklidir', 10, otobuton_resolve_page_size( otobuton_normalize_attributes( array( 'postsPerPage' => 10, 'columns' => 2, 'rows' => 3 ) ) ) );
otobuton_test_same( 'üst sınır uygulanır', OTOBUTON_NO_PAGINATION_CAP, otobuton_resolve_page_size( otobuton_normalize_attributes( array( 'postsPerPage' => 500 ) ) ) );

otobuton_test_group( 'Mevcut değerlerin korunması' );

$merged = otobuton_normalize_attributes( array( 'title' => 'Yeni başlık' ), array( 'sortBy' => 'modified', 'columns' => 4 ) );

otobuton_test_same( 'taban değeri korunur (sortBy)', 'modified', $merged['sortBy'] );
otobuton_test_same( 'taban değeri korunur (columns)', 4, $merged['columns'] );
otobuton_test_same( 'yeni değer uygulanır (title)', 'Yeni başlık', $merged['title'] );
otobuton_test_same( 'yazılabilir anahtar sayısı', 18, count( otobuton_writable_attribute_keys() ) );
otobuton_test_assert( 'instanceId yazılabilir anahtarlar arasında değil', ! in_array( 'instanceId', otobuton_writable_attribute_keys(), true ) );

otobuton_test_summary( 'attributes' );
