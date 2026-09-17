<?php

require_once __DIR__ . '/../../../app/bootstrap.php';

api_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    api_response([
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ], 405);
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$nombre = trim($input['nombre'] ?? '');
$usuario = trim($input['usuario'] ?? '');
$password = (string)($input['password'] ?? '');
$rol = strtoupper(
    trim($input['rol'] ?? 'ENCARGADO')
);

if ($nombre === '') {

    api_response([
        'success' => false,
        'mensaje' => 'El nombre es obligatorio.'
    ], 422);
}

if ($usuario === '') {

    api_response([
        'success' => false,
        'mensaje' => 'El usuario es obligatorio.'
    ], 422);
}

if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $usuario)) {

    api_response([
        'success' => false,
        'mensaje' => 'El usuario debe tener al menos 3 caracteres y solo puede contener letras, números, punto, guion o guion bajo.'
    ], 422);
}

if (strlen($password) < 6) {

    api_response([
        'success' => false,
        'mensaje' => 'La contraseña debe tener al menos 6 caracteres.'
    ], 422);
}

if (!in_array(
    $rol,
    ['ADMIN', 'ENCARGADO'],
    true
)) {

    api_response([
        'success' => false,
        'mensaje' => 'Rol inválido.'
    ], 422);
}

$pdo = db();

$check = $pdo->prepare("
    SELECT id
    FROM usuarios
    WHERE LOWER(usuario) = LOWER(:usuario)
    LIMIT 1
");

$check->execute([
    ':usuario' => $usuario
]);

if ($check->fetch()) {

    api_response([
        'success' => false,
        'mensaje' => 'Ese nombre de usuario ya está registrado.'
    ], 422);
}

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare("
    INSERT INTO usuarios (
        nombre,
        usuario,
        password_hash,
        rol,
        activo
    )
    VALUES (
        :nombre,
        :usuario,
        :password_hash,
        :rol,
        TRUE
    )
    RETURNING id
");

$stmt->execute([
    ':nombre' => $nombre,
    ':usuario' => $usuario,
    ':password_hash' => $passwordHash,
    ':rol' => $rol
]);

$id = $stmt->fetchColumn();

api_response([
    'success' => true,
    'mensaje' => 'Usuario registrado correctamente.',
    'id' => (int)$id
], 201);