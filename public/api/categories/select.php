<?php
require_once __DIR__ . '/../../../app/bootstrap.php';api_user();$rows=db()->query('SELECT id,nombre,descripcion FROM categorias WHERE activo=TRUE ORDER BY nombre')->fetchAll();api_response(['success'=>true,'data'=>$rows]);
