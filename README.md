# POSTAR

Mail ile içerik alan, yönetim onay akışı olan blog sistemi.

## Özellikler
- Yönetim paneli (giriş, ayarlar, izinli göndericiler, başvurular, yazı onayı)
- Siteye özel mail kutusunu IMAP ile periyodik tarama
- İzinli göndericilerden gelen mailleri otomatik yazıya dönüştürme
- Mail başlığı -> yazı başlığı, mail metni -> içerik
- Ek görselleri galeriye alma, ilk görseli öne çıkarma (görsel yoksa varsayılan görsel)
- Video/ses/PDF/dosya eklerini formatına uygun gösterme ve indirme
- Gönderici adı/mail bazlı otomatik kategori üretimi
- Kategori bulutu, arama, sayfalama
- Son 10 görseli anasayfada gösterme

## Kurulum
0. Gerçek ayarları local dosyaya yazın (git'e girmesin):
```bash
cp /var/www/html/postar/config.local.example.php /var/www/html/postar/config.local.php
```
`config.local.php` içine gerçek DB bilgilerini girin.

1. Veritabanını oluşturun:
```bash
mysql -h XXXXXX -u XXXXXX -p XXXXXX < /var/www/html/postar/db/schema.sql
```
2. Varsayılan admin ile giriş yapın:
- E-posta: `admin@postar.local`
- Şifre: `Admin123!`
3. `/admin/settings.php` ekranından:
- site özel mail adresi
- IMAP host/port/kullanıcı/şifre
- polling interval (dakika)
ayarlarını girin.

## Cron
```bash
* * * * * php /var/www/html/postar/cron/fetch_emails.php >> /var/log/postar_cron.log 2>&1
```
Script kendi içinde `poll_interval_minutes` değerine göre süre dolmadan taramayı atlar.

## Notlar
- Türkçe karakterler için tüm tablolar `utf8mb4_turkish_ci`.
- PHP'de `imap` eklentisi kurulu olmalıdır.
- Yüklenen medya dosyaları `uploads/` altında tutulur.
- Uygulama linkleri otomatik base path algılar. Gerekirse `config.php` içindeki `app.base_url` alanına örn. `/postar` yazabilirsiniz.
- `config.php` dosyası bilerek `XXXXXX` placeholder içerir. Gerçek değerler `config.local.php` dosyasında tutulmalıdır.
