<?php
require_once __DIR__ . '/../core/bootstrap.php';
Auth::requireLogin();

$user = User::getById(Auth::id());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $type = $_POST['type'] ?? '';

    switch ($type) {
        case 'info':
            $fio = trim($_POST['fio'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($fio) {
                $emailData = $user['email'];
                $gameLevel = intval($user['game_level'] ?? 0);
                if ($email && $email !== $user['email']) {
                    $existing = User::getByEmail($email);
                    if (!$existing) {
                        $emailData = $email;
                    } else {
                        flash('error', 'Этот email уже занят.');
                        Auth::redirect('account.php');
                    }
                }
                User::update(Auth::id(), ['fio' => $fio, 'phone' => $phone, 'email' => $emailData, 'game_level' => $gameLevel]);
                $_SESSION['fio'] = $fio;
                $_SESSION['phone'] = $phone;
                $_SESSION['email'] = $emailData;
                flash('success', 'Данные обновлены.');
            }
            break;

        case 'password':
            $oldPass = $_POST['old_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            if (password_verify($oldPass, $user['password_hash'])) {
                if (strlen($newPass) >= 6) {
                    User::updatePassword(Auth::id(), $newPass);
                    flash('success', 'Пароль изменён.');
                } else {
                    flash('error', 'Новый пароль слишком короткий (мин. 6 символов).');
                }
            } else {
                flash('error', 'Неверный текущий пароль.');
            }
            break;

        case 'notifications':
            if (Auth::role() >= 3) {
                $flags = [];
                $names = ['add_event', 'delete_event', 'edit_event', 'edit_member', 'edit_payment'];
                foreach ($names as $name) {
                    $flags[] = isset($_POST[$name]) ? '1' : '0';
                }
                User::updateNotifySettings(Auth::id(), implode('-', $flags));
                flash('success', 'Настройки уведомлений сохранены.');
            }
            break;
    }

    Auth::redirect('account.php');
}

$user = User::getById(Auth::id());
$notifyArr = explode('-', $user['notify_settings'] ?? '1-1-1-1-1');

$pageTitle = 'Мой кабинет';
include __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 text-white">Мой кабинет</h4>

<!-- Profile info -->
<div class="card mb-4">
    <div class="card-header"><strong>Личная информация</strong></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="type" value="info">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">ФИО</label>
                    <input type="text" name="fio" class="form-control" value="<?= sanitize($user['fio']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Телефон</label>
                    <input type="tel" name="phone" class="form-control" value="<?= sanitize($user['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= sanitize($user['email'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">VK аккаунт</label>
                    <?php if ($user['vk_id'] && ctype_digit($user['vk_id'])): ?>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success fs-6">VK привязан (ID: <?= sanitize($user['vk_id']) ?>)</span>
                        </div>
                    <?php else: ?>
                        <div>
                            <a href="vk-login.php?action=link" class="btn btn-sm" style="background:#0077FF;color:#fff;">
                                Привязать VK ID
                            </a>
                            <?php if (Auth::role() >= 3): ?>
                            <div><small class="text-danger">VK не привязан — уведомления не будут приходить.</small></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Уровень игры</label>
                    <?php $gameLevelMeta = getGameLevelMeta(intval($user['game_level'] ?? 0)); ?>
                    <div class="form-control d-flex align-items-center gap-2 bg-light-subtle">
                        <?= renderGameLevelDot(intval($user['game_level'] ?? 0)) ?>
                        <span><?= sanitize($gameLevelMeta['label']) ?></span>
                    </div>
                    <div class="form-text">Уровень игры устанавливается администратором.</div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить</button>
        </form>
    </div>
</div>

<!-- Password -->
<div class="card mb-4">
    <div class="card-header"><strong>Смена пароля</strong></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="type" value="password">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Текущий пароль</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Новый пароль</label>
                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                </div>
            </div>
            <button type="submit" class="btn btn-warning">Сменить пароль</button>
        </form>
    </div>
</div>

<!-- Notification settings (organizer+) -->
<?php if (Auth::role() >= 3): ?>
<div class="card mb-4">
    <div class="card-header"><strong>Настройки уведомлений ВКонтакте</strong></div>
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="type" value="notifications">
            <?php
            $names = ['add_event', 'delete_event', 'edit_event', 'edit_member', 'edit_payment'];
            $labels = [
                'Добавление мероприятия',
                'Удаление мероприятия',
                'Редактирование мероприятия',
                'Запись / удаление участников',
                'Подтверждение оплаты'
            ];
            foreach ($names as $i => $name): ?>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="<?= $name ?>" id="nf_<?= $name ?>" <?= ($notifyArr[$i] ?? '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="nf_<?= $name ?>"><?= $labels[$i] ?></label>
            </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-info mt-2">Сохранить настройки</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
