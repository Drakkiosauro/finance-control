// Navigation toggle
document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');

  if (toggle && links) {
    toggle.addEventListener('click', function () {
      links.classList.toggle('active');
    });

    document.addEventListener('click', function (e) {
      if (!toggle.contains(e.target) && !links.contains(e.target)) {
        links.classList.remove('active');
      }
    });
  }

  // Auto-dismiss alerts
  var alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.opacity = '0';
      alert.style.transition = 'opacity 0.3s ease';
      setTimeout(function () {
        alert.style.display = 'none';
      }, 300);
    }, 4000);
  });

  // Confirmation dialogs
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  // Format currency inputs
  document.querySelectorAll('.input-currency').forEach(function (input) {
    input.addEventListener('blur', function () {
      var value = parseFloat(this.value);
      if (!isNaN(value)) {
        this.value = value.toFixed(2);
      }
    });
  });
});
