(() => {
  const galleryImgs = Array.from(document.querySelectorAll('.gallery img[data-full]'));
  if (!galleryImgs.length) return;

  let idx = 0;
  const box = document.createElement('div');
  box.className = 'lightbox';
  box.innerHTML = `
    <button class="close" aria-label="Kapat">x</button>
    <button class="prev" aria-label="Önceki"><</button>
    <img alt="">
    <button class="next" aria-label="Sonraki">></button>
  `;
  document.body.appendChild(box);

  const img = box.querySelector('img');
  const open = (i) => {
    idx = i;
    img.src = galleryImgs[idx].dataset.full;
    box.classList.add('open');
  };
  const render = () => {
    img.src = galleryImgs[idx].dataset.full;
  };

  galleryImgs.forEach((el, i) => el.addEventListener('click', () => open(i)));

  box.querySelector('.close').addEventListener('click', () => box.classList.remove('open'));
  box.querySelector('.prev').addEventListener('click', (e) => {
    e.stopPropagation();
    idx = (idx - 1 + galleryImgs.length) % galleryImgs.length;
    render();
  });
  box.querySelector('.next').addEventListener('click', (e) => {
    e.stopPropagation();
    idx = (idx + 1) % galleryImgs.length;
    render();
  });

  box.addEventListener('click', (e) => {
    if (e.target === box) box.classList.remove('open');
  });

  document.addEventListener('keydown', (e) => {
    if (!box.classList.contains('open')) return;
    if (e.key === 'Escape') box.classList.remove('open');
    if (e.key === 'ArrowLeft') {
      idx = (idx - 1 + galleryImgs.length) % galleryImgs.length;
      render();
    }
    if (e.key === 'ArrowRight') {
      idx = (idx + 1) % galleryImgs.length;
      render();
    }
  });
})();
