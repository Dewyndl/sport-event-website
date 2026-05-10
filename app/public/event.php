<?php
require_once __DIR__ . '/../core/bootstrap.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { Auth::redirect('index.php'); }

$event = Event::getById($id);
if (!$event) {
    flash('error', 'Мероприятие не найдено.');
    Auth::redirect('index.php');
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::isLoggedIn()) {
    CSRF::check();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'register':
            if ($event['status'] != 2) break;
            $existing = Member::findByUserAndEvent(Auth::id(), $id);
            if (!$existing) {
                $mainCount = Event::mainMemberCount($id);
                $isReserve = ($event['max_members'] > 0 && $mainCount >= $event['max_members']) ? 1 : 0;
                Member::add([
                    'user_id' => Auth::id(),
                    'event_id' => $id,
                    'fio' => Auth::fio(),
                    'phone' => $_SESSION['phone'] ?? '',
                    'is_reserve' => $isReserve,
                ]);
                $newCount = Event::mainMemberCount($id);
                if ($isReserve) {
                    $logText = Auth::fio() . ' добавлен в запасные на мероприятие "' . $event['name'] . '". Участников: ' . $newCount . '/' . $event['max_members'];
                    flash('success', 'Мест нет — вы добавлены в список запасных.');
                } else {
                    $logText = Auth::fio() . ' записался на мероприятие "' . $event['name'] . '". Участников: ' . $newCount . '/' . $event['max_members'];
                    flash('success', 'Вы успешно записаны!');
                }
                ActionLog::add(Auth::id(), $id, 'member_add', $logText);
                VKNotifier::notifyIfNeeded(Auth::id(), $event['creator_id'], $logText, 3);
            } else {
                flash('error', 'Вы уже записаны.');
            }
            break;

        case 'unregister':
            $memberId = intval($_POST['member_id'] ?? 0);
            $member = Member::getById($memberId);
            if ($member && $member['event_id'] == $id) {
                $canRemove = false;
                if ($member['user_id'] == Auth::id()) $canRemove = true;
                if (Auth::role() >= 2 && $member['invited_by'] == Auth::id()) $canRemove = true;
                if (Auth::role() >= 3) $canRemove = true;

                if ($canRemove) {
                    $wasMain = ((int)$member['is_reserve'] === 0);
                    Member::remove($memberId);
                    $promoted = null;
                    if ($wasMain && $event['max_members'] > 0) {
                        $promoted = Member::promoteFirstReserve($id);
                    }
                    $newCount = Event::mainMemberCount($id);
                    if ($member['user_id'] == Auth::id()) {
                        $logText = Auth::fio() . ' удалился из мероприятия "' . $event['name'] . '". Участников: ' . $newCount . '/' . $event['max_members'];
                    } else {
                        $logText = Auth::fio() . ' удалил участника "' . $member['fio'] . '" из мероприятия "' . $event['name'] . '". Участников: ' . $newCount . '/' . $event['max_members'];
                    }
                    ActionLog::add(Auth::id(), $id, 'member_remove', $logText);
                    VKNotifier::notifyIfNeeded(Auth::id(), $event['creator_id'], $logText, 3);
                    if ($promoted && $promoted['user_id']) {
                        $promoteLog = 'Участник "' . $promoted['fio'] . '" переведён из запасных в основной список мероприятия "' . $event['name'] . '"';
                        ActionLog::add(Auth::id(), $id, 'member_promoted', $promoteLog);
                        VKNotifier::notifyIfNeeded(Auth::id(), $promoted['user_id'], 'Освободилось место! Вы переведены из запасных в основной список мероприятия "' . $event['name'] . '"', 3);
                    }
                    flash('success', 'Запись удалена.');
                }
            }
            break;

        case 'confirm_payment':
            if (Auth::role() >= 3) {
                $memberId = intval($_POST['member_id'] ?? 0);
                $member = Member::getById($memberId);
                if ($member && $member['event_id'] == $id) {
                    Member::confirmPayment($memberId);
                    $confCount = Event::confirmedMemberCount($id);
                    $logText = Auth::fio() . ' подтвердил оплату "' . $member['fio'] . '" для "' . $event['name'] . '". Оплативших: ' . $confCount . '/' . $event['max_members'];
                    ActionLog::add(Auth::id(), $id, 'payment_confirm', $logText);
                    VKNotifier::notifyIfNeeded(Auth::id(), $event['creator_id'], $logText, 4);
                    flash('success', 'Оплата подтверждена.');
                }
            }
            break;

        case 'unconfirm_payment':
            if (Auth::role() >= 3) {
                $memberId = intval($_POST['member_id'] ?? 0);
                $member = Member::getById($memberId);
                if ($member && $member['event_id'] == $id) {
                    Member::unconfirmPayment($memberId);
                    $logText = Auth::fio() . ' удалил оплату "' . $member['fio'] . '" для "' . $event['name'] . '"';
                    ActionLog::add(Auth::id(), $id, 'payment_unconfirm', $logText);
                    VKNotifier::notifyIfNeeded(Auth::id(), $event['creator_id'], $logText, 4);
                    flash('success', 'Оплата удалена.');
                }
            }
            break;

        case 'delete_event':
            if (Auth::role() >= 3) {
                $logText = Auth::fio() . ' удалил мероприятие "' . $event['name'] . '"';
                ActionLog::add(Auth::id(), $id, 'event_delete', $logText);
                VKNotifier::notifyManagers(Auth::id(), $logText, 1);
                Event::delete($id);
                flash('success', 'Мероприятие удалено.');
                Auth::redirect('index.php');
            }
            break;
    }

    Auth::redirect('event.php?id=' . $id);
}

