(() => {
  const cfg = window.mesAdmin || {};
  const headers = { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' };

  function setPath(obj, path, value) {
    const keys = path.replace(/\]/g, '').split('[');
    let cur = obj;
    keys.forEach((k, i) => {
      if (i === keys.length - 1) cur[k] = value;
      else {
        if (typeof cur[k] !== 'object' || cur[k] === null) cur[k] = {};
        cur = cur[k];
      }
    });
  }

  function formToObject(form) {
    const out = {};
    form.querySelectorAll('input, select, textarea').forEach((el) => {
      if (!el.name) return;
      if (el.type === 'checkbox') {
        setPath(out, el.name, el.checked);
        return;
      }
      setPath(out, el.name, el.value);
    });
    return out;
  }

  async function save(group, body) {
    const res = await fetch(cfg.root + 'settings/' + group, {
      method: 'POST',
      headers,
      body: JSON.stringify(body),
    });
    return res.json();
  }

  function toast(el, ok) {
    const p = document.createElement('p');
    p.className = 'mes-cc-toast';
    p.textContent = ok ? (cfg.i18n.saved || 'Saved') : (cfg.i18n.error || 'Error');
    el.appendChild(p);
    setTimeout(() => p.remove(), 2500);
  }

  document.querySelectorAll('form[data-group]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const group = form.getAttribute('data-group');
      try {
        const data = await save(group, formToObject(form));
        toast(form, !data.code);
      } catch (err) {
        toast(form, false);
      }
    });
  });

  const mig = document.getElementById('mes-run-migration');
  if (mig) {
    mig.addEventListener('click', async () => {
      const res = await fetch(cfg.root + 'migration/run', { method: 'POST', headers });
      const data = await res.json();
      const log = document.getElementById('mes-migration-log');
      if (log) log.textContent = JSON.stringify(data, null, 2);
    });
  }

  const seed = document.getElementById('mes-seed-demo');
  if (seed) {
    seed.addEventListener('click', async () => {
      const res = await fetch(cfg.root + 'demo/seed', { method: 'POST', headers });
      const data = await res.json();
      const log = document.getElementById('mes-demo-log');
      if (log) log.textContent = JSON.stringify(data, null, 2);
    });
  }

  const pair = document.getElementById('mes-pair-form');
  if (pair) {
    pair.addEventListener('submit', async (e) => {
      e.preventDefault();
      const res = await fetch(cfg.root + 'service-city', {
        method: 'POST',
        headers,
        body: JSON.stringify(formToObject(pair)),
      });
      const data = await res.json();
      toast(pair, !data.code);
      if (!data.code) window.location.reload();
    });
  }

  document.querySelectorAll('.mes-restore').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (!window.confirm('Restore this revision?')) return;
      const res = await fetch(cfg.root + 'revisions/' + btn.dataset.id + '/restore', { method: 'POST', headers });
      const data = await res.json();
      if (!data.code) window.location.reload();
    });
  });

  const cmd = document.querySelector('[data-mes-cmd]');
  if (cmd) {
    const run = async () => {
      const q = window.prompt('MAHMOUD-ELSAAD');
      if (!q) return;
      const res = await fetch(cfg.root + 'search?q=' + encodeURIComponent(q), { headers });
      const rows = await res.json();
      if (rows[0] && rows[0].url) window.location.href = rows[0].url;
    };
    cmd.addEventListener('click', run);
    document.addEventListener('keydown', (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        run();
      }
    });
  }
})();
