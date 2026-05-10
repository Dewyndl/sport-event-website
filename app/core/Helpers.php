<?php
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function loadConfig(): array {
    $rows = Database::select("SELECT name, value FROM config");
    $config = [];
    foreach ($rows as $row) {
        $config[$row['name']] = $row['value'];
    }
    return $config;
}

function formatDate(string $date): string {
    return date('d.m.Y', strtotime($date));
}

function formatTime(string $time): string {
    return date('H:i', strtotime($time));
}

function getGameLevelMeta(int $level): array {
    $levels = $GLOBALS['game_levels'] ?? [];
    return $levels[$level] ?? $levels[0] ?? ['label' => 'Не установлен', 'color' => '#9ca3af', 'class' => 'game-level-default'];
}

function renderGameLevelDot(int $level, string $extraClass = ''): string {
    $meta = getGameLevelMeta($level);
    $title = sanitize($meta['label']);
    $class = trim('game-level-dot ' . ($meta['class'] ?? '') . ' ' . $extraClass);
    $color = sanitize($meta['color'] ?? '#9ca3af');
    return '<span class="' . $class . '" style="--game-level-color:' . $color . ';background-color:' . $color . ';display:inline-block;width:0.85rem;height:0.85rem;border-radius:50%;flex-shrink:0;vertical-align:middle;" title="' . $title . '" aria-label="' . $title . '"></span>';
}
