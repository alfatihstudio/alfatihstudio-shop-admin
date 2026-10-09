(function () {
    'use strict';
    var busy = false;
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.afs-image-action');
        if (!button) return;
        event.preventDefault();
        var container = button.closest('.afs-image-tools');
        var status = container.querySelector('.afs-image-result');
        if (busy) { status.textContent = 'Satu gambar sedang diproses. Sila tunggu.'; return; }
        busy = true;
        var buttons = document.querySelectorAll('.afs-image-action');
        buttons.forEach(function (item) { item.disabled = true; });
        status.textContent = 'Sedang diproses…';
        var body = new URLSearchParams({ action: 'afs_optimize_image', nonce: afsImageOptimizer.nonce, id: button.dataset.id, operation: button.dataset.operation });
        fetch(afsImageOptimizer.url, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                status.textContent = response.data && response.data.message ? response.data.message : 'Pemprosesan gagal. Sila cuba lagi.';
                if (!response.success) return;
                button.hidden = true;
                if (response.data.status === 'optimized') {
                    button.dataset.operation = 'restore';
                    button.textContent = 'Pulihkan gambar asal';
                    button.classList.remove('button-primary');
                    button.hidden = false;
                }
                if (response.data.status === 'restored' || response.data.status === 'unchanged') {
                    button.dataset.operation = 'optimize';
                    button.textContent = 'Optimize gambar';
                    button.classList.add('button-primary');
                    button.hidden = false;
                }
                if (window.wp && wp.media && wp.media.attachment) {
                    wp.media.attachment(Number(button.dataset.id)).fetch();
                }
            })
            .catch(function () { status.textContent = 'Sambungan terputus. Buka semula gambar untuk semak status sebelum cuba lagi.'; })
            .finally(function () {
                busy = false;
                buttons.forEach(function (item) { item.disabled = false; });
            });
    });
}());
