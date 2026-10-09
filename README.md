# Alfatihstudio Shop Admin

Panel pengurusan kedai WooCommerce berjenama Alfatihstudio. Versi **0.5.1**.

## Muat turun dan pemasangan

1. [Muat turun ZIP plugin 0.5.1](dist/alfatihstudio-shop-admin-0.5.1.zip) dan pilih **Download raw file** pada GitHub.
2. Dalam WordPress, buka **Plugins > Add New > Upload Plugin**.
3. Pilih ZIP tersebut, pasang dan aktifkan. Untuk kemas kini, pilih **Replace current**.
4. Administrator: buka **Alfatihstudio Shop > Kawalan Panel Kedai** untuk menyemak nama kedai, WhatsApp dan akses klien.

Backup database dan konfigurasi sebelum kemas kini. Uji pada staging terlebih dahulu.

## Keperluan

- WordPress 6.5 atau lebih baharu.
- PHP 7.4 atau lebih baharu.
- WooCommerce aktif; header plugin menetapkan minimum WooCommerce 8.2.

## Fungsi

- Panel pengurusan dan kawalan akses klien.
- Banner berjadual, gambar responsif dan integrasi cache.
- Analytics kedai dan eksport CSV.
- Branding halaman login, profil pengguna dan panduan.
- Pengoptimuman gambar, katalog dan profil penulis.

Dokumentasi lengkap, shortcode, integrasi pembangun, batasan dan changelog tersedia dalam [readme.txt](readme.txt).

## Kandungan versi ini

Kod plugin dan ZIP pemasangan dimuat naik tanpa perubahan daripada fail `alfatihstudio-shop-admin-0.5.1.zip` yang dibekalkan. ZIP dalam `dist/` ialah fail pemasangan asal.

## Semakan penerbitan

- Integriti arkib ZIP disemak.
- Semua enam fail JavaScript lulus semakan sintaks `node --check`.
- PHP CLI dan WordPress/WooCommerce tidak tersedia dalam persekitaran penerbitan; tiada ujian PHP atau kedai langsung baharu dijalankan. Rekod ujian terdahulu dalam `readme.txt` ialah dokumentasi yang dibekalkan.

## Lesen

GPL-2.0-or-later, seperti dinyatakan dalam header plugin dan `readme.txt`.
