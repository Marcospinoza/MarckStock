<?php
require_once __DIR__ . '/../app/bootstrap.php'; require_login();
$pageTitle='Stock bajo';$active='stock';
$items=db()->query('SELECT p.*,c.nombre categoria FROM productos p JOIN categorias c ON c.id=p.categoria_id WHERE p.stock<=p.stock_minimo ORDER BY p.stock ASC,p.nombre')->fetchAll();
require __DIR__.'/../app/views/partials/header.php';
?>
<div class="page-head"><div><h1>Alertas de stock</h1><p>Productos que necesitan reposición.</p></div><a class="btn btn-secondary" href="/reports.php">Ver reportes</a></div>
<div class="alert-card-large"><div>⚠️</div><div><strong><?= count($items) ?> producto(s) requieren atención</strong><span>Las alertas se generan automáticamente usando stock actual y stock mínimo.</span></div></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Categoría</th><th>Stock actual</th><th>Stock mínimo</th><th>Estado</th><th></th></tr></thead><tbody><?php if(!$items):?><tr><td colspan="6" class="empty">Todo el inventario está por encima del stock mínimo. 🎉</td></tr><?php endif;?><?php foreach($items as $p):$st=stock_status($p);?><tr><td><strong><?= e($p['nombre']) ?></strong></td><td><?= e($p['categoria']) ?></td><td><?= (int)$p['stock'] ?></td><td><?= (int)$p['stock_minimo'] ?></td><td><span class="status <?= e($st['class']) ?>"><?= e($st['label']) ?></span></td><td><a class="btn btn-small btn-primary" href="/product_form.php?id=<?= (int)$p['id'] ?>">Actualizar</a></td></tr><?php endforeach;?></tbody></table></div></section>
<?php require __DIR__.'/../app/views/partials/footer.php'; ?>