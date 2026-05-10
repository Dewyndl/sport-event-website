<?php
class User {
    public static function getById(int $id): ?array {
        return Database::selectOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public static function getByEmail(string $email): ?array {
        return Database::selectOne("SELECT * FROM users WHERE email = ?", [$email]);
    }

    public static function getAll(): array {
        return Database::select("SELECT * FROM users ORDER BY id DESC");
    }

    public static function create(array $data): int {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        return Database::insert(
            "INSERT INTO users (email, password_hash, fio, phone, game_level) VALUES (?, ?, ?, ?, ?)",
            [$data['email'], $hash, $data['fio'], $data['phone'], intval($data['game_level'] ?? 0)]
        );
    }

    public static function update(int $id, array $data): int {
        return Database::update(
            "UPDATE users SET fio = ?, phone = ?, email = ?, game_level = ? WHERE id = ?",
            [$data['fio'], $data['phone'], $data['email'], intval($data['game_level'] ?? 0), $id]
        );
    }

    public static function updatePassword(int $id, string $newPassword): int {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        return Database::update("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $id]);
    }

    public static function updateRole(int $id, int $role): int {
        return Database::update("UPDATE users SET role = ? WHERE id = ?", [$role, $id]);
    }

    public static function updateStatus(int $id, int $status): int {
        return Database::update("UPDATE users SET status = ? WHERE id = ?", [$status, $id]);
    }

    public static function updateGameLevel(int $id, int $gameLevel): int {
        return Database::update("UPDATE users SET game_level = ? WHERE id = ?", [$gameLevel, $id]);
    }

    public static function updateVkId(int $id, string $vkId): int {
        return Database::update("UPDATE users SET vk_id = ? WHERE id = ?", [$vkId, $id]);
    }

    public static function updateNotifySettings(int $id, string $settings): int {
        return Database::update("UPDATE users SET notify_settings = ? WHERE id = ?", [$settings, $id]);
    }

    public static function getPreviousParticipants(): array {
        return Database::select(
            "SELECT id, fio, phone, email, game_level FROM users ORDER BY fio"
        );
    }

    public static function count(): int {
        return Database::count("SELECT COUNT(*) FROM users");
    }
}
