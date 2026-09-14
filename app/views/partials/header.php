<?php
require_login();
$pageTitle = $pageTitle ?? 'MarckStock';
$active = $active ?? '';
$flashes = consume_flashes();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(config_value('nombre_sistema', 'MarckStock')) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="app-shell">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="main-shell">
<?php require __DIR__ . '/topbar.php'; ?>
<main class="content">
<?php foreach ($flashes as $f): ?>
<div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
