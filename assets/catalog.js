(function () {
    'use strict';
    if (!document.body.classList.contains('afs-catalog') || typeof afsCatalog === 'undefined') return;
    var kind = afsCatalog.kind;
    var wrap = document.querySelector('#wpbody-content > .wrap');
    if (!wrap) return;
    var heading = wrap.querySelector('h1.wp-heading-inline, h1');
    if (heading) {
        // The native add-new control is a separate node and is left intact.
        var titles = {products: 'Produk', categories: afsCatalog.writer ? 'Penulis' : 'Kategori Produk', editor: afsCatalog.newProduct ? 'Tambah Produk' : 'Edit Produk'};
        heading.textContent = titles[kind];
        var intro = document.createElement('p'); intro.className = 'afs-catalog-intro';
        intro.textContent = kind === 'products' ? 'Urus katalog, harga dan stok kedai anda.' : kind === 'categories' ? (afsCatalog.writer ? 'Urus profil penulis dan buku yang berkaitan.' : 'Susun produk mengikut kategori supaya mudah ditemui pelanggan.') : 'Lengkapkan maklumat produk, semak harga dan stok, kemudian simpan perubahan.';
        var anchor = heading.nextElementSibling;
        if (anchor && anchor.classList.contains('page-title-action')) anchor.insertAdjacentElement('afterend', intro);
        else heading.insertAdjacentElement('afterend', intro);
    }
    var addProduct = wrap.querySelector('.page-title-action'); if (addProduct && kind !== 'categories') addProduct.textContent = 'Tambah Produk';
    if (kind === 'editor') {
        var boxNames = {'postimagediv-title':'Gambar Utama','product_catdiv-title':'Kategori Produk','tagsdiv-product_tag-title':'Tag Produk','product_branddiv-title':'Jenama Produk','submitdiv-title':'Simpan & Terbitkan','commentsdiv-title':'Ulasan Produk'};
        Object.keys(boxNames).forEach(function (id) { var node = document.getElementById(id); if (node) node.textContent = boxNames[id]; });
        var title = document.querySelector('#titlediv');
        if (title) {
            var label = document.createElement('label'); label.className = 'afs-product-title-label'; label.htmlFor = 'title'; label.textContent = 'Nama Produk'; title.prepend(label);
            var field = title.querySelector('#title'); if (field) field.setAttribute('placeholder', 'Contoh: Tafsir Surah Yasin');
        }
    }
    if (kind === 'products') {
        var addProduct = wrap.querySelector('.page-title-action'); if (addProduct) addProduct.textContent = 'Tambah Produk';
        document.querySelectorAll('.subsubsub a').forEach(function (link) {
            Array.from(link.childNodes).forEach(function (node) {
                if (node.nodeType === 3) node.textContent = node.textContent.replace(/\bAll\b/g, 'Semua').replace(/\bPublished\b/g, 'Diterbitkan').replace(/\bDrafts\b/g, 'Draf').replace(/\bTrash\b/g, 'Tong sampah');
            });
        });
        var form = document.querySelector('#posts-filter');
        var table = form && form.querySelector('table.wp-list-table');
        if (table) { var scroll = document.createElement('div'); scroll.className = 'afs-catalog-table'; table.parentNode.insertBefore(scroll, table); scroll.appendChild(table); }
        var search = form && form.querySelector('.search-box input[type=search]');
        if (search) search.setAttribute('placeholder', 'Cari nama produk atau SKU…');
        var searchSubmit = form && form.querySelector('#search-submit'); if (searchSubmit) searchSubmit.value = 'Cari produk';
    }
    if (kind === 'categories') {
        var categoryLabels = {'#col-left h2':afsCatalog.writer ? 'Tambah Penulis' : 'Tambah Kategori', 'label[for="parent"]':'Kategori induk', '.term-name-wrap p':afsCatalog.writer ? 'Nama penulis yang dipaparkan pada website.' : 'Nama kategori yang dipaparkan pada website.', '.term-description-wrap p':'Penerangan ringkas kategori. Paparannya bergantung pada reka bentuk website.'};
        Object.keys(categoryLabels).forEach(function (selector) { var node = wrap.querySelector(selector); if (node) node.textContent = categoryLabels[selector]; });
        var categorySubmit = document.querySelector('#addtag #submit'); if (categorySubmit) categorySubmit.value = afsCatalog.writer ? 'Tambah Penulis' : 'Tambah Kategori';
        var categorySearch = wrap.querySelector('#search-submit'); if (categorySearch) categorySearch.value = afsCatalog.writer ? 'Cari penulis' : 'Cari kategori';
        var categorySearchField = wrap.querySelector('#tag-search-input'); if (categorySearchField) categorySearchField.placeholder = afsCatalog.writer ? 'Cari penulis…' : 'Cari kategori…';
        var add = document.querySelector('#col-left .form-wrap');
        if (add) {
            add.classList.add('afs-category-card');
            var parentHelp = add.querySelector('.term-parent-wrap p'); if (parentHelp) parentHelp.textContent = 'Pilih kategori induk jika kategori ini berada di bawah kategori lain.';
            var slugHelp = add.querySelector('.term-slug-wrap p'); if (slugHelp) slugHelp.textContent = afsCatalog.writer ? 'Contoh: nama-penulis. Kosongkan untuk dijana daripada nama.' : 'Contoh: buku-kanak-kanak. Kosongkan untuk dijana daripada nama.';
            var introHelp = add.previousElementSibling; if (introHelp && introHelp.tagName === 'P') introHelp.textContent = afsCatalog.writer ? 'Tambah profil penulis, kemudian kaitkan buku melalui medan Penulis pada produk.' : 'Tambah kategori atau edit kategori sedia ada. Seret baris dalam senarai untuk mengubah susunan.';
        }
        var table = document.querySelector('#col-right .wp-list-table');
        if (table) { var scroll = document.createElement('div'); scroll.className = 'afs-catalog-table'; table.parentNode.insertBefore(scroll, table); scroll.appendChild(table); }
    }
}());
