<?php

namespace App\Core;

use SessionHandlerInterface;
use PDO;

/**
 * Handles storing PHP sessions in the database.
 * Enforces a 24-hour session lifetime.
 */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $db;
    private int $lifetime;

    public function __construct()
    {
        $this->db = Database::getInstance();
        // Set lifetime to 24 hours (86400 seconds)
        $this->lifetime = 86400;
    }

    /**
     * Initialize session.
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Close the session.
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Read session data.
     * @param string $id The session ID
     * @return string|false The session data or empty string
     */
    public function read(string $id): string|false
    {
        $stmt = $this->db->prepare("SELECT payload FROM sessions WHERE id = ? AND last_activity > ?");
        
        // Calculate expiration threshold
        $expiration = time() - $this->lifetime;
        
        if ($stmt->execute([$id, $expiration])) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['payload'];
            }
        }

        return '';
    }

    /**
     * Write session data.
     * @param string $id The session ID
     * @param string $data The serialized session data
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        // Attempt to extract user_id if it exists in the serialized data
        // Note: This is a simplistic regex check. It might not catch all cases but is useful for meta-data.
        $userId = null;
        if (preg_match('/user_id\|i:(\d+);/', $data, $matches)) {
            $userId = (int)$matches[1];
        }

        // UPSERT: Insert or Update on duplicate key
        $sql = "
            INSERT INTO sessions (id, user_id, payload, last_activity) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                payload = VALUES(payload), 
                last_activity = VALUES(last_activity),
                user_id = VALUES(user_id)
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId, $data, time()]);
    }

    /**
     * Destroy a session.
     * @param string $id The session ID
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Garbage Collection: Cleanup old sessions.
     * @param int $max_lifetime Provided by PHP ini, but we override/augment logic
     * @return int|false Number of deleted sessions
     */
    public function gc(int $max_lifetime): int|false
    {
        // We strictly enforce our 24-hour rule here
        $expiration = time() - $this->lifetime;
        
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_activity < ?");
        $stmt->execute([$expiration]);
        
        return $stmt->rowCount();
    }
}