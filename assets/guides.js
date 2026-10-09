(function () {
  'use strict';
  function openTopic(hash) {
    if (!/^#afs-guide-\d+$/.test(hash)) return;
    var topic = document.getElementById(hash.slice(1));
    if (topic && topic.tagName === 'DETAILS') topic.open = true;
  }
  document.addEventListener('click', function (event) {
    var link = event.target.closest('.afs-guide-index a');
    if (link) openTopic(link.hash);
  });
  window.addEventListener('hashchange', function () { openTopic(window.location.hash); });
  openTopic(window.location.hash);
}());
