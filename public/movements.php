<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$pageTitle = 'Movimientos de inventario';
$active = 'movimientos';

/*
|--------------------------------------------------------------------------
| Filtros
|--------------------------------------------------------------------------
*/

$tipo = strtoupper(trim((string)($_GET['tipo'] ?? '')));
$productoId = (int)($_GET['producto'] ?? 0);
$desde = trim((string)($_GET['desde'] ?? ''));
$hasta = trim((string)($_GET['hasta'] ?? ''));

$sql = '
    SELECT
        m.id,
        m.tipo,
        m.cantidad,
        m.motivo,
        m.observacion,
        m.stock_anterior,
        m.stock_nuevo,
        m.fecha,

        p.id AS producto_id,
        p.nombre AS producto,

        c.nombre AS categoria,

        u.nombre AS usuario_nombre,
        u.usuario AS usuario_login

    FROM movimientos_stock m

    JOIN productos p
        ON p.id = m.producto_id

    JOIN categorias c
        ON c.id = p.categoria_id

    JOIN usuarios u
        ON u.id = m.usuario_id

    WHERE 1 = 1
';

$params = [];

/*
|--------------------------------------------------------------------------
| Tipo
|--------------------------------------------------------------------------
*/

if (in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
    $sql .= ' AND m.tipo = :tipo';
    $params['tipo'] = $tipo;
}

/*
|--------------------------------------------------------------------------
| Producto
|--------------------------------------------------------------------------
*/

if ($productoId > 0) {
    $sql .= ' AND m.producto_id = :producto';
    $params['producto'] = $productoId;
}

/*
|--------------------------------------------------------------------------
| Fecha desde
|--------------------------------------------------------------------------
*/

if ($desde !== '') {
    $sql .= ' AND m.fecha >= :desde';
    $params['desde'] = $desde . ' 00:00:00';
}

/*
|--------------------------------------------------------------------------
| Fecha hasta
|--------------------------------------------------------------------------
*/

if ($hasta !== '') {
    $sql .= ' AND m.fecha <= :hasta';
    $params['hasta'] = $hasta . ' 23:59:59';
}

$sql .= ' ORDER BY m.fecha DESC, m.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);

$movimientos = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Productos para filtro
|--------------------------------------------------------------------------
*/

