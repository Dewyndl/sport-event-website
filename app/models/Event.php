<?php
class Event {
    public static function getById(int $id): ?array {
        return Database::selectOne("SELECT * FROM events WHERE id = ?", [$id]);
    }

    public static function getAll(int $offset = 0, int $limit = 20): array {
        return Database::select(
            "SELECT * FROM events ORDER BY id DESC LIMIT ?, ?",
            [$offset, $limit]
        );
    }

    public static function create(array $data): int {
        return Database::insert(
            "INSERT INTO events (creator_id, name, sport_type, level, place, event_date, event_time, max_reg_date, max_members, description, status, adv_payment)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['creator_id'], $data['name'],
                $data['sport_type'] ?? '',
                $data['level'] ?? 'Средний',
                $data['place'],
                $data['event_date'], $data['event_time'], $data['max_reg_date'],
                $data['max_members'], $data['description'],
                $data['status'] ?? 1, $data['adv_payment'] ?? 0
            ]
        );
    }

    public static function update(int $id, array $data): int {
        return Database::update(
            "UPDATE events SET name=?, sport_type=?, level=?, place=?, event_date=?, event_time=?, max_reg_date=?, max_members=?, description=?, status=?, adv_payment=? WHERE id=?",
            [
                $data['name'], $data['sport_type'] ?? '',
                $data['level'] ?? 'Средний',
                $data['place'], $data['event_date'],
                $data['event_time'], $data['max_reg_date'], $data['max_members'],
                $data['description'], $data['status'], $data['adv_payment'], $id
            ]
        );
    }

    public static function delete(int $id): int {
        return Database::delete("DELETE FROM events WHERE id = ?", [$id]);
    }

    public static function count(): int {
        return Database::count("SELECT COUNT(*) FROM events");
    }

    public static function memberCount(int $eventId): int {
        return Database::count("SELECT COUNT(*) FROM members WHERE event_id = ?", [$eventId]);
    }

    public static function mainMemberCount(int $eventId): int {
        return Database::count("SELECT COUNT(*) FROM members WHERE event_id = ? AND is_reserve = 0", [$eventId]);
    }

    public static function reserveMemberCount(int $eventId): int {
        return Database::count("SELECT COUNT(*) FROM members WHERE event_id = ? AND is_reserve = 1", [$eventId]);
    }

    public static function confirmedMemberCount(int $eventId): int {
        return Database::count(
            "SELECT COUNT(*) FROM members WHERE event_id = ? AND payment_confirmed_at IS NOT NULL",
            [$eventId]
        );
    }
}
