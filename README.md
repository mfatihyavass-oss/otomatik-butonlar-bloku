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
- **Elle yazı seçimi:** istediğiniz yazıları arayıp seçerek, verdiğiniz sırayla listeleme.
- **Hariç tutma:** seçilen yazılar kategori listesinde ve elle seçimde gösterilmez.
- **Bulunduğu yazıyı gizleme:** blok bir yazının içindeyken kendini listelemez.
- **Sayfalamayı kapatma ve kaydırma:** sayfalama kapatıldığında tüm yazılar tek listede (en fazla 200) gösterilir; en yeni N yazı atlanabilir.
- **Otomatik araçlar (MCP/REST):** blok örneklerini listeleyen, tek bir örneğin ayarını değiştiren, blok ekleyen ve kaldıran uçlar.

## Otomatik araçlar (REST + MCP)

Eklenti, bloğu WordPress dışından da yönetebilmek için iki katman sunar.

### Abilities API (MCP araçları)

WordPress Abilities API varsa aşağıdaki araçlar otomatik kaydolur ve bağlı MCP sunucusundan çağrılabilir:

| Araç | İşlev |
| --- | --- |
| `otobuton/status` | Sürüm, blok kaydı, örnek sayısı, yazılabilir ayar adları |
| `otobuton/list-instances` | Sayfa/yazılardaki blokları ayarlarıyla listeler |
| `otobuton/update-instance` | Tek bir bloğun ayarını değiştirir (`dry_run` destekler) |
| `otobuton/add-instance` | Sayfa/yazı başına veya sonuna blok ekler |
| `otobuton/remove-instance` | Bloğu kimliğine göre kaldırır |

### REST uçları

Tüm araçlar `otobuton/v1` altındaki uçlara dayanır (aynı yetki ve doğrulama kuralları):

- `GET  /wp-json/otobuton/v1/status`
- `GET  /wp-json/otobuton/v1/instances?post_id=2648`
- `POST /wp-json/otobuton/v1/instances/update`
- `POST /wp-json/otobuton/v1/instances/add`
- `POST /wp-json/otobuton/v1/instances/remove`

Yazma işlemleri `edit_post` yetkisi ister, değişiklik blok ayrıştırıcısıyla yapılır (içeriğin geri kalanı bozulmaz) ve kayıt sonrası içerik geri okunup doğrulanır. `instanceId` dışarıdan değiştirilemez.

## Testler

Yerel PHP CLI ile çalışan üç doğrulama dosyası vardır (WordPress kurulumu gerekmez; blok ayrıştırma için WordPress 6.8 çekirdek ayrıştırıcısı kullanılır):

```bash
for t in tests/verify-*.php; do php "$t" | tail -3; done
```

Son durum: **156 passed / 0 failed** (attributes 48, block-store 47, abilities 61).

## Sürüm geçmişi

- **1.6.0** — Elle yazı seçimi ve hariç tutma, bulunduğu yazıyı gizleme, sayfalamayı kapatma, kaydırma (offset); blok yönetimi için REST uçları ve beş MCP aracı; yerel test paketi.
- **1.5.3** — Sayfalama ve blok ayarları iyileştirmeleri.

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

Güncel sürüm: `1.6.0`

## Lisans

GPL-2.0-or-later.
