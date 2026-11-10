(() => {
  const staticCatalog = document.currentScript.dataset.catalog;
  const search = document.querySelector('#benefits-search');
  const status = document.querySelector('#benefits-status');
  const results = document.querySelector('#benefits-results');
  const simplify = (value) => value.normalize('NFD').replace(/[^a-z\d]/gi, '').toLowerCase();

  function element(tag, className, content) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (content) node.textContent = content;
    return node;
  }

  function card(platform) {
    const item = element('li', 'benefit-card');
    const tile = element('div', 'benefit-logo');
    const logo = element('img');
    logo.alt = '';
    logo.src = platform.logo;
    logo.width = 168;
    logo.height = 54;
    logo.addEventListener('error', () => { logo.replaceWith(element('span', '', platform.name)); });
    tile.append(logo);
    const actions = element('div', 'benefit-actions');
    const directions = element('a', 'button button--outline button--small', 'Como chegar');
    directions.href = '#visita';
    actions.append(directions);
    if (platform.url?.startsWith('https://')) {
      const provider = element('a', 'button button--outline button--small', 'Ver no ' + platform.name);
      provider.href = platform.url;
      provider.target = '_blank';
      provider.rel = 'noopener noreferrer';
      actions.append(provider);
    }
    item.append(tile, element('h3', '', platform.name),
      element('p', '', 'Aceito na OzzyBox · Pouso Alegre, MG'), actions);
    return item;
  }

  async function read(url) {
    const response = await fetch(url, { cache: 'no-store' });
    if (!response.ok) throw new Error(String(response.status));
    return response.json();
  }

  async function load() {
    status.textContent = 'Carregando convênios aceitos…';
    try {
      const platforms = await (staticCatalog ? read(staticCatalog) : read('convenios.php').catch((error) => {
        if (error.message !== '404') throw error;
        return read('beneficios.json');
      }));
      const accepted = platforms.filter((platform) => platform.accepted);
      function render() {
        const query = simplify(search.value.trim());
        const matches = query ? accepted.filter(({ name, aliases }) =>
          [name, ...aliases.split(',')].some((label) => simplify(label).includes(query))
        ) : accepted;
        status.textContent = matches.length === 1 ? '1 benefício encontrado.'
          : matches.length > 1 ? matches.length + ' benefícios encontrados.'
            : query ? 'Nenhum convênio aceito corresponde à sua busca.'
              : 'Nenhum convênio aceito está listado no momento.';
        results.replaceChildren(...matches.map(card));
      }
      search.closest('.benefits-filter').hidden = false;
      search.addEventListener('input', render);
      render();
    } catch {
      status.textContent = 'Não foi possível carregar os convênios agora. Consulte a equipe pelo WhatsApp.';
    }
  }

  load();
})();
