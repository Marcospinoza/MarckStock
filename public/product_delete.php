<?php
require_once __DIR__ . '/../app/bootstrap.php'; require_login();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('/products.php'); verify_csrf();
$id=(int)($_POST['id']??0); if($id>0){db()->prepare('DELETE FROM productos WHERE id=:id')->execute(['id'=>$id]);flash('success','Producto eliminado.');}
redirect('/products.php');
