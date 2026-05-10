<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="light">
    <title><?= sanitize($pageTitle ?? $siteConfig['site_title'] ?? SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="assets/css/style.css?v=20260508-1" rel="stylesheet">
</head>
<body>
<div class="site-logo-header">
    <div class="container text-center">
        <?php
        $logoFile = $siteConfig['logo_image'] ?? '';
        $logoSrc = $logoFile ? 'assets/uploads/' . $logoFile : 'assets/img/logo.png';
        ?>
        <a href="index.php"><img src="<?= sanitize($logoSrc) ?>" alt="Sport Time" class="site-logo-img"></a>
    </div>
</div>
<nav class="navbar navbar-expand-lg navbar-dark navbar-sport shadow-sm">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Мероприятия</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#rulesModal">Правила</a>
                </li>
            </ul>
            <div class="d-flex flex-column flex-lg-row gap-2 mt-2 mt-lg-0">
                <?php if (Auth::isLoggedIn()): ?>
                    <?php if (Auth::role() >= 3): ?>
                        <a class="btn btn-outline-warning btn-sm" href="add_event.php">+ Мероприятие</a>
                        <a class="btn btn-outline-info btn-sm" href="logs.php">История</a>
                    <?php endif; ?>
                    <?php if (Auth::role() == 4): ?>
                        <a class="btn btn-outline-success btn-sm" href="admin.php">Админка</a>
                    <?php endif; ?>
                    <a class="btn btn-outline-primary btn-sm" href="account.php"><?= sanitize(Auth::fio()) ?></a>
                    <a class="btn btn-outline-danger btn-sm" href="logout.php">Выход</a>
                <?php else: ?>
                    <a class="btn btn-outline-primary btn-sm" href="auth.php">Войти</a>
                    <a class="btn btn-outline-light btn-sm" href="register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= nl2br(sanitize($flash['message'])) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
