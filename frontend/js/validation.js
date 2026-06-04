(function () {
  'use strict';

  function showError(input, message) {
    input.classList.add('is-invalid');
    let hint = input.parentElement.querySelector('.field-error');
    if (!hint) {
      hint = document.createElement('div');
      hint.className = 'field-error';
      hint.style.cssText = 'color:#dc2626;font-size:0.8rem;margin-top:4px;';
      input.parentElement.appendChild(hint);
    }
    hint.textContent = message;
  }

  function clearError(input) {
    input.classList.remove('is-invalid');
    const hint = input.parentElement.querySelector('.field-error');
    if (hint) hint.remove();
  }

  function validateForm(form) {
    let valid = true;
    form.querySelectorAll('[required]').forEach((input) => {
      clearError(input);
      if (!input.value.trim()) {
        showError(input, 'This field is required.');
        valid = false;
      }
    });

    const email = form.querySelector('input[type="email"]');
    if (email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
      showError(email, 'Invalid email format.');
      valid = false;
    }

    const pwd = form.querySelector('input[name="password"]');
    const confirm = form.querySelector('input[name="confirm_password"]');
    if (pwd && confirm && pwd.value !== confirm.value) {
      showError(confirm, 'Passwords do not match.');
      valid = false;
    }

    if (pwd && pwd.value && pwd.value.length < 6) {
      showError(pwd, 'Password must be at least 6 characters.');
      valid = false;
    }

    return valid;
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-validate]').forEach((form) => {
      form.addEventListener('submit', function (e) {
        if (!validateForm(form)) {
          e.preventDefault();
        }
      });
    });
  });
})();
