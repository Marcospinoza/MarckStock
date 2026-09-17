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
$nombre = trim($input['nombre'] ?? '');
$descripcion = trim($input['descripcion'] ?? '');

if ($id <= 0) {
    api_response([
        'success' => false,
        'mensaje' => 'Categoría inválida.'
    ], 422);
}

if ($nombre === '') {
    api_response([
        'success' => false,
        'mensaje' => 'El nombre es obligatorio.'
    ], 422);
}

$pdo = db();

$check = $pdo->prepare("
    SELECT id
    FROM categorias
    WHERE LOWER(nombre) = LOWER(:nombre)
      AND id <> :id
    LIMIT 1
");

$check->execute([
    ':nombre' => $nombre,
    ':id' => $id
]);

if ($check->fetch()) {
    api_response([
        'success' => false,
        'mensaje' => 'Ya existe otra categoría con ese nombre.'
    ], 422);
}

$stmt = $pdo->prepare("
    UPDATE categorias
    SET
        nombre = :nombre,
        descripcion = :descripcion
    WHERE id = :id
");

$stmt->execute([
    ':nombre' => $nombre,
    ':descripcion' => $descripcion !== ''
        ? $descripcion
        : null,
    ':id' => $id
]);

if ($stmt->rowCount() === 0) {

    $exists = $pdo->prepare("
        SELECT id
        FROM categorias
        WHERE id = :id
    ");

    $exists->execute([
        ':id' => $id
    ]);

    if (!$exists->fetch()) {
        api_response([
            'success' => false,
            'mensaje' => 'La categoría no existe.'
        ], 404);
    }
}

api_response([
    'success' => true,
    'mensaje' => 'Categoría actualizada correctamente.'
]);