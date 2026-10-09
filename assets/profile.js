(function () {
    'use strict';
    var form = document.getElementById('your-profile');
    if (!form || !document.body.classList.contains('afs-profile')) return;
    var heading = document.querySelector('#profile-page > h1');
    if (heading) {
        var intro = document.createElement('p');
        intro.className = 'afs-profile-intro';
        intro.textContent = 'Urus maklumat peribadi, email dan keselamatan akaun kedai anda.';
        heading.insertAdjacentElement('afterend', intro);
    }
    // Move only adjacent native headings/tables. Inputs, names, form, nonces,
    // third-party sections and password event handlers remain the same nodes.
    Array.from(form.children).forEach(function (element) {
        if (element.tagName !== 'H2') return;
        var table = element.nextElementSibling;
        if (!table || !table.matches('table.form-table')) return;
        var rows = Array.from(table.querySelectorAll('tr'));
        var visible = rows.some(function (row) { return getComputedStyle(row).display !== 'none'; });
        if (!visible) { element.hidden = true; table.hidden = true; return; }
        var section = document.createElement('section');
        section.className = 'afs-profile-card';
        form.insertBefore(section, element);
        section.appendChild(element);
        section.appendChild(table);
        if (table.querySelector('.user-pass1-wrap')) section.classList.add('afs-profile-security');
    });
    var submit = form.querySelector('p.submit');
    if (submit) {
        submit.classList.add('afs-profile-actions');
        var note = document.createElement('span');
        note.textContent = 'Perubahan disimpan selepas anda menekan butang ini.';
        submit.appendChild(note);
    }
}());
