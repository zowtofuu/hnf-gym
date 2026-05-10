<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <p><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
<?php endif; ?>