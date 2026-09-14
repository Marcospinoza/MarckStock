<?php
require_once __DIR__ . '/../../../app/bootstrap.php';
api_user();

$q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT p.id, p.nombre, p.categoria_id, c.nombre AS categoria,
               p.precio, p.stock, p.stock_minimo, p.fecha_registro,
               CASE
                   WHEN p.stock = 0 THEN 'AGOTADO'
                   WHEN p.stock <= p.stock_minimo THEN 'BAJO'
                   ELSE 'DISPONIBLE'
               END AS estado
        FROM productos p
        JOIN categorias c ON c.id = p.categoria_id";
$params = [];

if ($q !== '') {
    $sql .= ' WHERE p.nombre ILIKE :q OR c.nombre ILIKE :q';
    $params['q'] = '%' . $q . '%';
}

$sql .= ' ORDER BY p.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
api_response(['success' => true, 'data' => $stmt->fetchAll()]);
