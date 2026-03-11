# POSTAR Mail to Content

E-posta gönderimini site içeriği paylaşımı için kullanan, yönetim onay akışına sahip blog sistemi.

## Ozellikler
- Yonetim paneli (giris, ayarlar, izinli gondericiler, basvurular, yazi onayi)
- Siteye ozel mail kutusunu IMAP ile periyodik tarama
- Izinli gondericilerden gelen mailleri otomatik yaziya donusturme
- Mail basligi -> yazi basligi, mail metni -> icerik
- Ek gorselleri galeriye alma, ilk gorseli one cikarma (gorsel yoksa varsayilan gorsel)
- Video/ses/PDF/dosya eklerini formatina uygun gosterme ve indirme
- Gonderici adi/mail bazli otomatik kategori uretimi
- Kategori bulutu, arama, sayfalama
- Son 10 gorseli anasayfada gosterme

## Kurulum
0. Gercek ayarlari local dosyaya yazin (git'e girmesin):
```bash
cp /var/www/html/postar/config.local.example.php /var/www/html/postar/config.local.php
```
`config.local.php` icine gercek DB bilgilerini girin.

1. Veritabanini olusturun:
```bash
mysql -h XXXXXX -u XXXXXX -p XXXXXX < /var/www/html/postar/db/schema.sql
```
2. Varsayilan admin ile giris yapin:
- E-posta: `admin@postar.local`
- Sifre: `Admin123!`
3. `/admin/settings.php` ekranindan:
- site ozel mail adresi
- IMAP host/port/kullanici/sifre
- polling interval (dakika)
ayarlarini girin.

## Cron
```bash
* * * * * php /var/www/html/postar/cron/fetch_emails.php >> /var/log/postar_cron.log 2>&1
```
Script kendi icinde `poll_interval_minutes` degerine gore sure dolmadan taramayi atlar.

## Notlar
- Turkce karakterler icin tum tablolar `utf8mb4_turkish_ci`.
- PHP'de `imap` eklentisi kurulu olmalidir.
- Yuklenen medya dosyalari `uploads/` altinda tutulur.
- Uygulama linkleri otomatik base path algilar. Gerekirse `config.php` icindeki `app.base_url` alanina ornegin `/postar` yazabilirsiniz.
- `config.php` dosyasi bilerek `XXXXXX` placeholder icerir. Gercek degerler `config.local.php` dosyasinda tutulmalidir.
