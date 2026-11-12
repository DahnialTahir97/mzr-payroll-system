<?php
/**
 * MySQL-backed PHP session storage.
 * Required for stateless/multi-instance container hosting such as Vercel.
 */
final class MzrDatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS mzr_app_sessions (
                session_id VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
                session_data MEDIUMBLOB NOT NULL,
                last_activity INT UNSIGNED NOT NULL,
                PRIMARY KEY (session_id),
                KEY idx_mzr_sessions_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT session_data, last_activity FROM mzr_app_sessions WHERE session_id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return '';
        if ((int)$row['last_activity'] < time() - 7200) {
            $this->destroy($id);
            return '';
        }
        return (string)$row['session_data'];
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO mzr_app_sessions (session_id, session_data, last_activity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                session_data = VALUES(session_data),
                last_activity = VALUES(last_activity)'
        );
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM mzr_app_sessions WHERE session_id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $cutoff = time() - min(max($max_lifetime, 300), 86400);
        $stmt = $this->pdo->prepare('DELETE FROM mzr_app_sessions WHERE last_activity < ?');
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    }
}
