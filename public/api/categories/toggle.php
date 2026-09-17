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

$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    api_response([
        'success' => false,
        'mensaje' => 'Categoría inválida.'
    ], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    UPDATE categorias
    SET activo = NOT activo
    WHERE id = :id
    RETURNING activo
");

$stmt->execute([
    ':id' => $id
]);

$activo = $stmt->fetchColumn();

if ($activo === false) {
    api_response([
        'success' => false,
        'mensaje' => 'La categoría no existe.'
    ], 404);
}

api_response([
    'success' => true,
    'mensaje' => $activo
        ? 'Categoría activada.'
        : 'Categoría desactivada.',
    'activo' => (bool)$activo
]);