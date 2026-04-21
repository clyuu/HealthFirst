(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const baseUrl = (document.querySelector('a.brand')?.href || window.location.origin).replace(/\/$/, '');

  function asFormData(source) {
    if (source instanceof FormData) {
      return source;
    }
    if (source instanceof HTMLFormElement) {
      return new FormData(source);
    }
    const formData = new FormData();
    Object.entries(source || {}).forEach(([key, value]) => {
      formData.append(key, value);
    });
    return formData;
  }

  async function postForm(url, source) {
    const body = asFormData(source);
    if (!body.has('_token') && csrf) {
      body.append('_token', csrf);
    }
    const response = await fetch(url, {
      method: 'POST',
      body,
      headers: {
        'X-CSRF-Token': csrf,
      },
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(payload.error || payload.message || 'Request failed.');
    }
    return payload;
  }

  function formatEta(seconds) {
    const safe = Math.max(Number(seconds || 0), 0);
    const hours = Math.floor(safe / 3600);
    const minutes = Math.floor((safe % 3600) / 60);
    const secs = safe % 60;
    if (hours > 0) {
      return `${hours}h ${minutes}m ${secs}s`;
    }
    if (minutes > 0) {
      return `${minutes}m ${secs}s`;
    }
    return `${secs}s`;
  }

  function initCountdowns(root = document) {
    root.querySelectorAll('[data-eta-seconds]').forEach((node) => {
      if (node.dataset.countdownBound === '1') {
        return;
      }
      node.dataset.countdownBound = '1';
      let remaining = Number(node.dataset.etaSeconds || 0);
      node.textContent = formatEta(remaining);
      window.setInterval(() => {
        remaining = Math.max(remaining - 1, 0);
        node.textContent = formatEta(remaining);
      }, 1000);
    });
  }

  let mapsLoader = null;
  function loadGoogleMaps(apiKey) {
    if (!apiKey) {
      return Promise.resolve(null);
    }
    if (window.google?.maps) {
      return Promise.resolve(window.google.maps);
    }
    if (mapsLoader) {
      return mapsLoader;
    }
    mapsLoader = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=geometry`;
      script.async = true;
      script.defer = true;
      script.onload = () => resolve(window.google.maps);
      script.onerror = reject;
      document.head.appendChild(script);
    });
    return mapsLoader;
  }

  function setNotice(target, message, type = 'info') {
    if (!target) return;
    target.textContent = message;
    target.classList.remove('error', 'success');
    if (type === 'error' || type === 'success') {
      target.classList.add(type);
    }
  }

  function getCurrentPosition() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation is not available in this browser.'));
        return;
      }
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
      });
    });
  }

  window.HealthFirst = {
    csrf,
    baseUrl,
    postForm,
    formatEta,
    initCountdowns,
    loadGoogleMaps,
    setNotice,
    getCurrentPosition,
  };

  document.addEventListener('DOMContentLoaded', () => initCountdowns(document));
})();
