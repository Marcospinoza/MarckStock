<?php

require_once __DIR__ . '/../../../app/bootstrap.php';

api_user();

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    api_response([
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ], 405);
}

$d = json_input();

$id = (int)($d['id'] ?? 0);
$nombre = trim((string)($d['nombre'] ?? ''));
$categoria = (int)($d['categoria_id'] ?? 0);
$precio = (float)($d['precio'] ?? -1);
$stockMinimo = (int)($d['stock_minimo'] ?? -1);
$fecha = (string)($d['fecha_registro'] ?? date('Y-m-d'));

if (
    $id <= 0 ||
    $nombre === '' ||
    $categoria <= 0 ||
    $precio < 0 ||
    $stockMinimo < 0
) {
    api_response([
        'success' => false,
        'mensaje' => 'Datos inválidos.'
    ], 422);
}

$pdo = db();

/*
|--------------------------------------------------------------------------
| Verificar producto
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare('
    SELECT id, stock
    FROM productos
    WHERE id = :id
');

$stmt->execute([
    'id' => $id
]);

$producto = $stmt->fetch();

if (!$producto) {
    api_response([
        'success' => false,
        'mensaje' => 'Producto no encontrado.'
    ], 404);
}

/*
|--------------------------------------------------------------------------
| Actualizar datos generales
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| El stock NO se modifica desde este endpoint.
| Las existencias se controlan mediante movimientos de Entrada / Salida.
|
*/

$stmt = $pdo->prepare('
    UPDATE productos
    SET
        nombre = :nombre,
        categoria_id = :categoria,
        precio = :precio,
        stock_minimo = :minimo,
        fecha_registro = :fecha,
        actualizado_en = NOW()
    WHERE id = :id
');

$stmt->execute([
    'nombre' => $nombre,
    'categoria' => $categoria,
    'precio' => $precio,
    'minimo' => $stockMinimo,
    'fecha' => $fecha,
    'id' => $id
]);

api_response([
    'success' => true,
    'mensaje' => 'Producto actualizado correctamente.',
    'stock_actual' => (int)$producto['stock']
]);
