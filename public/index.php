<?php
require_once __DIR__ . '/../app/bootstrap.php';
if (current_user()) redirect('/dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $usuario = trim((string)($_POST['usuario'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT * FROM usuarios WHERE usuario = :u LIMIT 1');
    $stmt->execute(['u' => $usuario]);
    $user = $stmt->fetch();
    if ($user && (bool)$user['activo'] && password_verify($password, $user['password_hash'])) {
        login_user($user);
        db()->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id')->execute(['id'=>$user['id']]);
        redirect('/dashboard.php');
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MarckStock - Iniciar sesión</title><link rel="stylesheet" href="/assets/css/style.css"></head>
<body class="login-page">
<div class="login-wrap">
    <section class="login-hero"><div class="hero-icon">📦</div><h1>MarckStock</h1><p>Controla productos, stock, categorías y reportes de tu tienda escolar desde cualquier lugar.</p><div class="hero-feature"><span>✓</span> Inventario sincronizado</div><div class="hero-feature"><span>✓</span> Alertas de stock bajo</div><div class="hero-feature"><span>✓</span> Acceso privado por roles</div></section>
    <section class="login-card"><div class="login-logo">MS</div><h2>Bienvenido</h2><p>Ingresa tus credenciales para continuar.</p><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Usuario</label><input class="form-control" name="usuario" required autocomplete="username" placeholder="Ej. admin"><label>Contraseña</label><input class="form-control" type="password" name="password" required autocomplete="current-password" placeholder="••••••••"><button class="btn btn-primary btn-block">Iniciar sesión</button></form><small class="login-note">Sistema privado · No existe registro público</small></section>
</div></body></html>