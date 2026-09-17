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

$id = (int)(
    $input['id'] ?? 0
);

if ($id <= 0) {

    api_response([
        'success' => false,
        'mensaje' => 'Usuario inválido.'
    ], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT
        id,
        nombre,
        usuario,
        rol,
        activo
    FROM usuarios
    WHERE id = :id
");

$stmt->execute([
    ':id' => $id
]);

$usuario = $stmt->fetch();

if (!$usuario) {

    api_response([
        'success' => false,
        'mensaje' => 'El usuario no existe.'
    ], 404);
}

/*
 * Si se intenta desactivar un ADMIN,
 * comprobamos que no sea el último
 * administrador activo.
 */
if (
    $usuario['rol'] === 'ADMIN'
    && $usuario['activo']
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
            'mensaje' => 'No puedes desactivar al último administrador activo.'
        ], 422);
    }
}

$update = $pdo->prepare("
    UPDATE usuarios
    SET activo = NOT activo
    WHERE id = :id
    RETURNING activo
");

$update->execute([
    ':id' => $id
]);

$nuevoEstado = $update->fetchColumn();

api_response([
    'success' => true,
    'mensaje' => $nuevoEstado
        ? 'Usuario activado correctamente.'
        : 'Usuario desactivado correctamente.',
    'activo' => (bool)$nuevoEstado
]);