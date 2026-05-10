</div><!-- /.container -->

<?php include __DIR__ . '/rules_modal.php'; ?>

<footer class="py-4">
    <div class="container text-center">
        <small>&copy; <?= date('Y') ?> <?= sanitize($siteConfig['site_title'] ?? SITE_NAME) ?></small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
