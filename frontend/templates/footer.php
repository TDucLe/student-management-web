        </main>
        <footer class="app-footer">
            <div class="footer-content">
                <div class="footer-brand">
                    <div class="footer-logo">🏛️</div>
                    <div>
                        <h3>Trường Quốc tế</h3>
                        <p class="footer-sub">Đại học Quốc gia Hà Nội</p>
                    </div>
                </div>
                <div class="footer-links">
                    <a href="https://www.is.vnu.edu.vn/" target="_blank" rel="noopener">🌐 is.vnu.edu.vn</a>
                    <span class="footer-sep">|</span>
                    <span>📍 144 Xuân Thủy, Cầu Giấy, Hà Nội</span>
                    <span class="footer-sep">|</span>
                    <span>📞 (024) 3754 7461</span>
                    <span class="footer-sep">|</span>
                    <span>✉️ info@is.vnu.edu.vn</span>
                </div>
            </div>
            <div class="footer-copy">&copy; <?= date('Y') ?> Trường Quốc tế — Đại học Quốc gia Hà Nội. All rights reserved.</div>
        </footer>
    </div>
</div>
<!-- Notification dropdown: rendered outside all containers to avoid stacking/overflow issues -->
<?php $__notifs = $GLOBALS['__notifications'] ?? []; ?>
<div class="notif-dropdown" id="notifDropdown" hidden>
    <div class="notif-dropdown-head">
        🔔 <?= htmlspecialchars(t('notifications')) ?>
        <?php if (!empty($__notifs)): ?>
        <span class="notif-head-count"><?= count($__notifs) ?></span>
        <?php endif; ?>
    </div>
    <div class="notif-dropdown-body">
        <?php if (empty($__notifs)): ?>
            <p class="notif-empty"><?= htmlspecialchars(t('no_notifications')) ?></p>
        <?php else: foreach ($__notifs as $n): ?>
            <div class="notif-item">
                <span class="badge badge-<?= htmlspecialchars($n['type'] ?? 'general') ?>"><?= htmlspecialchars($n['type']) ?></span>
                <p><?= htmlspecialchars($n['message']) ?></p>
                <small><?= htmlspecialchars($n['created_at']) ?></small>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
</body>
</html>
