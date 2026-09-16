<?php

require_once __DIR__ . '/../../../app/bootstrap.php';

api_user();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_response([
        'success' => false,
        'mensaje' => 'Método no permitido.'
    ], 405);
}

/*
|--------------------------------------------------------------------------
| Filtros opcionales
|--------------------------------------------------------------------------
*/

$productoId = (int)($_GET['producto_id'] ?? 0);
$tipo = strtoupper(trim((string)($_GET['tipo'] ?? '')));
$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));

$sql = '
    SELECT
        m.id,
        m.producto_id,
        p.nombre AS producto,
        c.nombre AS categoria,

        m.tipo,
        m.cantidad,
        m.motivo,
        m.observacion,

        m.stock_anterior,
        m.stock_nuevo,

        m.fecha,

        u.id AS usuario_id,
        u.nombre AS usuario_nombre,
        u.usuario AS usuario

    FROM movimientos_stock m

    INNER JOIN productos p
        ON p.id = m.producto_id

    INNER JOIN categorias c
        ON c.id = p.categoria_id

    INNER JOIN usuarios u
        ON u.id = m.usuario_id

    WHERE 1 = 1
';

$params = [];

/*
|--------------------------------------------------------------------------
| Producto
|--------------------------------------------------------------------------
*/

if ($productoId > 0) {

    $sql .= '
        AND m.producto_id = :producto
    ';

    $params['producto'] = $productoId;
}

/*
|--------------------------------------------------------------------------
| Tipo
|--------------------------------------------------------------------------
*/

if (in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {

    $sql .= '
        AND m.tipo = :tipo
    ';

    $params['tipo'] = $tipo;
}

/*
|--------------------------------------------------------------------------
| Fecha desde
|--------------------------------------------------------------------------
*/

if ($desde !== '') {

    $sql .= '
        AND m.fecha >= :desde
    ';

    $params['desde'] = $desde . ' 00:00:00';
}

/*
|--------------------------------------------------------------------------
| Fecha hasta
|--------------------------------------------------------------------------
*/

if ($hasta !== '') {

    $sql .= '
        AND m.fecha <= :hasta
    ';

    $params['hasta'] = $hasta . ' 23:59:59';
}

/*
|--------------------------------------------------------------------------
| Orden
|--------------------------------------------------------------------------
*/

$sql .= '
    ORDER BY m.fecha DESC, m.id DESC
';

$stmt = db()->prepare($sql);
$stmt->execute($params);

$movimientos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Convertir valores numéricos
|--------------------------------------------------------------------------
*/

foreach ($movimientos as &$m) {

    $m['id'] = (int)$m['id'];

    $m['producto_id'] = (int)$m['producto_id'];

    $m['usuario_id'] = (int)$m['usuario_id'];

    $m['cantidad'] = (int)$m['cantidad'];

    $m['stock_anterior'] = (int)$m['stock_anterior'];

    $m['stock_nuevo'] = (int)$m['stock_nuevo'];
}

unset($m);

/*
|--------------------------------------------------------------------------
| Totales
|--------------------------------------------------------------------------
*/

$totalEntradas = 0;
$totalSalidas = 0;

foreach ($movimientos as $m) {

    if ($m['tipo'] === 'ENTRADA') {
        $totalEntradas += $m['cantidad'];
    }

    if ($m['tipo'] === 'SALIDA') {
        $totalSalidas += $m['cantidad'];
    }
}

/*
|--------------------------------------------------------------------------
| Respuesta
|--------------------------------------------------------------------------
*/

api_response([
    'success' => true,

    'total' => count($movimientos),

    'resumen' => [
        'entradas' => $totalEntradas,
        'salidas' => $totalSalidas
    ],

    'data' => $movimientos
]);