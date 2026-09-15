<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$product = null;

if ($editing) {
    $s = db()->prepare('SELECT * FROM productos WHERE id = :id');
    $s->execute(['id' => $id]);
    $product = $s->fetch();

    if (!$product) {
        flash('danger', 'Producto no encontrado.');
        redirect('/products.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $categoria = (int)($_POST['categoria_id'] ?? 0);
    $precio = (float)($_POST['precio'] ?? -1);
    $min = (int)($_POST['stock_minimo'] ?? -1);
    $fecha = (string)($_POST['fecha_registro'] ?? '');

    // Solo se usa al crear un producto nuevo
    $stockInicial = $editing
        ? (int)$product['stock']
        : (int)($_POST['stock'] ?? -1);

    if (
        $nombre === '' ||
        $categoria <= 0 ||
        $precio < 0 ||
        $min < 0 ||
        $fecha === '' ||
        (!$editing && $stockInicial < 0)
    ) {
        flash('danger', 'Completa correctamente todos los campos.');
    } else {
        $pdo = db();

        try {
            $pdo->beginTransaction();

            if ($editing) {
                // Al editar ya NO se modifica el stock
                $s = $pdo->prepare('
                    UPDATE productos
                    SET nombre = :n,
                        categoria_id = :c,
                        precio = :p,
                        stock_minimo = :m,
                        fecha_registro = :f,
                        actualizado_en = NOW()
                    WHERE id = :id
                ');

                $s->execute([
                    'n' => $nombre,
                    'c' => $categoria,
                    'p' => $precio,
                    'm' => $min,
                    'f' => $fecha,
                    'id' => $id
                ]);

                flash('success', 'Producto actualizado correctamente.');
            } else {
                // Crear producto con stock inicial
                $s = $pdo->prepare('
                    INSERT INTO productos
                        (nombre, categoria_id, precio, stock, stock_minimo, fecha_registro)
                    VALUES
                        (:n, :c, :p, :s, :m, :f)
                    RETURNING id
                ');

                $s->execute([
                    'n' => $nombre,
                    'c' => $categoria,
                    'p' => $precio,
                    's' => $stockInicial,
                    'm' => $min,
                    'f' => $fecha
                ]);

                $nuevoProductoId = (int)$s->fetchColumn();

                // Si tiene stock inicial, registrar movimiento
                if ($stockInicial > 0) {
                    $usuario = current_user();

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
                            :motivo,
                            :observacion,
                            0,
                            :stock_nuevo
                        )
                    ');

                    $mov->execute([
                        'producto' => $nuevoProductoId,
                        'usuario' => (int)$usuario['id'],
                        'cantidad' => $stockInicial,
                        'motivo' => 'Stock inicial',
                        'observacion' => 'Stock registrado al crear el producto.',
                        'stock_nuevo' => $stockInicial
                    ]);
                }

                flash('success', 'Producto registrado correctamente.');
            }

            $pdo->commit();
            redirect('/products.php');

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash('danger', 'No se pudo guardar el producto.');
        }
    }
}

$categories = db()
    ->query('SELECT id, nombre FROM categorias WHERE activo = TRUE ORDER BY nombre')
    ->fetchAll();

$defaultMin = (int)config_value('stock_minimo_default', '5');

$pageTitle = $editing ? 'Editar producto' : 'Nuevo producto';
$active = $editing ? 'productos' : 'nuevo';

require __DIR__ . '/../app/views/partials/header.php';
?>

<div class="page-head">
    <div>
        <h1><?= $editing ? 'Editar producto' : 'Registrar nuevo producto' ?></h1>

        <p>
            <?= $editing
                ? 'Actualiza los datos generales del producto. El stock se administra mediante entradas y salidas.'
                : 'Completa la información del producto.'
            ?>
        </p>
    </div>

    <a class="btn btn-ghost" href="/products.php">
        ← Volver
    </a>
</div>

<section class="panel form-panel">

    <form method="post" class="form-grid">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <?php if ($editing): ?>
            <input
                type="hidden"
                name="id"
                value="<?= $id ?>"
            >
        <?php endif; ?>

        <div class="field span-2">
            <label>Nombre del producto *</label>

            <input
                class="form-control"
                name="nombre"
                required
                value="<?= e($_POST['nombre'] ?? $product['nombre'] ?? '') ?>"
                placeholder="Ej. Cuaderno A4"
            >
        </div>

        <div class="field">
            <label>Categoría *</label>

            <select
                class="form-control"
                name="categoria_id"
                required
            >
                <option value="">Selecciona...</option>

                <?php
                $sel = (int)(
                    $_POST['categoria_id']
                    ?? $product['categoria_id']
                    ?? 0
                );
                ?>

                <?php foreach ($categories as $c): ?>
                    <option
                        value="<?= (int)$c['id'] ?>"
                        <?= $sel === (int)$c['id'] ? 'selected' : '' ?>
                    >
                        <?= e($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Precio *</label>

            <input
                class="form-control"
                type="number"
                step="0.01"
                min="0"
                name="precio"
                required
                value="<?= e((string)(
                    $_POST['precio']
                    ?? $product['precio']
                    ?? ''
                )) ?>"
                placeholder="0.00"
            >
        </div>

        <?php if ($editing): ?>

            <div class="field">
                <label>Stock actual</label>

                <input
                    class="form-control"
                    type="number"
                    value="<?= (int)$product['stock'] ?>"
                    readonly
                >

                <small>
                    El stock se modifica mediante Entrada o Salida.
                </small>
            </div>

        <?php else: ?>

            <div class="field">
                <label>Stock inicial *</label>

                <input
                    class="form-control"
                    type="number"
                    min="0"
                    name="stock"
                    required
                    value="<?= e((string)(
                        $_POST['stock'] ?? 0
                    )) ?>"
                >

                <small>
                    Se registrará automáticamente como una entrada inicial.
                </small>
            </div>

        <?php endif; ?>

        <div class="field">
            <label>Stock mínimo *</label>

            <input
                class="form-control"
                type="number"
                min="0"
                name="stock_minimo"
                required
                value="<?= e((string)(
                    $_POST['stock_minimo']
                    ?? $product['stock_minimo']
                    ?? $defaultMin
                )) ?>"
            >

            <small>
                Genera una alerta cuando el stock sea igual o menor.
            </small>
        </div>

        <div class="field">
            <label>Fecha de registro *</label>

            <input
                class="form-control"
                type="date"
                name="fecha_registro"
                required
                value="<?= e((string)(
                    $_POST['fecha_registro']
                    ?? $product['fecha_registro']
                    ?? date('Y-m-d')
                )) ?>"
            >
        </div>

        <div class="form-actions span-2">

            <a
                class="btn btn-ghost"
                href="/products.php"
            >
                Cancelar
            </a>

            <button class="btn btn-primary">
                <?= $editing
                    ? 'Guardar cambios'
                    : 'Registrar producto'
                ?>
            </button>

        </div>

    </form>

</section>

<?php
require __DIR__ . '/../app/views/partials/footer.php';
?>