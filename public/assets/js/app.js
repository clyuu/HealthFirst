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

  function bindCountdown(node) {
    if (!node || node._etaTimer) {
      return;
    }
    node._etaTimer = window.setInterval(() => {
      if (node.dataset.etaLive !== '1') {
        return;
      }
      const remaining = Math.max(Number(node.dataset.etaSeconds || 0) - 1, 0);
      node.dataset.etaSeconds = `${remaining}`;
      node.textContent = formatEta(remaining);
    }, 1000);
  }

  function initCountdowns(root = document) {
    root.querySelectorAll('[data-eta-seconds]').forEach((node) => {
      if (node.dataset.countdownBound === '1') {
        return;
      }
      node.dataset.countdownBound = '1';
      const live = node.dataset.etaLive === '1';
      node.textContent = formatEta(Number(node.dataset.etaSeconds || 0));
      if (!live) {
        return;
      }
      bindCountdown(node);
    });
  }

  function setEta(node, seconds, live = false) {
    if (!node) {
      return;
    }
    const safe = Math.max(Number(seconds || 0), 0);
    node.dataset.etaSeconds = `${safe}`;
    node.dataset.etaLive = live ? '1' : '0';
    node.textContent = formatEta(safe);
    if (live) {
      bindCountdown(node);
    }
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
      script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=geometry,places`;
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
    const text = document.createElement('span');
    text.textContent = message;
    const dismiss = document.createElement('button');
    dismiss.className = 'notice-dismiss';
    dismiss.type = 'button';
    dismiss.setAttribute('aria-label', 'Remove notification');
    dismiss.textContent = 'x';
    dismiss.addEventListener('click', () => {
      target.hidden = true;
    });
    target.hidden = false;
    target.replaceChildren(text, dismiss);
    target.classList.add('notice-with-dismiss');
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
      if (!window.isSecureContext && !/^localhost$|^127(?:\.\d{1,3}){3}$/.test(window.location.hostname)) {
        reject(new Error('Location access needs HTTPS or localhost. Open this page in a secure browser context and try again.'));
        return;
      }
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0,
      });
    });
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.hidden = true;
    if (!document.querySelector('.app-modal:not([hidden])')) {
      document.body.classList.remove('modal-open');
    }
  }

  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.hidden = false;
    document.body.classList.add('modal-open');
    document.dispatchEvent(new CustomEvent('healthfirst:modal-open', { detail: { modalId } }));
    window.setTimeout(() => {
      (modal.querySelector('input, select, textarea') || modal.querySelector('button'))?.focus();
    }, 0);
  }

  function initModals(root = document) {
    root.querySelectorAll('[data-modal-open]').forEach((trigger) => {
      if (trigger.dataset.modalBound === '1') return;
      trigger.dataset.modalBound = '1';
      trigger.addEventListener('click', (event) => {
        event.preventDefault();
        openModal(trigger.dataset.modalOpen || '');
      });
    });

    root.querySelectorAll('[data-modal-close]').forEach((trigger) => {
      if (trigger.dataset.modalCloseBound === '1') return;
      trigger.dataset.modalCloseBound = '1';
      trigger.addEventListener('click', () => closeModal(trigger.closest('.app-modal')));
    });
  }

  function initDismissibles(root = document) {
    root.querySelectorAll('[data-flash-dismiss]').forEach((button) => {
      if (button.dataset.dismissBound === '1') return;
      button.dataset.dismissBound = '1';
      button.addEventListener('click', () => {
        button.closest('.flash')?.remove();
      });
    });
  }

  function feedbackFor(field) {
    const label = field.closest('label');
    let feedback = label?.querySelector('.field-feedback');
    if (!feedback && label) {
      feedback = document.createElement('span');
      feedback.className = 'field-feedback';
      label.appendChild(feedback);
    }
    return feedback;
  }

  function setFieldState(field, valid, message = '') {
    const feedback = feedbackFor(field);
    field.classList.toggle('is-invalid', !valid);
    field.classList.toggle('is-valid', valid && field.value.trim() !== '');
    field.setCustomValidity(valid ? '' : message);
    if (feedback) {
      feedback.textContent = valid ? '' : message;
    }
  }

  function normalizePhoneField(field) {
    field.value = field.value.replace(/\D+/g, '').slice(0, 10);
  }

  function normalizeNicField(field) {
    const cleaned = field.value.toUpperCase().replace(/[^0-9VX]+/g, '');
    const digits = cleaned.replace(/\D+/g, '');
    const letter = cleaned.match(/[VX]/)?.[0] || '';
    if (letter && digits.length >= 9) {
      field.value = `${digits.slice(0, 9)}${letter}`;
      return;
    }
    field.value = digits.slice(0, 12);
  }

  function validateField(field, showEmpty = false) {
    const rule = field.dataset.validate || (field.type === 'email' ? 'email' : '');
    const value = field.value.trim();

    if (rule === 'phone' || rule === 'phone-optional') {
      normalizePhoneField(field);
      const required = rule === 'phone' || field.required;
      if (field.value === '' && !required) {
        setFieldState(field, true);
        return true;
      }
      if (field.value === '' && !showEmpty) {
        setFieldState(field, true);
        return true;
      }
      const valid = /^0\d{9}$/.test(field.value);
      setFieldState(field, valid, 'Phone number must be exactly 10 digits, e.g. 0771234567.');
      return valid;
    }

    if (rule === 'nic') {
      normalizeNicField(field);
      if (field.value === '' && !showEmpty) {
        setFieldState(field, true);
        return true;
      }
      const valid = /^(?:\d{9}[VX]|\d{12})$/.test(field.value);
      setFieldState(field, valid, 'NIC must be 9 digits plus V/X, or exactly 12 digits.');
      return valid;
    }

    if (rule === 'email') {
      if (value === '' && !showEmpty) {
        setFieldState(field, true);
        return true;
      }
      const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
      setFieldState(field, valid, 'Please enter a valid email address.');
      return valid;
    }

    if (rule === 'password') {
      if (value === '' && !showEmpty) {
        setFieldState(field, true);
        return true;
      }
      const valid = /^(?=.*[A-Za-z])(?=.*\d).{8,}$/.test(value);
      setFieldState(field, valid, 'Password needs at least 8 characters with letters and numbers.');
      return valid;
    }

    return true;
  }

  function initLiveValidation(root = document) {
    root.querySelectorAll('[data-validate], input[type="email"]').forEach((field) => {
      if (field.dataset.validationBound === '1') return;
      field.dataset.validationBound = '1';
      field.noValidate = true;

      field.addEventListener('input', () => {
        const rule = field.dataset.validate || (field.type === 'email' ? 'email' : '');
        const liveRules = ['phone', 'phone-optional', 'nic', 'password'];
        validateField(field, field.dataset.touched === '1' || liveRules.includes(rule));
      });

      field.addEventListener('blur', () => {
        field.dataset.touched = '1';
        validateField(field, true);
      });
    });

    root.querySelectorAll('form').forEach((form) => {
      if (form.dataset.validationSubmitBound === '1') return;
      form.dataset.validationSubmitBound = '1';
      form.setAttribute('novalidate', 'novalidate');
      form.addEventListener('submit', (event) => {
        const invalid = [...form.querySelectorAll('[data-validate], input[type="email"]')]
          .filter((field) => !validateField(field, true));
        if (invalid.length > 0) {
          event.preventDefault();
          invalid[0].focus();
        }
      });
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const openModals = [...document.querySelectorAll('.app-modal:not([hidden])')];
    closeModal(openModals.at(-1));
  });

  window.HealthFirst = {
    csrf,
    baseUrl,
    postForm,
    formatEta,
    initCountdowns,
    setEta,
    loadGoogleMaps,
    setNotice,
    getCurrentPosition,
    openModal,
    closeModal,
  };

  document.addEventListener('DOMContentLoaded', () => {
    initCountdowns(document);
    initModals(document);
    initDismissibles(document);
    initLiveValidation(document);
  });
})();
