=== Alfatihstudio Shop Admin ===
Contributors: alfatihstudio
Requires at least: 6.5
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 0.5.1
License: GPL-2.0-or-later
Text Domain: alfatihstudio-shop-admin

Panel pengurusan kedai berjenama Alfatihstudio.

== Installation / Update ==
1. Backup konfigurasi plugin dan database kedai.
2. Muat naik ZIP melalui Plugins > Add New > Upload Plugin; gunakan Replace current apabila mengemas kini pemasangan sedia ada.
3. Data afs_shop_banners dan afs_shop_settings sedia ada dikekalkan. Tiada peranan pengguna ditukar secara automatik.
4. Tutup borang banner lama dan buka semula selepas kemas kini. Versi 0.2.0 memerlukan revision borang.
5. Administrator: Alfatihstudio Shop > Kawalan Panel Kedai. Semak nama, WhatsApp dan akses klien.
6. Uji dahulu pada staging dengan akaun Pengurus Kedai Alfatihstudio sebenar; Administrator tidak tertakluk kepada sekatan klien.

== Login kedai ==
Halaman login, lupa/reset kata laluan, pendaftaran (jika dibenarkan oleh WordPress), pengesahan email pentadbir dan selepas logout menggunakan logo Alfatihstudio, nama kedai serta reka bentuk responsif.
Nama kedai mengambil tetapan Kawalan Panel Kedai; tajuk website menjadi fallback. Logo dan pautan kembali menuju home_url kedai semasa.
Handler authentication, cookie, nonce, reset token dan redirect asal WordPress tidak diganti. Mesej ralat dan kandungan tambahan plugin keselamatan dikekalkan. Label utama login menggunakan Bahasa Melayu; mesej lain mengikut bahasa WordPress/plugin asal.
Login branding kekal dimuatkan apabila WooCommerce tidak aktif sementara. Plugin login/security lain yang menetapkan CSS atau tajuk pada priority lebih lewat mungkin memerlukan penyesuaian pada staging.

== Analytics ==
Analytics Kedai menggunakan endpoint WooCommerce Analytics moden /wc-analytics/reports/revenue/stats dan /wc-analytics/reports/products, dengan permission callback WooCommerce yang asal.
Paparan berjenama merangkumi penapis tarikh, jumlah jualan, jualan bersih, pulangan, bilangan pesanan, jadual harian/mingguan dan 10 produk teratas.
Ini bukan keseluruhan UI wc-admin Analytics. Perbandingan tempoh sebelumnya yang sama panjang, CSV jualan (ringkasan dan butiran kedua-dua tempoh) serta CSV 10 produk teratas kini disediakan. Semua laporan pelanggan/kategori/cukai belum ditambah.
Tempoh maksimum 366 hari. Tempoh melebihi 91 hari menggunakan interval mingguan. Perbandingan ialah julat sebelumnya yang sama panjang, dikira termasuk hari mula/tamat. Asas sifar tidak menghasilkan peratus palsu. CSV produk merujuk 10 teratas dalam tempoh dipilih, bukan semua produk.
Import data sejarah dan tetapan statistik diurus melalui akaun Administrator pada WooCommerce Analytics Settings.
Statistik bergantung pada pemprosesan/import Analytics kedai, tetapan status dan asas tarikh yang dipilih. Plugin tidak mencetuskan import atau menukar tetapan statistik secara automatik.

