<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (Auth::isLoggedIn()) {
    Auth::redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $fio = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    $errors = [];
    if (!$fio) $errors[] = 'Укажите ФИО';
    if (!$phone) $errors[] = 'Укажите телефон';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Укажите корректный email';
    if (strlen($password) < 6) $errors[] = 'Пароль должен быть не менее 6 символов';
    if ($password !== $password2) $errors[] = 'Пароли не совпадают';

    if (empty($errors)) {
        $existing = User::getByEmail($email);
        if ($existing) {
            flash('error', 'Пользователь с таким email уже существует.');
        } else {
            Auth::register([
                'email' => $email,
                'password' => $password,
                'fio' => $fio,
                'phone' => $phone
            ]);
            flash('success', 'Регистрация успешна! Войдите в аккаунт.');
            Auth::redirect('auth.php');
        }
    } else {
        flash('error', implode("\n", $errors));
    }
    Auth::redirect('register.php');
}

$pageTitle = 'Регистрация';
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-form">
    <h3 class="text-center">Регистрация</h3>
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
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Пароль * (мин. 6 символов)</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Подтвердите пароль *</label>
            <input type="password" name="password2" class="form-control" minlength="6" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Зарегистрироваться</button>
    </form>
    <div class="text-center mt-3">
        <a href="vk-login.php" class="btn w-100" style="background:#0077FF;border-color:#0077FF;color:#fff;">
            Зарегистрироваться через VK ID
        </a>
    </div>
    <p class="text-center mt-3"><a href="auth.php" class="text-info">Уже есть аккаунт? Войдите</a></p>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
