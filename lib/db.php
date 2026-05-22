<?php
/**
 * PDO-Wrapper für MySQL-Verbindung
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone='+01:00', NAMES utf8mb4",
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    } catch (PDOException $e) {
        http_response_code(500);
        if (DEBUG_MODE) {
            echo 'DB-Verbindung fehlgeschlagen: ' . htmlspecialchars($e->getMessage());
        } else {
            echo 'Service vorübergehend nicht erreichbar.';
        }
        exit;
    }
    return $pdo;
}
