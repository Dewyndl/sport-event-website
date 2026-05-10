<?php
require_once __DIR__ . '/../core/bootstrap.php';

if (Auth::isLoggedIn()) {
    Auth::redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        if (Auth::login($email, $password)) {
            flash('success', 'Добро пожаловать!');
            Auth::redirect('index.php');
        } else {
            flash('error', 'Неверный телефон/email или пароль, либо аккаунт заблокирован.');
        }
    } else {
        flash('error', 'Заполните все поля.');
    }
    Auth::redirect('auth.php');
}

$pageTitle = 'Вход';
include __DIR__ . '/../templates/header.php';
?>

<div class="auth-form">
    <h3 class="text-center">Вход</h3>
    <form method="post">
        <?= CSRF::field() ?>
        <div class="mb-3">
            <label class="form-label">Телефон или Email</label>
            <input type="text" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Войти</button>
    </form>
    <div class="text-center mt-3">
        <a href="vk-login.php" class="btn btn-outline-primary w-100" style="background:#0077FF;border-color:#0077FF;color:#fff;">
            Войти через VK ID
        </a>
    </div>
    <p class="text-center mt-3"><a href="register.php" class="text-info">Нет аккаунта? Зарегистрируйтесь</a></p>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
