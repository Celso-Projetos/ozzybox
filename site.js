/**
 * OzzyBox V2 — Interatividade e Melhorias Progressivas
 * - Efeito de aproximação suave do mascote (Nopal push-in)
 * - Compartilhamento nativo de localização e fallback para cópia
 * - Respeito integral a prefers-reduced-motion e navegação por teclado
 */

document.addEventListener('DOMContentLoaded', () => {
  // ---------------------------------------------------------------------------
  // 1. Aproximação Suave do Mascote (Nopal Hero Push-In)
  // ---------------------------------------------------------------------------
  const heroSection = document.querySelector('.hero');
  const heroMascotImg = document.querySelector('.hero-art img');
  const reducedMotionMedia = window.matchMedia('(prefers-reduced-motion: reduce)');
  let heroAnimationId = 0;

  function updateHeroPushIn() {
    heroAnimationId = 0;

    if (!heroSection || !heroMascotImg) return;

    const bounds = heroSection.getBoundingClientRect();
    document.body.classList.toggle('show-mobile-actions', bounds.bottom <= 0);

    if (reducedMotionMedia.matches) {
      heroMascotImg.style.removeProperty('--hero-zoom');
      return;
    }

    const heroHeight = bounds.height || 1;

    // Progresso relativo à rolagem da seção hero (de 0 no topo a 1 quando sai de tela)
    const scrollProgress = Math.max(0, Math.min(1, -bounds.top / heroHeight));

    // Aproximação suave de 1.000 até 1.185 (push-in cinematográfico)
    const zoomLevel = (1 + scrollProgress * 0.185).toFixed(3);
    heroMascotImg.style.setProperty('--hero-zoom', zoomLevel);
  }

  function scheduleHeroUpdate() {
    if (!heroAnimationId) {
      heroAnimationId = window.requestAnimationFrame(updateHeroPushIn);
    }
  }

  if (heroSection && heroMascotImg) {
    window.addEventListener('scroll', scheduleHeroUpdate, { passive: true });
    window.addEventListener('resize', scheduleHeroUpdate);
    reducedMotionMedia.addEventListener('change', scheduleHeroUpdate);
    scheduleHeroUpdate();
  }

  // ---------------------------------------------------------------------------
  // 2. Compartilhamento e Cópia da Localização (Acessibilidade e Fallbacks)
  // ---------------------------------------------------------------------------
  const btnShare = document.getElementById('btn-share');
  const btnCopy = document.getElementById('btn-copy');
  const shareFeedback = document.getElementById('share-feedback');
  const shareFallback = document.getElementById('share-fallback');
  const shareUrlInput = document.getElementById('share-url-input');

  const locationUrl = shareUrlInput ? shareUrlInput.value : 'https://www.google.com/maps/search/?api=1&query=Centro%20de%20Treinamento%20Ozzy%20Box%2C%20Rua%20Nazareto%2C%20145%2C%20Bairro%20de%20F%C3%A1tima%2C%20Pouso%20Alegre%20-%20MG&query_place_id=ChIJD-mU_TrHy5QRb8AGiYAL5Ig';
  const shareTitle = 'Centro de Treinamento OzzyBox — Pouso Alegre';
  const shareText = 'OzzyBox: Rua Nazareto, 145, Bairro de Fátima, Pouso Alegre–MG (Treino Híbrido e Cross Training). Veja a localização no mapa:';

  // Habilitar botão de compartilhamento nativo em navegadores compatíveis (ex.: mobile)
  if (btnShare && navigator.share) {
    btnShare.hidden = false;

    btnShare.addEventListener('click', async () => {
      if (shareFeedback) shareFeedback.textContent = '';
      if (shareFallback) shareFallback.hidden = true;

      try {
        await navigator.share({
          title: shareTitle,
          text: shareText,
          url: locationUrl,
        });
      } catch (err) {
        // Se o usuário simplesmente fechou a janela de compartilhamento, não exibe erro
        if (err.name === 'AbortError') return;

        // Se falhou, recorre à cópia
        attemptClipboardCopy();
      }
    });
  }

  async function attemptClipboardCopy() {
    if (shareFeedback) shareFeedback.textContent = '';

    try {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(locationUrl);
        if (shareFeedback) {
          shareFeedback.textContent = '✓ Link da localização copiado para a área de transferência!';
        }
        if (shareFallback) shareFallback.hidden = true;
      } else {
        throw new Error('Clipboard API indisponível');
      }
    } catch {
      // Exibe fallback visível e foca no campo para seleção manual
      if (shareFeedback) {
        shareFeedback.textContent = 'Copie o link no campo abaixo:';
      }
      if (shareFallback) {
        shareFallback.hidden = false;
        if (shareUrlInput) {
          shareUrlInput.focus();
          shareUrlInput.select();
        }
      }
    }
  }

  if (btnCopy) {
    btnCopy.hidden = false;
    btnCopy.addEventListener('click', attemptClipboardCopy);
  }

  const mobileMenu = document.querySelector('.mobile-menu');
  mobileMenu.addEventListener('click', (event) => {
    if (event.target.closest('a')) mobileMenu.open = false;
  });
  mobileMenu.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !mobileMenu.open) return;
    mobileMenu.open = false;
    mobileMenu.querySelector('summary').focus();
  });
  mobileMenu.closest('.header').classList.add('has-mobile-menu');
});
