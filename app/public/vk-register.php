<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (Auth::isLoggedIn()) {
    Auth::redirect('index.php');
}

if (empty($_SESSION['vk_pending'])) {
    Auth::redirect('auth.php');
}

$vkData = $_SESSION['vk_pending'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $fio   = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $errors = [];
    if (!$fio)   $errors[] = 'Укажите ФИО';
    if (!$phone) $errors[] = 'Укажите телефон';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';

    if (empty($errors) && $email && User::getByEmail($email)) {
        $errors[] = 'Пользователь с таким email уже существует.';
    }

        if (empty($errors)) {
            $userId = Database::insert(
            "INSERT INTO users (fio, phone, email, vk_id, game_level) VALUES (?, ?, ?, ?, ?)",
            [$fio, $phone, $email ?: null, $vkData['vk_id'], 0]
        );
            $user = User::getById($userId);
        unset($_SESSION['vk_pending']);
        Auth::loginByUser($user);
        flash('success', 'Регистрация успешна! Добро пожаловать!');
        Auth::redirect('index.php');
    } else {
        flash('error', implode("\n", $errors));
        Auth::redirect('vk-register.php');
    }
}

$pageTitle = 'Завершение регистрации';
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-form">
    <h3 class="text-center">Завершение регистрации</h3>
    <p class="text-center text-muted small">Вы входите через VK. Заполните данные профиля.</p>
    <form method="post">
        <?= CSRF::field() ?>
        <div class="mb-3">
            <label class="form-label">ФИО *</label>
            <input type="text" name="fio" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Телефон *</label>
            <input type="tel" name="phone" class="form-control" placeholder="+7 (___) ___-__-__" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email (необязательно)</label>
            <input type="email" name="email" class="form-control"
                   value="<?= sanitize($vkData['email'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary w-100">Завершить регистрацию</button>
    </form>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
