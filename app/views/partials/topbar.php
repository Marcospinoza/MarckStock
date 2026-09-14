<?php
$notifStmt = db()->query('SELECT p.id, p.nombre, p.stock, p.stock_minimo FROM productos p WHERE p.stock <= p.stock_minimo ORDER BY p.stock ASC, p.nombre ASC LIMIT 5');
$notifications = $notifStmt->fetchAll();
$notifCount = (int)db()->query('SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo')->fetchColumn();
$user = current_user();
?>
<header class="topbar">
    <button class="icon-btn sidebar-toggle" type="button" data-toggle-sidebar>☰</button>
    <div class="topbar-title"><?= e($pageTitle) ?></div>
    <div class="top-actions">
        <div class="dropdown">
            <button class="icon-btn bell" type="button" data-dropdown="notifications">🔔<?php if($notifCount>0): ?><span class="badge-count"><?= $notifCount ?></span><?php endif; ?></button>
            <div class="dropdown-menu notifications" id="notifications">
                <div class="dropdown-head"><strong>Notificaciones</strong><span><?= $notifCount ?> alerta(s)</span></div>
                <?php if (!$notifications): ?>
                    <div class="dropdown-empty">Sin alertas de stock.</div>
                <?php else: ?>
                    <?php foreach($notifications as $n): ?>
                    <a class="notification-item" href="/product_form.php?id=<?= (int)$n['id'] ?>">
                        <span class="notification-icon <?= (int)$n['stock']===0?'danger':'warning' ?>">⚠</span>
                        <span><strong><?= e($n['nombre']) ?></strong><small><?= (int)$n['stock']===0?'Producto agotado':'Stock bajo' ?> · <?= (int)$n['stock'] ?> unidades</small></span>
                    </a>
                    <?php endforeach; ?>
                    <a class="dropdown-footer" href="/stock_low.php">Ver todas las alertas →</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="dropdown">
            <button class="user-btn" type="button" data-dropdown="user-menu"><span class="avatar"><?= strtoupper(substr($user['nombre'],0,1)) ?></span><span class="user-text"><strong><?= e($user['nombre']) ?></strong><small><?= e(ucfirst(strtolower($user['rol']))) ?></small></span><span>▾</span></button>
            <div class="dropdown-menu user-menu" id="user-menu">
                <a href="/profile.php">👤 Mi perfil</a>
                <?php if (is_admin()): ?><a href="/settings.php">⚙️ Configuración</a><?php endif; ?>
                <a href="/change_password.php">🔐 Cambiar contraseña</a>
                <div class="divider"></div>
                <a href="/logout.php">🚪 Cerrar sesión</a>
            </div>
        </div>
    </div>
</header>