== Client access ==
Kawalan Panel Kedai mengawal Analytics, pengurusan banner, atribut global, refund dan pemadaman nota untuk klien. Pemasangan baharu mematikan refund dan pemadaman nota. Migrasi schema 3 mengisi nilai lalai sejarah untuk pemasangan lama dan mengekalkan semua pilihan yang sudah disimpan.
Mematikan pengurusan banner tidak memadam atau menyembunyikan banner frontend sedia ada.
Klien tidak diberi manage_options atau manage_woocommerce. Core WordPress/WooCommerce tetap memeriksa capabilities dan nonces.
Store API /wc/store/v*/ didelegasikan kepada pengawal asal WooCommerce supaya cart, checkout dan batch storefront tidak disekat oleh plugin ini.
Analytics reports hanya GET/HEAD/OPTIONS. API katalog WooCommerce v2/v3 dibenarkan untuk bacaan; mutasi katalog kekal melalui handler admin asli. Media dan users/me dikekalkan.
Tetapan sistem, pengurusan pengguna lain dan mutasi API pesanan tidak dibuka.

== Banners ==
Shortcodes:
[alfatih_banner slot="utama"]
[alfatih_banner slot="promosi"]
[alfatih_banner slot="iklan"]
Borang lengkap diperlukan: image/mobile/alt/url untuk setiap slot; checkbox active adalah pilihan.
URL mesti bermula dengan http:// atau https://; alt maksimum 250 aksara dan URL maksimum 2048 bait. ID gambar mesti integer bukan negatif dan attachment gambar yang sah.
Simpanan menggunakan revision dan conditional database UPDATE untuk mengelakkan kehilangan perubahan antara pengguna.
Borang bercanggah tidak disimpan. Data terkini dimuatkan dan salinan draft ditunjukkan secara berasingan selama feedback tersedia.
Gambar mesti dioptimumkan dahulu. Plugin tidak memotong atau memampatkan imej secara automatik.

Jadual mula/tamat ialah masa tempatan website dengan ketepatan minit. Mula termasuk, tamat tidak termasuk. Jadual tarikh tidak sah, termasuk masa DST yang tidak wujud, ditolak. Tanpa tarikh ialah tanpa had masa. Status tersedia: Tidak aktif, Dijadualkan, Aktif, Tamat tempoh dan Jadual tidak sah.
Lebar maksimum per slot 1 hingga 7680 piksel, lalai 1200. Digunakan untuk max-width dan sizes gambar responsif. Tab baharu menggunakan noopener noreferrer.
Satu tugas WP-Cron dijadualkan pada sempadan masa terdekat untuk memanggil adapter page-cache yang disokong. Jika WP-Cron tidak berjalan tepat masa, halaman yang dicache mungkin lewat berubah; cache hosting/CDN tambahan tetap memerlukan integrasi.
Borang editor membaca terus dari database, bukan option cache yang mungkin lapuk. Revision dan conditional UPDATE dikekalkan untuk melindungi perubahan serentak.

== Extension integration (developers) ==
Kawalan Panel Kedai mempunyai medan frontend_ajax_actions untuk maksimum 20 nama tindakan tepat tema/plugin. Senarai ini digunakan pada permintaan admin-ajax untuk klien, termasuk permintaan storefront; ia bukan kebenaran berasaskan Referer. Suis refund/nota diperiksa dahulu dan tidak boleh dibuka melalui senarai ini.
Filter afs_shop_allowed_ajax_actions: senarai tambahan tindakan AJAX. Handler extension mesti mempunyai nonce dan capability sendiri. Sekatan refund/nota tidak boleh dibuka melalui filter ini.
Filter afs_shop_rest_route_allowed(false, route, method): opt-in route extension yang sempit. Jangan pulangkan true untuk semua route. Handler asal mesti menguatkuasakan permission sendiri.
Hook afs_banners_saved(clean, previous): cache page-purge terbina memanggil LiteSpeed (litespeed_purge wildcard), WP Rocket (rocket_clean_domain), WP Super Cache (wp_cache_clear_cache untuk blog semasa) dan W3 Total Cache (w3tc_flush_posts) jika tersedia. Permintaan dijana hanya selepas banner berubah dan berjaya disimpan; kegagalan cache tidak membatalkan banner.
Hook afs_banner_schedule_cache(banners): dipancarkan selepas tugas sempadan masa membaca database dan menjadualkan sempadan seterusnya. Adapter cache terbina menerima hook ini; hook afs_banners_saved kekal khusus untuk perubahan disimpan.
Hook afs_shop_purge_external_cache(clean, previous): sambungkan cache hosting/CDN tambahan; tiada sambungan API luaran dibuat secara automatik.
Option afs_shop_cache_status merekodkan waktu dan hasil permintaan adapter. Requested bukan pengesahan cache server/CDN telah selesai dipadam.
CSV melalui admin-post afs_export_analytics memerlukan sesi, capability, modul Analytics aktif dan nonce. Data lengkap dijana ke stream sementara sebelum header download. Formula pada medan teks CSV dineutralkan; angka/mata wang kekal berasingan.
Bantuan kini mempunyai 12 topik terperinci, indeks pautan dan penerangan refund manual/gateway.
Banner CAS mengemas kini option secara atomik; updated_option dan update_option_afs_shop_banners dipancarkan selepas perubahan. Filter pre_update_option tidak digunakan untuk simpanan CAS ini.

