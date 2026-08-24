    <footer class="pv-footer">
        <div class="pv-footer-inner">
            <div class="pv-footer-brand">
                <div class="pv-footer-logo">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <strong>Phone<span class="pv-brand-accent">Vault</span></strong>
                </div>
                <span class="pv-footer-copy">&copy; <?= date('Y') ?> &mdash; Second-Hand Phone Store &amp; Warranty Management</span>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/Second_Hand_Phone_Store/assets/js/app.js"></script>
    <?php if (!empty($pageScript)): ?>
    <script src="/Second_Hand_Phone_Store/assets/js/<?= htmlspecialchars($pageScript) ?>"></script>
    <?php endif; ?>
</body>
</html>
