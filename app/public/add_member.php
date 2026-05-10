<?php
require_once __DIR__ . '/../core/bootstrap.php';
Auth::requireRole(2);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Auth::redirect('index.php');
}

CSRF::check();

$eventId = intval($_POST['event_id'] ?? 0);
$fio = trim($_POST['fio'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

if (!$eventId || !$fio) {
    flash('error', 'Укажите ФИО участника.');
    Auth::redirect('event.php?id=' . $eventId);
}

$event = Event::getById($eventId);
if (!$event) {
    flash('error', 'Мероприятие не найдено.');
    Auth::redirect('index.php');
}

$userId = null;
if ($email) {
    $account = User::getByEmail($email);
    if ($account) {
        $userId = $account['id'];
        $fio = $account['fio'];

        $existing = Member::findByUserAndEvent($userId, $eventId);
        if ($existing) {
            flash('error', 'Этот пользователь уже записан.');
            Auth::redirect('event.php?id=' . $eventId);
        }
    }
}

$mainCount = Event::mainMemberCount($eventId);
$isReserve = ($event['max_members'] > 0 && $mainCount >= $event['max_members']) ? 1 : 0;

Member::add([
    'user_id' => $userId,
    'event_id' => $eventId,
    'fio' => $fio,
    'phone' => $phone,
    'invited_by' => Auth::id(),
    'is_reserve' => $isReserve,
]);

$memberCount = Event::mainMemberCount($eventId);
$logText = Auth::fio() . ' добавил участника "' . $fio . '" в ' . ($isReserve ? 'запасные' : 'основной список') . ' мероприятия "' . $event['name'] . '". Участников: ' . $memberCount . '/' . $event['max_members'];
ActionLog::add(Auth::id(), $eventId, 'member_add_by_organizer', $logText);
VKNotifier::notifyIfNeeded(Auth::id(), $event['creator_id'], $logText, 3);

flash('success', $isReserve ? 'Участник добавлен в список запасных.' : 'Участник добавлен.');
Auth::redirect('event.php?id=' . $eventId);
