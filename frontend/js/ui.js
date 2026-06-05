(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    // ── Notification toggle ──
    var btn = document.getElementById('notifToggle');
    var panel = document.getElementById('notifDropdown');
    if (btn && panel) {
      var badge = btn.querySelector('.notif-badge');
      var isOpen = false;

      function openPanel() {
        var rect = btn.getBoundingClientRect();
        panel.style.top = (rect.bottom + 8) + 'px';
        var right = window.innerWidth - rect.right;
        if (right < 10) right = 10;
        panel.style.right = right + 'px';
        panel.style.display = 'flex';
        isOpen = true;

        // Mark as seen: hide badge + set cookie
        if (badge) {
          badge.style.display = 'none';
        }
        var now = new Date();
        var y = now.getFullYear();
        var m = String(now.getMonth() + 1).padStart(2, '0');
        var d = String(now.getDate()).padStart(2, '0');
        var h = String(now.getHours()).padStart(2, '0');
        var mi = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        var ts = y + '-' + m + '-' + d + ' ' + h + ':' + mi + ':' + s;
        document.cookie = 'notif_seen=' + encodeURIComponent(ts) + ';path=/;max-age=31536000;SameSite=Lax';
      }

      function closePanel() {
        panel.style.display = 'none';
        isOpen = false;
      }

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (isOpen) {
          closePanel();
        } else {
          openPanel();
        }
      });

      // Close when clicking anywhere outside the panel (mousedown fires before click)
      document.addEventListener('mousedown', function (e) {
        if (isOpen && !panel.contains(e.target) && !btn.contains(e.target)) {
          closePanel();
        }
      });

      // Also close when scrolling in main-wrap
      var mainWrap = document.querySelector('.main-wrap');
      if (mainWrap) {
        mainWrap.addEventListener('scroll', function () {
          if (isOpen) closePanel();
        });
      }
    }

    // ── Card scroll entrance animation ──
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.card').forEach(function (card) {
      // Skip cards inside grids that would cause layout shift when animated
      if (card.closest('.stat-grid') || card.closest('.attendance-class-grid') || card.closest('.quick-grid') || card.classList.contains('stat-card') || card.classList.contains('attendance-class-card') || card.classList.contains('quick-tile')) {
        return;
      }
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
      observer.observe(card);
    });

    // ── Mobile sidebar toggle ──
    var sidebar = document.querySelector('.sidebar');
    if (sidebar && window.innerWidth <= 900) {
      var toggler = document.createElement('button');
      toggler.className = 'sidebar-toggle';
      toggler.innerHTML = '☰';
      toggler.style.cssText = 'position:fixed;top:12px;left:12px;z-index:999;background:var(--role-primary,#0a1e3d);color:#fff;border:none;border-radius:10px;width:44px;height:44px;font-size:1.4rem;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,0.25);';
      document.body.appendChild(toggler);

      sidebar.style.transition = 'transform 0.35s cubic-bezier(0.22,1,0.36,1)';

      toggler.addEventListener('click', function (e) {
        e.stopPropagation();
        var isSideOpen = sidebar.getAttribute('data-open') === 'true';
        sidebar.setAttribute('data-open', isSideOpen ? 'false' : 'true');
        sidebar.style.transform = isSideOpen ? 'translateX(-100%)' : 'translateX(0)';
        toggler.innerHTML = isSideOpen ? '☰' : '✕';
      });
    }
  });
})();
