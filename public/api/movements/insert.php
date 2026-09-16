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

$productoId = (int)($d['producto_id'] ?? 0);
$tipo = strtoupper(trim((string)($d['tipo'] ?? '')));
$cantidad = (int)($d['cantidad'] ?? 0);
$motivo = trim((string)($d['motivo'] ?? ''));
$observacion = trim((string)($d['observacion'] ?? ''));

if (
    $productoId <= 0 ||
    !in_array($tipo, ['ENTRADA', 'SALIDA'], true) ||
    $cantidad <= 0 ||
    $motivo === ''
) {
    api_response([
        'success' => false,
        'mensaje' => 'Datos del movimiento inválidos.'
    ], 422);
}

$pdo = db();

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Bloquear producto
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare('
        SELECT
            id,
            nombre,
            stock
        FROM productos
        WHERE id = :id
        FOR UPDATE
    ');

    $stmt->execute([
        'id' => $productoId
    ]);

    $producto = $stmt->fetch();

    if (!$producto) {
        $pdo->rollBack();

        api_response([
            'success' => false,
            'mensaje' => 'Producto no encontrado.'
        ], 404);
    }

    $stockAnterior = (int)$producto['stock'];

    /*
    |--------------------------------------------------------------------------
    | Calcular stock nuevo
    |--------------------------------------------------------------------------
    */

    if ($tipo === 'ENTRADA') {

        $stockNuevo = $stockAnterior + $cantidad;

    } else {

        if ($cantidad > $stockAnterior) {

            $pdo->rollBack();

            api_response([
                'success' => false,
                'mensaje' => 'Stock insuficiente.',
                'stock_actual' => $stockAnterior
            ], 422);
        }

        $stockNuevo = $stockAnterior - $cantidad;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar stock
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare('
        UPDATE productos
        SET
            stock = :stock,
            actualizado_en = NOW()
        WHERE id = :id
    ');

    $stmt->execute([
        'stock' => $stockNuevo,
        'id' => $productoId
    ]);

    /*
    |--------------------------------------------------------------------------
    | Registrar movimiento
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare('
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
            :tipo,
            :cantidad,
            :motivo,
            :observacion,
            :anterior,
            :nuevo
        )
        RETURNING id
    ');

    $stmt->execute([
        'producto' => $productoId,
        'usuario' => (int)$user['id'],
        'tipo' => $tipo,
        'cantidad' => $cantidad,
        'motivo' => $motivo,
        'observacion' => $observacion !== ''
            ? $observacion
            : null,
        'anterior' => $stockAnterior,
        'nuevo' => $stockNuevo
    ]);

    $movimientoId = (int)$stmt->fetchColumn();

    $pdo->commit();

    api_response([
        'success' => true,
        'mensaje' => $tipo === 'ENTRADA'
            ? 'Entrada registrada correctamente.'
            : 'Salida registrada correctamente.',
        'movimiento_id' => $movimientoId,
        'producto_id' => $productoId,
        'producto' => $producto['nombre'],
        'tipo' => $tipo,
        'cantidad' => $cantidad,
        'stock_anterior' => $stockAnterior,
        'stock_actual' => $stockNuevo
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    api_response([
        'success' => false,
        'mensaje' => 'No se pudo registrar el movimiento.'
    ], 500);
}