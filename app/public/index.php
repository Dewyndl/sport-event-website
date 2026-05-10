<?php
require_once __DIR__ . '/../core/bootstrap.php';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, intval($siteConfig['events_per_page'] ?? 10));
$offset = ($page - 1) * $limit;
$totalEvents = Event::count();
$totalPages = max(1, ceil($totalEvents / $limit));
$events = Event::getAll($offset, $limit);

$pageTitle = $siteConfig['site_title'] ?? SITE_NAME;
include __DIR__ . '/../templates/header.php';
?>

<h4 class="mb-4 text-white">Список мероприятий</h4>

<?php
$sportIcons = [
    'Волейбол'  => '🏐',
    'Футбол'    => '⚽',
    'Баскетбол' => '🏀',
    'Настольный теннис' => '🏓',
    'Бадминтон' => '🏸',
    'Хоккей'    => '🏒',
    'Плавание'  => '🏊',
    'Другое'    => '🎯',
];
?>
<?php if (empty($events)): ?>
    <p class="text-muted">Мероприятий пока нет.</p>
<?php else: ?>
    <div class="event-cards">
        <?php foreach ($events as $event):
            $memberCount = Event::memberCount($event['id']);
            $statusInfo = $GLOBALS['event_statuses'][$event['status']] ?? $GLOBALS['event_statuses'][1];
            $sport = $event['sport_type'] ?? '';
            $icon = $sportIcons[$sport] ?? '🏅';
        ?>
        <div class="event-card">
            <div class="event-card-title">
                <span class="event-card-icon"><?= $icon ?></span>
                <a href="event.php?id=<?= $event['id'] ?>">
                    <?= strtoupper(sanitize($event['name'])) ?>
                    <span class="event-card-status text-<?= $statusInfo['class'] ?>"> [<?= $statusInfo['label'] ?>]</span>
                </a>
            </div>
            <div class="event-card-details">
                <?php if ($sport): ?>
                <div class="event-card-row">
                    <span class="event-card-label">Тип мероприятия:</span>
                    <span><?= sanitize($sport) ?></span>
                </div>
                <?php endif; ?>
                <div class="event-card-row">
                    <span class="event-card-label">Адрес мероприятия:</span>
                    <span><?= sanitize($event['place']) ?></span>
                </div>
                <div class="event-card-row">
                    <span class="event-card-label">Дата проведения:</span>
                    <span><?= $event['event_date'] ?> <?= $event['event_time'] ? formatTime($event['event_time']) : '' ?></span>
                </div>
                <?php if (!empty($event['level'])): ?>
                <div class="event-card-row">
                    <span class="event-card-label">Уровень:</span>
                    <span><?= sanitize($event['level']) ?></span>
                </div>
                <?php endif; ?>
                <div class="event-card-row">
                    <span class="event-card-label">Количество участников:</span>
                    <span><?= $memberCount ?><?= $event['max_members'] ? ' / ' . $event['max_members'] : '' ?></span>
                </div>
            </div>
            <div class="event-card-footer">
                <?php if ($event['status'] == 3): ?>
                    <button class="btn btn-secondary w-100" disabled>Мероприятие завершено</button>
                <?php elseif ($event['status'] == 2): ?>
                    <a href="event.php?id=<?= $event['id'] ?>" class="btn btn-success w-100">Записаться</a>
                <?php else: ?>
                    <a href="event.php?id=<?= $event['id'] ?>" class="btn btn-outline-light w-100">Подробнее</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center flex-wrap">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>">«</a>
            </li>
            <?php endif; ?>

            <?php
            $window = 2;
            $start = max(1, $page - $window);
            $end   = min($totalPages, $page + $window);
            if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>">»</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
