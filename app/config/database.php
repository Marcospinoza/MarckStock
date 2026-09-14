<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseUrl = getenv('DATABASE_URL') ?: '';

    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL no es válida.');
        }

        $host = $parts['host'];
        $port = $parts['port'] ?? 5432;
        $dbname = ltrim($parts['path'], '/');
        $user = isset($parts['user']) ? urldecode($parts['user']) : '';
        $pass = isset($parts['pass']) ? urldecode($parts['pass']) : '';
    } else {
        $host = getenv('PGHOST') ?: '127.0.0.1';
        $port = (int)(getenv('PGPORT') ?: 5432);
        $dbname = getenv('PGDATABASE') ?: 'marckstock';
        $user = getenv('PGUSER') ?: 'postgres';
        $pass = getenv('PGPASSWORD') ?: '';
    }

    $sslmode = getenv('DB_SSLMODE') ?: 'prefer';
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=%s', $host, $port, $dbname, $sslmode);

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
