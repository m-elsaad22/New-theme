(() => {
  const root = document.getElementById('mes-fb');
  if (!root) return;

  const cfg = window.mesAdmin || {};
  const headers = { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' };
  const types = JSON.parse(root.dataset.types || '[]');
  let forms = JSON.parse(root.dataset.forms || '[]');
  let current = null;
  let selectedField = null;

  const list = document.getElementById('mes-fb-forms');
  const editor = root.querySelector('.mes-fb-editor');
  const fieldsEl = document.getElementById('mes-fb-fields');
  const settingsEl = document.getElementById('mes-fb-field-settings');
  const titleEl = document.getElementById('mes-fb-title');

  function uid() {
    return 'field_' + Math.random().toString(36).slice(2, 8);
  }

  function toast(ok) {
    const p = document.createElement('p');
    p.className = 'mes-cc-toast';
    p.textContent = ok ? (cfg.i18n.saved || 'Saved') : (cfg.i18n.error || 'Error');
    editor.appendChild(p);
    setTimeout(() => p.remove(), 2500);
  }

  function renderList() {
    list.innerHTML = forms.map((f) => `<li><button type="button" class="mes-fb-open" data-id="${f.id}">${escapeHtml(f.title)}</button></li>`).join('');
    list.querySelectorAll('.mes-fb-open').forEach((btn) => {
      btn.addEventListener('click', () => openForm(Number(btn.dataset.id)));
    });
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function settingsFromUi() {
    return {
      save_lead: document.getElementById('mes-fb-save-lead').checked,
      email: document.getElementById('mes-fb-email').checked,
      notify_email: document.getElementById('mes-fb-notify').value,
      webhook: document.getElementById('mes-fb-webhook').checked,
      webhook_url: document.getElementById('mes-fb-webhook-url').value,
      whatsapp: document.getElementById('mes-fb-wa').checked,
      whatsapp_number: document.getElementById('mes-fb-wa-number').value,
      whatsapp_redirect: document.getElementById('mes-fb-wa-redirect').checked,
      success_message: document.getElementById('mes-fb-success').value,
      error_message: document.getElementById('mes-fb-error').value,
    };
  }

  function settingsToUi(s) {
    s = s || {};
    document.getElementById('mes-fb-save-lead').checked = s.save_lead !== false;
    document.getElementById('mes-fb-email').checked = s.email !== false;
    document.getElementById('mes-fb-notify').value = s.notify_email || '';
    document.getElementById('mes-fb-webhook').checked = !!s.webhook;
    document.getElementById('mes-fb-webhook-url').value = s.webhook_url || '';
    document.getElementById('mes-fb-wa').checked = !!s.whatsapp;
    document.getElementById('mes-fb-wa-number').value = s.whatsapp_number || '';
    document.getElementById('mes-fb-wa-redirect').checked = !!s.whatsapp_redirect;
    document.getElementById('mes-fb-success').value = s.success_message || '';
    document.getElementById('mes-fb-error').value = s.error_message || '';
  }

  function renderFields() {
    if (!current) return;
    fieldsEl.innerHTML = current.fields.map((f, i) => `
      <li draggable="true" data-index="${i}" class="${selectedField === i ? 'is-on' : ''}">
        <span class="mes-fb-handle">☰</span>
        <strong>${escapeHtml(f.label || f.id)}</strong>
        <em>${escapeHtml(f.type)}</em>
        <button type="button" data-remove="${i}">×</button>
      </li>`).join('');

    fieldsEl.querySelectorAll('li').forEach((li) => {
      li.addEventListener('click', (e) => {
        if (e.target.dataset.remove != null) return;
        selectedField = Number(li.dataset.index);
        renderFields();
        renderFieldSettings();
      });
      li.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('text/plain', li.dataset.index);
      });
      li.addEventListener('dragover', (e) => e.preventDefault());
      li.addEventListener('drop', (e) => {
        e.preventDefault();
        const from = Number(e.dataTransfer.getData('text/plain'));
        const to = Number(li.dataset.index);
        const moved = current.fields.splice(from, 1)[0];
        current.fields.splice(to, 0, moved);
        selectedField = to;
        renderFields();
        renderFieldSettings();
      });
    });
    fieldsEl.querySelectorAll('[data-remove]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        current.fields.splice(Number(btn.dataset.remove), 1);
        selectedField = null;
        renderFields();
        settingsEl.innerHTML = '';
      });
    });
  }

  function renderFieldSettings() {
    const f = current && current.fields[selectedField];
    if (!f) {
      settingsEl.innerHTML = '';
      return;
    }
    f.validation = f.validation || {};
    f.conditional = f.conditional || {};
    settingsEl.innerHTML = `
      <fieldset class="mes-visual-group">
        <legend>Field settings</legend>
        <label>Label<input type="text" data-k="label" value="${escapeHtml(f.label || '')}" /></label>
        <label>ID<input type="text" data-k="id" value="${escapeHtml(f.id || '')}" /></label>
        <label>Type<select data-k="type">${types.map((t) => `<option${t === f.type ? ' selected' : ''}>${t}</option>`).join('')}</select></label>
        <label>Placeholder<input type="text" data-k="placeholder" value="${escapeHtml(f.placeholder || '')}" /></label>
        <label>Help<input type="text" data-k="help" value="${escapeHtml(f.help || '')}" /></label>
        <label>Width<select data-k="width"><option value="full"${f.width !== 'half' ? ' selected' : ''}>full</option><option value="half"${f.width === 'half' ? ' selected' : ''}>half</option></select></label>
        <label class="mes-cc-check"><input type="checkbox" data-k="required"${f.required ? ' checked' : ''} /> Required</label>
        <label>Options (one per line)<textarea data-k="options">${escapeHtml((f.options || []).join('\n'))}</textarea></label>
        <label>Min<input type="text" data-v="min" value="${escapeHtml(f.validation.min || '')}" /></label>
        <label>Max<input type="text" data-v="max" value="${escapeHtml(f.validation.max || '')}" /></label>
        <label>Min length<input type="text" data-v="minLength" value="${escapeHtml(f.validation.minLength || '')}" /></label>
        <label>Max length<input type="text" data-v="maxLength" value="${escapeHtml(f.validation.maxLength || '')}" /></label>
        <label>Pattern<input type="text" data-v="pattern" value="${escapeHtml(f.validation.pattern || '')}" /></label>
        <label>Show if field<input type="text" data-c="field" value="${escapeHtml(f.conditional.field || '')}" /></label>
        <label>Operator<select data-c="op">
          <option value="equals"${f.conditional.op === 'equals' ? ' selected' : ''}>equals</option>
          <option value="not_equals"${f.conditional.op === 'not_equals' ? ' selected' : ''}>not equals</option>
          <option value="contains"${f.conditional.op === 'contains' ? ' selected' : ''}>contains</option>
        </select></label>
        <label>Value<input type="text" data-c="value" value="${escapeHtml(f.conditional.value || '')}" /></label>
      </fieldset>`;
    settingsEl.querySelectorAll('[data-k]').forEach((el) => {
      el.addEventListener('input', () => {
        if (el.type === 'checkbox') f[el.dataset.k] = el.checked;
        else if (el.dataset.k === 'options') f.options = el.value.split('\n').map((s) => s.trim()).filter(Boolean);
        else f[el.dataset.k] = el.value;
        renderFields();
      });
    });
    settingsEl.querySelectorAll('[data-v]').forEach((el) => {
      el.addEventListener('input', () => { f.validation[el.dataset.v] = el.value; });
    });
    settingsEl.querySelectorAll('[data-c]').forEach((el) => {
      el.addEventListener('input', () => { f.conditional[el.dataset.c] = el.value; });
    });
  }

  function openForm(id) {
    current = forms.find((f) => Number(f.id) === Number(id));
    if (!current) return;
    current.fields = current.fields || [];
    editor.hidden = false;
    titleEl.value = current.title;
    settingsToUi(current.settings);
    selectedField = current.fields.length ? 0 : null;
    renderFields();
    renderFieldSettings();
  }

  function addField(type) {
    if (!current) return;
    current.fields.push({
      id: uid(),
      type,
      label: type,
      placeholder: '',
      help: '',
      required: false,
      width: 'full',
      options: ['select', 'radio', 'checkbox'].includes(type) ? ['Option 1', 'Option 2'] : [],
      validation: {},
      conditional: {},
    });
    selectedField = current.fields.length - 1;
    renderFields();
    renderFieldSettings();
  }

  document.getElementById('mes-fb-new').addEventListener('click', async () => {
    const res = await fetch(cfg.root + 'forms', {
      method: 'POST',
      headers,
      body: JSON.stringify({ title: 'New form', type: 'custom' }),
    });
    const data = await res.json();
    if (data.id) {
      forms.push(data);
      renderList();
      openForm(data.id);
    }
  });

  document.querySelectorAll('.mes-fb-add').forEach((btn) => {
    btn.addEventListener('click', () => addField(btn.dataset.type));
    btn.addEventListener('dragstart', (e) => {
      e.dataTransfer.setData('text/plain', 'type:' + btn.dataset.type);
    });
  });

  fieldsEl.addEventListener('dragover', (e) => e.preventDefault());
  fieldsEl.addEventListener('drop', (e) => {
    const raw = e.dataTransfer.getData('text/plain');
    if (raw.indexOf('type:') === 0) {
      e.preventDefault();
      addField(raw.slice(5));
    }
  });

  document.getElementById('mes-fb-save').addEventListener('click', async () => {
    if (!current) return;
    current.title = titleEl.value;
    current.settings = Object.assign({}, current.settings || {}, settingsFromUi());
    const res = await fetch(cfg.root + 'forms/' + current.id, {
      method: 'POST',
      headers,
      body: JSON.stringify(current),
    });
    const data = await res.json();
    if (data.id) {
      forms = forms.map((f) => (Number(f.id) === Number(data.id) ? data : f));
      current = data;
      renderList();
      toast(true);
    } else toast(false);
  });

  document.getElementById('mes-fb-dup').addEventListener('click', async () => {
    if (!current) return;
    const res = await fetch(cfg.root + 'forms/' + current.id + '/duplicate', { method: 'POST', headers });
    const data = await res.json();
    if (data.id) {
      forms.push(data);
      renderList();
      openForm(data.id);
    }
  });

  document.getElementById('mes-fb-del').addEventListener('click', async () => {
    if (!current || !window.confirm('Delete this form?')) return;
    const res = await fetch(cfg.root + 'forms/' + current.id, { method: 'DELETE', headers });
    const data = await res.json();
    if (data.ok) {
      forms = forms.filter((f) => Number(f.id) !== Number(current.id));
      current = null;
      editor.hidden = true;
      renderList();
    }
  });

  renderList();
})();
