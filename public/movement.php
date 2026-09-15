<?php

require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$productoId = (int)($_GET['producto'] ?? $_POST['producto_id'] ?? 0);
$tipo = strtoupper(trim((string)($_GET['tipo'] ?? $_POST['tipo'] ?? '')));

if ($productoId <= 0 || !in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
    flash('danger', 'Movimiento inválido.');
    redirect('/products.php');
}

/*
|--------------------------------------------------------------------------
| Buscar producto
|--------------------------------------------------------------------------
*/
$stmt = db()->prepare('
    SELECT
        p.*,
        c.nombre AS categoria
    FROM productos p
    JOIN categorias c
        ON c.id = p.categoria_id
    WHERE p.id = :id
');

$stmt->execute(['id' => $productoId]);
$product = $stmt->fetch();

if (!$product) {
    flash('danger', 'Producto no encontrado.');
    redirect('/products.php');
}

/*
|--------------------------------------------------------------------------
| Motivos disponibles
|--------------------------------------------------------------------------
*/
$motivosEntrada = [
    'Reposición',
    'Compra',
    'Devolución',
    'Ajuste de inventario',
    'Otro'
];

$motivosSalida = [
    'Venta',
    'Consumo',
    'Merma',
    'Producto dañado',
    'Ajuste de inventario',
    'Otro'
];

$motivos = $tipo === 'ENTRADA'
    ? $motivosEntrada
    : $motivosSalida;

/*
|--------------------------------------------------------------------------
| Registrar movimiento
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $motivo = trim((string)($_POST['motivo'] ?? ''));
    $observacion = trim((string)($_POST['observacion'] ?? ''));

    if ($cantidad <= 0) {
        flash('danger', 'La cantidad debe ser mayor que cero.');
    } elseif ($motivo === '') {
        flash('danger', 'Selecciona un motivo.');
    } else {

        $pdo = db();

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | Bloqueamos el producto mientras modificamos el stock
            |--------------------------------------------------------------------------
            |
            | FOR UPDATE evita que dos usuarios cambien el mismo stock al mismo
            | tiempo y terminen generando cantidades incorrectas.
            |
            */

            $stmt = $pdo->prepare('
                SELECT id, nombre, stock
                FROM productos
                WHERE id = :id
                FOR UPDATE
            ');

            $stmt->execute([
                'id' => $productoId
            ]);

            $productoActual = $stmt->fetch();

            if (!$productoActual) {
                throw new RuntimeException(
                    'El producto ya no existe.'
                );
            }

            $stockAnterior = (int)$productoActual['stock'];

            /*
            |--------------------------------------------------------------------------
            | Calcular nuevo stock
            |--------------------------------------------------------------------------
            */

            if ($tipo === 'ENTRADA') {

                $stockNuevo = $stockAnterior + $cantidad;

            } else {

                if ($cantidad > $stockAnterior) {
                    throw new RuntimeException(
                        'Stock insuficiente. Actualmente hay '
                        . $stockAnterior
                        . ' unidades disponibles.'
                    );
                }

                $stockNuevo = $stockAnterior - $cantidad;
            }

            /*
            |--------------------------------------------------------------------------
            | Actualizar producto
            |--------------------------------------------------------------------------
            */

            $update = $pdo->prepare('
                UPDATE productos
                SET
                    stock = :stock,
                    actualizado_en = NOW()
                WHERE id = :id
            ');

            $update->execute([
                'stock' => $stockNuevo,
                'id' => $productoId
            ]);

            /*
            |--------------------------------------------------------------------------
            | Guardar historial
            |--------------------------------------------------------------------------
            */

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
                    :tipo,
                    :cantidad,
                    :motivo,
                    :observacion,
                    :stock_anterior,
                    :stock_nuevo
                )
            ');

            $mov->execute([
                'producto' => $productoId,
                'usuario' => (int)$usuario['id'],
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'motivo' => $motivo,
                'observacion' => $observacion !== ''
                    ? $observacion
                    : null,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo
            ]);

            $pdo->commit();

            $textoTipo = $tipo === 'ENTRADA'
                ? 'Entrada'
                : 'Salida';

            flash(
                'success',
                $textoTipo
                . ' registrada correctamente. Nuevo stock: '
                . $stockNuevo
                . '.'
            );

            redirect('/products.php');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash(
                'danger',
                $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'No se pudo registrar el movimiento.'
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| Página
|--------------------------------------------------------------------------
*/

$pageTitle = $tipo === 'ENTRADA'
    ? 'Registrar entrada'
    : 'Registrar salida';

$active = 'productos';

require __DIR__ . '/../app/views/partials/header.php';

$esEntrada = $tipo === 'ENTRADA';
?>

<div class="page-head">

    <div>

        <h1>
            <?= $esEntrada
                ? '📥 Registrar entrada'
                : '📤 Registrar salida'
            ?>
        </h1>

        <p>
            <?= $esEntrada
                ? 'Agrega unidades al inventario.'
                : 'Retira unidades del inventario.'
            ?>
        </p>

    </div>

    <a
        class="btn btn-ghost"
        href="/products.php"
    >
        ← Volver
    </a>

</div>


<section class="panel form-panel">

    <form
        method="post"
        class="form-grid"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="producto_id"
            value="<?= (int)$productoId ?>"
        >

        <input
            type="hidden"
            name="tipo"
            value="<?= e($tipo) ?>"
        >


        <!-- Producto -->

        <div class="field span-2">

            <label>Producto</label>

            <input
                class="form-control"
                value="<?= e($product['nombre']) ?>"
                readonly
            >

            <small>
                Categoría:
                <?= e($product['categoria']) ?>
            </small>

        </div>


        <!-- Stock actual -->

        <div class="field">

            <label>Stock actual</label>

            <input
                class="form-control"
                type="number"
                value="<?= (int)$product['stock'] ?>"
                readonly
            >

        </div>


        <!-- Tipo -->

        <div class="field">

            <label>Tipo de movimiento</label>

            <input
                class="form-control"
                value="<?= $esEntrada ? 'ENTRADA' : 'SALIDA' ?>"
                readonly
            >

        </div>


        <!-- Cantidad -->

        <div class="field">

            <label>Cantidad *</label>

            <input
                class="form-control"
                type="number"
                name="cantidad"
                min="1"
                <?= !$esEntrada
                    ? 'max="' . (int)$product['stock'] . '"'
                    : ''
                ?>
                required
                value="<?= e(
                    (string)($_POST['cantidad'] ?? '')
                ) ?>"
                placeholder="Ej. 10"
            >

            <?php if (!$esEntrada): ?>

                <small>
                    Máximo disponible:
                    <?= (int)$product['stock'] ?>
                    unidades.
                </small>

            <?php endif; ?>

        </div>


        <!-- Motivo -->

        <div class="field">

            <label>Motivo *</label>

            <select
                class="form-control"
                name="motivo"
                required
            >

                <option value="">
                    Selecciona...
                </option>

                <?php foreach ($motivos as $m): ?>

                    <option
                        value="<?= e($m) ?>"
                        <?= ($_POST['motivo'] ?? '') === $m
                            ? 'selected'
                            : ''
                        ?>
                    >
                        <?= e($m) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- Observación -->

        <div class="field span-2">

            <label>Observación</label>

            <textarea
                class="form-control"
                name="observacion"
                rows="4"
                placeholder="<?= $esEntrada
                    ? 'Ej. Reposición de mercadería...'
                    : 'Ej. Venta realizada en tienda...'
                ?>"
            ><?= e($_POST['observacion'] ?? '') ?></textarea>

        </div>


        <!-- Botones -->

        <div class="form-actions span-2">

            <a
                class="btn btn-ghost"
                href="/products.php"
            >
                Cancelar
            </a>

            <button
                class="btn <?= $esEntrada
                    ? 'btn-primary'
                    : 'btn-secondary'
                ?>"
            >

                <?= $esEntrada
                    ? '📥 Registrar entrada'
                    : '📤 Registrar salida'
                ?>

            </button>

        </div>

    </form>

</section>


<?php
require __DIR__ . '/../app/views/partials/footer.php';
?>