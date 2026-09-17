<?php

require_once __DIR__ . '/../../../app/bootstrap.php';

api_require_admin();

$rows = db()->query("
    SELECT
        id,
        nombre,
        usuario,
        rol,
        activo,
        fecha_creacion,
        ultimo_acceso
    FROM usuarios
    ORDER BY
        CASE WHEN rol = 'ADMIN' THEN 0 ELSE 1 END,
        nombre
")->fetchAll();

api_response([
    'success' => true,
    'data' => $rows
]);