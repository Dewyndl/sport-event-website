<?php
require_once __DIR__ . '/../core/bootstrap.php';
Auth::requireRole(3);

if (Auth::role() == 4) {
    $logs = ActionLog::getAll(200);
} else {
    $logs = ActionLog::getByUser(Auth::id(), 200);
}

$pageTitle = 'История действий';
include __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 text-white">История действий</h4>

<?php if (empty($logs)): ?>
    <p class="text-muted">История пуста.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-dark table-hover">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Пользователь</th>
                    <th>Действие</th>
                    <th>Детали</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><small><?php
                        $dt = new DateTime($log['created_at'], new DateTimeZone('UTC'));
                        $dt->setTimezone(new DateTimeZone('Asia/Irkutsk'));
                        echo $dt->format('d.m.Y H:i');
                    ?></small></td>
                    <td><?= sanitize($log['user_fio'] ?? 'Система') ?></td>
                    <td><span class="badge bg-secondary"><?= sanitize($log['action']) ?></span></td>
                    <td><?= sanitize($log['details'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<a href="index.php" class="btn btn-outline-secondary">← Назад</a>

<?php include __DIR__ . '/../templates/footer.php'; ?>
