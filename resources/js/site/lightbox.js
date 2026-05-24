const html = (urls, index) => `
  <div class="lightbox" data-lightbox-overlay>
    <button type="button" class="close" data-lightbox-close aria-label="Close">×</button>
    <button type="button" class="nav-l" data-lightbox-prev aria-label="Previous">‹</button>
    <button type="button" class="nav-r" data-lightbox-next aria-label="Next">›</button>
    <img src="${urls[index]}" alt="">
    <div class="counter">${String(index + 1).padStart(2, '0')} / ${String(urls.length).padStart(2, '0')}</div>
  </div>`;

let activeOverlay = null;
let activeUrls = [];
let activeIndex = 0;

function open(urls, index) {
  activeUrls = urls;
  activeIndex = index;
  document.body.insertAdjacentHTML('beforeend', html(urls, index));
  activeOverlay = document.querySelector('[data-lightbox-overlay]');
  document.addEventListener('keydown', onKey);
}

function close() {
  if (!activeOverlay) return;
  document.removeEventListener('keydown', onKey);
  activeOverlay.remove();
  activeOverlay = null;
}

function go(delta) {
  if (!activeOverlay || !activeUrls.length) return;
  activeIndex = (activeIndex + delta + activeUrls.length) % activeUrls.length;
  activeOverlay.querySelector('img').src = activeUrls[activeIndex];
  activeOverlay.querySelector('.counter').textContent =
    `${String(activeIndex + 1).padStart(2, '0')} / ${String(activeUrls.length).padStart(2, '0')}`;
}

function onKey(e) {
  if (e.key === 'Escape') close();
  if (e.key === 'ArrowLeft') go(-1);
  if (e.key === 'ArrowRight') go(1);
}

document.addEventListener('click', (e) => {
  const trigger = e.target.closest('[data-lightbox-trigger]');
  if (trigger) {
    e.preventDefault();
    let urls = [];
    try { urls = JSON.parse(trigger.dataset.lightboxImages || '[]'); } catch { return; }
    if (! urls.length) return;
    open(urls, parseInt(trigger.dataset.lightboxIndex || '0', 10));
    return;
  }
  if (activeOverlay) {
    if (e.target.closest('[data-lightbox-close]')) close();
    else if (e.target.closest('[data-lightbox-prev]')) go(-1);
    else if (e.target.closest('[data-lightbox-next]')) go(1);
    else if (e.target === activeOverlay) close();
  }
});

document.addEventListener('click', (e) => {
  const thumb = e.target.closest('[data-thumb-index]');
  if (! thumb) return;
  const trigger = thumb.closest('.gallery')?.querySelector('[data-lightbox-trigger]');
  if (! trigger) return;
  let urls = [];
  try { urls = JSON.parse(trigger.dataset.lightboxImages || '[]'); } catch { return; }
  const i = parseInt(thumb.dataset.thumbIndex, 10);
  if (Number.isNaN(i) || ! urls[i]) return;
  trigger.dataset.lightboxIndex = String(i);
  trigger.querySelector('[data-main-image]').src = urls[i];
  trigger.closest('.gallery').querySelectorAll('.thumbs button').forEach((b, j) =>
    b.classList.toggle('active', j === i));
});
