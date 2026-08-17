<?php
/**
 * Frontend interactions from the HTML design, plus search overlay.
 */
(function () {
  const loader = document.getElementById('loader');
  if (loader) {
    const hide = () => {
      loader.classList.add('out');
      loader.classList.remove('is-pending');
    };
    const showIfSlow = window.setTimeout(() => {
      if (document.readyState !== 'complete') {
        loader.classList.add('is-pending');
      }
    }, 2000);
    window.addEventListener('load', () => {
      window.clearTimeout(showIfSlow);
      hide();
    });
    if (document.readyState === 'complete') {
      window.clearTimeout(showIfSlow);
      hide();
    }
  }

  const hdr = document.getElementById('hdr');
  const fab = document.getElementById('fabStack');
  const onScroll = () => {
    const y = window.scrollY;
    if (hdr) hdr.classList.toggle('scrolled', y > 40);
    if (fab) fab.classList.toggle('show', y > 500);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  window.toggleMob = function (open) {
    const m = document.getElementById('mob');
    const ham = document.querySelector('.ham');
    if (m) {
      m.classList.toggle('open', open);
      if (open) {
        const close = m.querySelector('.mob-close');
        if (close) close.focus();
      } else if (ham) {
        ham.focus();
      }
    }
    if (ham) ham.setAttribute('aria-expanded', open ? 'true' : 'false');
  };
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') window.toggleMob(false);
  });

  const box = document.getElementById('particles');
  if (box) {
    const spawn = () => {
      if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      for (let i = 0; i < 22; i++) {
        const p = document.createElement('span');
        p.className = 'particle';
        const s = Math.random() * 4 + 2;
        p.style.width = p.style.height = s + 'px';
        p.style.left = Math.random() * 100 + '%';
        p.style.top = Math.random() * 100 + '%';
        p.style.opacity = Math.random() * 0.5 + 0.2;
        p.style.animationDelay = Math.random() * 8 + 's';
        box.appendChild(p);
      }
    };
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(spawn, { timeout: 2500 });
    } else {
      window.setTimeout(spawn, 1);
    }
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add('on');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.rv,.rv-l').forEach((el) => io.observe(el));

  const counted = new Set();
  function animateCount(el) {
    if (counted.has(el)) return;
    counted.add(el);
    const target = parseFloat(el.dataset.count);
    if (Number.isNaN(target)) return;
    const dec = parseInt(el.dataset.dec || '0', 10);
    const suffix = el.dataset.suffix || '';
    const dur = 1600;
    const start = performance.now();
    function step(now) {
      const t = Math.min((now - start) / dur, 1);
      const eased = 1 - Math.pow(1 - t, 3);
      const val = target * eased;
      el.textContent = (dec ? val.toFixed(dec) : Math.floor(val).toLocaleString('en-US')) + suffix;
      if (t < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  const cio = new IntersectionObserver((entries) => {
    entries.forEach((e) => { if (e.isIntersecting) { animateCount(e.target); cio.unobserve(e.target); } });
  }, { threshold: 0.4 });
  document.querySelectorAll('[data-count]').forEach((el) => cio.observe(el));

  window.faqT = function (q) {
    const item = q.parentElement;
    const ans = item.querySelector('.faq-a');
    const open = item.classList.contains('faq-open');
    document.querySelectorAll('.faq-item.faq-open').forEach((i) => {
      i.classList.remove('faq-open');
      const a = i.querySelector('.faq-a');
      if (a) a.style.maxHeight = null;
    });
    if (!open && ans) {
      item.classList.add('faq-open');
      ans.style.maxHeight = ans.scrollHeight + 'px';
    }
  };

  document.querySelectorAll('[data-ba]').forEach((stage) => {
    const before = stage.querySelector('.ba-before');
    const handle = stage.querySelector('.ba-handle');
    if (!before || !handle) return;
    let dragging = false;
    function setPos(clientX) {
      const rect = stage.getBoundingClientRect();
      let pct = ((clientX - rect.left) / rect.width) * 100;
      pct = Math.max(2, Math.min(98, pct));
      before.style.clipPath = 'inset(0 0 0 ' + pct + '%)';
      handle.style.left = pct + '%';
    }
    handle.addEventListener('mousedown', () => { dragging = true; });
    window.addEventListener('mouseup', () => { dragging = false; });
    window.addEventListener('mousemove', (e) => { if (dragging) setPos(e.clientX); });
  });

  const searchBtn = document.querySelector('[data-mes-search]');
  const search = document.getElementById('mes-search');
  if (searchBtn && search) {
    searchBtn.addEventListener('click', () => {
      search.hidden = !search.hidden;
      const input = search.querySelector('input');
      if (!search.hidden && input) input.focus();
    });
    const input = search.querySelector('input');
    const live = document.getElementById('mes-search-live');
    let t;
    if (input && live && window.mesFront) {
      input.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(async () => {
          if (input.value.length < 2) { live.innerHTML = ''; return; }
          const res = await fetch(window.mesFront.root + 'search?q=' + encodeURIComponent(input.value));
          const rows = await res.json();
          live.innerHTML = rows.map((r) => '<a href="' + r.url + '">' + r.title + '</a>').join('');
        }, 220);
      });
    }
  }

  const btn = document.getElementById('fnBtn');
  if (btn) {
    btn.addEventListener('click', () => {
      const svc = document.getElementById('fnSvc');
      const city = document.getElementById('fnCity');
      const res = document.getElementById('fnResult');
      if (!svc || !city || !res) return;
      const svcOpt = svc.options[svc.selectedIndex];
      const cityOpt = city.options[city.selectedIndex];
      document.getElementById('frTitle').textContent = (svcOpt ? svcOpt.text : svc.value) + ' — ' + (cityOpt ? cityOpt.text : city.value);
      document.getElementById('frSub').textContent = cityOpt ? cityOpt.text : city.value;
      const time = document.getElementById('frTime');
      if (time) time.textContent = cityOpt && cityOpt.dataset.time ? cityOpt.dataset.time : '';
      res.hidden = false;
      const svcSlug = svcOpt && svcOpt.dataset.slug;
      const citySlug = cityOpt && cityOpt.dataset.slug;
      if (svcSlug && citySlug && window.mesFront) {
        const base = (window.mesFront.home || '/').replace(/\/$/, '');
        const lang = window.mesFront.lang || 'ar';
        const url = base + '/' + lang + '/services/' + svcSlug + '/' + citySlug + '/';
        let go = document.getElementById('frGo');
        if (!go) {
          go = document.createElement('a');
          go.id = 'frGo';
          go.className = 'btn btn-call';
          res.appendChild(go);
        }
        go.href = url;
        go.textContent = (svcOpt.text || '') + ' — ' + (cityOpt.text || '');
      }
    });
  }

  window.rvMove = function (dir) {
    const track = document.getElementById('rvTrack');
    if (!track) return;
    track.scrollBy({ left: dir * -280, behavior: 'smooth' });
  };
})();
