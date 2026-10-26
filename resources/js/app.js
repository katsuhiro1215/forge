/* Shared, dependency-free presentation behavior. No payment, tracking or storage. */
(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');

  const loader = $('.loader');
  let loaderTimer;
  function finishLoading() { loader?.classList.add('is-finished'); }
  if (loader) {
    loader.addEventListener('animationend', event => { if(event.target === loader) finishLoading(); });
    loaderTimer = setTimeout(finishLoading, 2600);
    // Keyboard interaction skips the decorative opening immediately.
    document.addEventListener('keydown', finishLoading, { once: true });
    $('.replay-loader')?.addEventListener('click', () => {
      if (reducedMotion.matches) return;
      document.documentElement.classList.remove('no-opening');
      clearTimeout(loaderTimer);
      loader.style.animation = 'none';
      loader.classList.remove('is-finished');
      void loader.offsetWidth;
      loader.style.animation = '';
      // Restart the child animations together with the curtain.
      $$('.loader-inner, .loader-inner *', loader).forEach(el => {
        el.getAnimations().forEach(animation => { animation.currentTime = 0; animation.play(); });
      });
      loaderTimer = setTimeout(finishLoading, 2600);
    });
  }

  const menuButton = $('.menu-toggle');
  const menu = $('#site-nav');
  function setMenu(open) {
    menu.classList.toggle('is-open', open);
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? 'メニューを閉じる' : 'メニューを開く');
  }
  menuButton?.addEventListener('click', () => setMenu(menuButton.getAttribute('aria-expanded') !== 'true'));
  $$('a', menu).forEach(link => link.addEventListener('click', () => setMenu(false)));
  document.addEventListener('click', event => { if(!event.target.closest('.site-header')) setMenu(false); });
  document.addEventListener('keydown', event => { if(event.key === 'Escape' && menu.classList.contains('is-open')) { setMenu(false); menuButton.focus(); } });
  matchMedia('(min-width:901px)').addEventListener('change', event => { if(event.matches) setMenu(false); });

  if ('IntersectionObserver' in window && !reducedMotion.matches) {
    document.documentElement.classList.add('has-motion');
    const reveal = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('is-visible'); reveal.unobserve(entry.target); }
    }), {threshold:.07});
    $$('.reveal').forEach(el => reveal.observe(el));
  }

  const tabs = $$('[role="tab"]');
  function activateTab(tab) {
    tabs.forEach(item => {
      const selected = item === tab;
      item.setAttribute('aria-selected', String(selected));
      item.tabIndex = selected ? 0 : -1;
      document.getElementById(item.getAttribute('aria-controls')).hidden = !selected;
    });
  }
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab));
    tab.addEventListener('keydown', event => {
      let next;
      if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
      if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length];
      if (event.key === 'Home') next = tabs[0];
      if (event.key === 'End') next = tabs[tabs.length - 1];
      if (next) { event.preventDefault(); activateTab(next); next.focus(); }
    });
  });
  $$('.swatch').forEach(button => button.addEventListener('click', () => {
    $$('.swatch').forEach(swatch => { const active = swatch === button; swatch.classList.toggle('active', active); swatch.setAttribute('aria-pressed', String(active)); });
    $('.palette-preview').style.setProperty('--preview-color', button.dataset.color);
  }));

  const productLinks = $$('.product-nav-links a');
  if(productLinks.length && 'IntersectionObserver' in window) {
    const sections = productLinks.map(link => document.querySelector(link.getAttribute('href'))).filter(Boolean);
    const activeSection = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        productLinks.forEach(link => {
          const active = link.hash === '#' + entry.target.id;
          link.classList.toggle('active', active);
          if(active) link.setAttribute('aria-current','location'); else link.removeAttribute('aria-current');
        });
      });
    }, {rootMargin:'-15% 0px -65% 0px', threshold:0});
    sections.forEach(section => activeSection.observe(section));
  }

  const dialog = $('#purchase-dialog');
  if(dialog) {
    let trigger;
    function closeDialog() { dialog.close(); }
    $$('.purchase-trigger').forEach(button => button.addEventListener('click', () => {
      trigger = button;
      document.body.classList.add('modal-open');
      dialog.showModal();
    }));
    $('.dialog-close', dialog).addEventListener('click', closeDialog);
    dialog.addEventListener('close', () => { document.body.classList.remove('modal-open'); trigger?.focus(); });
    dialog.addEventListener('click', event => {
      if(event.target !== dialog) return;
      const rect = dialog.getBoundingClientRect();
      if(event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) closeDialog();
    });
  }
})();
