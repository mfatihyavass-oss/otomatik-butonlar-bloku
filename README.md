# Otomatik Butonlar Bloku

WordPress Gutenberg için dinamik kategori yazı kutuları bloğu. Seçilen kategorideki yazıları en yeniden en eskiye doğru otomatik sıralar ve şık, hover efektli kutucuklarla gösterir.

Geliştirici: **Maya Hukuk**  
Eklenti sitesi: [bursa.mayahukuk.com](https://bursa.mayahukuk.com)

## Özellikler

- Gutenberg blok editörüyle tam uyumlu dinamik blok.
- Blok altında görünen hızlı ayarlar.
- Kategori seçimi.
- Blok başlığı belirleme.
- Başlık yazısı için hazır renk paleti ve özel renk seçimi.
- Ortalı, hover efektli özel başlık tasarımı.
- Toplam yazı sayısında sınırsız gösterim ve otomatik sayfalama.
- Sayfa yenilemeden AJAX tabanlı sayfa geçişi.
- Numaralı sayfalama ve hızlı sayfa seçimi.
- 1-6 arası sütun seçimi.
- Serbest satır sayısı; sınırsız modda sayfa boyutu sütun × satır olarak hesaplanır.
- Yayın tarihi, güncellenme tarihi veya başlığa göre sıralama.
- Kategori arama.
- Seçilen ölçüte göre otomatik sıralama.
- Yeni yazı eklendiğinde içeriğin otomatik güncellenmesi.
- Öne çıkan görseli mat arka plan olarak kullanma seçeneği.
- Tarihi gizleme ve bağlantıyı yeni sekmede açma seçenekleri.
- Çok yazı olduğunda sağ ve sol oklarla önceki/sonraki sayfaya geçiş.
- Mobil ekranlarda otomatik uyumlanan kutu düzeni.

## Gereksinimler

- WordPress 6.5 veya üzeri.
- PHP 7.4 veya üzeri.
- Gutenberg blok editörü.

## Kurulum

### WordPress panelinden

1. GitHub deposunu indirdiyseniz klasör adının `otomatik-butonlar-bloku` olduğundan emin olun.
2. Bu klasörü zip olarak paketleyin.
3. WordPress yönetim panelinde `Eklentiler > Yeni Ekle > Eklenti Yükle` alanına gidin.
4. Zip dosyasını seçip yükleyin.
5. Eklentiyi etkinleştirin.

### Manuel kurulum

1. `otomatik-butonlar-bloku` klasörünü WordPress kurulumundaki `wp-content/plugins/` dizinine kopyalayın.
2. WordPress yönetim panelinden eklentiyi etkinleştirin.

## Kullanım

1. Yazı veya sayfa düzenleyicisinde blok ekleme menüsünü açın.
2. `Otomatik Yazı Kutuları` bloğunu ekleyin.
3. Bloğun altında veya sağ panelde ayarları düzenleyin.
4. Sayfayı kaydedin veya yayımlayın.

## Blok Ayarları

- **Blok başlığı:** Kutuların üstünde görünen başlık.
- **Başlık rengi:** Başlık metninin rengi.
- **Kategori:** Hangi kategorideki yazıların gösterileceği.
- **Kategori ara:** Çok sayıda kategori arasından hızlı arama yapmanızı sağlar.
- **Gösterilecek yazı sayısı:** Varsayılan sınırsız seçenek toplam yazı sayısını kısıtlamaz; yazıları otomatik olarak sayfalara böler. İsterseniz sayfa başına 1-1.000 arası özel bir değer seçebilirsiniz.
- **Sıralama:** Yazıları yayın tarihine, güncellenme tarihine veya başlığa göre sıralayabilirsiniz.
- **Sütun sayısı:** Masaüstünde kutuların kaç sütun halinde dizileceği.
- **Tarihi göster:** Kartlardaki yayın tarihini açıp kapatır.
- **Yazıyı yeni sekmede aç:** Kart bağlantılarını yeni tarayıcı sekmesinde açar.
- **Öne çıkan görsel:** Etkinse yazının öne çıkan görseli mat arka plan olarak kullanılır.

## Sayfalama Davranışı

Varsayılan **Sınırsız** seçeneği toplam yazı sayısına limit koymaz; yazılar otomatik olarak sayfalara bölünür ve her istekte yalnızca aktif sayfanın yazıları yüklenir. Sayfa boyutu varsayılan olarak sütun düzenine göre belirlenir. Sayfa geçişleri JavaScript destekleniyorsa sayfa yenilenmeden yapılır; JavaScript devre dışıysa normal bağlantılar çalışmaya devam eder. Her blok kendi sayfalama anahtarını kullandığı için aynı sayfada birden fazla blok eklenebilir.

## Dosya Yapısı

```text
otomatik-butonlar-bloku/
├── blocks/category-post-buttons/
│   ├── block.json
│   ├── editor.css
│   ├── editor.js
│   ├── style.css
│   └── view.js
├── otomatik-butonlar-bloku.php
├── readme.txt
└── README.md
```

## Sürüm

Güncel sürüm: `1.5.3`

## Lisans

GPL-2.0-or-later.
