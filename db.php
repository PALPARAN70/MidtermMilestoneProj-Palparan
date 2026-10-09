<?php
declare(strict_types=1);

/**
 * The one place that knows how to reach the database.
 * Every page gets the connection through Database::connection().
 */
final class Database
{
    private const HOST    = '127.0.0.1';
    private const NAME    = 'kusina_ng_barangay';
    private const USER    = 'root';
    private const PASS    = '';
    private const CHARSET = 'utf8mb4';

    private static ?PDO $pdo = null;

    private function __construct() {}

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', self::HOST, self::NAME, self::CHARSET);
            self::$pdo = new PDO($dsn, self::USER, self::PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$pdo->exec("SET time_zone = '+08:00'");
        }
        return self::$pdo;
    }
}
