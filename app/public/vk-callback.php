<?php
require_once __DIR__ . '/../core/bootstrap.php';

$code     = $_GET['code'] ?? '';
$state    = $_GET['state'] ?? '';
$deviceId = $_GET['device_id'] ?? '';
$error    = $_GET['error'] ?? '';
$isLink   = ($_SESSION['vk_action'] ?? '') === 'link';
unset($_SESSION['vk_action']);

if ($error || !$code) {
    flash('error', 'Вход через VK отменён.');
    Auth::redirect($isLink ? 'account.php' : 'auth.php');
}

$vkData = VKAuth::handleCallback($code, $state, $deviceId);

if (!$vkData) {
    flash('error', 'Ошибка авторизации через VK. Попробуйте ещё раз.');
    Auth::redirect($isLink ? 'account.php' : 'auth.php');
}

// --- LINK MODE: attach VK ID to already logged-in user ---
if ($isLink && Auth::isLoggedIn()) {
    $existing = Database::selectOne("SELECT id FROM users WHERE vk_id = ?", [$vkData['vk_id']]);
    if ($existing && (int)$existing['id'] !== Auth::id()) {
        flash('error', 'Этот VK аккаунт уже привязан к другому пользователю.');
        Auth::redirect('account.php');
    }
    User::updateVkId(Auth::id(), $vkData['vk_id']);
    flash('success', 'VK аккаунт успешно привязан!');
    Auth::redirect('account.php');
}

// --- LOGIN MODE ---
$user = Database::selectOne("SELECT * FROM users WHERE vk_id = ?", [$vkData['vk_id']]);

if (!$user && $vkData['email']) {
    $user = User::getByEmail($vkData['email']);
    if ($user) {
        User::updateVkId($user['id'], $vkData['vk_id']);
        $user['vk_id'] = $vkData['vk_id'];
    }
}

if ($user) {
    if ($user['status'] == 2) {
        flash('error', 'Ваш аккаунт заблокирован.');
        Auth::redirect('auth.php');
    }
    Auth::loginByUser($user);
    flash('success', 'Добро пожаловать!');
    Auth::redirect('index.php');
}

$_SESSION['vk_pending'] = $vkData;
Auth::redirect('vk-register.php');
