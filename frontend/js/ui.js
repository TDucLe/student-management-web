(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    // ── Notification toggle ──
    var btn = document.getElementById('notifToggle');
    var panel = document.getElementById('notifDropdown');
    if (btn && panel) {
      var badge = btn.querySelector('.notif-badge');
      var seenSent = false;

      function positionPanel() {
        var rect = btn.getBoundingClientRect();
        panel.style.top = (rect.bottom + 8) + 'px';
        // Ensure dropdown doesn't overflow right edge
        var right = window.innerWidth - rect.right;
        if (right < 10) right = 10;
        panel.style.right = right + 'px';
      }

      function markAsSeen() {
        if (seenSent) return;
        seenSent = true;
        // Hide badge immediately
        if (badge) {
          badge.style.display = 'none';
          badge.remove();
        }
        // Tell server to update last_seen timestamp
        var seenUrl = btn.getAttribute('data-seen-url');
        if (seenUrl) {
          fetch(seenUrl, { method: 'POST', credentials: 'same-origin' }).catch(function() {});
        }
      }

      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.hidden = !panel.hidden;
        if (!panel.hidden) {
          positionPanel();
          markAsSeen();
        }
      });
      document.addEventListener('click', function () {
        panel.hidden = true;
      });
      panel.addEventListener('click', function (e) {
        e.stopPropagation();
      });
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
        var isOpen = sidebar.getAttribute('data-open') === 'true';
        sidebar.setAttribute('data-open', isOpen ? 'false' : 'true');
        sidebar.style.transform = isOpen ? 'translateX(-100%)' : 'translateX(0)';
        toggler.innerHTML = isOpen ? '☰' : '✕';
      });
    }
  });
})();
