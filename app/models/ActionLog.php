<?php
class ActionLog {
    public static function add(?int $userId, ?int $eventId, string $action, string $details = ''): int {
        return Database::insert(
            "INSERT INTO logs (user_id, event_id, action, details) VALUES (?, ?, ?, ?)",
            [$userId, $eventId, $action, $details]
        );
    }

    public static function getAll(int $limit = 100): array {
        return Database::select(
            "SELECT l.*, u.fio as user_fio FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT ?",
            [$limit]
        );
    }

    public static function getByUser(int $userId, int $limit = 100): array {
        return Database::select(
            "SELECT l.*, u.fio as user_fio FROM logs l LEFT JOIN users u ON l.user_id = u.id WHERE l.user_id = ? ORDER BY l.id DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
