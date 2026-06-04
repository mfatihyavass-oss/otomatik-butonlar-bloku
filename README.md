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
- 1-36 arası gösterilecek yazı sayısı seçimi.
- 1-6 arası sütun seçimi.
- En yeni yazıdan en eski yazıya otomatik sıralama.
- Yeni yazı eklendiğinde içeriğin otomatik güncellenmesi.
- Öne çıkan görseli mat arka plan olarak kullanma seçeneği.
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
- **Gösterilecek yazı sayısı:** Her sayfada kaç yazı kutusu gösterileceği.
- **Sütun sayısı:** Masaüstünde kutuların kaç sütun halinde dizileceği.
- **Öne çıkan görsel:** Etkinse yazının öne çıkan görseli mat arka plan olarak kullanılır.

## Sayfalama Davranışı

Seçilen kategoride gösterilecek yazı sayısından daha fazla yazı varsa blok altında sağ ve sol oklar görünür. Her blok kendi sayfalama anahtarını kullandığı için aynı sayfada birden fazla blok eklenebilir.

## Dosya Yapısı

```text
otomatik-butonlar-bloku/
├── blocks/category-post-buttons/
│   ├── block.json
│   ├── editor.css
│   ├── editor.js
│   └── style.css
├── otomatik-butonlar-bloku.php
├── readme.txt
└── README.md
```

## Sürüm

Güncel sürüm: `1.3.0`

## Lisans

GPL-2.0-or-later.
