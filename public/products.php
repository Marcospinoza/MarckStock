<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$pageTitle = 'Productos';
$active = 'productos';

$q = trim((string)($_GET['q'] ?? ''));
$cat = (int)($_GET['categoria'] ?? 0);
$estado = (string)($_GET['estado'] ?? '');

$sql = '
    SELECT
        p.*,
        c.nombre AS categoria
    FROM productos p
    JOIN categorias c
        ON c.id = p.categoria_id
    WHERE 1 = 1
';

$params = [];

if ($q !== '') {
    $sql .= ' AND (p.nombre ILIKE :q OR c.nombre ILIKE :q)';
    $params['q'] = '%' . $q . '%';
}

if ($cat > 0) {
    $sql .= ' AND p.categoria_id = :cat';
    $params['cat'] = $cat;
}

if ($estado === 'bajo') {
    $sql .= ' AND p.stock <= p.stock_minimo AND p.stock > 0';
}

if ($estado === 'agotado') {
    $sql .= ' AND p.stock = 0';
}

if ($estado === 'ok') {
    $sql .= ' AND p.stock > p.stock_minimo';
}

$sql .= ' ORDER BY p.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = db()
    ->query('
        SELECT id, nombre
        FROM categorias
        WHERE activo = TRUE
        ORDER BY nombre
    ')
    ->fetchAll();

require __DIR__ . '/../app/views/partials/header.php';
?>

<div class="page-head">
    <div>
        <h1>Gestión de productos</h1>

        <p>
            Administra productos y controla las entradas
            y salidas del inventario.
        </p>
    </div>

    <a
        class="btn btn-primary"
        href="/product_form.php"
    >
        + Nuevo producto
    </a>
</div>

<section class="panel">

    <form class="filters" method="get">

        <input
            class="form-control"
            name="q"
            value="<?= e($q) ?>"
            placeholder="Buscar producto o categoría..."
        >

        <select
            class="form-control"
            name="categoria"
        >
            <option value="0">
                Todas las categorías
            </option>

            <?php foreach ($categories as $c): ?>
                <option
                    value="<?= (int)$c['id'] ?>"
                    <?= $cat === (int)$c['id'] ? 'selected' : '' ?>
                >
                    <?= e($c['nombre']) ?>
                </option>
            <?php endforeach; ?>

        </select>

        <select
            class="form-control"
            name="estado"
        >
            <option value="">
                Todos los estados
            </option>

            <option
                value="ok"
                <?= $estado === 'ok' ? 'selected' : '' ?>
            >
                Disponible
            </option>

            <option
                value="bajo"
                <?= $estado === 'bajo' ? 'selected' : '' ?>
            >
                Stock bajo
            </option>

            <option
                value="agotado"
                <?= $estado === 'agotado' ? 'selected' : '' ?>
            >
                Agotado
            </option>
        </select>

        <button class="btn btn-secondary">
            Filtrar
        </button>

        <a
            class="btn btn-ghost"
            href="/products.php"
        >
            Limpiar
        </a>

    </form>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Mínimo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!$products): ?>

                    <tr>
                        <td
                            colspan="8"
                            class="empty"
                        >
                            No se encontraron productos.
                        </td>
                    </tr>

                <?php endif; ?>

                <?php foreach ($products as $p): ?>

                    <?php $st = stock_status($p); ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($p['nombre']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= e($p['categoria']) ?>
                        </td>

                        <td>
                            <?= e(money($p['precio'])) ?>
                        </td>

                        <td>
                            <strong>
                                <?= (int)$p['stock'] ?>
                            </strong>
                        </td>

                        <td>
                            <?= (int)$p['stock_minimo'] ?>
                        </td>

                        <td>
                            <?= e(
                                date(
                                    'd/m/Y',
                                    strtotime($p['fecha_registro'])
                                )
                            ) ?>
                        </td>

                        <td>
                            <span
                                class="status <?= e($st['class']) ?>"
                            >
                                <?= e($st['label']) ?>
                            </span>
                        </td>

                        <td class="actions">

                            <!-- Entrada -->
                            <a
                                class="btn-icon"
                                href="/movement.php?producto=<?= (int)$p['id'] ?>&tipo=ENTRADA"
                                title="Registrar entrada"
                            >
                                📥
                            </a>

                            <!-- Salida -->
                            <?php if ((int)$p['stock'] > 0): ?>

                                <a
                                    class="btn-icon"
                                    href="/movement.php?producto=<?= (int)$p['id'] ?>&tipo=SALIDA"
                                    title="Registrar salida"
                                >
                                    📤
                                </a>

                            <?php else: ?>

                                <span
                                    class="btn-icon"
                                    title="No hay stock disponible"
                                    style="opacity:.35; cursor:not-allowed;"
                                >
                                    📤
                                </span>

                            <?php endif; ?>

                            <!-- Editar -->
                            <a
                                class="btn-icon"
                                href="/product_form.php?id=<?= (int)$p['id'] ?>"
                                title="Editar producto"
                            >
                                ✏️
                            </a>

                            <!-- Eliminar -->
                            <form
                                method="post"
                                action="/product_delete.php"
                                onsubmit="return confirm(
                                    '¿Eliminar este producto?'
                                )"
                            >

                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrf_token()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$p['id'] ?>"
                                >

                                <button
                                    class="btn-icon danger"
                                    title="Eliminar producto"
                                >
                                    🗑️
                                </button>

                            </form>

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