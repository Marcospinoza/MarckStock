<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Solicitud inválida (CSRF).');
    }
}

function config_value(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT valor FROM configuracion WHERE clave = :clave');
    $stmt->execute(['clave' => $key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function set_config_value(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO configuracion (clave, valor, actualizado_en) VALUES (:clave, :valor, NOW()) ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor, actualizado_en = NOW()');
    $stmt->execute(['clave' => $key, 'valor' => $value]);
}

function money(float|string $amount): string
{
    $currency = config_value('moneda', 'S/');
    return $currency . ' ' . number_format((float)$amount, 2);
}

function stock_status(array $product): array
{
    $stock = (int)$product['stock'];
    $min = (int)$product['stock_minimo'];
    if ($stock <= 0) {
        return ['label' => 'Agotado', 'class' => 'danger'];
    }
    if ($stock <= $min) {
        return ['label' => 'Stock bajo', 'class' => 'warning'];
    }
    return ['label' => 'Disponible', 'class' => 'success'];
}

function json_input(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}

function api_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
