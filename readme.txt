=== Otomatik Butonlar Bloku ===
Contributors: maya-hukuk
Tags: gutenberg, block, posts, category, grid
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.6.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seçilen kategorideki yazıları Gutenberg bloğu içinde şık kutucuklarla otomatik gösterir.

== Description ==

Otomatik Butonlar Bloku, WordPress Gutenberg editöründe kullanılmak üzere hazırlanmış dinamik bir blok eklentisidir. Kullanıcı blok içinden kategori, başlık, başlık rengi, yazı sayısı, sütun sayısı ve öne çıkan görsel arka plan ayarlarını seçebilir.

Yazılar seçilen ölçüte göre otomatik sıralanır. Varsayılan sınırsız seçenek toplam yazı sayısını kısıtlamaz; yazılar otomatik sayfalara bölünür ve her istekte yalnızca aktif sayfa yüklenir.

== Installation ==

1. `otomatik-butonlar-bloku` klasörünü `wp-content/plugins/` dizinine yükleyin.
2. WordPress yönetim panelinden eklentiyi etkinleştirin.
3. Gutenberg editöründe `Otomatik Yazı Kutuları` bloğunu ekleyin.
4. Blok altındaki ayarları düzenleyin.

== Changelog ==

= 1.6.1 =
* Abiliteler artık WordPress Abilities API'nin REST listesinde görünüyor (`meta.public`, `meta.show_in_rest`). Böylece miniOrange Secure MCP Server gibi abilite toplayıcıları bu araçları rollere verebiliyor.

= 1.6.0 =
* Elle yazı seçimi paneli: yazıları arayıp seçerek verdiğiniz sırayla listeleme.
* Listenin dışında tutulacak yazıları seçme (hariç tutma).
* Blok bir yazının içindeyken kendini listeden çıkarabilir.
* Sayfalama kapatma ve baştan kaydırma (offset) ayarları.
* Blok yönetimi için REST uçları ve beş MCP aracı (durum, listeleme, güncelleme, ekleme, kaldırma).
* Yerel PHP test paketi eklendi.

= 1.5.3 =
* Satır sayısındaki gereksiz 6 üst sınırı kaldırıldı.

= 1.5.2 =
* Satır sayısı ayarı editöre eklendi.
* Sınırsız modda sayfa boyutu artık seçilen sütun ve satır sayısına göre belirleniyor.

= 1.5.1 =
* Sayfalama alanındaki gereksiz toplam sayfa metni kaldırıldı.

= 1.5.0 =
* Sayfa yenilemeden AJAX tabanlı sayfa geçişi eklendi.
* Numaralı sayfalama eklendi.
* Kategori arama ve sıralama seçenekleri eklendi.
* Tarihi gizleme ve bağlantıyı yeni sekmede açma seçenekleri eklendi.

= 1.4.1 =
* Sınırsız gösterim artık tüm yazıları tek seferde yüklemiyor; otomatik sayfalama kullanıyor.

= 1.4.0 =
* Gösterilecek yazı sayısı için sınırsız seçenek eklendi ve varsayılan sınırsız yapıldı.
* Sayfalama için 6-1.000 arası seçenekler eklendi.

= 1.3.0 =
* Başlık rengi seçimi eklendi.
* Başlık hover tasarımı korundu.
* Gutenberg blok ayarları blok altında görünür hale getirildi.
* Sayfalama, kategori seçimi, yazı sayısı ve sütun ayarları eklendi.
