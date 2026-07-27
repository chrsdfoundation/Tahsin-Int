/* Tahsin admin — self-contained rich-text editor + media picker (no dependencies). */
(function () {
  'use strict';
  var csrf = (document.querySelector('meta[name="ti-csrf"]') || {}).content || '';

  /* ---------------- Media picker (shared modal) ---------------- */
  function openMediaPicker(onPick) {
    var back = document.createElement('div');
    back.className = 'mp-back';
    back.innerHTML =
      '<div class="mp" role="dialog" aria-modal="true" aria-label="Media picker">' +
        '<div class="mp-head"><strong>Media library</strong>' +
          '<label class="a-btn a-btn--primary mp-up">Upload<input type="file" accept=".jpg,.jpeg,.png,.webp,.gif" hidden></label>' +
          '<button type="button" class="a-btn mp-close">Close</button>' +
        '</div>' +
        '<div class="mp-note"></div>' +
        '<div class="mp-grid">Loading…</div>' +
      '</div>';
    document.body.appendChild(back);
    var grid = back.querySelector('.mp-grid');
    var note = back.querySelector('.mp-note');
    var close = function () { back.remove(); };
    back.addEventListener('click', function (e) { if (e.target === back) close(); });
    back.querySelector('.mp-close').addEventListener('click', close);
    document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { close(); document.removeEventListener('keydown', esc); } });

    function render(files) {
      var imgs = files.filter(function (f) { return f.isImage; });
      if (!imgs.length) { grid.innerHTML = '<p class="a-hint" style="padding:16px">No images yet — upload one above.</p>'; return; }
      grid.innerHTML = '';
      imgs.forEach(function (f) {
        var b = document.createElement('button');
        b.type = 'button'; b.className = 'mp-item'; b.title = f.name;
        b.innerHTML = '<img src="../' + f.path + '" alt="" loading="lazy"><span>' + f.name + '</span>';
        b.addEventListener('click', function () { onPick(f.path); close(); });
        grid.appendChild(b);
      });
    }
    function load() {
      fetch('media-list.php', { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { render((d && d.files) || []); })
        .catch(function () { grid.innerHTML = '<p class="a-hint" style="padding:16px">Could not load media.</p>'; });
    }
    load();

    back.querySelector('.mp-up input').addEventListener('change', function () {
      if (!this.files || !this.files[0]) return;
      note.textContent = 'Uploading…';
      var fd = new FormData(); fd.append('csrf', csrf); fd.append('file', this.files[0]);
      fetch('media-upload.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d && d.ok) { note.textContent = 'Uploaded ' + d.name; load(); }
          else { note.textContent = (d && d.error) || 'Upload failed.'; }
        })
        .catch(function () { note.textContent = 'Upload failed.'; });
    });
  }

  // Expose for image-path fields ("Choose from media" buttons)
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-media-pick]');
    if (!b) return;
    e.preventDefault();
    var input = document.querySelector(b.getAttribute('data-media-pick'));
    openMediaPicker(function (path) { if (input) { input.value = path; input.dispatchEvent(new Event('input')); } });
  });

  /* ---------------- Rich-text editor ---------------- */
  function exec(cmd, val) { document.execCommand(cmd, false, val || null); }

  function buildEditor(textarea) {
    var wrap = document.createElement('div');
    wrap.className = 'rte';
    var tools = [
      ['Bold', 'b', function () { exec('bold'); }],
      ['Italic', 'i', function () { exec('italic'); }],
      ['H2', 'h2', function () { exec('formatBlock', 'H2'); }],
      ['H3', 'h3', function () { exec('formatBlock', 'H3'); }],
      ['Paragraph', 'p', function () { exec('formatBlock', 'P'); }],
      ['• List', 'ul', function () { exec('insertUnorderedList'); }],
      ['1. List', 'ol', function () { exec('insertOrderedList'); }],
      ['Quote', 'q', function () { exec('formatBlock', 'BLOCKQUOTE'); }],
      ['Link', 'a', function () { var u = window.prompt('Link URL:', 'https://'); if (u) exec('createLink', u); }],
      ['Image', 'img', function () { openMediaPicker(function (p) { exec('insertHTML', '<img src="../' + p + '" alt="">'); syncFrom(); }); }],
      ['Clear', 'x', function () { exec('removeFormat'); }],
    ];
    var bar = document.createElement('div');
    bar.className = 'rte-bar';
    tools.forEach(function (t) {
      var btn = document.createElement('button');
      btn.type = 'button'; btn.className = 'rte-btn'; btn.textContent = t[0];
      btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
      btn.addEventListener('click', function () { area.focus(); t[2](); syncFrom(); });
      bar.appendChild(btn);
    });
    var srcBtn = document.createElement('button');
    srcBtn.type = 'button'; srcBtn.className = 'rte-btn rte-src'; srcBtn.textContent = '</> HTML';
    bar.appendChild(srcBtn);

    var area = document.createElement('div');
    area.className = 'rte-area news-body';
    area.contentEditable = 'true';
    area.innerHTML = toDisplay(textarea.value) || '<p></p>';

    // Stored HTML uses root-relative asset paths (correct on the public page);
    // the admin preview needs ../ because /admin is one level down.
    function toStored(html) { return html.replace(/(src|href)="\.\.\/assets\//g, '$1="assets/'); }
    function toDisplay(html) { return html.replace(/(src|href)="assets\//g, '$1="../assets/'); }
    function syncFrom() { textarea.value = toStored(area.innerHTML); }
    function syncTo() { area.innerHTML = toDisplay(textarea.value) || '<p></p>'; }
    area.addEventListener('input', syncFrom);
    area.addEventListener('blur', syncFrom);

    var showingSource = false;
    srcBtn.addEventListener('click', function () {
      showingSource = !showingSource;
      if (showingSource) { syncFrom(); textarea.style.display = 'block'; area.style.display = 'none'; srcBtn.classList.add('is-on'); }
      else { syncTo(); textarea.style.display = 'none'; area.style.display = 'block'; srcBtn.classList.remove('is-on'); }
    });

    textarea.style.display = 'none';
    textarea.parentNode.insertBefore(wrap, textarea);
    wrap.appendChild(bar);
    wrap.appendChild(area);
    wrap.appendChild(textarea); // keep in DOM for form submit
    if (textarea.form) textarea.form.addEventListener('submit', syncFrom);
  }

  document.querySelectorAll('textarea[data-richtext]').forEach(buildEditor);
})();
