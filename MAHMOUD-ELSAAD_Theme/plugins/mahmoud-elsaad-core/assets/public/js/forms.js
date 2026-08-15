(() => {
  function valueOf(form, name) {
    const el = form.querySelector('[name="mes_field[' + name + ']"]') || form.querySelector('[name="mes_field[' + name + '][]"]');
    if (!el) return '';
    if (el.type === 'checkbox' || el.type === 'radio') {
      const checked = form.querySelectorAll('[name="' + el.name + '"]:checked');
      return Array.from(checked).map((n) => n.value).join(',');
    }
    return el.value || '';
  }

  function match(op, actual, expected) {
    if (op === 'not_equals') return actual !== expected;
    if (op === 'contains') return actual.indexOf(expected) !== -1;
    return actual === expected;
  }

  function apply(form) {
    form.querySelectorAll('[data-mes-cond-field]').forEach((wrap) => {
      const field = wrap.getAttribute('data-mes-cond-field');
      const op = wrap.getAttribute('data-mes-cond-op') || 'equals';
      const expected = wrap.getAttribute('data-mes-cond-value') || '';
      const show = match(op, valueOf(form, field), expected);
      wrap.hidden = !show;
      wrap.querySelectorAll('input, select, textarea').forEach((el) => {
        el.disabled = !show;
      });
    });
  }

  document.querySelectorAll('form.mes-form').forEach((form) => {
    apply(form);
    form.addEventListener('input', () => apply(form));
    form.addEventListener('change', () => apply(form));
  });
})();
