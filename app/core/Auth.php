<?php
class Auth {
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(string $email, string $password): bool {
        $user = Database::selectOne("SELECT * FROM users WHERE email = ? OR phone = ?", [$email, $email]);
        if (!$user) return false;
        if ($user['status'] == 2) return false;
        if (!password_verify($password, $user['password_hash'])) return false;

        self::loginByUser($user);
        return true;
    }

    public static function loginByUser(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'] ?? '';
        $_SESSION['fio']     = $user['fio'];
        $_SESSION['phone']   = $user['phone'] ?? '';
        $_SESSION['role']    = $user['role'];
    }

    public static function register(array $data): int {
        return User::create($data);
    }

    public static function logout(): void {
        $_SESSION = [];
        session_destroy();
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            self::redirect('auth.php');
        }
    }

    public static function requireRole(int $minRole): void {
        self::requireLogin();
        if ($_SESSION['role'] < $minRole) {
            http_response_code(403);
            exit('У вас нет доступа к этому разделу.');
        }
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): int {
        return $_SESSION['role'] ?? 0;
    }

    public static function fio(): string {
        return $_SESSION['fio'] ?? '';
    }

    public static function redirect(string $url): void {
        header("Location: " . BASE_URL . $url);
        exit;
    }
}
