<?php
class Database
{
    private $pdo;
    private $dbFile;

    public function __construct($dbFile = null)
    {
        $this->dbFile = $dbFile ?: __DIR__ . '/../data/db.sqlite';
    }

    public function getConnection()
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        $dir = dirname($this->dbFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $dsn = 'sqlite:' . $this->dbFile;
        $this->pdo = new PDO($dsn);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        // run migrations if tables missing
        $this->runMigrations();

        return $this->pdo;
    }

    private function runMigrations()
    {
        $sqlFile = __DIR__ . '/../database.sql';
        if (!file_exists($sqlFile)) {
            return;
        }

        $sql = file_get_contents($sqlFile);
        // split on ; followed by newline to avoid issues with statements
        $statements = preg_split('/;\s*\n/', $sql);
        foreach ($statements as $stmt) {
            $trim = trim($stmt);
            if ($trim === '') continue;
            $this->pdo->exec($trim);
        }
    }
}
