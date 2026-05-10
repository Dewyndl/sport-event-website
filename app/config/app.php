<?php
define('SITE_NAME', 'Sport Time');
define('BASE_URL', '/');

$game_levels = [
    0 => ['label' => 'Не установлен', 'color' => '#9ca3af', 'class' => 'game-level-default'],
    1 => ['label' => 'Начальный', 'color' => '#38bdf8', 'class' => 'game-level-beginner'],
    2 => ['label' => 'Начально-средний', 'color' => '#facc15', 'class' => 'game-level-beginner-mid'],
    3 => ['label' => 'Средний', 'color' => '#22c55e', 'class' => 'game-level-middle'],
    4 => ['label' => 'Сильный', 'color' => '#ef4444', 'class' => 'game-level-strong'],
    5 => ['label' => 'Супер-сильный', 'color' => '#111111', 'class' => 'game-level-super-strong'],
];

$roles = [
    1 => 'Пользователь',
    2 => 'Продвинутый',
    3 => 'Организатор',
    4 => 'Администратор'
];

$event_statuses = [
    1 => ['label' => 'Планируется', 'class' => 'warning'],
    2 => ['label' => 'Идет регистрация', 'class' => 'success'],
    3 => ['label' => 'Завершено', 'class' => 'primary']
];

$user_statuses = [
    1 => 'Работает',
    2 => 'Заблокирован'
];

$notify_flags = [
    0 => 'Добавление мероприятия',
    1 => 'Удаление мероприятия',
    2 => 'Редактирование мероприятия',
    3 => 'Запись/удаление участников',
    4 => 'Подтверждение оплаты'
];

$event_levels = [
    'Начальный',
    'Начально средний',
    'Средний',
    'Средний 4дев',
    'Средний +сильный 4дев',
    'Сильный 4дев',
    'Мужская тренировка',
    'Женская тренировка',
];

$sport_types = [
    'Волейбол',
    'Футбол',
    'Баскетбол',
    'Настольный теннис',
    'Бадминтон',
    'Хоккей',
    'Плавание',
    'Другое',
];

date_default_timezone_set('Asia/Irkutsk');