== Language support ==
Panel ini menggunakan Bahasa Melayu. Sokongan i18n masih separa; belum ada katalog terjemahan Inggeris menyeluruh. load_plugin_textdomain tidak menjamin bahawa terjemahan tersedia. Direktori languages belum dibekalkan; ini bukan ralat fatal tetapi sokongan terjemahan masih belum lengkap.

== Validation ==
Semakan sintaks semua 14 fail PHP lulus pada PHP WASM 7.4.33 dan 8.5.10.
150 pemeriksaan regresi PHP lulus pada kedua-dua runtime: validator, access scope, policy controls, revision conflict, simulated concurrent UPDATE, database failure, Analytics dates/errors/rendering dan draft conflict markup.
23 pemeriksaan login tambahan lulus pada kedua-dua runtime: mesej asal, hook keselamatan, pautan staging, tajuk, kelas interim, terjemahan terhad dan escaping nama kedai.
15 pemeriksaan migrasi/KPI lulus pada kedua-dua runtime: pemasangan baharu, reaktivasi, peranan lama, pilihan tersimpan, autoload schema dan penapisan pesanan hari ini.
7 pemeriksaan JavaScript banners.js lulus dengan DOM/wp.media tiruan; sintaks JavaScript lulus.
Ujian ini menggunakan test doubles. Belum mengesahkan integrasi WordPress/WooCommerce hidup, MySQL sebenar, persistent object cache, gateway, tema, frontend blocks atau layout browser.
Staging pengguna belum diuji: Browser tidak dapat mengesahkan polisi keselamatan pentadbir.

== Client media ==
Untuk peranan Pengurus Kedai Alfatihstudio, tab Pexels daripada Kadence Blocks serta pilihan Create audio playlist dan Create video playlist dimatikan melalui filters asal.
Upload files, Media Library, galeri dan gambar produk dikekalkan. Fail media sedia ada dan jenis upload yang dibenarkan tidak diubah. Administrator mengekalkan pilihan asal. Penyederhanaan media picker ini tidak memerlukan CSS/JavaScript atau query tambahan.

