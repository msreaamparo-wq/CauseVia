<?php

$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);

    if ($parts === false) {
        throw new RuntimeException('Invalid DATABASE_URL.');
    }

    $host = $parts['host'] ?? '';
    $port = $parts['port'] ?? 5432;
    $dbname = isset($parts['path']) ? ltrim(rawurldecode($parts['path']), '/') : '';
    $user = isset($parts['user']) ? rawurldecode($parts['user']) : '';
    $password = isset($parts['pass']) ? rawurldecode($parts['pass']) : '';
} else {
    $host = getenv('PGHOST') ?: '';
    $port = getenv('PGPORT') ?: '5432';
    $dbname = getenv('PGDATABASE') ?: '';
    $user = getenv('PGUSER') ?: '';
    $password = getenv('PGPASSWORD') ?: '';
}

if (!$host || !$dbname || !$user || !$password) {
    throw new RuntimeException('Database environment variables are not configured.');
}

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    $conn = $pdo;

    if (function_exists('pg_connect')) {
        $pgConnection = $databaseUrl
            ? $databaseUrl
            : "host='$host' port=$port dbname='$dbname' user='$user' password='$password'";

        $dbconn = @pg_connect($pgConnection);

        if (!$dbconn) {
            error_log('PostgreSQL connection failed.');
        }
    }

} catch (PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Database connection failed. Please check the server configuration.');
}
?>