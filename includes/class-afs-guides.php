<?php
defined( 'ABSPATH' ) || exit;

final class AFS_Shop_Guides {
    public static function all() {
        return array(
            'Mula di sini: kenali panel kedai' => array(
                'Halaman login menggunakan logo Alfatihstudio dan nama kedai. Masukkan nama pengguna/email serta kata laluan akaun anda; Lupa kata laluan menghantar pautan pemulihan melalui email akaun.',
                'Selepas login, buka Ringkasan Kedai. Menu yang kelihatan bergantung pada akses yang diberikan oleh pentadbir.',
                'Pesanan Hari Ini mengira pesanan yang dibuat hari ini dengan status Processing atau Completed sahaja. On hold dikira dalam kad berasingan. Status ini bukan bukti wang sudah diterima daripada bank atau gateway.',
                'Perlu Diproses bermaksud status Processing. Pesanan Ditahan bermaksud On hold. Semak bukti pembayaran sebelum mengambil tindakan.',
                'Senarai Pesanan Perlu Tindakan mengutamakan pesanan tertua. Klik nombor pesanan untuk melihat butiran; Lihat Semua membuka senarai penuh.',
                'Produk Kehabisan Stok mengira produk bertanda Out of stock. Semak setiap variasi jika produk mempunyai pilihan seperti saiz atau edisi.',
                'Ringkasan boleh lewat sehingga satu minit. Gunakan Analytics Kedai untuk melihat angka jualan mengikut tempoh.',
                'Klik Lihat Website untuk semakan kedai. Selepas selesai, Log keluar terutama pada komputer yang dikongsi.'
            ),
            'Tambah produk ringkas: langkah lengkap' => array(
                'Buka Produk → Tambah Produk / Add new. Gunakan tajuk jelas, contohnya nama buku dan edisi.',
                'Isi penerangan penuh: kandungan, penulis, bahasa, halaman dan maklumat yang pelanggan perlukan. Penerangan ringkas merumuskan kelebihan produk.',
                'Dalam Data Produk pilih Simple product / Produk ringkas jika tiada pilihan variasi. Virtual dan Downloadable hanya dipilih untuk produk yang sesuai.',
                'Masukkan Regular price / Harga biasa. Jika ada tawaran, isi Sale price / Harga promosi yang lebih rendah daripada harga biasa.',
                'Dalam Inventory / Inventori, isi SKU yang unik. Jika stok perlu dikira, aktifkan Manage stock, masukkan kuantiti sebenar dan semak Backorders.',
                'Untuk produk fizikal, masukkan berat dan dimensi mengikut unit yang dipaparkan dalam tetapan kedai. Jangan menganggap unit sentiasa kg.',
                'Pilih kategori yang sesuai. Pilih gambar utama dan tambah galeri; gunakan imej jelas dan saiz fail yang munasabah.',
                'Simpan sebagai Draft / Draf dahulu. Pratonton tajuk, gambar, harga, penerangan dan pilihan penghantaran.',
                'Klik Publish / Terbitkan apabila lengkap. Semak halaman produk di website; perubahan pada produk sedia ada disimpan melalui Update / Kemas Kini.'
            ),
            'Upload dan optimize gambar' => array(
                'Upload gambar melalui Media atau pilihan gambar produk/banner. Had lalai klien ialah 3 MB setiap gambar, sisi terpanjang 6,000px dan maksimum 24 megapiksel. Had hosting yang lebih rendah tetap terpakai.',
                'Pilih gambar yang anda upload sendiri. Dalam butiran gambar, klik Optimize gambar. Tiada pemprosesan bulk atau optimize automatik.',
                'Tunggu sehingga selesai. Hanya satu gambar diproses pada satu masa untuk seluruh kedai; jika server sedang sibuk, cuba semula selepas pemprosesan selesai.',
                'Gambar dikecilkan maksimum 1,600px tanpa crop atau membesarkan gambar kecil. Kualiti 78%; JPG menggunakan WebP jika disokong dan menjimatkan ruang, atau fallback JPG. PNG/format lain mengekalkan format asal dan transparency.',
                'Saiz sebelum/selepas serta penjimatan dipaparkan. Hasil hanya digunakan jika lebih kecil; gambar yang telah disemak tidak dimampatkan berulang kali.',
                'Fail asal dikekalkan. Klik Pulihkan gambar asal jika hasil kurang sesuai. Jika gambar disunting selepas optimize, pemulihan tidak menimpa suntingan baharu.',
                'Gambar produk dan banner yang menggunakan ID media mengikuti hasil optimize. Pautan gambar lama dalam HTML tetap menggunakan fail lama; masukkan semula gambar daripada Media Library untuk menggunakan hasil baharu.',
                'Gambar animasi, SVG, gambar di storan luar dan fail melebihi had pemprosesan tidak diproses. Jika memori server tidak mencukupi, kecilkan gambar terlebih dahulu. Administrator boleh melaraskan had upload melalui Kawalan Panel Kedai.'
            ),
            'Kategori, atribut dan produk variasi' => array(
                'Kategori mengelompokkan produk, contohnya Buku Kanak-kanak. Buka Kategori Produk, isi nama, pilih induk jika perlu dan simpan.',
                'Atribut menerangkan pilihan produk, contohnya Edisi atau Warna. Buka Atribut Produk jika menu ini diaktifkan oleh pentadbir.',
                'Cipta atribut global, kemudian tambah pilihannya melalui Configure terms / Konfigurasi pilihan. Contohnya Edisi mempunyai pilihan Biasa dan Premium.',
                'Buka produk dan pilih Variable product / Produk variasi. Dalam Attributes / Atribut, pilih atribut dan nilai yang digunakan oleh produk itu.',
                'Tandakan Used for variations / Digunakan untuk variasi, kemudian simpan atribut.',
                'Buka Variations / Variasi. Tambah atau jana variasi yang diperlukan. Semak gabungan pilihan supaya tiada kombinasi yang tidak dijual.',
                'Isi harga bagi setiap variasi; variasi tanpa harga boleh tidak tersedia untuk dibeli. Isi SKU, stok, gambar atau berat khusus jika berbeza.',
                'Simpan variasi, kemudian Update produk. Pratonton setiap pilihan pada website dan semak harga serta status stok.',
                'Jika Atribut Produk tiada atau akses terhad, hubungi pentadbir. Pemilihan atribut sedia ada dalam produk dan pengurusan katalog atribut global ialah fungsi berbeza.'
            ),
            'Harga, stok dan produk kehabisan stok' => array(
                'Buka produk → Data Produk. Tukar harga biasa/promosi pada bahagian General / Umum. Untuk produk variasi, semak setiap variasi.',
                'Jika harga promosi perlu berjalan pada tempoh tertentu, gunakan Schedule / Jadual. Semak tarikh mula/tamat dan zon masa kedai.',
                'Manage stock menyimpan kuantiti. Tanpa pengurusan kuantiti, semak Stock status / Status stok secara manual.',
                'Backorders menentukan sama ada pelanggan boleh membeli apabila stok tidak cukup. Pilih mengikut kemampuan kedai memenuhi pesanan.',
                'Semak SKU supaya unik. SKU membantu carian dan pengurusan inventori, tetapi tidak menggantikan nama produk.',
                'Klik Update. Jika website masih menunjukkan harga lama, semak variasi, jadual promosi dan cache sebelum mengubah harga sekali lagi.'
            ),
            'Urus pesanan dan nota pelanggan' => array(
                'Buka Pesanan. Cari nombor pesanan atau nama pelanggan, kemudian buka rekod yang betul.',
                'Semak item, kuantiti, jumlah, pembayaran, alamat dan nota. Jangan anggap semua pesanan baharu sudah dibayar.',
                'Pending payment: belum dibayar. On hold: menunggu semakan. Processing: sedang diproses. Completed: urusan telah selesai.',
                'Tukar status mengikut tindakan sebenar. Completed tidak secara automatik memasukkan nombor tracking kurier.',
                'Jika extension kurier digunakan, ikut medan dan butang extension itu. Pastikan tracking merujuk pesanan yang betul.',
                'Private note / Nota peribadi merekodkan maklumat dalaman. Note to customer / Nota kepada pelanggan boleh menghantar email; semak jenis nota sebelum tambah.',
                'Simpan perubahan dan semak rekod sekali lagi. Beberapa perubahan status boleh mencetuskan email mengikut tetapan kedai.',
                'Akaun pengurus tidak boleh memadam pesanan atau rekod refund. Jika tersalah rekod, rujuk pentadbir untuk pembetulan.'
            ),
            'Harga promosi dan kupon diskaun' => array(
                'Harga promosi ditetapkan dalam produk. Kupon pula dimasukkan pelanggan semasa checkout. Kedua-duanya boleh mempunyai syarat berbeza.',
                'Buka Promosi & Diskaun → Tambah Kod. Isi kod yang pelanggan akan gunakan dan penerangan dalaman jika perlu.',
                'Pilih jenis kupon: peratus, jumlah tetap troli atau jumlah tetap produk. Pastikan amaun bersesuaian dengan jenis yang dipilih.',
                'Tetapkan tarikh luput, minimum/maksimum belian dan produk atau kategori yang layak jika tawaran tidak terbuka kepada semua produk.',
                'Individual use only menghalang gabungan kupon. Exclude sale items mengecualikan produk yang sudah mempunyai harga promosi.',
                'Tetapkan had penggunaan keseluruhan dan per pelanggan jika diperlukan. Semak syarat sebelum menerbitkan kupon.',
                'Uji kod pada troli tanpa membuat bayaran: lihat jumlah sebelum/selepas dan semak produk yang layak. Kongsi kod hanya selepas disahkan.',
                'Diskaun automatik tanpa kod kupon belum disediakan oleh panel ini.'
            ),
            'Tukar banner desktop dan telefon' => array(
                'Buka Banner Website. Tiga ruang tersedia: Banner Utama, Promosi dan Iklan. Ruang mesti sudah disambungkan pada website oleh pentadbir.',
                'Klik Pilih Gambar pada Gambar Desktop. Pilih imej dari pustaka media atau muat naik fail yang sesuai.',
                'Sediakan imej mengikut nisbah ruang website. Panel tidak memotong gambar secara automatik. Imej yang terlalu besar boleh melambatkan website.',
                'Gambar Mudah Alih ialah pilihan. Ia digunakan pada lebar sehingga 600px. Jika kosong, gambar desktop digunakan.',
                'Isi Penerangan gambar, contohnya Tawaran buku kanak-kanak Oktober. Maksimum 250 aksara; medan ini diperlukan untuk banner aktif.',
                'Jika banner boleh diklik, isi pautan penuh bermula dengan https:// atau http://. Kosongkan jika tiada destinasi.',
                'Isi Tarikh / masa mula dan tamat jika promosi berjadual. Masa ikut zon website yang ditunjukkan. Kosongkan untuk tiada had; masa tamat mesti selepas masa mula.',
                'Semak status: Dijadualkan sebelum mula, Aktif dalam tempoh, Tamat tempoh selepas tamat. Checkbox aktif mesti ditandakan supaya jadual berfungsi.',
                'Tetapkan Lebar maksimum banner dalam piksel untuk setiap ruang. Gambar responsif menyesuaikan ruang tema yang lebih kecil. Tandakan tab baharu hanya jika pautan perlu dibuka berasingan.',
                'Cache menerima permintaan pembersihan pada masa mula/tamat melalui tugas WordPress. Jika hosting tidak menjalankan tugas tepat masa, paparan cache mungkin lambat; pentadbir perlu semak konfigurasi hosting.',
                'Tandakan Aktifkan banner. Klik Simpan Banner untuk menyimpan semua ruang bersama. Memilih gambar sahaja belum menyimpan perubahan.',
                'Butang Buang mengosongkan pilihan gambar dalam borang; ia tidak memadam fail media. Nyahaktifkan banner jika gambar utama dibuang.',
                'Selepas mesej berjaya, buka website dan semak desktop/telefon. Cache plugin yang disokong menerima permintaan pembersihan selepas perubahan disimpan.'
            ),
            'Jika banner gagal disimpan, bercanggah atau masih lama' => array(
                'Jika medan tidak lengkap atau pautan salah, tiada perubahan disimpan. Baca mesej pada ruang yang ditandakan, betulkan input dan cuba semula.',
                'Jika pengguna lain telah menyimpan selepas anda membuka borang, borang lama ditolak. Panel memuatkan data terkini untuk mengelakkan perubahan mereka ditimpa.',
                'Buka Salinan perubahan anda yang belum disimpan. Semak salinan tersebut, kemudian masukkan hanya perubahan yang masih diperlukan ke borang terkini.',
                'Salinan sementara tersedia melalui pautan maklum balas dan luput selepas 10 minit; jangan menganggap ia sejarah banner kekal. Salin maklumat penting sebelum meninggalkan halaman.',
                'Lihat mesej status cache. Permintaan dihantar bermaksud plugin cache telah dipanggil; semak website untuk memastikan gambar baharu muncul.',
                'Jika gambar masih lama, cuba muat semula penuh atau buka tingkap peribadi. Cache hosting/CDN yang berasingan mungkin memerlukan sambungan pentadbir.',
                'Jika banner hilang, semak status jadual, zon masa, status aktif, gambar utama dan sama ada fail media masih wujud. Pastikan shortcode dipasang pada ruang website yang betul.',
                'Jika berulang, maklumkan kepada pentadbir ruang banner, waktu simpanan dan mesej ralat. Jangan menghantar kata laluan dalam laporan masalah.'
            ),
            'Baca Analytics dan bandingkan tempoh' => array(
                'Buka Analytics Kedai. Pilih Dari dan Hingga, kemudian klik Tapis Laporan. Tempoh maksimum ialah 366 hari termasuk hari mula dan tamat.',
                'Bandingkan → Tempoh sebelumnya memilih julat terdahulu yang sama panjang. Contoh 1–6 Oktober dibandingkan dengan 25–30 September.',
                'Pilih Tiada perbandingan jika hanya mahu melihat satu tempoh. Julat sebenar dipaparkan supaya anda tahu tarikh yang digunakan.',
                'Kad menunjukkan Jumlah Jualan, Jualan Bersih, Pulangan dan Pesanan. Angka mengikut tetapan tarikh/status WooCommerce Analytics; semak asas statistik dengan pentadbir.',
                'Setiap kad boleh menunjukkan nilai sebelumnya dan perubahan peratus. Tambahan pada Pulangan tidak semestinya baik walaupun peratusnya positif.',
                'Jika nilai sebelumnya sifar dan nilai kini bukan sifar, peratus tidak mempunyai asas. Panel tidak menunjukkan kenaikan 100% yang mengelirukan.',
                'Jadual menunjukkan butiran harian bagi tempoh sehingga 91 hari, atau mingguan bagi tempoh lebih panjang. Senarai produk memaparkan 10 teratas mengikut jualan bersih.',
                'Jika data kosong atau belum tersedia, semak import sejarah, pemprosesan latar dan status yang dimasukkan melalui Administrator. Angka dashboard dan Analytics boleh berbeza kerana asas tarikhnya berbeza.'
            ),
            'Export CSV dan buka dalam Excel' => array(
                'Tapis tarikh dahulu supaya paparan menunjukkan tempoh yang diperlukan.',
                'Klik Export CSV Jualan untuk ringkasan dan butiran tempoh dipilih. Jika perbandingan aktif, CSV turut mengandungi tempoh sebelumnya.',
                'Jenis Baris membezakan Ringkasan daripada Butiran. Jangan menjumlahkan kedua-duanya bersama kerana ringkasan sudah merangkumi butiran.',
                'CSV 10 Produk Teratas mengeksport senarai produk bagi tempoh dipilih sahaja. Ia bukan export semua produk dalam katalog.',
                'Fail dimuat turun melalui akaun anda; pautan memerlukan pengesahan sesi. Jangan berkongsi fail jualan kepada penerima yang tidak memerlukannya.',
                'Buka fail dalam Excel atau import sebagai UTF-8 dengan pemisah koma jika lajur tidak tersusun. Nilai mata wang berupa nombor dengan lajur Mata Wang berasingan.',
                'Nilai peratus terdapat pada paparan Analytics; CSV menyediakan nilai asas kedua-dua tempoh untuk pengiraan lanjut.',
                'Jika data API tidak lengkap, export ditolak. Cuba semula selepas data tersedia; plugin tidak menggantikan laporan yang gagal dengan angka sifar.'
            ),
            'Refund: pemulangan bayaran pelanggan' => array(
                'Contoh: pelanggan membayar RM50 dan kedai bersetuju memulangkan RM20. Refund itu sebahagian; baki RM30 tidak dipulangkan.',
                'Refund melalui gateway yang menyokongnya menghantar pemulangan wang melalui kaedah bayaran asal.',
                'Refund manual merekodkan pulangan dalam WooCommerce. Anda masih perlu memulangkan wang melalui bank atau dashboard penyedia bayaran.',
                'Menukar status pesanan kepada Refunded atau Cancelled sahaja tidak memulangkan wang.',
                'Jika refund dibenarkan, buka pesanan → Refund. Semak item, amaun, sebab dan pilihan tambah semula stok. Pilih cara yang betul sebelum mengesahkan.',
                'Semak nota pesanan serta rekod gateway/bank untuk memastikan wang dipulangkan. Jangan mengulang refund yang sudah berjaya; email pelanggan mungkin dihantar.',
                'Suis Benarkan pengurus memulangkan bayaran hanya mengawal akses pengurus. Ia tidak memulangkan wang secara automatik atau menukar polisi pelanggan.',
                'Jika suis dimatikan, rujuk Administrator. Tiada refund sebenar dibuat oleh proses kemas kini plugin ini.'
            ),
            'Panduan Administrator: kawal panel kedai' => array(
                'Login dengan Administrator, kemudian buka Alfatihstudio Shop → Kawalan Panel Kedai. Pengurus biasa tidak boleh mengubah halaman ini.',
                'Semak nama kedai dan nombor WhatsApp sokongan. Gunakan kod negara tanpa + atau ruang; kosongkan nombor untuk pautan website sahaja.',
                'Pilih modul Analytics, Banner dan Atribut Global yang pengurus perlukan. Pilihan mengawal menu serta laluan tindakan berkaitan, bukan paparan sahaja.',
                'Benarkan pengurus memulangkan bayaran jika itu tugas mereka. Mematikan pilihan tidak memadam rekod refund terdahulu dan tidak menyekat Administrator.',
                'Semak pilihan pemadaman nota pesanan. Jika nota diperlukan sebagai rekod operasi, matikan pemadaman untuk pengurus.',
                'Klik Simpan Tetapan. Uji menu dengan akaun pengurus sebenar; akaun Administrator mempunyai pengecualian akses.',
                'Peranan pengurus ditetapkan melalui Users → Edit → Pengurus Kedai Alfatihstudio. Plugin tidak menukar peranan akaun secara automatik; kekalkan akaun pentadbir sendiri.',
                'Semak Status Panel dan status cache. Cache hosting/CDN tambahan perlu disambungkan jika tiada adapter sesuai dikesan.',
                'Urus import data dan asas statistik melalui Tetapan Analytics WooCommerce. Pastikan data selesai diproses sebelum membuat perbandingan.',
                'Sebelum update, sediakan backup. Gantikan plugin sedia ada melalui ZIP; buka semula borang banner selepas update supaya revision terkini dimuatkan.'
            )
        );
    }
}
