(function () {
  'use strict';

  window.SMS = window.SMS || {};

  SMS.ajax = function (url, options) {
    options = options || {};
    const method = (options.method || 'GET').toUpperCase();
    const headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {});

    return fetch(url, {
      method: method,
      headers: headers,
      body: options.body || null,
      credentials: 'same-origin',
    }).then(function (res) {
      if (!res.ok) {
        throw new Error('Request failed: ' + res.status);
      }
      const type = res.headers.get('Content-Type') || '';
      if (type.indexOf('application/json') !== -1) {
        return res.json();
      }
      return res.text();
    });
  };

  SMS.postForm = function (url, formData) {
    return SMS.ajax(url, { method: 'POST', body: formData });
  };

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-ajax-delete]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        if (!confirm('Are you sure you want to delete this item?')) {
          e.preventDefault();
        }
      });
    });
  });
})();