// Reload data after potential changes
$event = Event::getById($id);
if (!$event) { Auth::redirect('index.php'); }
$memberCount = Event::mainMemberCount($id);
$reserveCount = Event::reserveMemberCount($id);
$members = Member::getMainByEventId($id, (bool) $event['adv_payment']);
$reserveMembers = Member::getReserveByEventId($id);
$isRegistered = Auth::isLoggedIn() ? Member::findByUserAndEvent(Auth::id(), $id) : null;
$statusInfo = $event_statuses[$event['status']] ?? $event_statuses[1];

$pageTitle = $event['name'];
include __DIR__ . '/../templates/header.php';
?>

<div class="card mb-2">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <h4 class="mb-1"><?= sanitize($event['name']) ?></h4>
                <span class="badge bg-<?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
            </div>
            <?php if (Auth::role() >= 3): ?>
            <div class="d-flex gap-2">
                <a href="edit_event.php?id=<?= $id ?>" class="btn btn-outline-warning btn-sm">Редактировать</a>
                <form method="post" class="d-inline">
                    <?= CSRF::field() ?>
                    <input type="hidden" name="action" value="delete_event">
                    <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Удалить мероприятие?">Удалить</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <hr class="my-3">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Тип мероприятия:</strong> <?= sanitize($event['sport_type'] ?? '') ?: '-' ?></p>
                <p><strong>Место:</strong> <?= sanitize($event['place']) ?></p>
                <p><strong>Дата:</strong> <?= formatDate($event['event_date']) ?> <?= $event['event_time'] ? formatTime($event['event_time']) : '' ?></p>
                <?php if (!empty($event['level'])): ?>
                <p><strong>Уровень:</strong> <?= sanitize($event['level']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <p><strong>Участники:</strong> <?= $memberCount ?><?= $event['max_members'] ? ' / ' . $event['max_members'] : '' ?><?= $reserveCount > 0 ? ' <span class="text-warning">+ ' . $reserveCount . ' запасных</span>' : '' ?></p>
                <?php if ($event['max_reg_date']): ?>
                <p><strong>Регистрация до:</strong> <?= formatDate($event['max_reg_date']) ?></p>
                <?php endif; ?>
                <?php if ($event['adv_payment']): ?>
                <p><strong>С предоплатой</strong></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($event['description']): ?>
        <hr>
        <p><?= nl2br(sanitize($event['description'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Registration + Add member buttons -->
<div class="d-flex flex-wrap gap-2 mb-2">
<?php if (Auth::isLoggedIn() && $event['status'] == 2): ?>
    <?php if (!$isRegistered):
        $isFull = $event['max_members'] > 0 && $memberCount >= $event['max_members'];
    ?>
        <form method="post">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="register">
            <?php if ($isFull): ?>
            <button type="submit" class="btn" style="background-color:#ffc107;color:#000;border-radius:12px;font-weight:600;font-size:1rem;">
                Записаться как запасной
            </button>
            <?php else: ?>
            <button type="submit" class="btn btn-success">Записаться на мероприятие</button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
<?php endif; ?>
</div>

<?php if (Auth::isLoggedIn() && $event['status'] == 2 && $isRegistered): ?>
    <div class="alert alert-<?= $isRegistered['is_reserve'] ? 'warning' : 'info' ?> d-flex justify-content-between align-items-center mb-3">
        <span><?= $isRegistered['is_reserve'] ? 'Вы в списке запасных' : 'Вы записаны на это мероприятие' ?></span>
        <form method="post" class="d-inline">
            <?= CSRF::field() ?>
            <input type="hidden" name="action" value="unregister">
            <input type="hidden" name="member_id" value="<?= $isRegistered['id'] ?>">
            <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Отменить запись?">Отменить запись</button>
        </form>
    </div>
<?php elseif (!Auth::isLoggedIn() && $event['status'] == 2): ?>
    <div class="alert alert-warning mb-3">
        <a href="auth.php">Войдите</a> или <a href="register.php">зарегистрируйтесь</a>, чтобы записаться.
    </div>
<?php endif; ?>

<?php if (Auth::role() >= 2 && in_array($event['status'], [1, 2])):
    $previousParticipants = Auth::role() >= 3 ? User::getPreviousParticipants() : [];
?>
    <button class="btn" style="background-color:#000;color:#fff;border-radius:12px;font-weight:600;font-size:1rem;" type="button" data-bs-toggle="collapse" data-bs-target="#addMemberForm">
        + Добавить участника
    </button>
</div>
    <div class="collapse mb-3" id="addMemberForm">
    <div class="card">
        <div class="card-header"><strong>Добавление нового участника</strong></div>
        <div class="card-body">
            <form method="post" action="add_member.php" class="row g-2 align-items-end">
                <?= CSRF::field() ?>
                <input type="hidden" name="event_id" value="<?= $id ?>">
                <?php if (Auth::role() >= 3): ?>
                <div class="col-md-12 mb-2">
                    <label class="form-label">Выбрать из зарегистрированных</label>
                    <div class="position-relative">
                        <input type="text" id="prev-participant-search" class="form-control" placeholder="Поиск по имени..." autocomplete="off">
                        <div id="prev-participant-dropdown" class="list-group position-absolute w-100" style="z-index:1050;display:none;max-height:220px;overflow-y:auto;border-radius:0 0 8px 8px;box-shadow:0 4px 12px rgba(0,0,0,.15);"></div>
                    </div>
                    <select id="prev-participant" class="d-none">
                        <option value="">— Новый участник (гость) —</option>
                        <?php foreach ($previousParticipants as $pp): ?>
                        <option value="<?= $pp['id'] ?>" data-fio="<?= sanitize($pp['fio']) ?>" data-phone="<?= sanitize($pp['phone'] ?? '') ?>" data-email="<?= sanitize($pp['email'] ?? '') ?>"><?= sanitize($pp['fio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if (Auth::role() >= 3): ?>
                <div class="col-md-4">
                <?php else: ?>
                <div class="col-md-7">
                <?php endif; ?>
                    <label class="form-label">ФИО *</label>
                    <input type="text" name="fio" id="add-member-fio" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Телефон</label>
                    <input type="text" name="phone" id="add-member-phone" class="form-control">
                </div>
                <?php if (Auth::role() >= 3): ?>
                <div class="col-md-3">
                    <label class="form-label">Email (если есть аккаунт)</label>
                    <input type="email" name="email" id="add-member-email" class="form-control">
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Добавить</button>
                </div>
            </form>
        </div>
    </div>
    </div>
<?php endif; ?>

<!-- Members list -->
<?php
function renderMembersTable(array $members, array $event, int $startIndex = 1): void { ?>
    <div class="table-responsive">
        <table class="table table-hover members-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ФИО</th>
                    <?php if (Auth::role() >= 3): ?><th>Телефон</th><?php endif; ?>
                    <?php if ($event['adv_payment']): ?><th>Оплата</th><?php endif; ?>
                    <th>Дата записи</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $i => $member): ?>
                <tr>
                    <td><?= $startIndex + $i ?></td>
                    <td>
                        <span class="member-fio-wrap">
                            <?= renderGameLevelDot(intval($member['game_level'] ?? 0), 'member-game-level-dot') ?>
                            <span><?= sanitize($member['fio']) ?></span>
                        </span>
                        <?php if ($member['invited_by']): ?>
                            <?php $inviter = User::getById($member['invited_by']); ?>
                            <small class="text-muted">(добавил <?= sanitize($inviter['fio'] ?? '?') ?>)</small>
                        <?php endif; ?>
                    </td>
                    <?php if (Auth::role() >= 3): ?>
                    <td><?= sanitize($member['phone'] ?? '') ?></td>
                    <?php endif; ?>
                    <?php if ($event['adv_payment']): ?>
                    <td>
                        <?php if ($member['payment_confirmed_at']): ?>
                            <span class="badge bg-success">Оплачено</span>
                            <?php if (Auth::role() >= 3): ?>
                            <form method="post" class="d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="unconfirm_payment">
                                <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm ms-1" title="Отменить оплату">✕</button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">Не оплачено</span>
                            <?php if (Auth::role() >= 3): ?>
                            <form method="post" class="d-inline">
                                <?= CSRF::field() ?>
                                <input type="hidden" name="action" value="confirm_payment">
                                <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                <button type="submit" class="btn btn-outline-success btn-sm ms-1" title="Подтвердить оплату">✓</button>
                            </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td><small><?= date('d.m.Y H:i', strtotime($member['created_at'])) ?></small></td>
                    <td>
                        <?php
                        $canRemove = false;
                        if (Auth::isLoggedIn()) {
                            if ($member['user_id'] == Auth::id()) $canRemove = true;
                            if (Auth::role() >= 2 && $member['invited_by'] == Auth::id()) $canRemove = true;
                            if (Auth::role() >= 3) $canRemove = true;
                        }
                        ?>
                        <?php if ($canRemove): ?>
                        <form method="post" class="d-inline">
                            <?= CSRF::field() ?>
                            <input type="hidden" name="action" value="unregister">
                            <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Удалить участника?" title="Удалить">✕</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<h5 class="mt-4 mb-3 text-white">Участники (<?= $memberCount ?>)</h5>
<?php if (empty($members)): ?>
    <p class="text-muted">Пока никто не записался.</p>
<?php else: ?>
    <?php renderMembersTable($members, $event, 1); ?>
<?php endif; ?>

<?php if (!empty($reserveMembers)): ?>
<h5 class="mt-4 mb-2 text-white">Список запасных (<?= $reserveCount ?>)</h5>
<?php renderMembersTable($reserveMembers, $event, $memberCount + 1); ?>
<?php endif; ?>

<a href="index.php" class="btn btn-outline-light mt-2">← К списку мероприятий</a>

<?php include __DIR__ . '/../templates/footer.php'; ?>
