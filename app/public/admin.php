<?php
require_once __DIR__ . '/../core/bootstrap.php';
Auth::requireRole(4);

// Handle user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_user':
            $userId = intval($_POST['user_id'] ?? 0);
            $newRole = intval($_POST['role'] ?? 1);
            $newStatus = intval($_POST['status'] ?? 1);
            $newGameLevel = intval($_POST['game_level'] ?? 0);
            if ($userId && $userId !== Auth::id()) {
                User::updateRole($userId, $newRole);
                User::updateStatus($userId, $newStatus);
                User::updateGameLevel($userId, $newGameLevel);
                $targetUser = User::getById($userId);
                ActionLog::add(Auth::id(), null, 'user_update',
                    Auth::fio() . ' изменил роль/статус/уровень игрока пользователя "' . ($targetUser['fio'] ?? '') . '"');
                flash('success', 'Пользователь обновлён.');
            }
            break;

        case 'update_config':
            $fields = ['site_title', 'bg_color', 'vk_public_id', 'vk_token', 'events_per_page'];
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    Database::insert(
                        "INSERT INTO config (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)",
                        [$field, trim($_POST[$field])]
                    );
                }
            }
            flash('success', 'Настройки сохранены.');
            break;

        case 'update_logo':
            if (!empty($_FILES['logo']['tmp_name'])) {
                $file = $_FILES['logo'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    flash('error', 'Допустимые форматы: jpg, png, gif, webp.');
                    break;
                }
                $uploadDir = __DIR__ . '/assets/uploads/';
                $newName = 'logo_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $old = Database::selectOne("SELECT value FROM config WHERE name = 'logo_image'");
                    if ($old && !empty($old['value']) && file_exists($uploadDir . $old['value'])) {
                        unlink($uploadDir . $old['value']);
                    }
                    Database::insert(
                        "INSERT INTO config (name, value) VALUES ('logo_image', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)",
                        [$newName]
                    );
                    flash('success', 'Логотип обновлён.');
                } else {
                    flash('error', 'Ошибка загрузки файла.');
                }
            } elseif (isset($_POST['remove_logo'])) {
                $old = Database::selectOne("SELECT value FROM config WHERE name = 'logo_image'");
                if ($old && !empty($old['value'])) {
                    $uploadDir = __DIR__ . '/assets/uploads/';
                    if (file_exists($uploadDir . $old['value'])) unlink($uploadDir . $old['value']);
                }
                Database::insert(
                    "INSERT INTO config (name, value) VALUES ('logo_image', '') ON DUPLICATE KEY UPDATE value = ''",
                    []
                );
                flash('success', 'Логотип сброшен до стандартного.');
            }
            break;

    }
    Auth::redirect('admin.php');
}

$users = User::getAll();
$userCount = count($users);
$eventCount = Event::count();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteConfig = loadConfig();
}

$pageTitle = 'Админ-панель';
include __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 text-white">Админ-панель</h4>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="text-info"><?= $userCount ?></h5>
                <small class="text-muted">Пользователей</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="text-info"><?= $eventCount ?></h5>
                <small class="text-muted">Мероприятий</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <a href="logs.php" class="btn btn-outline-info btn-sm">Просмотр логов</a>
            </div>
        </div>
    </div>
</div>

<!-- Site config -->
<div class="card mb-4">
    <div class="card-header"><strong>Настройки сайта</strong></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="update_config">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Заголовок сайта</label>
                    <input type="text" name="site_title" class="form-control" value="<?= sanitize($siteConfig['site_title'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Цвет фона</label>
                    <input type="color" name="bg_color" class="form-control form-control-color" value="<?= sanitize($siteConfig['bg_color'] ?? '#1a1a2e') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">VK Public ID</label>
                    <input type="text" name="vk_public_id" class="form-control" value="<?= sanitize($siteConfig['vk_public_id'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">VK Token сообщества</label>
                    <input type="text" name="vk_token" class="form-control" value="<?= sanitize($siteConfig['vk_token'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Событий на странице</label>
                    <input type="number" name="events_per_page" class="form-control" min="1" max="100" value="<?= intval($siteConfig['events_per_page'] ?? 10) ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-success">Сохранить настройки</button>
        </form>
    </div>
</div>

