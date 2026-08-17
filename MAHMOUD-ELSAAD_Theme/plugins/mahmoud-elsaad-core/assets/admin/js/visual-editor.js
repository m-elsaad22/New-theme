(() => {
  const root = document.getElementById('mes-visual');
  if (!root) return;

  const cfg = window.mesAdmin || {};
  const headers = { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' };
  const form = document.getElementById('mes-visual-form');
  const currentEl = document.getElementById('mes-visual-current');
  const frame = document.getElementById('mes-visual-frame');

  let tree = null;
  let schema = {};
  let selected = 'global';
  let breakpoint = 'desktop';

  function findNode(node, id) {
    if (!node) return null;
    if (node.id === id) return node;
    for (const child of node.children || []) {
      const hit = findNode(child, id);
      if (hit) return hit;
    }
    return null;
  }

  function decls(props) {
    let out = '';
    Object.entries(props || {}).forEach(([key, value]) => {
      if (!value) return;
      const map = schema[key];
      if (!map) return;
      if (key === 'hide') {
        out += 'display:none;';
        return;
      }
      if (key === 'hover-transform') {
        out += `--mes-hover-transform:${value};transition:transform var(--mes-duration,200ms) var(--mes-ease,ease);`;
        return;
      }
      let cssVal = value;
      if (key === 'animation' && value.indexOf(' ') === -1) {
        const named = { 'fade-in': 'mes-fade-in 600ms ease', 'slide-up': 'mes-slide-up 600ms ease' };
        cssVal = named[value] || value;
      }
      if (key === 'blur' && value.indexOf('blur(') === -1) cssVal = `blur(${value})`;
      out += `${map.css}:${cssVal};`;
    });
    return out;
  }

  function compile(node) {
    const parts = [
      '/* preview */',
      '@keyframes mes-fade-in{from{opacity:0}to{opacity:1}}',
      '@keyframes mes-slide-up{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}',
      '[data-mes-node]:hover{transform:var(--mes-hover-transform,none)}',
    ];
    const walk = (n) => {
      if (!n || !n.id) return;
      const sel = `[data-mes-node="${n.id}"]`;
      const own = n.props || {};
      const d = decls(own.desktop);
      const t = decls(own.tablet);
      const m = decls(own.mobile);
      if (d) parts.push(`${sel}{${d}}`);
      if (t) parts.push(`@media (max-width:1024px){${sel}{${t}}}`);
      if (m) parts.push(`@media (max-width:640px){${sel}{${m}}}`);
      (n.children || []).forEach(walk);
    };
    walk(node);
    parts.push('@media (prefers-reduced-motion: reduce){[data-mes-node]{animation:none!important;transition:none!important}}');
    return parts.join('\n');
  }

  function postCss() {
    if (!frame || !frame.contentWindow) return;
    frame.contentWindow.postMessage(
      { type: 'mes-visual-css', css: compile(tree) },
      window.location.origin
    );
    frame.contentWindow.postMessage(
      { type: 'mes-visual-select', node: selected },
      window.location.origin
    );
  }

  function fillForm() {
    const node = findNode(tree, selected);
    if (currentEl) currentEl.textContent = node ? `${node.type} · ${node.label} · ${breakpoint}` : '';
    if (!form || !node) return;
    const props = ((node.props || {})[breakpoint]) || {};
    form.querySelectorAll('input, select').forEach((el) => {
      if (!el.name) return;
      el.value = props[el.name] || '';
    });
    document.querySelectorAll('.mes-visual-node').forEach((btn) => {
      btn.setAttribute('aria-current', btn.dataset.node === selected ? 'true' : 'false');
    });
    document.querySelectorAll('.mes-bp').forEach((btn) => {
      btn.setAttribute('aria-pressed', btn.dataset.bp === breakpoint ? 'true' : 'false');
    });
  }

  function writeFormToTree() {
    const node = findNode(tree, selected);
    if (!node || !form) return;
    if (!node.props) node.props = { desktop: {}, tablet: {}, mobile: {} };
    if (!node.props[breakpoint]) node.props[breakpoint] = {};
    const next = {};
    form.querySelectorAll('input, select').forEach((el) => {
      if (el.name && el.value) next[el.name] = el.value;
    });
    node.props[breakpoint] = next;
  }

  async function load() {
    const res = await fetch(cfg.root + 'visual/tree', { headers });
    const data = await res.json();
    tree = data.tree;
    schema = data.schema || {};
    fillForm();
    postCss();
  }

  document.querySelectorAll('.mes-visual-node').forEach((btn) => {
    btn.addEventListener('click', () => {
      writeFormToTree();
      selected = btn.dataset.node;
      fillForm();
      postCss();
    });
  });

  document.querySelectorAll('.mes-bp').forEach((btn) => {
    btn.addEventListener('click', () => {
      writeFormToTree();
      breakpoint = btn.dataset.bp;
      fillForm();
      postCss();
    });
  });

  if (form) {
    form.addEventListener('input', () => {
      writeFormToTree();
      postCss();
    });
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      writeFormToTree();
      const res = await fetch(cfg.root + 'visual/tree', {
        method: 'POST',
        headers,
        body: JSON.stringify({ tree }),
      });
      const data = await res.json();
      const p = document.createElement('p');
      p.className = 'mes-cc-toast';
      p.textContent = data.ok ? (cfg.i18n.saved || 'Saved') : (cfg.i18n.error || 'Error');
      form.appendChild(p);
      setTimeout(() => p.remove(), 2500);
    });
  }

  const clear = document.getElementById('mes-visual-clear');
  if (clear) {
    clear.addEventListener('click', () => {
      const node = findNode(tree, selected);
      if (!node) return;
      if (!node.props) node.props = { desktop: {}, tablet: {}, mobile: {} };
      node.props[breakpoint] = {};
      fillForm();
      postCss();
    });
  }

  window.addEventListener('message', (e) => {
    if (e.origin !== window.location.origin) return;
    const data = e.data || {};
    if (data.type === 'mes-visual-ready') postCss();
    if (data.type === 'mes-visual-click' && data.node) {
      writeFormToTree();
      selected = data.node;
      fillForm();
      postCss();
    }
  });

  if (frame) frame.addEventListener('load', postCss);
  load();
})();
