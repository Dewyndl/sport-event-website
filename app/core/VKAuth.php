<?php
class VKAuth {
    public static function getAuthUrl(): string {
        $state = bin2hex(random_bytes(16));
        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $_SESSION['vk_oauth_state']   = $state;
        $_SESSION['vk_code_verifier'] = $codeVerifier;

        $params = http_build_query([
            'response_type'         => 'code',
            'client_id'             => VK_APP_ID,
            'redirect_uri'          => VK_REDIRECT_URI,
            'scope'                 => 'email vkid.personal_info',
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);
        return 'https://id.vk.com/authorize?' . $params;
    }

    public static function handleCallback(string $code, string $state, string $deviceId): ?array {
        if (empty($_SESSION['vk_oauth_state']) || $state !== $_SESSION['vk_oauth_state']) {
            return null;
        }
        $codeVerifier = $_SESSION['vk_code_verifier'] ?? '';
        unset($_SESSION['vk_oauth_state'], $_SESSION['vk_code_verifier']);

        $postData = http_build_query([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => VK_REDIRECT_URI,
            'client_id'     => VK_APP_ID,
            'client_secret' => VK_APP_SECRET,
            'code_verifier' => $codeVerifier,
            'device_id'     => $deviceId,
        ]);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'timeout' => 10,
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
            ]
        ]);

        $response = @file_get_contents('https://id.vk.com/oauth2/auth', false, $ctx);
        if (!$response) return null;

        $data = json_decode($response, true);
        if (empty($data['access_token'])) return null;

        $userInfo = self::getUserInfo($data['access_token']);

        return [
            'vk_id' => (string)($userInfo['user_id'] ?? $data['user_id'] ?? ''),
            'email' => $userInfo['email'] ?? $data['email'] ?? null,
        ];
    }

    private static function getUserInfo(string $token): array {
        $response = @file_get_contents(
            'https://id.vk.com/oauth2/user_info',
            false,
            stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'timeout' => 10,
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAuthorization: Bearer " . $token . "\r\n",
                    'content' => http_build_query(['client_id' => VK_APP_ID]),
                ]
            ])
        );
        if (!$response) return [];
        $data = json_decode($response, true);
        return $data['user'] ?? $data ?? [];
    }
}