<!-- Logo -->
<div class="card mb-4">
    <div class="card-header"><strong>Логотип сайта (шапка)</strong></div>
    <div class="card-body">
        <?php $logoFile = $siteConfig['logo_image'] ?? ''; ?>
        <?php if ($logoFile): ?>
        <div class="mb-3">
            <img src="assets/uploads/<?= sanitize($logoFile) ?>" class="img-fluid rounded" style="max-height:100px;" alt="Логотип">
        </div>
        <form method="post" class="mb-3">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="update_logo">
            <input type="hidden" name="remove_logo" value="1">
            <button type="submit" class="btn btn-outline-danger btn-sm">Сбросить до стандартного</button>
        </form>
        <?php else: ?>
        <div class="mb-3">
            <img src="assets/img/logo.png" class="img-fluid rounded" style="max-height:100px;" alt="Логотип (стандартный)">
            <p class="text-muted small mt-1">Используется стандартный логотип.</p>
        </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="update_logo">
            <div class="mb-3">
                <label class="form-label">Загрузить новый логотип</label>
                <input type="file" name="logo" class="form-control" accept="image/*" required>
                <div class="form-text">JPG, PNG, GIF, WebP. Рекомендуется PNG с прозрачным фоном.</div>
            </div>
            <button type="submit" class="btn btn-success">Загрузить</button>
        </form>
    </div>
</div>

<!-- Users -->
<div class="card">
    <div class="card-header"><strong>Пользователи (<?= $userCount ?>)</strong></div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="userSearch" class="form-control" placeholder="Поиск по ФИО, Email, Телефону, VK ID...">
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Уровень игры</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Дата рег.</th>
                        <th>VK</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr data-search="<?= mb_strtolower(sanitize($u['fio'] . ' ' . ($u['email'] ?? '') . ' ' . ($u['phone'] ?? '') . ' ' . ($u['vk_id'] ? 'id' . $u['vk_id'] . ' ' . $u['vk_id'] : ''))) ?>">
                            <td><?= $u['id'] ?></td>
                            <td><?= sanitize($u['fio']) ?></td>
                            <td><?= sanitize($u['email'] ?? '') ?></td>
                            <td><?= sanitize($u['phone'] ?? '') ?></td>
                            <td>
                                <select name="game_level" form="user_form_<?= $u['id'] ?>" class="form-select form-select-sm" <?= $u['id'] == Auth::id() ? 'disabled' : '' ?>>
                                    <?php foreach ($game_levels as $gk => $gv): ?>
                                    <option value="<?= $gk ?>" <?= intval($u['game_level'] ?? 0) === $gk ? 'selected' : '' ?>><?= $gv['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="role" form="user_form_<?= $u['id'] ?>" class="form-select form-select-sm" <?= $u['id'] == Auth::id() ? 'disabled' : '' ?>>
                                    <?php foreach ($roles as $rk => $rv): ?>
                                    <option value="<?= $rk ?>" <?= $u['role'] == $rk ? 'selected' : '' ?>><?= $rv ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="status" form="user_form_<?= $u['id'] ?>" class="form-select form-select-sm" <?= $u['id'] == Auth::id() ? 'disabled' : '' ?>>
                                    <option value="1" <?= $u['status'] == 1 ? 'selected' : '' ?>>Активен</option>
                                    <option value="2" <?= $u['status'] == 2 ? 'selected' : '' ?>>Заблокирован</option>
                                </select>
                            </td>
                            <td><small><?= date('d.m.Y', strtotime($u['created_at'])) ?></small></td>
                            <td>
                                <?php if (!empty($u['vk_id'])): ?>
                                <a href="https://vk.com/id<?= sanitize($u['vk_id']) ?>" target="_blank" class="text-info small">id<?= sanitize($u['vk_id']) ?></a>
                                <?php else: ?>
                                <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['id'] != Auth::id()): ?>
                                <button type="submit" form="user_form_<?= $u['id'] ?>" class="btn btn-outline-primary btn-sm">Сохранить</button>
                                <?php endif; ?>
                            </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($users as $u): ?>
<?php if ($u['id'] != Auth::id()): ?>
<form method="post" id="user_form_<?= $u['id'] ?>" class="d-none">
    <?= CSRF::field() ?>
    <input type="hidden" name="action" value="update_user">
    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
</form>
<?php endif; ?>
<?php endforeach; ?>

<script>
document.getElementById('userSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.admin-table tbody tr').forEach(function(row) {
        row.style.display = !q || row.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>
<?php include __DIR__ . '/../templates/footer.php'; ?>
