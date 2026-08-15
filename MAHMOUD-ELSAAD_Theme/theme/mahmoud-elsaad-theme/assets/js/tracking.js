(() => {
  const cfg = window.mesFront || {};

  function sid() {
    try {
      let id = sessionStorage.getItem('mes_sid');
      if (!id) {
        id = (crypto.randomUUID && crypto.randomUUID()) || String(Date.now());
        sessionStorage.setItem('mes_sid', id);
      }
      return id;
    } catch (e) {
      return String(Date.now());
    }
  }

  function payloadFrom(a) {
    return {
      event_type: a.getAttribute('data-mes-type') === 'whatsapp' ? 'whatsapp' : 'call',
      number: a.getAttribute('data-mes-number') || '',
      placement: a.getAttribute('data-mes-placement') || 'content',
      post_id: cfg.post || 0,
      content_type: cfg.type || '',
      source_url: location.href,
      referrer: document.referrer,
      device: window.innerWidth < 768 ? 'mobile' : 'desktop',
      nonce: cfg.nonce,
      session: sid(),
      utm: location.search.slice(1),
    };
  }

  function send(data) {
    const body = JSON.stringify(data);
    if (cfg.root && navigator.sendBeacon) {
      const blob = new Blob([body], { type: 'application/json' });
      if (navigator.sendBeacon(cfg.root + 'track-click', blob)) return;
    }
    if (cfg.ajax) {
      const fd = new FormData();
      Object.keys(data).forEach((k) => fd.append(k, data[k]));
      fd.append('action', 'mes_track_click');
      if (navigator.sendBeacon) {
        navigator.sendBeacon(cfg.ajax, fd);
        return;
      }
      fetch(cfg.ajax, { method: 'POST', body: fd, keepalive: true });
      return;
    }
    if (cfg.root) {
      fetch(cfg.root + 'track-click', {
        method: 'POST',
        body,
        headers: { 'Content-Type': 'application/json' },
        keepalive: true,
      });
    }
  }

  document.addEventListener(
    'click',
    (e) => {
      const a = e.target.closest('.mes-track-contact');
      if (!a) return;
      send(payloadFrom(a));
    },
    true
  );
})();
