(() => {
  const cfg = window.mesAdmin || {};
  const headers = { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' };

  async function save(group, body) {
    const res = await fetch(cfg.root + 'settings/' + group, { method: 'POST', headers, body: JSON.stringify(body) });
    return res.json();
  }

  const design = document.getElementById('mes-design-form');
  if (design) {
    design.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(design);
      await save('brand_settings', Object.fromEntries(fd.entries()));
      design.insertAdjacentHTML('beforeend', '<p>' + (cfg.i18n.saved || 'Saved') + '</p>');
    });
  }

  const brand = document.getElementById('mes-brand-form');
  if (brand) {
    brand.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(brand);
      await save('brand_settings', Object.fromEntries(fd.entries()));
    });
  }

  const ai = document.getElementById('mes-ai-form');
  if (ai) {
    ai.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(ai);
      await save('ai_settings', Object.fromEntries(fd.entries()));
    });
  }

  const mig = document.getElementById('mes-run-migration');
  if (mig) {
    mig.addEventListener('click', async () => {
      const res = await fetch(cfg.root + 'migration/run', { method: 'POST', headers });
      const data = await res.json();
      const log = document.getElementById('mes-migration-log');
      if (log) log.textContent = JSON.stringify(data, null, 2);
    });
  }

  const analytics = document.querySelector('[data-mes-analytics]');
  if (analytics) {
    fetch(cfg.root + 'analytics/clicks', { headers })
      .then((r) => r.json())
      .then((data) => {
        analytics.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
      })
      .catch(() => { analytics.textContent = cfg.i18n.error || 'Error'; });
  }

  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      const q = window.prompt('MAHMOUD-ELSAAD');
      if (!q) return;
      fetch(cfg.root + 'search?q=' + encodeURIComponent(q), { headers })
        .then((r) => r.json())
        .then((rows) => {
          if (rows[0] && rows[0].url) window.location.href = rows[0].url;
        });
    }
  });
})();
