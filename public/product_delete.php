<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/products.php');
}

verify_csrf();

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('danger', 'Producto inválido.');
    redirect('/products.php');
}

$pdo = db();

/*
|--------------------------------------------------------------------------
| Comprobar si el producto existe
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare('
    SELECT id, nombre
    FROM productos
    WHERE id = :id
');

$stmt->execute([
    'id' => $id
]);

$producto = $stmt->fetch();

if (!$producto) {
    flash('danger', 'Producto no encontrado.');
    redirect('/products.php');
}

/*
|--------------------------------------------------------------------------
| Comprobar movimientos
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare('
    SELECT COUNT(*)
    FROM movimientos_stock
    WHERE producto_id = :id
');

$stmt->execute([
    'id' => $id
]);

$totalMovimientos = (int)$stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| No eliminar productos con historial
|--------------------------------------------------------------------------
*/

if ($totalMovimientos > 0) {

    flash(
        'danger',
        'No se puede eliminar "' .
        $producto['nombre'] .
        '" porque tiene ' .
        $totalMovimientos .
        ' movimiento(s) de inventario registrado(s).'
    );

    redirect('/products.php');
}

/*
|--------------------------------------------------------------------------
| Eliminar
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare('
    DELETE FROM productos
    WHERE id = :id
');

$stmt->execute([
    'id' => $id
]);

flash(
    'success',
    'Producto eliminado correctamente.'
);

redirect('/products.php');