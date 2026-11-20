(() => {
  // Animate on first entry only. Before observation, all content stays readable.
  if ('IntersectionObserver' in window) {
    const targets = [
      '.service__image', '.service__content > :is(h2, p)', '.service__list li',
      '.vr-editorial h1', '.vr-editorial h2', '.vr-steps li', '.vr-bike',
      '.vr-guide-group', '.vr-routes article', '.vr-reviews figure',
      '.vr-story-photo', '.vr-stats', '#contact address',
      '.vr-product-gallery', '.vr-landscape',
    ];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const observer = new IntersectionObserver(entries => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        observer.unobserve(entry.target);
        if (reduceMotion.matches) continue;
        const element = entry.target;
        element.classList.add('vr-in-view');
        const clear = event => {
          if (event.target !== element || event.animationName !== 'vr-enter') return;
          element.classList.remove('vr-in-view');
          element.removeEventListener('animationend', clear);
          element.removeEventListener('animationcancel', clear);
        };
        element.addEventListener('animationend', clear);
        element.addEventListener('animationcancel', clear);
      }
    });
    document.querySelectorAll(targets.join(', ')).forEach(element => observer.observe(element));
  }

  // On narrow screens WordPress scrolls its admin bar out of the viewport.
  // Reserve only the part still visible, for both the header and the drawer.
  const adminBar = document.getElementById('wpadminbar');
  let lastAdminOffset = -1;
  function updateAdminOffset() {
    const bounds = adminBar?.getBoundingClientRect();
    const offset = bounds ? Math.max(0, Math.min(bounds.height, bounds.bottom)) : 0;
    if (offset === lastAdminOffset) return;
    document.documentElement.style.setProperty('--vr-admin-offset', offset + 'px');
    lastAdminOffset = offset;
  }
  updateAdminOffset();
  if (adminBar) {
    window.addEventListener('scroll', updateAdminOffset, { passive: true });
    window.addEventListener('resize', updateAdminOffset);
    window.addEventListener('pageshow', updateAdminOffset);
  }

  const toggle = document.querySelector('#vr-toggle');
  const nav = document.querySelector('#vr-menu');
  const contacts = document.querySelector('.vr-contact-dock');
  const shade = document.querySelector('.vr-nav-shade');
  if (toggle && nav && shade) {
    const compact = window.matchMedia('(max-width: 1279px), (hover: none) and (pointer: coarse)');
    function setOpen(open, animate = false) {
      open = Boolean(open && compact.matches);
      // Only user-initiated toggles animate; boot/navigation/restore stay still.
      nav.classList.toggle('vr-drawer-motion', animate);
      nav.classList.toggle('is-open', open);
      nav.inert = compact.matches && !open;
      shade.hidden = !open;
      document.body.classList.toggle('vr-drawer-open', open);
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      if (open && contacts) contacts.open = false;
    }
    toggle.hidden = false;
    setOpen(false);
    compact.addEventListener('change', () => setOpen(false));
    toggle.addEventListener('click', () => setOpen(!nav.classList.contains('is-open'), true));
    nav.addEventListener('click', event => {
      const link = event.target.closest('a');
      if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || (link.target && link.target !== '_self')) return;
      const destination = new URL(link.href, location.href);
      const sameDocument = destination.origin === location.origin && destination.pathname === location.pathname && destination.search === location.search && Boolean(destination.hash);
      setOpen(false, sameDocument);
    });
    shade.addEventListener('click', () => { setOpen(false, true); toggle.focus(); });
    // Store a closed drawer in the back/forward cache and restore without motion.
    window.addEventListener('pagehide', () => setOpen(false));
    window.addEventListener('pageshow', event => {
      updateAdminOffset();
      if (event.persisted) setOpen(false);
    });
    document.addEventListener('keydown', event => {
      if (!nav.classList.contains('is-open')) return;
      const lastLink = nav.querySelector('a:last-child');
      if (event.key === 'Escape') {
        setOpen(false, true);
        toggle.focus();
      } else if (event.key === 'Tab' && event.shiftKey && document.activeElement === toggle) {
        event.preventDefault();
        lastLink.focus();
      } else if (event.key === 'Tab' && !event.shiftKey && document.activeElement === lastLink) {
        event.preventDefault();
        toggle.focus();
      }
    });
  }
  if (contacts) {
    document.addEventListener('click', event => {
      if (!contacts.contains(event.target) || event.target.closest('.vr-contact-links a')) contacts.open = false;
    });
    document.addEventListener('keydown', event => {
      if (contacts.open && event.key === 'Escape') {
        contacts.open = false;
        contacts.querySelector('summary').focus();
      }
    });
  }
  const infoNav = document.querySelector('.vr-info-nav');
  if (infoNav) {
    const links = [...infoNav.querySelectorAll('a[href^="#"]')];
    const sections = links.map(link => document.getElementById(link.hash.slice(1)));
    function markTopic(section) {
      links.forEach((link, i) => {
        if (sections[i] === section) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
    }
    links.forEach((link, i) => link.addEventListener('click', () => {
      if (sections[i]) {
        sections[i].open = true;
        markTopic(sections[i]);
      }
    }));
    if ('IntersectionObserver' in window) {
      const topics = new IntersectionObserver(() => {
        const current = [...sections].reverse().find(section => section && section.getBoundingClientRect().top <= 150);
        markTopic(current || sections[0]);
      }, { rootMargin: '-100px 0px -60% 0px' });
      sections.filter(Boolean).forEach(section => topics.observe(section));
    }
  }
  document.querySelectorAll('[data-bike-gallery]').forEach(gallery => {
    const main = gallery.querySelector('[data-gallery-main]');
    const thumbnails = [...gallery.querySelectorAll('[data-gallery-thumb]')];
    const previous = gallery.querySelector('[data-gallery-prev]');
    const next = gallery.querySelector('[data-gallery-next]');
    if (!main || !previous || !next || !thumbnails.length) return;
    let current = 0;
    function selectPhoto(index, focus = false) {
      current = Math.max(0, Math.min(index, thumbnails.length - 1));
      const chosen = thumbnails[current];
      // A previous photo's srcset must never override the newly selected source.
      main.removeAttribute('srcset');
      main.removeAttribute('sizes');
      if (chosen.dataset.gallerySizes) main.sizes = chosen.dataset.gallerySizes;
      if (chosen.dataset.gallerySrcset) main.srcset = chosen.dataset.gallerySrcset;
      main.src = chosen.dataset.gallerySrc || chosen.href;
      main.alt = chosen.dataset.alt;
      thumbnails.forEach((thumbnail, i) => {
        if (i === current) thumbnail.setAttribute('aria-current', 'true');
        else thumbnail.removeAttribute('aria-current');
      });
      previous.disabled = current === 0;
      next.disabled = current === thumbnails.length - 1;
      gallery.querySelector('[data-gallery-announcement]').textContent = 'Photo ' + (current + 1) + ' of ' + thumbnails.length;
      chosen.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      if (focus) chosen.focus({ preventScroll: true });
    }
    previous.hidden = false;
    next.hidden = false;
    previous.disabled = true;
    previous.addEventListener('click', () => selectPhoto(current - 1));
    next.addEventListener('click', () => selectPhoto(current + 1));
    thumbnails.forEach((thumbnail, i) => thumbnail.addEventListener('click', event => {
      event.preventDefault();
      selectPhoto(i);
    }));
    gallery.querySelector('[data-gallery-thumbs]').addEventListener('keydown', event => {
      let target;
      if (event.key === 'ArrowLeft') target = current - 1;
      if (event.key === 'ArrowRight') target = current + 1;
      if (event.key === 'Home') target = 0;
      if (event.key === 'End') target = thumbnails.length - 1;
      if (target === undefined) return;
      event.preventDefault();
      selectPhoto(target, true);
    });
  });
  document.querySelectorAll('[data-fleet]').forEach(fleet => {
    const controls = fleet.querySelector('.vr-filters');
    controls.hidden = false;
    controls.addEventListener('click', event => {
      const chosen = event.target.closest('[data-filter]');
      if (!chosen) return;
      controls.querySelectorAll('button').forEach(btn => btn.setAttribute('aria-pressed', String(btn === chosen)));
      let count = 0;
      fleet.querySelectorAll('[data-tags]').forEach(card => {
        card.hidden = chosen.dataset.filter !== 'all' && !card.dataset.tags.split(' ').includes(chosen.dataset.filter);
        if (!card.hidden) {
          count++;
          if (fleet.classList.contains('vr-preview') && count > 3) card.hidden = true;
        }
      });
      const prefix = fleet.classList.contains('vr-preview') && count > 3 ? 'Showing 3 of ' : '';
      fleet.querySelector('output').textContent = prefix + count + ' bikes';
    });
  });
})();
