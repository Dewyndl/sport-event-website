<?php
require_once __DIR__ . '/../core/bootstrap.php';
Auth::requireRole(3);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::check();
    $data = [
        'creator_id' => Auth::id(),
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
        Auth::redirect('add_event.php');
    }

    $eventId = Event::create($data);
    $logText = Auth::fio() . ' добавил мероприятие "' . $data['name'] . '"';
    ActionLog::add(Auth::id(), $eventId, 'event_create', $logText);
    VKNotifier::notifyManagers(Auth::id(), $logText, 0);

    flash('success', 'Мероприятие создано.');
    Auth::redirect('event.php?id=' . $eventId);
}

$pageTitle = 'Новое мероприятие';
include __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 text-white">Новое мероприятие</h4>

<div class="card">
    <div class="card-body">
        <form method="post">
            <?= CSRF::field() ?>
            <div class="mb-3">
                <label class="form-label">Название *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Тип мероприятия</label>
                <select name="sport_type" class="form-select">
                    <option value="">-- не указан --</option>
                    <?php foreach ($sport_types as $st): ?>
                    <option value="<?= sanitize($st) ?>"><?= sanitize($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Уровень</label>
                <select name="level" class="form-select">
                    <?php foreach ($event_levels as $lv): ?>
                    <option value="<?= sanitize($lv) ?>" <?= $lv === 'Средний' ? 'selected' : '' ?>><?= sanitize($lv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Место проведения (адрес)</label>
                <input type="text" name="place" class="form-control">
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Дата мероприятия *</label>
                    <input type="date" name="event_date" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Время</label>
                    <input type="time" name="event_time" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Макс. участников</label>
                    <input type="number" name="max_members" class="form-control" value="15" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Статус</label>
                    <select name="status" class="form-select">
                        <?php foreach ($event_statuses as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Предоплата</label>
                    <select name="adv_payment" class="form-select">
                        <option value="0">Нет</option>
                        <option value="1">Да</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Описание</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Создать мероприятие</button>
            <a href="index.php" class="btn btn-outline-secondary">Отмена</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
