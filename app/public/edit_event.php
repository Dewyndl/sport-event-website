<?php
require_once __DIR__ . '/../core/bootstrap.php';
Auth::requireRole(3);

$id = intval($_GET['id'] ?? 0);
if (!$id) { Auth::redirect('index.php'); }

$event = Event::getById($id);
if (!$event) {
    flash('error', 'Мероприятие не найдено.');
    Auth::redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'sport_type' => trim($_POST['sport_type'] ?? ''),
        'level' => trim($_POST['level'] ?? 'Средний'),
        'place' => trim($_POST['place'] ?? ''),
        'event_date' => $_POST['event_date'] ?? '',
        'event_time' => $_POST['event_time'] ?: null,
        'max_reg_date' => null,
        'max_members' => intval($_POST['max_members'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
        'status' => intval($_POST['status'] ?? 1),
        'adv_payment' => intval($_POST['adv_payment'] ?? 0),
    ];

    if (!$data['name'] || !$data['event_date']) {
        flash('error', 'Заполните обязательные поля.');
        Auth::redirect('edit_event.php?id=' . $id);
    }

    Event::update($id, $data);
    if ($data['max_members'] > 0) {
        $mainCount = Event::mainMemberCount($id);
        $diff = $data['max_members'] - $mainCount;
        if ($diff > 0) {
            Member::promoteReserve($id, $diff);
        } elseif ($diff < 0) {
            Member::demoteExcessMain($id, abs($diff));
        }
    }
    $logText = Auth::fio() . ' отредактировал мероприятие "' . $data['name'] . '"';
    ActionLog::add(Auth::id(), $id, 'event_edit', $logText);
    VKNotifier::notifyIfNeeded(Auth::id(), $event['creator_id'], $logText, 2);

    flash('success', 'Мероприятие обновлено.');
    Auth::redirect('event.php?id=' . $id);
}

$pageTitle = 'Редактирование: ' . $event['name'];
include __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 text-white">Редактирование мероприятия</h4>

<div class="card">
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <div class="mb-3">
                <label class="form-label">Название *</label>
                <input type="text" name="name" class="form-control" value="<?= sanitize($event['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Тип мероприятия</label>
                <select name="sport_type" class="form-select">
                    <option value="">-- не указан --</option>
                    <?php foreach ($sport_types as $st): ?>
                    <option value="<?= sanitize($st) ?>" <?= ($event['sport_type'] ?? '') === $st ? 'selected' : '' ?>><?= sanitize($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Уровень</label>
                <select name="level" class="form-select">
                    <?php foreach ($event_levels as $lv): ?>
                    <option value="<?= sanitize($lv) ?>" <?= ($event['level'] ?? 'Средний') === $lv ? 'selected' : '' ?>><?= sanitize($lv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Место проведения (адрес)</label>
                <input type="text" name="place" class="form-control" value="<?= sanitize($event['place']) ?>">
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Дата мероприятия *</label>
                    <input type="date" name="event_date" class="form-control" value="<?= $event['event_date'] ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Время</label>
                    <input type="time" name="event_time" class="form-control" value="<?= $event['event_time'] ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Макс. участников</label>
                    <input type="number" name="max_members" class="form-control" value="<?= $event['max_members'] ?>" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-select">
                        <?php foreach ($event_statuses as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $event['status'] == $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Предоплата</label>
                    <select name="adv_payment" class="form-select">
                        <option value="0" <?= !$event['adv_payment'] ? 'selected' : '' ?>>Нет</option>
                        <option value="1" <?= $event['adv_payment'] ? 'selected' : '' ?>>Да</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control" rows="4"><?= sanitize($event['description']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-warning">Сохранить изменения</button>
            <a href="event.php?id=<?= $id ?>" class="btn btn-outline-secondary">Отмена</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
