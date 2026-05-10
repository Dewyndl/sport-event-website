<?php
require_once __DIR__ . '/../core/bootstrap.php';

$action = $_GET['action'] ?? '';

if ($action === 'link') {
    Auth::requireLogin();
    $_SESSION['vk_action'] = 'link';
} elseif (Auth::isLoggedIn()) {
    Auth::redirect('index.php');
}

header('Location: ' . VKAuth::getAuthUrl());
exit;
