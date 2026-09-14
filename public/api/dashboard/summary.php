<?php
require_once __DIR__ . '/../../../app/bootstrap.php';api_user();$r=db()->query("SELECT COUNT(*) total_productos,COALESCE(SUM(stock),0) unidades,COALESCE(SUM(precio*stock),0) valor_inventario,COUNT(*) FILTER(WHERE stock<=stock_minimo) stock_bajo,COUNT(*) FILTER(WHERE stock=0) agotados FROM productos")->fetch();api_response(['success'=>true,'data'=>$r]);