== Manual image optimization ==
Klien: had upload gambar lalai 3 MiB, sisi terpanjang 6,000px dan maksimum 24 megapiksel. Had hosting lebih rendah kekal berkuat kuasa. Semakan menggunakan bytes/dimensi sebenar pada upload dan sideload. Administrator boleh menetapkan 1–10 MB dan 1,920–6,000px; had upload ini tidak dikenakan kepada Administrator.
Butang Optimize gambar pada butiran media memproses satu gambar statik JPG/PNG/WebP/AVIF pada satu masa. Klien hanya boleh memproses gambar milik sendiri atau gambar dengan capability edit_post asli; administrator boleh memproses semua media. Tiada bulk/auto optimize baharu. WordPress masih menjalankan pemprosesan upload asalnya.
Resize maksimum 1,600px, nisbah asal, kualiti 78%, tidak membesarkan gambar kecil. JPG mencuba WebP jika disokong, fallback JPG jika gagal/tidak menjimatkan bytes. Format lain dikekalkan. Hasil yang tidak lebih kecil dibuang. Animasi WebP/APNG, GIF, SVG dan fail offloaded tidak diproses. Had dimensi/bytes dan semakan memori juga terpakai semasa optimize.
Nonce dan capability/ownership diperiksa di server. Lock fail bukan blocking menghalang kerja serentak merentas pengguna/tab pada filesystem uploads yang sama dan dilepaskan apabila proses tamat. Tiada servis luar atau aset frontend. JavaScript kecil hanya pada skrin admin yang menggunakan media/attachment.
ID media, alt dan caption dikekalkan. Fail asal serta thumbnails lama disimpan bersama rekod pemulihan; tambahan ruang hosting diperlukan. Pulihkan gambar asal memulihkan path, MIME dan metadata. Pemulihan tidak menimpa gambar yang telah disunting kepada path lain. Kegagalan metadata menyimpan recovery journal; jika proses terhenti, gunakan butang pemulihan. Delete attachment secara kekal membersihkan set fail yang disimpan juga.
Produk/banner berasaskan ID mengikuti hasil baharu. URL yang ditampal dalam HTML kekal menggunakan fail lama; pilih semula gambar untuk menggunakan URL baharu. Cache/CDN mungkin perlu dikemas kini. Gambar yang sudah diproses ditandakan dan tidak diproses semula.

== Client profile ==
Profil Saya mengekalkan borang, input, nonce, validation, email confirmation dan password/session handlers WordPress. Kad responsif Maklumat Akaun, Perhubungan dan Keselamatan menggunakan tema Alfatihstudio dan label Melayu.
Skema warna, toolbar, editor preferences, infinite scrolling, website, bio dan Gravatar disembunyikan untuk klien sahaja. Nilai input sedia ada dikekalkan dalam borang; tiada perubahan tetapan/peranan automatik. Medan plugin/security tambahan dan akses aplikasi/revocation dikekalkan. Skrip kecil hanya menyusun node heading/table asli, tanpa menggantikan elemen input atau event handlers. Profile CSS/JS hanya pada load-profile.php klien; Administrator dan user-edit.php tidak diubah.

== Changelog ==
= 0.5.1 =
* Jadual produk menggunakan lebar minimum setiap kolum termasuk Penulis dan kolum pihak ketiga/SEO. Kolum tambahan menyebabkan scroll mendatar dalam jadual, bukan nama satu huruf sebaris.
* Semua kolum, data SEO, pilihan Paparan Jadual dan tindakan native dikekalkan. Paparan telefon asal tidak diubah.

= 0.5.0 =
* Modul Penulis: taksonomi produk, foto, biodata ringkas/penuh, beberapa penulis setiap buku, profil /penulis/nama/ dan buku automatik dengan pagination 12 item.
* Menu klien dan akses native term/product dikekalkan. Foto menggunakan media library dan semakan pemilikan asal.
* CSS profil hanya dimuatkan pada arkib penulis; warna mengambil palette tema website. Tiada library frontend tambahan.

= 0.4.5 =
* Optimize: maksimum sisi terpanjang 1,600px, kualiti 78, tanpa upscale; had upload berasingan dikekalkan.
* Optimize boleh digunakan semula selepas Restore dan status unchanged; fail derivatif lama dibersihkan dengan perlindungan fail asal/aktif.
* Analytics menormalkan objek stdClass dalam respons REST dalaman sebelum validasi. Ralat sebenar dikekalkan, tidak ditukar kepada jualan sifar.
* Notis Analytics menunjukkan kod ralat; butiran ralat boleh dibuka oleh pentadbir. Tiada cadangan import sejarah tanpa bukti.

