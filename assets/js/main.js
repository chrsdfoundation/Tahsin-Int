/* =====================================================================
   Tahsin International — main.js
   Vanilla ES2019, no dependencies. One IIFE, small named modules.
   Progressive enhancement: the site is fully usable with JS disabled.
   ===================================================================== */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var qs = function (s, r) { return (r || document).querySelector(s); };
  var qsa = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var escHtml = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var assetsBase = function () { return document.documentElement.getAttribute('data-assets-base') || './'; };

  /* ---------- Sticky header condense ---------- */
  function stickyHeader() {
    var header = qs('[data-header]');
    if (!header) return;
    var onScroll = function () {
      header.classList.toggle('header--scrolled', window.scrollY > 30);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- Desktop dropdown nav (hover + keyboard focus) ---------- */
  function dropdownNav() {
    var mqDesktop = window.matchMedia('(min-width: 1024px)');
    qsa('[data-navitem]').forEach(function (item) {
      var panel = qs('.nav__panel', item);
      if (!panel) return;
      var timer;
      var open = function () { window.clearTimeout(timer); if (mqDesktop.matches) item.classList.add('is-open'); };
      var close = function () { timer = window.setTimeout(function () { item.classList.remove('is-open'); }, 120); };
      item.addEventListener('mouseenter', open);
      item.addEventListener('mouseleave', close);
      item.addEventListener('focusin', open);
      item.addEventListener('focusout', close);
    });
  }

  /* ---------- Mobile drawer (focus trap + Escape + click-outside) ---------- */
  var drawerApi = { close: function () {} };
  function mobileDrawer() {
    var drawer = qs('[data-drawer]');
    var burger = qs('[data-burger]');
    if (!drawer || !burger) return;
    var panel = qs('[data-drawer-panel]', drawer) || drawer;
    var lastFocused = null;

    var focusables = function () {
      return qsa('a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])', panel)
        .filter(function (el) { return el.offsetParent !== null; });
    };

    var onKeydown = function (e) {
      if (e.key === 'Escape') { setOpen(false); return; }
      if (e.key !== 'Tab') return;
      var f = focusables();
      if (!f.length) return;
      var first = f[0], last = f[f.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    };

    var setOpen = function (openIt) {
      if (openIt) {
        lastFocused = document.activeElement;
        drawer.hidden = false;
        document.body.style.overflow = 'hidden';
        burger.setAttribute('aria-expanded', 'true');
        document.addEventListener('keydown', onKeydown);
        var f = focusables();
        var closeBtn = qs('[data-close-drawer]', drawer);
        (closeBtn || f[0]) && (closeBtn || f[0]).focus();
      } else {
        drawer.hidden = true;
        document.body.style.overflow = '';
        burger.setAttribute('aria-expanded', 'false');
        document.removeEventListener('keydown', onKeydown);
        if (lastFocused && lastFocused.focus) lastFocused.focus();
      }
    };
    drawerApi.close = function () { setOpen(false); };

    burger.addEventListener('click', function () { setOpen(true); });
    var closeBtn = qs('[data-close-drawer]', drawer);
    if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
    drawer.addEventListener('click', function (e) { if (e.target === drawer) setOpen(false); });
    qsa('a', drawer).forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
  }

  /* ---------- Scroll-snap carousel ---------- */
  function carousel() {
    qsa('[data-carousel]').forEach(function (track) {
      var scope = track.closest('[data-carousel-scope]') || document;
      var prev = qs('[data-carousel-prev]', scope);
      var next = qs('[data-carousel-next]', scope);
      var step = function () {
        var s = qs('.slide', track);
        return s ? s.getBoundingClientRect().width + 20 : 380;
      };
      if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
      if (next) next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
    });
  }

  /* ---------- Certificate lightbox (image-aware) ---------- */
  var lightboxApi = { close: function () {} };
  function lightbox() {
    var lb = qs('[data-lightbox]');
    if (!lb) return;
    var base = document.documentElement.getAttribute('data-assets-base') || './';
    var title = qs('[data-lightbox-title]', lb);
    var imgEl = qs('[data-lightbox-img]', lb);
    var msgEl = qs('[data-lightbox-msg]', lb);
    var lastFocused = null;
    var setOpen = function (openIt, label, img) {
      if (openIt) {
        lastFocused = document.activeElement;
        lb.hidden = false;
        document.body.style.overflow = 'hidden';
        if (title && label) title.textContent = label;
        if (imgEl && msgEl) {
          if (img) { imgEl.src = base + img; imgEl.alt = label || 'Certificate'; imgEl.hidden = false; msgEl.hidden = true; }
          else { imgEl.hidden = true; imgEl.removeAttribute('src'); msgEl.hidden = false; }
        }
        var closeBtn = qs('[data-close-lightbox]', lb);
        if (closeBtn) closeBtn.focus();
      } else {
        lb.hidden = true;
        document.body.style.overflow = '';
        if (lastFocused && lastFocused.focus) lastFocused.focus();
      }
    };
    lightboxApi.close = function () { setOpen(false); };
    qsa('[data-cert]').forEach(function (b) {
      b.addEventListener('click', function () { setOpen(true, b.getAttribute('data-cert'), b.getAttribute('data-cert-img')); });
    });
    var closeBtn = qs('[data-close-lightbox]', lb);
    if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
    lb.addEventListener('click', function (e) { if (e.target === lb) setOpen(false); });
  }

  /* ---------- Site photos (admin-managed assets/data/site-images.json) ---------- */
  function siteImages() {
    var slots = qsa('[data-photo]');
    if (!slots.length) return;
    var base = document.documentElement.getAttribute('data-assets-base') || './';
    fetch(base + 'assets/data/site-images.json', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { images: {} }; })
      .then(function (data) {
        var imgs = (data && data.images) || {};
        slots.forEach(function (slot) {
          var entry = imgs[slot.getAttribute('data-photo')];
          if (!entry || !entry.image) return;
          var src = base + entry.image;
          var alt = entry.alt || 'Tahsin International';
          if (slot.classList.contains('hero__bg')) {
            // keep text legible: dark brand gradient over the photo
            slot.style.backgroundImage =
              'linear-gradient(115deg, rgba(0,0,139,.82) 0%, rgba(0,0,102,.86) 55%, rgba(18,18,110,.86) 100%), url("' + src + '")';
            slot.style.backgroundSize = 'cover';
            slot.style.backgroundPosition = 'center';
          } else {
            slot.classList.add('has-img');
            var img = document.createElement('img');
            img.src = src; img.alt = alt; img.loading = 'lazy';
            slot.innerHTML = '';
            slot.appendChild(img);
          }
        });
      })
      .catch(function () {});
  }

  /* ---------- Certificate scans (admin-managed assets/data/certificates.json) ---------- */
  function certificates() {
    var tiles = qsa('[data-cert-id]');
    if (!tiles.length) return;
    var base = document.documentElement.getAttribute('data-assets-base') || './';
    fetch(base + 'assets/data/certificates.json', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { certificates: {} }; })
      .then(function (data) {
        var certs = (data && data.certificates) || {};
        tiles.forEach(function (tile) {
          var c = certs[tile.getAttribute('data-cert-id')];
          if (!c || !c.image || c.published === false) return;
          tile.setAttribute('data-cert-img', c.image);
          tile.classList.add('has-img');
          var img = document.createElement('img');
          img.src = base + c.image;
          img.alt = tile.getAttribute('data-cert') || 'Certificate';
          img.loading = 'lazy';
          tile.insertBefore(img, tile.firstChild);
        });
      })
      .catch(function () {});
  }

  /* ---------- Global Escape (drawer + lightbox) ---------- */
  function globalEscape() {
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { lightboxApi.close(); }
    });
  }

  /* ---------- Lazy Google Map (click to load) ---------- */
  function lazyMap() {
    var btn = qs('[data-load-map]');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var wrap = qs('[data-map]');
      if (!wrap) return;
      wrap.innerHTML = '';
      var f = document.createElement('iframe');
      f.src = 'https://www.google.com/maps?q=Paltan+China+Town+East+Tower,+Naya+Paltan,+Dhaka+1000&output=embed';
      f.title = 'Tahsin International office location';
      f.loading = 'lazy';
      f.referrerPolicy = 'no-referrer-when-downgrade';
      f.width = '100%';
      f.height = '100%';
      wrap.appendChild(f);
    });
  }

  /* ---------- Scroll reveal ---------- */
  function scrollReveal() {
    var els = qsa('[data-reveal]');
    if (!els.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) return; // no-JS/reduced: stays visible
    document.documentElement.classList.add('reveal-on');
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    els.forEach(function (el) { io.observe(el); });
    // Safety: reveal everything after 2.5s in case observer misses
    window.setTimeout(function () { els.forEach(function (el) { el.classList.add('is-visible'); }); }, 2500);
  }

  /* ---------- Count-up stats ---------- */
  function countUp() {
    var counters = qsa('[data-count]');
    if (!counters.length) return;
    var run = function (el) {
      var target = parseInt(el.getAttribute('data-count'), 10) || 0;
      if (reduceMotion) { el.textContent = String(target); return; }
      var dur = 1100, t0 = performance.now();
      var tick = function (now) {
        var p = Math.min((now - t0) / dur, 1);
        el.textContent = String(Math.round(target * (1 - Math.pow(1 - p, 3))));
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    };
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) { run(en.target); io.unobserve(en.target); } });
      }, { threshold: 0.4 });
      counters.forEach(function (el) { io.observe(el); });
    } else counters.forEach(run);
  }

  /* ---------- Footer year ---------- */
  function footerYear() {
    qsa('[data-year]').forEach(function (el) { el.textContent = String(new Date().getFullYear()); });
  }

  /* ---------- Tabs & filter chips ---------- */
  function tabs() {
    qsa('[data-tabs]').forEach(function (group) {
      var buttons = qsa('[data-tab]', group);
      buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var target = btn.getAttribute('data-tab');
          buttons.forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', String(on));
          });
          qsa('[data-tab-panel]', group).forEach(function (p) {
            p.hidden = p.getAttribute('data-tab-panel') !== target;
          });
        });
      });
    });
  }

  function filterChips() {
    qsa('[data-filter-group]').forEach(function (group) {
      var chips = qsa('[data-filter]', group);
      var itemsScope = qs('[data-filter-items]', group.parentNode) || document;
      chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
          var key = chip.getAttribute('data-filter');
          chips.forEach(function (c) { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-pressed', String(c === chip)); });
          qsa('[data-category]', itemsScope).forEach(function (item) {
            item.hidden = key !== 'all' && item.getAttribute('data-category') !== key;
          });
        });
      });
    });
  }

  /* ---------- Multi-step forms (RFQ) ---------- */
  function multiStep() {
    qsa('[data-multistep]').forEach(function (form) {
      var steps = qsa('[data-step]', form);
      if (steps.length < 2) return;
      var dots = qsa('[data-progress-dot]', form);
      var label = qs('[data-progress-label]', form);
      var current = 0;

      var show = function (i) {
        current = Math.max(0, Math.min(i, steps.length - 1));
        steps.forEach(function (s, idx) { s.classList.toggle('is-active', idx === current); });
        dots.forEach(function (d, idx) {
          d.classList.toggle('is-active', idx === current);
          d.classList.toggle('is-done', idx < current);
        });
        if (label) label.textContent = 'Step ' + (current + 1) + ' of ' + steps.length;
        var focusable = qs('input, select, textarea', steps[current]);
        if (focusable) focusable.focus();
      };

      var validateStep = function () {
        var fields = qsa('input, select, textarea', steps[current]);
        for (var i = 0; i < fields.length; i++) {
          if (!fields[i].checkValidity()) { fields[i].reportValidity(); return false; }
        }
        return true;
      };

      form.addEventListener('click', function (e) {
        var nextBtn = e.target.closest('[data-step-next]');
        var prevBtn = e.target.closest('[data-step-prev]');
        if (nextBtn) { e.preventDefault(); if (validateStep()) show(current + 1); }
        if (prevBtn) { e.preventDefault(); show(current - 1); }
      });
      show(0);
    });
  }

  /* ---------- Form handler (honeypot + validation + fetch enhancement) ---------- */
  function formHandler() {
    qsa('[data-form]').forEach(function (form) {
      var note = qs('[data-form-note]', form);
      var defaultNote = note ? note.textContent : '';

      form.addEventListener('submit', function (e) {
        var hp = form.querySelector('[name="company_website"]');
        if (hp && hp.value) { e.preventDefault(); return; } // bot

        if (!form.checkValidity()) { return; } // let native validation show

        if (!('fetch' in window) || !('FormData' in window)) { return; } // fall back to normal POST

        e.preventDefault();
        var submitBtn = qs('[type="submit"]', form);
        var originalLabel = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) { submitBtn.disabled = true; submitBtn.setAttribute('aria-busy', 'true'); }

        fetch(form.getAttribute('action'), {
          method: 'POST',
          body: new FormData(form),
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
          return r.json().catch(function () { return { ok: r.ok }; });
        }).then(function (data) {
          if (data && data.ok) {
            form.reset();
            if (note) { note.textContent = 'Thank you — your message has been sent. We reply within one business day.'; note.className = 'form__note form__note--ok'; }
          } else {
            throw new Error((data && data.error) || 'send failed');
          }
        }).catch(function () {
          if (note) { note.textContent = ‘Sorry — that didn’t send. Please email info@tigroup.com.bd or WhatsApp 01714781490.’; note.className = ‘form__note form__note--err’; }
        }).then(function () {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.removeAttribute('aria-busy'); submitBtn.innerHTML = originalLabel; }
          window.setTimeout(function () { if (note) { note.textContent = defaultNote; note.className = 'form__note'; } }, 8000);
        });
      });
    });
  }

  /* ---------- News & Blog (reads admin-published assets/data/news.json) ---------- */
  function news() {
    var root = qs('[data-news]');
    if (!root) return;
    var base = document.documentElement.getAttribute('data-assets-base') || './';
    var listEl = qs('[data-news-list]', root);
    var articleEl = qs('[data-news-article]', root);
    var loadingEl = qs('[data-news-loading]', root);
    var emptyEl = qs('[data-news-empty]', root);
    var esc = function (s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    };
    var fmtDate = function (d) {
      if (!d) return '';
      var t = new Date(d);
      if (isNaN(t)) return esc(d);
      return t.toLocaleDateString('en-GB', { year: 'numeric', month: 'long', day: 'numeric' });
    };
    var getParam = function (k) {
      var m = new RegExp('[?&]' + k + '=([^&]+)').exec(location.search);
      return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : null;
    };
    var show = function (el) { if (el) el.hidden = false; };
    var hide = function (el) { if (el) el.hidden = true; };

    fetch(base + 'assets/data/news.json', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { posts: [] }; })
      .then(function (data) {
        hide(loadingEl);
        var posts = ((data && data.posts) || []).filter(function (p) { return p && p.published !== false; });
        posts.sort(function (a, b) { return String(b.date || '').localeCompare(String(a.date || '')); });
        var slug = getParam('post');

        if (slug) {
          var post = posts.filter(function (p) { return p.slug === slug; })[0];
          if (!post) { renderList(posts); return; }
          document.title = post.title + ' — News · Tahsin International';
          var cover = post.cover ? '<img src="' + base + esc(post.cover) + '" alt="' + esc(post.title) + '" style="width:100%;border-radius:12px;margin:0 0 24px" loading="lazy">' : '';
          articleEl.innerHTML =
            '<a class="link-more" href="' + base + 'news.html" style="margin-bottom:20px">‹ All posts</a>' +
            '<span class="eyebrow" style="margin-top:8px">' + fmtDate(post.date) + (post.author ? ' · ' + esc(post.author) : '') + '</span>' +
            '<h1 class="h2" style="font-size:clamp(1.75rem,4vw,2.4rem);margin:0 0 20px">' + esc(post.title) + '</h1>' +
            cover +
            '<div class="news-body">' + (post.body || '') + '</div>';
          show(articleEl);
          return;
        }
        renderList(posts);

        function renderList(list) {
          if (!list.length) { show(emptyEl); return; }
          listEl.innerHTML = list.map(function (p) {
            var cover = p.cover
              ? '<img src="' + base + esc(p.cover) + '" alt="' + esc(p.title) + '" style="width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:10px" loading="lazy">'
              : '<div class="photo-slot ratio-16-10" style="border-radius:10px;padding:16px"><span class="photo-slot__desc" style="font-size:13px">No cover image</span></div>';
            return '<a class="card" href="' + base + 'news.html?post=' + encodeURIComponent(p.slug) + '">' +
              cover +
              '<span class="slide__meta" style="margin-top:12px">' + fmtDate(p.date) + '</span>' +
              '<h3 class="card__title" style="font-size:1.125rem">' + esc(p.title) + '</h3>' +
              '<p class="card__body" style="font-size:14.5px">' + esc(p.excerpt || '') + '</p>' +
              '<span class="card__more">Read more<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>' +
              '</a>';
          }).join('');
          show(listEl);
        }
      })
      .catch(function () { hide(loadingEl); show(emptyEl); });
  }

  /* ---------- Products (admin-managed assets/data/products.json) ---------- */
  function products() {
    var wrap = qs('[data-products]');
    if (!wrap) return;
    var base = assetsBase();
    var arrow = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
    fetch(base + 'assets/data/products.json', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { products: [] }; })
      .then(function (data) {
        var list = ((data && data.products) || []).filter(function (p) { return p && p.published !== false; });
        if (!list.length) { wrap.innerHTML = '<div class="empty-state"><h3>No products yet</h3><p>Products will appear here soon.</p></div>'; return; }
        wrap.innerHTML = list.map(function (p) {
          var media = p.image
            ? '<img src="' + base + escHtml(p.image) + '" alt="' + escHtml(p.title) + '" style="width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:10px" loading="lazy">'
            : '<div class="photo-slot ratio-16-10" style="border-radius:10px;padding:16px"><span class="photo-slot__desc" style="font-size:13px">Product image · 16:10</span></div>';
          return '<article class="card" data-category="' + escHtml(p.category || '') + '">' + media +
            '<h3 class="card__title" style="font-size:1.05rem;margin-top:14px">' + escHtml(p.title) + '</h3>' +
            '<p class="card__body" style="font-size:14.5px">' + escHtml(p.description || '') + '</p>' +
            '<a class="card__more" href="' + base + 'rfq.html">Request quote' + arrow + '</a></article>';
        }).join('');
      })
      .catch(function () { wrap.innerHTML = '<div class="empty-state"><h3>Could not load products</h3></div>'; });
  }

  /* ---------- Projects (admin-managed assets/data/projects.json) ---------- */
  function projects() {
    var panels = qsa('[data-projects-panel]');
    var carousel = qs('[data-projects-carousel]');
    if (!panels.length && !carousel) return;
    var base = assetsBase();
    var imgIcon = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.5-4.5L7 21"/></svg>';
    var meta = function (p) { var m = [p.client, p.year].filter(Boolean).join(' · '); return m || 'Client · Year'; };

    fetch(base + 'assets/data/projects.json', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { projects: [] }; })
      .then(function (data) {
        var all = ((data && data.projects) || []).filter(function (p) { return p && p.published !== false; });

        panels.forEach(function (panel) {
          var status = panel.getAttribute('data-projects-panel');
          var list = all.filter(function (p) { return (p.status || 'completed') === status; });
          if (!list.length) {
            panel.innerHTML = '<div class="empty-state">' +
              '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>' +
              '<h3>No ' + escHtml(status) + ' projects yet</h3><p>Records are published here once available. <a href="' + base + 'contact.html">Contact us</a> to discuss current capacity.</p></div>';
            return;
          }
          panel.innerHTML = '<div class="grid grid--cards">' + list.map(function (p) {
            var media = p.image
              ? '<img src="' + base + escHtml(p.image) + '" alt="' + escHtml(p.title) + '" style="width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:10px" loading="lazy">'
              : '<div class="photo-slot ratio-16-10" style="border-radius:10px;padding:16px">' + imgIcon + '</div>';
            return '<article class="card">' + media +
              '<span class="slide__meta" style="margin-top:12px">' + escHtml(meta(p)) + '</span>' +
              '<h3 class="card__title" style="font-size:1.125rem">' + escHtml(p.title) + '</h3>' +
              '<p class="card__body" style="font-size:14.5px">' + escHtml(p.description || '') + '</p></article>';
          }).join('') + '</div>';
        });

        if (carousel) {
          var list = all.slice(0, 8);
          if (list.length) {
            var cta = carousel.querySelector('.slide--cta');
            qsa('.slide:not(.slide--cta)', carousel).forEach(function (s) { s.remove(); });
            var html = list.map(function (p) {
              var media = p.image
                ? '<div class="slide__media" style="padding:0"><img src="' + base + escHtml(p.image) + '" alt="' + escHtml(p.title) + '" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>'
                : '<div class="slide__media">' + imgIcon + '<span>' + escHtml(p.title) + '</span></div>';
              return '<article class="slide">' + media +
                '<div class="slide__body"><span class="slide__meta">' + escHtml(meta(p)) + '</span>' +
                '<h3 class="slide__title">' + escHtml(p.title) + '</h3>' +
                '<p class="slide__desc">' + escHtml(p.description || '') + '</p></div></article>';
            }).join('');
            if (cta) cta.insertAdjacentHTML('beforebegin', html);
            else carousel.innerHTML = html;
          }
        }
      })
      .catch(function () {});
  }

  /* ---------- Memberships & partners (admin-managed assets/data/memberships.json) ---------- */
  function memberships() {
    var wraps = qsa('[data-memberships]');
    if (!wraps.length) return;
    var base = assetsBase();
    var MARK = '<svg class="member__mark" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M4 21V10l8-5 8 5v11M9 21v-6h6v6M6 21v-8M18 21v-8"/></svg>';
    fetch(base + 'assets/data/memberships.json', { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : { items: [] }; })
      .then(function (data) {
        var items = ((data && data.items) || []).filter(function (m) { return m && m.published !== false; });
        wraps.forEach(function (wrap) {
          if (!items.length) {
            wrap.innerHTML = '<div class="member member--empty">' + MARK + '<span class="member__name">Memberships &amp; partners will appear here</span></div>';
            return;
          }
          wrap.innerHTML = items.map(function (m) {
            var inner = m.logo
              ? '<img class="member__logo" src="' + base + escHtml(m.logo) + '" alt="' + escHtml(m.name || m.acronym || '') + '" loading="lazy">'
              : MARK + '<span class="member__badge">' + escHtml(m.acronym || '') + '</span>';
            return '<div class="member">' + inner + '<span class="member__name">' + escHtml(m.name || '') + '</span></div>';
          }).join('');
        });
      })
      .catch(function () {});
  }

  /* ---------- i18n scaffolding (EN active; বাংলা disabled until copy arrives) ---------- */
  function i18n() {
    var buttons = qsa('[data-lang]');
    if (!buttons.length) return;
    var base = document.documentElement.getAttribute('data-assets-base') || './';
    var cache = {};
    var apply = function (dict) {
      qsa('[data-i18n]').forEach(function (el) {
        var key = el.getAttribute('data-i18n');
        if (dict && dict[key]) el.textContent = dict[key];
      });
    };
    var setLang = function (lang) {
      buttons.forEach(function (b) { b.classList.toggle('lang__btn--active', b.getAttribute('data-lang') === lang); });
      document.documentElement.lang = lang === 'bn' ? 'bn' : 'en';
      if (lang === 'en') { apply(null); return; } // EN is the authored source
      if (cache[lang]) { apply(cache[lang]); return; }
      fetch(base + 'assets/js/i18n/' + lang + '.json')
        .then(function (r) { return r.json(); })
        .then(function (d) { cache[lang] = d; apply(d); })
        .catch(function () {});
    };
    buttons.forEach(function (b) {
      if (b.disabled || b.classList.contains('lang__btn--disabled')) return;
      b.addEventListener('click', function () { setLang(b.getAttribute('data-lang')); });
    });
  }

  /* ---------- Init ---------- */
  function init() {
    stickyHeader();
    dropdownNav();
    mobileDrawer();
    carousel();
    lightbox();
    certificates();
    siteImages();
    globalEscape();
    lazyMap();
    scrollReveal();
    countUp();
    footerYear();
    tabs();
    filterChips();
    multiStep();
    formHandler();
    news();
    products();
    projects();
    memberships();
    i18n();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
