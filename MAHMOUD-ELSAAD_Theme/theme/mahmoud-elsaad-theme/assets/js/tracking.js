(() => {
  const cfg = window.mesFront || {};
  document.addEventListener('click', (e) => {
    const a = e.target.closest('.mes-track-contact');
    if (!a || !cfg.root) return;
    const payload = {
      event_type: a.getAttribute('data-mes-type') === 'whatsapp' ? 'whatsapp' : 'call',
      number: a.getAttribute('data-mes-number') || '',
      placement: a.getAttribute('data-mes-placement') || 'content',
      post_id: cfg.post || 0,
      content_type: cfg.type || '',
      source_url: location.href,
      referrer: document.referrer,
      device: window.innerWidth < 768 ? 'mobile' : 'desktop',
      nonce: cfg.nonce,
      session: sessionStorage.getItem('mes_sid') || (sessionStorage.setItem('mes_sid', crypto.randomUUID()), sessionStorage.getItem('mes_sid')),
    };
    const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
    if (navigator.sendBeacon) {
      navigator.sendBeacon(cfg.root + 'track-click', blob);
    } else {
      fetch(cfg.root + 'track-click', { method: 'POST', body: JSON.stringify(payload), headers: { 'Content-Type': 'application/json' }, keepalive: true });
    }
  }, true);
})();
