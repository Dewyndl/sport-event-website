<?php
class Member {
    public static function getById(int $id): ?array {
        return Database::selectOne(
            "SELECT m.*, COALESCE(u.game_level, 0) AS game_level FROM members m LEFT JOIN users u ON u.id = m.user_id WHERE m.id = ?",
            [$id]
        );
    }

    public static function getMainByEventId(int $eventId, bool $advPayment = false): array {
        $order = $advPayment ? "m.payment_confirmed_at IS NULL, m.created_at" : "m.created_at";
        return Database::select(
            "SELECT m.*, COALESCE(u.game_level, 0) AS game_level FROM members m LEFT JOIN users u ON u.id = m.user_id WHERE m.event_id = ? AND m.is_reserve = 0 ORDER BY $order",
            [$eventId]
        );
    }

    public static function getReserveByEventId(int $eventId): array {
        return Database::select(
            "SELECT m.*, COALESCE(u.game_level, 0) AS game_level FROM members m LEFT JOIN users u ON u.id = m.user_id WHERE m.event_id = ? AND m.is_reserve = 1 ORDER BY m.created_at",
            [$eventId]
        );
    }

    public static function findByUserAndEvent(int $userId, int $eventId): ?array {
        return Database::selectOne(
            "SELECT m.*, COALESCE(u.game_level, 0) AS game_level FROM members m LEFT JOIN users u ON u.id = m.user_id WHERE m.user_id = ? AND m.event_id = ?",
            [$userId, $eventId]
        );
    }

    public static function add(array $data): int {
        return Database::insert(
            "INSERT INTO members (user_id, event_id, fio, phone, invited_by, is_reserve) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'] ?? null,
                $data['event_id'],
                $data['fio'],
                $data['phone'] ?? '',
                $data['invited_by'] ?? null,
                $data['is_reserve'] ?? 0,
            ]
        );
    }

    public static function remove(int $id): int {
        return Database::delete("DELETE FROM members WHERE id = ?", [$id]);
    }

    public static function demoteExcessMain(int $eventId, int $excess): void {
        $mains = Database::select(
            "SELECT * FROM members WHERE event_id = ? AND is_reserve = 0 ORDER BY created_at DESC LIMIT ?",
            [$eventId, $excess]
        );
        foreach ($mains as $m) {
            Database::update("UPDATE members SET is_reserve = 1 WHERE id = ?", [$m['id']]);
        }
    }

    public static function promoteReserve(int $eventId, int $limit): void {
        $reserves = Database::select(
            "SELECT * FROM members WHERE event_id = ? AND is_reserve = 1 ORDER BY created_at LIMIT ?",
            [$eventId, $limit]
        );
        foreach ($reserves as $r) {
            Database::update("UPDATE members SET is_reserve = 0 WHERE id = ?", [$r['id']]);
        }
    }

    public static function promoteFirstReserve(int $eventId): ?array {
        $first = Database::selectOne(
            "SELECT * FROM members WHERE event_id = ? AND is_reserve = 1 ORDER BY created_at LIMIT 1",
            [$eventId]
        );
        if ($first) {
            Database::update("UPDATE members SET is_reserve = 0 WHERE id = ?", [$first['id']]);
        }
        return $first ?: null;
    }

    public static function confirmPayment(int $id): int {
        return Database::update(
            "UPDATE members SET payment_confirmed_at = NOW() WHERE id = ?", [$id]
        );
    }

    public static function unconfirmPayment(int $id): int {
        return Database::update(
            "UPDATE members SET payment_confirmed_at = NULL WHERE id = ?", [$id]
        );
    }
}
