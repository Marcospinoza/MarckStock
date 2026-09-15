<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/auth.php';

function ensure_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = db();
    $exists = $pdo->query("SELECT to_regclass('public.usuarios')")->fetchColumn();
    if (!$exists) {
        $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
        if ($sql === false) throw new RuntimeException('No se pudo leer schema.sql');
        $pdo->exec($sql);
    }
    
    // Migración: tabla de movimientos de inventario
$movimientosExists = $pdo
    ->query("SELECT to_regclass('public.movimientos_stock')")
    ->fetchColumn();

if (!$movimientosExists) {
    $pdo->exec("
        CREATE TABLE movimientos_stock (
            id BIGSERIAL PRIMARY KEY,

            producto_id BIGINT NOT NULL
                REFERENCES productos(id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT,

            usuario_id BIGINT NOT NULL
                REFERENCES usuarios(id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT,

            tipo VARCHAR(10) NOT NULL
                CHECK (tipo IN ('ENTRADA', 'SALIDA')),

            cantidad INTEGER NOT NULL
                CHECK (cantidad > 0),

            motivo VARCHAR(100) NOT NULL,
            observacion VARCHAR(255),

            stock_anterior INTEGER NOT NULL
                CHECK (stock_anterior >= 0),

            stock_nuevo INTEGER NOT NULL
                CHECK (stock_nuevo >= 0),

            fecha TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );

        CREATE INDEX IF NOT EXISTS idx_movimientos_producto
            ON movimientos_stock(producto_id);

        CREATE INDEX IF NOT EXISTS idx_movimientos_usuario
            ON movimientos_stock(usuario_id);

        CREATE INDEX IF NOT EXISTS idx_movimientos_fecha
            ON movimientos_stock(fecha);

        CREATE INDEX IF NOT EXISTS idx_movimientos_tipo
            ON movimientos_stock(tipo);
    ");
}

    $defaults = [
        'nombre_sistema' => getenv('APP_NAME') ?: 'MarckStock',
        'nombre_tienda' => 'Tienda Escolar',
        'moneda' => 'S/',
        'stock_minimo_default' => '5',
        'encargado_tienda' => 'Encargado',
    ];
    foreach ($defaults as $k => $v) {
        $stmt = $pdo->prepare('INSERT INTO configuracion (clave, valor) VALUES (:k, :v) ON CONFLICT (clave) DO NOTHING');
        $stmt->execute(['k' => $k, 'v' => $v]);
    }

    $categories = [
        ['Bebidas', 'Agua, jugos y bebidas'],
        ['Snacks', 'Galletas, papas y bocaditos'],
        ['Útiles escolares', 'Cuadernos, lapiceros y materiales'],
        ['Dulces', 'Caramelos, chocolates y similares'],
        ['Higiene', 'Productos de higiene personal'],
        ['Otros', 'Productos sin categoría específica'],
    ];
    foreach ($categories as [$name, $desc]) {
        $stmt = $pdo->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (:n, :d) ON CONFLICT (nombre) DO NOTHING');
        $stmt->execute(['n' => $name, 'd' => $desc]);
    }

    $countUsers = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if ($countUsers === 0) {
        $adminUser = getenv('ADMIN_USER') ?: 'admin';
        $adminPass = getenv('ADMIN_PASSWORD') ?: 'MarckStock2026!';
        $adminName = getenv('ADMIN_NAME') ?: 'Administrador General';
        $encUser = getenv('ENCARGADO_USER') ?: 'encargado';
        $encPass = getenv('ENCARGADO_PASSWORD') ?: 'Encargado2026!';
        $encName = getenv('ENCARGADO_NAME') ?: 'Encargado Principal';

        $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (:nombre, :usuario, :password_hash, :rol)');
        $stmt->execute(['nombre' => $adminName, 'usuario' => $adminUser, 'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT), 'rol' => 'ADMIN']);
        $stmt->execute(['nombre' => $encName, 'usuario' => $encUser, 'password_hash' => password_hash($encPass, PASSWORD_DEFAULT), 'rol' => 'ENCARGADO']);
    }

    $countProducts = (int)$pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn();
    if ($countProducts === 0) {
        $catMap = [];
        foreach ($pdo->query('SELECT id, nombre FROM categorias') as $row) {
            $catMap[$row['nombre']] = (int)$row['id'];
        }
        $seed = [
            ['Gaseosa Cola', 'Bebidas', 3.50, 5, 10],
            ['Galleta Choco', 'Snacks', 2.00, 3, 8],
            ['Cuaderno A4', 'Útiles escolares', 8.50, 20, 5],
            ['Lapicero Azul', 'Útiles escolares', 1.50, 35, 10],
            ['Chocolate', 'Dulces', 2.50, 0, 5],
        ];
        $stmt = $pdo->prepare('INSERT INTO productos (nombre, categoria_id, precio, stock, stock_minimo, fecha_registro) VALUES (:n, :c, :p, :s, :m, CURRENT_DATE)');
        foreach ($seed as [$n, $c, $p, $s, $m]) {
            if (isset($catMap[$c])) $stmt->execute(['n'=>$n,'c'=>$catMap[$c],'p'=>$p,'s'=>$s,'m'=>$m]);
        }
    }
}

ensure_schema();
