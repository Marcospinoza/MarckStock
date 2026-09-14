<?php

declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['rol'] ?? '') === 'ADMIN';
}

function require_login(): void
{
    if (!current_user()) {
        redirect('/index.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        require __DIR__ . '/../views/403.php';
        exit;
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'nombre' => $user['nombre'],
        'usuario' => $user['usuario'],
        'rol' => $user['rol'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function api_user(): array
{
    $token = bearer_token();
    if (!$token) {
        api_response(['success' => false, 'mensaje' => 'Token requerido.'], 401);
    }

    $hash = hash('sha256', $token);
    $stmt = db()->prepare('SELECT u.id, u.nombre, u.usuario, u.rol, u.activo FROM api_tokens t JOIN usuarios u ON u.id = t.usuario_id WHERE t.token_hash = :hash AND t.expira_en > NOW() LIMIT 1');
    $stmt->execute(['hash' => $hash]);
    $user = $stmt->fetch();

    if (!$user || !(bool)$user['activo']) {
        api_response(['success' => false, 'mensaje' => 'Token inválido o vencido.'], 401);
    }
    return $user;
}

function api_require_admin(): array
{
    $user = api_user();
    if ($user['rol'] !== 'ADMIN') {
        api_response(['success' => false, 'mensaje' => 'Acceso solo para administrador.'], 403);
    }
    return $user;
}
