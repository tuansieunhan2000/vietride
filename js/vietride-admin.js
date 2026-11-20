(() => {
  function bindPicker(buttonId, fieldId, multiple) {
    const button = document.getElementById(buttonId);
    const field = document.getElementById(fieldId);
    if (!button || !field || !window.wp?.media) return;
    let picker;
    button.addEventListener('click', () => {
      if (!picker) {
        picker = wp.media({
          title: multiple ? 'Choose bike photos' : 'Choose a preview image',
          button: { text: multiple ? 'Add to gallery' : 'Use as preview' },
          library: { type: ['image/jpeg', 'image/png', 'image/webp'] },
          multiple,
        });
        picker.on('select', () => {
          const urls = picker.state().get('selection').map(image => image.get('url'));
          if (multiple) {
            const existing = field.value.split(/\r?\n/).map(url => url.trim()).filter(Boolean);
            field.value = [...new Set([...existing, ...urls])].join('\n');
          } else {
            field.value = urls[0] || '';
          }
          field.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      picker.open();
    });
  }
  bindPicker('vr-choose-photos', 'images', true);
  bindPicker('vr-choose-preview', 'thumb', false);
})();

(() => {
  const gallery = document.getElementById('images');
  const cover = document.getElementById('thumb');
  const editor = document.getElementById('vr-gallery-editor');
  const preview = document.getElementById('vr-cover-preview');
  if (!gallery || !cover || !editor || !preview) return;

  function photos() {
    return [...new Set(gallery.value.split(/\r?\n/).map(url => url.trim()).filter(Boolean))];
  }
  function imageUrl(path) {
    try {
      const url = new URL(path, window.vrFleetMedia?.themeUrl || document.baseURI);
      return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
    } catch {
      return '';
    }
  }
  function savePhotos(urls) {
    gallery.value = urls.join('\n');
    gallery.dispatchEvent(new Event('change', { bubbles: true }));
  }
  function control(label, disabled, action) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'button';
    button.textContent = label;
    button.disabled = disabled;
    button.addEventListener('click', action);
    return button;
  }
  function render() {
    const urls = photos();
    const main = cover.value.trim() || urls[0] || '';
    preview.replaceChildren();
    editor.replaceChildren();
    if (main) {
      const image = document.createElement('img');
      image.src = imageUrl(main);
      image.alt = 'Current cover photo';
      preview.append(image);
    }
    urls.forEach((url, index) => {
      const card = document.createElement('div');
      card.className = 'vr-gallery-photo';
      const image = document.createElement('img');
      image.src = imageUrl(url);
      image.alt = 'Bike photo ' + (index + 1);
      const label = document.createElement('p');
      label.textContent = 'Photo ' + (index + 1) + (url === main ? ' · Cover photo' : '');
      const actions = document.createElement('div');
      actions.className = 'vr-photo-actions';
      actions.append(control('Set as main', url === main, () => {
        cover.value = url;
        savePhotos([url, ...urls.filter(item => item !== url)]);
      }));
      actions.append(control('Move earlier', index === 0, () => {
        [urls[index - 1], urls[index]] = [urls[index], urls[index - 1]];
        savePhotos(urls);
      }));
      actions.append(control('Move later', index === urls.length - 1, () => {
        [urls[index], urls[index + 1]] = [urls[index + 1], urls[index]];
        savePhotos(urls);
      }));
      actions.append(control('Remove', false, () => {
        const remaining = urls.filter(item => item !== url);
        if (cover.value.trim() === url) cover.value = remaining[0] || '';
        savePhotos(remaining);
      }));
      card.append(image, label, actions);
      editor.append(card);
    });
  }
  gallery.addEventListener('input', render);
  gallery.addEventListener('change', render);
  cover.addEventListener('input', render);
  cover.addEventListener('change', render);
  render();
})();
