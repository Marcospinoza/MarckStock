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
$descripcion = trim($input['descripcion'] ?? '');

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
    LIMIT 1
");

$check->execute([
    ':nombre' => $nombre
]);

if ($check->fetch()) {
    api_response([
        'success' => false,
        'mensaje' => 'Ya existe una categoría con ese nombre.'
    ], 422);
}

$stmt = $pdo->prepare("
    INSERT INTO categorias (
        nombre,
        descripcion,
        activo
    )
    VALUES (
        :nombre,
        :descripcion,
        TRUE
    )
    RETURNING id
");

$stmt->execute([
    ':nombre' => $nombre,
    ':descripcion' => $descripcion !== ''
        ? $descripcion
        : null
]);

$id = $stmt->fetchColumn();

api_response([
    'success' => true,
    'mensaje' => 'Categoría registrada correctamente.',
    'id' => (int)$id
], 201);