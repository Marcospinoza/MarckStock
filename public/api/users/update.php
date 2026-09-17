<?php

require_once __DIR__ . '/../../../app/bootstrap.php';

api_require_admin();

if (!in_array(
    $_SERVER['REQUEST_METHOD'],
    ['POST', 'PUT'],
    true
)) {

    api_response([
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ], 405);
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$id = (int)($input['id'] ?? 0);

$nombre = trim(
    $input['nombre'] ?? ''
);

$usuario = trim(
    $input['usuario'] ?? ''
);

$rol = strtoupper(
    trim($input['rol'] ?? '')
);

$password = (string)(
    $input['password'] ?? ''
);

if ($id <= 0) {

    api_response([
        'success' => false,
        'mensaje' => 'Usuario inválido.'
    ], 422);
}

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
        'mensaje' => 'El nombre de usuario no es válido.'
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

if ($password !== ''
    && strlen($password) < 6) {

    api_response([
        'success' => false,
        'mensaje' => 'La nueva contraseña debe tener al menos 6 caracteres.'
    ], 422);
}

$pdo = db();

$usuarioActual = $pdo->prepare("
    SELECT
        id,
        rol,
        activo
    FROM usuarios
    WHERE id = :id
");

$usuarioActual->execute([
    ':id' => $id
]);

$actual = $usuarioActual->fetch();

if (!$actual) {

    api_response([
        'success' => false,
        'mensaje' => 'El usuario no existe.'
    ], 404);
}

/*
 * Si estamos quitándole el rol ADMIN a un
 * administrador activo, comprobamos que no
 * sea el último administrador disponible.
 */
if (
    $actual['rol'] === 'ADMIN'
    && $actual['activo']
    && $rol !== 'ADMIN'
) {

    $adminsActivos = (int)$pdo
        ->query("
            SELECT COUNT(*)
            FROM usuarios
            WHERE rol = 'ADMIN'
              AND activo = TRUE
        ")
        ->fetchColumn();

    if ($adminsActivos <= 1) {

        api_response([
            'success' => false,
            'mensaje' => 'No puedes quitar el rol al último administrador activo.'
        ], 422);
    }
}

$check = $pdo->prepare("
    SELECT id
    FROM usuarios
    WHERE LOWER(usuario) = LOWER(:usuario)
      AND id <> :id
    LIMIT 1
");

$check->execute([
    ':usuario' => $usuario,
    ':id' => $id
]);

if ($check->fetch()) {

    api_response([
        'success' => false,
        'mensaje' => 'Ese nombre de usuario ya está siendo utilizado.'
    ], 422);
}

if ($password !== '') {

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET
            nombre = :nombre,
            usuario = :usuario,
            rol = :rol,
            password_hash = :password_hash
        WHERE id = :id
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':usuario' => $usuario,
        ':rol' => $rol,
        ':password_hash' => $passwordHash,
        ':id' => $id
    ]);

} else {

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET
            nombre = :nombre,
            usuario = :usuario,
            rol = :rol
        WHERE id = :id
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':usuario' => $usuario,
        ':rol' => $rol,
        ':id' => $id
    ]);
}

api_response([
    'success' => true,
    'mensaje' => 'Usuario actualizado correctamente.'
]);