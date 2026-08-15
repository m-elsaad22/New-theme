(function (wp) {
  if (!wp || !wp.plugins || !window.mesAI) return;
  const el = wp.element.createElement;
  const PluginSidebar = wp.editPost.PluginSidebar;
  function Panel() {
    const [text, setText] = wp.element.useState('');
    const run = async (task) => {
      const content = wp.data.select('core/editor').getEditedPostAttribute('content') || '';
      const res = await fetch(window.mesAI.root + 'ai/complete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.mesAI.nonce },
        body: JSON.stringify({ task: task, prompt: content || wp.data.select('core/editor').getEditedPostAttribute('title') }),
      });
      const json = await res.json();
      setText(json.text || json.fallback || json.message || '');
    };
    return el('div', { className: 'mes-ai-panel', style: { padding: '12px' } },
      el('button', { className: 'button', onClick: () => run('title') }, 'Title'),
      el('button', { className: 'button', onClick: () => run('meta') }, 'Meta'),
      el('button', { className: 'button', onClick: () => run('faq') }, 'FAQ'),
      el('textarea', { readOnly: true, value: text, style: { width: '100%', minHeight: '160px', marginTop: '12px' } })
    );
  }
  wp.plugins.registerPlugin('mes-ai-assistant', {
    render: function () {
      return el(PluginSidebar, { name: 'mes-ai-assistant', title: 'AI Assistant' }, el(Panel));
    },
  });
})(window.wp);
