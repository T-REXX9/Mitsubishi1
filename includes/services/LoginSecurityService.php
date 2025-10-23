<?php
namespace Mitsubishi\Services;

/**
 * Login Security Service
 * 
 * Handles login security features including:
 * - Rate limiting for brute force protection
 * - Failed login attempt tracking
 * - Account lockout mechanism
 * - IP-based blocking
 */
class LoginSecurityService
{
    private $pdo;
    
    // Rate limiting constants
    const MAX_ATTEMPTS = 5;              // Max failed attempts before lockout
    const LOCKOUT_DURATION = 900;        // 15 minutes lockout in seconds
    const ATTEMPT_WINDOW = 300;          // 5 minute window for counting attempts
    const IP_MAX_ATTEMPTS = 10;          // Max attempts per IP before blocking
    const IP_LOCKOUT_DURATION = 1800;    // 30 minutes IP lockout
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->createTablesIfNotExist();
    }
    
    /**
     * Create login_attempts table if it doesn't exist
     */
    private function createTablesIfNotExist()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT NULL,
                attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                success TINYINT(1) DEFAULT 0,
                INDEX idx_email (email),
                INDEX idx_ip (ip_address),
                INDEX idx_attempt_time (attempt_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            error_log("Failed to create login_attempts table: " . $e->getMessage());
        }
    }
    
    /**
     * Check if email is currently locked out
     * 
     * @param string $email Email address to check
     * @return array ['locked' => bool, 'remaining_time' => int, 'attempts' => int]
     */
    public function isEmailLockedOut($email)
    {
        try {
            // Get failed attempts within the lockout window
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempt_count, MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE email = ? 
                AND success = 0
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $stmt->execute([$email, self::ATTEMPT_WINDOW]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result['attempt_count'] >= self::MAX_ATTEMPTS) {
                $lastAttemptTime = strtotime($result['last_attempt']);
                $lockoutEnd = $lastAttemptTime + self::LOCKOUT_DURATION;
                $remainingTime = $lockoutEnd - time();
                
                if ($remainingTime > 0) {
                    return [
                        'locked' => true,
                        'remaining_time' => $remainingTime,
                        'attempts' => $result['attempt_count']
                    ];
                }
            }
            
            return [
                'locked' => false,
                'remaining_time' => 0,
                'attempts' => $result['attempt_count']
            ];
            
        } catch (\PDOException $e) {
            error_log("Error checking email lockout: " . $e->getMessage());
            return ['locked' => false, 'remaining_time' => 0, 'attempts' => 0];
        }
    }
    
    /**
     * Check if IP address is currently locked out
     * 
     * @param string $ipAddress IP address to check
     * @return array ['locked' => bool, 'remaining_time' => int, 'attempts' => int]
     */
    public function isIPLockedOut($ipAddress)
    {
        try {
            // Get failed attempts from this IP within the lockout window
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempt_count, MAX(attempt_time) as last_attempt
                FROM login_attempts
                WHERE ip_address = ? 
                AND success = 0
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $stmt->execute([$ipAddress, self::ATTEMPT_WINDOW]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result['attempt_count'] >= self::IP_MAX_ATTEMPTS) {
                $lastAttemptTime = strtotime($result['last_attempt']);
                $lockoutEnd = $lastAttemptTime + self::IP_LOCKOUT_DURATION;
                $remainingTime = $lockoutEnd - time();
                
                if ($remainingTime > 0) {
                    return [
                        'locked' => true,
                        'remaining_time' => $remainingTime,
                        'attempts' => $result['attempt_count']
                    ];
                }
            }
            
            return [
                'locked' => false,
                'remaining_time' => 0,
                'attempts' => $result['attempt_count']
            ];
            
        } catch (\PDOException $e) {
            error_log("Error checking IP lockout: " . $e->getMessage());
            return ['locked' => false, 'remaining_time' => 0, 'attempts' => 0];
        }
    }
    
    /**
     * Record a login attempt
     * 
     * @param string|null $email Email address (null for non-existent accounts)
     * @param bool $success Whether the login was successful
     */
    public function recordAttempt($email, $success)
    {
        try {
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $this->pdo->prepare("
                INSERT INTO login_attempts (email, ip_address, user_agent, success)
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $email,
                $ipAddress,
                $userAgent,
                $success ? 1 : 0
            ]);
            
        } catch (\PDOException $e) {
            error_log("Error recording login attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Clear successful login attempts for an email
     * 
     * @param string $email Email address
     */
    public function clearAttempts($email)
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM login_attempts
                WHERE email = ?
                AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $stmt->execute([$email, self::ATTEMPT_WINDOW]);
            
        } catch (\PDOException $e) {
            error_log("Error clearing login attempts: " . $e->getMessage());
        }
    }
    
    /**
     * Get remaining attempts before lockout
     * 
     * @param string $email Email address
     * @return int Number of remaining attempts
     */
    public function getRemainingAttempts($email)
    {
        $lockoutStatus = $this->isEmailLockedOut($email);
        
        if ($lockoutStatus['locked']) {
            return 0;
        }
        
        return max(0, self::MAX_ATTEMPTS - $lockoutStatus['attempts']);
    }
    
    /**
     * Get client IP address (handles proxies)
     * 
     * @return string Client IP address
     */
    private function getClientIP()
    {
        $ipAddress = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        // If multiple IPs (proxy chain), get the first one
        if (strpos($ipAddress, ',') !== false) {
            $ipAddress = trim(explode(',', $ipAddress)[0]);
        }
        
        return $ipAddress;
    }
    
    /**
     * Clean up old login attempts (should be run periodically)
     */
    public function cleanupOldAttempts()
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM login_attempts
                WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $stmt->execute();
            
        } catch (\PDOException $e) {
            error_log("Error cleaning up login attempts: " . $e->getMessage());
        }
    }
}

