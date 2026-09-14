<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();
$pageTitle='Dashboard'; $active='dashboard';
$stats = db()->query("SELECT COUNT(*) total_productos, COALESCE(SUM(stock),0) unidades, COALESCE(SUM(precio*stock),0) valor, COUNT(*) FILTER (WHERE stock <= stock_minimo) stock_bajo, COUNT(*) FILTER (WHERE stock=0) agotados FROM productos")->fetch();
$cats = db()->query("SELECT c.nombre, COUNT(p.id) cantidad FROM categorias c LEFT JOIN productos p ON p.categoria_id=c.id WHERE c.activo=TRUE GROUP BY c.id,c.nombre ORDER BY cantidad DESC, c.nombre LIMIT 6")->fetchAll();
$recent = db()->query("SELECT p.*, c.nombre categoria FROM productos p JOIN categorias c ON c.id=p.categoria_id ORDER BY p.creado_en DESC LIMIT 6")->fetchAll();
require __DIR__ . '/../app/views/partials/header.php';
?>
<div class="page-head"><div><h1>Resumen del inventario</h1><p>Vista general de MarckStock.</p></div><a class="btn btn-primary" href="/product_form.php">+ Nuevo producto</a></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon">📦</div><div><span>Total productos</span><strong><?= (int)$stats['total_productos'] ?></strong><small><?= (int)$stats['unidades'] ?> unidades</small></div></div>
<div class="stat-card"><div class="stat-icon warning">⚠️</div><div><span>Stock bajo</span><strong><?= (int)$stats['stock_bajo'] ?></strong><small><?= (int)$stats['agotados'] ?> agotado(s)</small></div></div>
<div class="stat-card"><div class="stat-icon success">💰</div><div><span>Valor inventario</span><strong><?= e(money($stats['valor'])) ?></strong><small>Precio × stock</small></div></div>
<div class="stat-card"><div class="stat-icon info">🏷️</div><div><span>Categorías activas</span><strong><?= count($cats) ?></strong><small>Clasificación actual</small></div></div>
</div>
<div class="grid-2">
<section class="panel"><div class="panel-head"><h2>Productos recientes</h2><a href="/products.php">Ver todos</a></div><div class="table-wrap"><table><thead><tr><th>Producto</th><th>Categoría</th><th>Stock</th><th>Estado</th></tr></thead><tbody><?php foreach($recent as $p): $st=stock_status($p); ?><tr><td><a href="/product_form.php?id=<?= (int)$p['id'] ?>"><strong><?= e($p['nombre']) ?></strong></a></td><td><?= e($p['categoria']) ?></td><td><?= (int)$p['stock'] ?></td><td><span class="status <?= e($st['class']) ?>"><?= e($st['label']) ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>
<section class="panel"><div class="panel-head"><h2>Productos por categoría</h2></div><div class="bar-list"><?php $max=max(array_column($cats,'cantidad') ?: [1]); foreach($cats as $c): $pct=$max?round(((int)$c['cantidad']/$max)*100):0; ?><div class="bar-item"><div><span><?= e($c['nombre']) ?></span><strong><?= (int)$c['cantidad'] ?></strong></div><div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div></div><?php endforeach; ?></div></section>
</div>
<?php require __DIR__ . '/../app/views/partials/footer.php'; ?>