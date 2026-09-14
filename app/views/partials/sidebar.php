<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-mark">📦</div>
        <div><strong>MarckStock</strong><span>Sistema de Inventario</span></div>
    </div>
    <nav>
        <a class="nav-link <?= $active==='dashboard'?'active':'' ?>" href="/dashboard.php"><span>🏠</span> Dashboard</a>
        <a class="nav-link <?= $active==='productos'?'active':'' ?>" href="/products.php"><span>📦</span> Productos</a>
        <a class="nav-link <?= $active==='nuevo'?'active':'' ?>" href="/product_form.php"><span>➕</span> Nuevo producto</a>
        <a class="nav-link <?= $active==='stock'?'active':'' ?>" href="/stock_low.php"><span>⚠️</span> Stock bajo</a>
        <a class="nav-link <?= $active==='buscar'?'active':'' ?>" href="/search.php"><span>🔎</span> Buscar</a>
        <a class="nav-link <?= $active==='reportes'?'active':'' ?>" href="/reports.php"><span>📊</span> Reportes</a>
        <?php if (is_admin()): ?>
            <div class="nav-section">Administración</div>
            <a class="nav-link <?= $active==='categorias'?'active':'' ?>" href="/categories.php"><span>🏷️</span> Categorías</a>
            <a class="nav-link <?= $active==='usuarios'?'active':'' ?>" href="/users.php"><span>👥</span> Usuarios</a>
            <a class="nav-link <?= $active==='config'?'active':'' ?>" href="/settings.php"><span>⚙️</span> Configuración</a>
        <?php endif; ?>
    </nav>
</aside>