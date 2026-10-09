/* Only loaded on the banner screen. Uses the existing WordPress media library. */
(function () {
  'use strict';
  document.addEventListener('click', function (event) {
    var choose = event.target.closest('.afs-select-image');
    var clear = event.target.closest('.afs-clear-image');
    if (!choose && !clear) return;
    var container = (choose || clear).closest('.afs-media');
    var input = container.querySelector('input[type="hidden"]');
    var preview = container.querySelector('.afs-media-preview');
    if (clear) {
      input.value = '0';
      preview.replaceChildren();
      return;
    }
    var frame = wp.media({ title: 'Pilih Gambar Banner', button: { text: 'Gunakan Gambar' }, library: { type: 'image' }, multiple: false });
    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      input.value = String(attachment.id);
      var image = document.createElement('img');
      image.src = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
      image.alt = attachment.alt || '';
      preview.replaceChildren(image);
    });
    frame.open();
  });
}());
