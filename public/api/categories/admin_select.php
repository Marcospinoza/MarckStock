<?php

require_once __DIR__ . '/../../../app/bootstrap.php';

api_require_admin();

$rows = db()->query("
    SELECT
        id,
        nombre,
        descripcion,
        activo,
        fecha_creacion
    FROM categorias
    ORDER BY nombre
")->fetchAll();

api_response([
    'success' => true,
    'data' => $rows
]);