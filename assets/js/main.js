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

  /* ---------- Certificate lightbox ---------- */
  var lightboxApi = { close: function () {} };
  function lightbox() {
    var lb = qs('[data-lightbox]');
    if (!lb) return;
    var title = qs('[data-lightbox-title]', lb);
    var lastFocused = null;
    var setOpen = function (openIt, label) {
      if (openIt) {
        lastFocused = document.activeElement;
        lb.hidden = false;
        document.body.style.overflow = 'hidden';
        if (title && label) title.textContent = label;
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
      b.addEventListener('click', function () { setOpen(true, b.getAttribute('data-cert')); });
    });
    var closeBtn = qs('[data-close-lightbox]', lb);
    if (closeBtn) closeBtn.addEventListener('click', function () { setOpen(false); });
    lb.addEventListener('click', function (e) { if (e.target === lb) setOpen(false); });
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
          if (note) { note.textContent = 'Sorry — that didn’t send. Please email tahsininternationalbd2021@gmail.com or WhatsApp +880 1716 610665.'; note.className = 'form__note form__note--err'; }
        }).then(function () {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.removeAttribute('aria-busy'); submitBtn.innerHTML = originalLabel; }
          window.setTimeout(function () { if (note) { note.textContent = defaultNote; note.className = 'form__note'; } }, 8000);
        });
      });
    });
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
    globalEscape();
    lazyMap();
    scrollReveal();
    countUp();
    footerYear();
    tabs();
    filterChips();
    multiStep();
    formHandler();
    i18n();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
