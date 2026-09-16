<?php

require_once __DIR__ . '/../../../app/bootstrap.php';

$user = api_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response([
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ], 405);
}

$d = json_input();

$nombre = trim((string)($d['nombre'] ?? ''));
$categoria = (int)($d['categoria_id'] ?? 0);
$precio = (float)($d['precio'] ?? -1);
$stockInicial = (int)($d['stock'] ?? 0);

$stockMinimo = (int)(
    $d['stock_minimo']
    ?? config_value('stock_minimo_default', '5')
);

$fecha = (string)(
    $d['fecha_registro']
    ?? date('Y-m-d')
);

if (
    $nombre === '' ||
    $categoria <= 0 ||
    $precio < 0 ||
    $stockInicial < 0 ||
    $stockMinimo < 0
) {
    api_response([
        'success' => false,
        'mensaje' => 'Datos inválidos.'
    ], 422);
}

$pdo = db();

try {

    $pdo->beginTransaction();

    // Crear producto
    $stmt = $pdo->prepare('
        INSERT INTO productos
        (
            nombre,
            categoria_id,
            precio,
            stock,
            stock_minimo,
            fecha_registro
        )
        VALUES
        (
            :nombre,
            :categoria,
            :precio,
            :stock,
            :minimo,
            :fecha
        )
        RETURNING id
    ');

    $stmt->execute([
        'nombre' => $nombre,
        'categoria' => $categoria,
        'precio' => $precio,
        'stock' => $stockInicial,
        'minimo' => $stockMinimo,
        'fecha' => $fecha
    ]);

    $productoId = (int)$stmt->fetchColumn();

    // Registrar stock inicial como movimiento
    if ($stockInicial > 0) {

        $mov = $pdo->prepare('
            INSERT INTO movimientos_stock
            (
                producto_id,
                usuario_id,
                tipo,
                cantidad,
                motivo,
                observacion,
                stock_anterior,
                stock_nuevo
            )
            VALUES
            (
                :producto,
                :usuario,
                \'ENTRADA\',
                :cantidad,
                \'Stock inicial\',
                \'Producto registrado desde Android\',
                0,
                :stock_nuevo
            )
        ');

        $mov->execute([
            'producto' => $productoId,
            'usuario' => (int)$user['id'],
            'cantidad' => $stockInicial,
            'stock_nuevo' => $stockInicial
        ]);
    }

    $pdo->commit();

    api_response([
        'success' => true,
        'mensaje' => 'Producto registrado correctamente.',
        'id' => $productoId,
        'stock' => $stockInicial
    ], 201);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_response([
        'success' => false,
        'mensaje' => 'No se pudo registrar el producto.'
    ], 500);
}
