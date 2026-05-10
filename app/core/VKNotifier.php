<?php
class VKNotifier {
    public static function sendMessage(string $vkUserId, string $token, string $message): string {
        $url = "https://api.vk.com/method/messages.send";
        $params = [
            'user_id' => $vkUserId,
            'v' => '5.131',
            'access_token' => $token,
            'random_id' => rand(0, 999999),
            'message' => $message,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: '';
    }

    public static function dispatchAsync(string $vkUserId, string $token, string $message): void {
        $url = "https://api.vk.com/method/messages.send";
        $params = http_build_query([
            'user_id' => $vkUserId,
            'v' => '5.131',
            'access_token' => $token,
            'random_id' => rand(0, 999999),
            'message' => $message,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => 200,
            CURLOPT_CONNECTTIMEOUT_MS => 200,
            CURLOPT_NOSIGNAL => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    public static function notifyIfNeeded(int $currentUserId, int $targetUserId, string $text, int $actionIndex): void {
        if ($currentUserId === $targetUserId) return;

        $target = Database::selectOne("SELECT vk_id, notify_settings FROM users WHERE id = ?", [$targetUserId]);
        if (!$target) return;

        self::sendIfAllowed($currentUserId, $targetUserId, $target, $text, $actionIndex);
    }

    public static function notifyManagers(int $currentUserId, string $text, int $actionIndex): void {
        $managers = Database::select("SELECT id, vk_id, notify_settings FROM users WHERE role >= 3 AND status = 1");
        foreach ($managers as $manager) {
            if ($currentUserId === (int)$manager['id']) continue;
            self::sendIfAllowed($currentUserId, $manager['id'], $manager, $text, $actionIndex);
        }
    }

    private static function sendIfAllowed(int $currentUserId, int $targetUserId, array $target, string $text, int $actionIndex): void {
        $flags = explode('-', $target['notify_settings'] ?? '1-1-1-1-1');
        if (($flags[$actionIndex] ?? '0') !== '1') return;

        $vkId = $target['vk_id'];
        if (!$vkId || !ctype_digit($vkId)) {
            ActionLog::add($currentUserId, null, 'vk_error',
                "Не удалось отправить уведомление: VK ID отсутствует или неверный формат (user_id=$targetUserId)");
            return;
        }

        static $token = null;
        if ($token === null) {
            $config = loadConfig();
            $token = $config['vk_token'] ?? '';
        }
        if (!$token) return;

        self::dispatchAsync($vkId, $token, $text);
    }
}