$productos = db()
    ->query('
        SELECT id, nombre
        FROM productos
        ORDER BY nombre
    ')
    ->fetchAll();

/*
|--------------------------------------------------------------------------
| Resumen según los filtros actuales
|--------------------------------------------------------------------------
*/

$totalEntradas = 0;
$totalSalidas = 0;

foreach ($movimientos as $movimiento) {

    if ($movimiento['tipo'] === 'ENTRADA') {
        $totalEntradas += (int)$movimiento['cantidad'];
    }

    if ($movimiento['tipo'] === 'SALIDA') {
        $totalSalidas += (int)$movimiento['cantidad'];
    }
}

$totalMovimientos = count($movimientos);

require __DIR__ . '/../app/views/partials/header.php';

?>

<div class="page-head">

    <div>
        <h1>🔄 Movimientos de inventario</h1>

        <p>
            Historial de entradas y salidas de productos de MarckStock.
        </p>
    </div>

    <a
        class="btn btn-primary"
        href="/products.php"
    >
        📦 Ver productos
    </a>

</div>


<!-- RESUMEN -->

<div class="stats-grid">

    <div class="stat-card">

        <div class="stat-icon">
            🔄
        </div>

        <div>
            <span>Total movimientos</span>

            <strong>
                <?= $totalMovimientos ?>
            </strong>
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon">
            📥
        </div>

        <div>
            <span>Unidades ingresadas</span>

            <strong>
                <?= $totalEntradas ?>
            </strong>
        </div>

    </div>


    <div class="stat-card">

        <div class="stat-icon">
            📤
        </div>

        <div>
            <span>Unidades retiradas</span>

            <strong>
                <?= $totalSalidas ?>
            </strong>
        </div>

    </div>

</div>


<section class="panel">

    <!-- FILTROS -->

    <form
        method="get"
        class="filters"
    >

        <!-- Tipo -->

        <select
            name="tipo"
            class="form-control"
        >

            <option value="">
                Todos los movimientos
            </option>

            <option
                value="ENTRADA"
                <?= $tipo === 'ENTRADA'
                    ? 'selected'
                    : ''
                ?>
            >
                📥 Entradas
            </option>

            <option
                value="SALIDA"
                <?= $tipo === 'SALIDA'
                    ? 'selected'
                    : ''
                ?>
            >
                📤 Salidas
            </option>

        </select>


        <!-- Producto -->

        <select
            name="producto"
            class="form-control"
        >

            <option value="0">
                Todos los productos
            </option>

            <?php foreach ($productos as $producto): ?>

                <option
                    value="<?= (int)$producto['id'] ?>"
                    <?= $productoId === (int)$producto['id']
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= e($producto['nombre']) ?>
                </option>

            <?php endforeach; ?>

        </select>


        <!-- Desde -->

        <input
            class="form-control"
            type="date"
            name="desde"
            value="<?= e($desde) ?>"
            title="Fecha desde"
        >


        <!-- Hasta -->

        <input
            class="form-control"
            type="date"
            name="hasta"
            value="<?= e($hasta) ?>"
            title="Fecha hasta"
        >


        <button class="btn btn-secondary">
            Filtrar
        </button>


        <a
            class="btn btn-ghost"
            href="/movements.php"
        >
            Limpiar
        </a>

    </form>


    <!-- TABLA -->

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Antes</th>
                    <th>Después</th>
                    <th>Motivo</th>
                    <th>Usuario</th>
                </tr>

            </thead>


            <tbody>

                <?php if (!$movimientos): ?>

                    <tr>

                        <td
                            colspan="9"
                            class="empty"
                        >
                            Todavía no existen movimientos de inventario.
                        </td>

                    </tr>

                <?php endif; ?>


                <?php foreach ($movimientos as $m): ?>

                    <tr>

                        <!-- Fecha -->

                        <td>
                            <?= e(
                                date(
                                    'd/m/Y H:i',
                                    strtotime($m['fecha'])
                                )
                            ) ?>
                        </td>


                        <!-- Producto -->

                        <td>

                            <strong>
                                <?= e($m['producto']) ?>
                            </strong>

                        </td>


                        <!-- Categoría -->

                        <td>
                            <?= e($m['categoria']) ?>
                        </td>


                        <!-- Tipo -->

                        <td>

                            <?php if ($m['tipo'] === 'ENTRADA'): ?>

                                <span class="status success">
                                    📥 Entrada
                                </span>

                            <?php else: ?>

                                <span class="status danger">
                                    📤 Salida
                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- Cantidad -->

                        <td>

                            <strong>

                                <?= $m['tipo'] === 'ENTRADA'
                                    ? '+'
                                    : '-'
                                ?>

                                <?= (int)$m['cantidad'] ?>

                            </strong>

                        </td>


                        <!-- Stock anterior -->

                        <td>
                            <?= (int)$m['stock_anterior'] ?>
                        </td>


                        <!-- Stock nuevo -->

                        <td>

                            <strong>
                                <?= (int)$m['stock_nuevo'] ?>
                            </strong>

                        </td>


                        <!-- Motivo -->

                        <td>

                            <?= e($m['motivo']) ?>

                            <?php if (!empty($m['observacion'])): ?>

                                <br>

                                <small
                                    title="<?= e($m['observacion']) ?>"
                                >
                                    <?= e($m['observacion']) ?>
                                </small>

                            <?php endif; ?>

                        </td>


                        <!-- Usuario -->

                        <td>

                            <?= e(
                                $m['usuario_nombre']
                                ?: $m['usuario_login']
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>


<?php

require __DIR__ . '/../app/views/partials/footer.php';

?>