= 0.4.4 =
* Reka bentuk senarai produk, editor produk klasik dan kategori untuk Pengurus Kedai Alfatihstudio: kad putih, label utama Melayu, carian/penapis tersusun dan paparan responsif.
* Kolum tag, jenama dan featured disembunyikan secara lalai; boleh dipaparkan semula melalui Paparan Jadual. Pilihan pengguna sedia ada dikekalkan.
* Borang, nonce, edit pantas, pengurusan stok, variasi, media dan proses simpan asal WordPress/WooCommerce dikekalkan.
* CSS dan JavaScript katalog dimuatkan hanya pada skrin klien berkaitan; tiada aset frontend, library atau pertanyaan database baharu.

= 0.4.3 =
* Redesign profil klien: kad responsif, label Melayu, sembunyikan pilihan teknikal dan kekalkan handler keselamatan/profil asli.

= 0.4.2 =
* Had upload gambar klien dengan tetapan administrator.
* Manual single-image resize/compression, smaller-file-only selection, native formats/WebP fallback, server lock and recovery button.
* Panduan optimize gambar dan semakan ownership/AJAX tanpa menambah akses edit_posts.

= 0.4.1 =
* Ringkaskan media picker klien: matikan Pexels Kadence, playlist audio dan playlist video tanpa aset tambahan.

= 0.4.0 =
* AJAX get-post-thumbnail-html dan set-post-thumbnail dibenarkan untuk gambar produk; pemeriksaan native kekal.
* Tetapan tindakan AJAX tambahan tema/plugin dengan skop nama tepat, tanpa bypass polisi refund/nota.
* Refund/padam nota dimatikan untuk pemasangan baharu; migrasi mengekalkan akses/pilihan lama.
* Schema role autoload true; KPI hari ini hanya Processing/Completed, On hold berasingan.
* Jadual, status, lebar maksimum per slot dan pautan tab baharu untuk banner.
* Editor membaca database terus untuk mengelakkan revision option-cache lapuk; CAS kekal.
* WP-Cron meminta purge page-cache pada sempadan jadual banner.
* Pakej dev berasingan mengandungi tests, arahan dan keputusan ujian yang boleh diulang.

= 0.3.1 =
* Login berjenama Alfatihstudio dengan nama kedai dan label Bahasa Melayu.
* Paparan pemulihan kata laluan, log keluar dan skrin login berkaitan menggunakan gaya yang sama.
* Pengesahan akaun dan mesej keselamatan asal dikekalkan.

= 0.3.0 =
* Dua belas topik panduan dengan langkah operasi, contoh dan penyelesaian masalah.
* Cache banner dibersihkan melalui adapter plugin cache aktif; status permintaan dan kegagalan tersedia.
* Perbandingan Analytics dengan tempoh sebelumnya sama panjang; asas sifar dan data tidak lengkap dikendalikan.
* CSV jualan meliputi ringkasan/butiran tempoh dipilih dan sebelumnya; CSV top ten produk dilabel jelas.
* Export memerlukan capability/nonce dan menghalang formula spreadsheet pada medan teks.
* Suis refund diberi label dan penerangan yang lebih mudah difahami; hak sedia ada tidak diubah.

= 0.2.0 =
* Paparan laporan berdasarkan WooCommerce Analytics moden menggantikan pautan Reports legacy.
* Store API tidak lagi ditolak oleh guard REST klien; permission native dikekalkan.
* Bacaan Analytics/katalog diberi skop khusus. Mutasi API pentadbiran kekal terhad.
* Atribut global dan taxonomy produk pa_* berdaftar boleh diurus mengikut pilihan pentadbir.
* Revision banner dan conditional database update melindungi perubahan serentak.
* Payload separa, ID gambar tidak sah, protokol URL, panjang input dan status banner ditolak sebelum simpan.
* Kawalan Panel Kedai untuk identiti, modul klien, refund, nota, versi sistem dan pautan tetapan Analytics.
* Tetapan malformed ditolak; tiada perubahan role pengguna atau transaksi dijalankan semasa update.
