<?php
include_once 'db_conn.php';

class AccountsOperations {
    private $db;
    private $lastError = '';

    public function __construct() {
        global $connect;
        $this->db = $connect;
    }

    public function getLastError() {
        return $this->lastError;
    }

    private function tableHasColumn(string $table, string $column): bool {
        try {
            // Use consistent lowercase table name to avoid Linux case-sensitivity issues
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `accounts` LIKE :col");
            $stmt->execute([':col' => $column]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If we cannot verify, assume column might not exist
            return false;
        }
    }

    private function backtick(string $identifier): string {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
    
    // Create new account
    public function createAccount($data) {
        $this->lastError = '';

        try {
            // Sanitize and normalize inputs
            $username   = isset($data['username']) ? trim((string)$data['username']) : '';
            $email      = isset($data['email']) ? trim((string)$data['email']) : '';
            $password   = isset($data['password']) ? (string)$data['password'] : '';
            $role       = isset($data['role']) ? trim((string)$data['role']) : '';
            $first_name = isset($data['first_name']) ? trim((string)$data['first_name']) : '';
            $last_name  = isset($data['last_name']) ? trim((string)$data['last_name']) : '';

            // Validate required fields
            if ($username === '' || $email === '' || $password === '' || $role === '' || $first_name === '' || $last_name === '') {
                $this->lastError = 'All fields are required.';
                return false;
            }

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->lastError = 'Invalid email address.';
                return false;
            }

            // Enforce allowed roles (Admin, SalesAgent)
            $allowedRoles = ['Admin', 'SalesAgent'];
            if (!in_array($role, $allowedRoles, true)) {
                $this->lastError = 'Invalid role. Allowed roles are Admin and SalesAgent.';
                return false;
            }

            // Basic password policy: min 8 chars, with letters and numbers
            if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $this->lastError = 'Password must be at least 8 characters and include letters and numbers.';
                return false;
            }

            // Check duplicates (case-insensitive username/email if DB collation allows; still check explicitly)
            $dupStmt = $this->db->prepare("SELECT
                    SUM(CASE WHEN Username = :u THEN 1 ELSE 0 END) AS ucnt,
                    SUM(CASE WHEN Email = :e THEN 1 ELSE 0 END) AS ecnt
                FROM `accounts`");
            $dupStmt->execute([':u' => $username, ':e' => $email]);
            $dup = $dupStmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($dup['ucnt'])) {
                $this->lastError = 'Username already exists.';
                return false;
            }
            if (!empty($dup['ecnt'])) {
                $this->lastError = 'Email already exists.';
                return false;
            }

            // Build INSERT dynamically based on available columns
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $status = 'Active';

            $columns = ['Username','Email','PasswordHash','Role','FirstName','LastName'];
            $values  = [':username', ':email', ':password_hash', ':role', ':first_name', ':last_name'];

            $hasStatus       = $this->tableHasColumn('Accounts', 'Status');
            $hasCreatedAt    = $this->tableHasColumn('Accounts', 'CreatedAt');
            $hasUpdatedAt    = $this->tableHasColumn('Accounts', 'UpdatedAt');
            $hasLastLoginAt  = $this->tableHasColumn('Accounts', 'LastLoginAt');

            // Only set Status for Customer accounts; many schemas restrict Status via ENUM (Pending/Approved/Rejected)
            $includeStatus = $hasStatus && $role === 'Customer';
            if ($includeStatus) {
                $columns[] = 'Status';
                $values[]  = ':status';
            }
            // Use CURRENT_TIMESTAMP for CreatedAt/UpdatedAt if present
            if ($hasCreatedAt) {
                $columns[] = 'CreatedAt';
                $values[]  = 'CURRENT_TIMESTAMP';
            }
            if ($hasUpdatedAt) {
                $columns[] = 'UpdatedAt';
                $values[]  = 'CURRENT_TIMESTAMP';
            }
            if ($hasLastLoginAt) {
                $columns[] = 'LastLoginAt';
                $values[]  = 'NULL';
            }

            $colsSql = implode(', ', array_map([$this, 'backtick'], $columns));
            $valsSql = implode(', ', $values);
            $sql = "INSERT INTO `accounts` ($colsSql) VALUES ($valsSql)";
            $stmt = $this->db->prepare($sql);

            // Bind common params
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            if ($includeStatus) {
                $stmt->bindParam(':status', $status);
            }

            $ok = $stmt->execute();
            if (!$ok) {
                $this->lastError = 'Failed to create account.';
            }
            return $ok;
        } catch (PDOException $e) {
            // Map common MySQL errors to admin-friendly messages
            $info = $e->errorInfo;
            if (is_array($info) && isset($info[1])) {
                $code = (int)$info[1];
                if ($code === 1062) {
                    $msg = isset($info[2]) ? $info[2] : '';
                    if (stripos($msg, 'Username') !== false) {
                        $this->lastError = 'Username already exists.';
                    } elseif (stripos($msg, 'Email') !== false) {
                        $this->lastError = 'Email already exists.';
                    } else {
                        $this->lastError = 'Duplicate entry.';
                    }
                } elseif ($code === 1364) {
                    $this->lastError = 'Database constraint error. A required field has no default value.';
                } elseif ($code === 1048) {
                    $this->lastError = 'Database constraint error. A required field was empty.';
                } elseif ($code === 1146) {
                    $this->lastError = 'Database error: accounts table not found.';
                } elseif ($code === 1054) {
                    $this->lastError = 'Database error: Unknown column in insert.';
                } else {
                    // If the current user is Admin, surface more details to help diagnose
                    if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
                        // Include driver message to quickly pinpoint the failing column/constraint
                        $driverMsg = isset($info[2]) ? $info[2] : $e->getMessage();
                        $this->lastError = 'Database error [' . $code . ']: ' . $driverMsg;
                    } else {
                        $this->lastError = 'Database error occurred.';
                    }
                }
            } else {
                // No errorInfo available; still provide readable details for Admins
                if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
                    $this->lastError = 'Database error: ' . $e->getMessage();
                } else {
                    $this->lastError = 'Database error occurred.';
                }
            }
            error_log("Create account error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get all accounts with filtering
    public function getAccounts($role = null, $search = null, $sortBy = 'CreatedAt', $sortOrder = 'DESC') {
        try {
            $sql = "SELECT Id, Username, Email, Role, FirstName, LastName, LastLoginAt, CreatedAt, 
                           COALESCE(IsDisabled, 0) AS IsDisabled
                    FROM `accounts` WHERE 1=1";
            $params = [];
            
            if ($role && $role !== 'all') {
                $sql .= " AND Role = :role";
                $params[':role'] = $role;
            }
            
            if ($search) {
                $sql .= " AND (FirstName LIKE :search OR LastName LIKE :search OR Email LIKE :search OR Username LIKE :search)";
                $params[':search'] = "%{$search}%";
            }
            
            // Whitelist sort fields and order to prevent SQL injection
            $allowedSort = ['CreatedAt','Username','Role'];
            $allowedOrder = ['ASC','DESC'];
            $sortBy = in_array($sortBy, $allowedSort, true) ? $sortBy : 'CreatedAt';
            $sortOrder = in_array(strtoupper($sortOrder), $allowedOrder, true) ? strtoupper($sortOrder) : 'DESC';
            $sql .= " ORDER BY $sortBy $sortOrder";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get accounts error: " . $e->getMessage());
            return [];
        }
    }
    
    // Get account by ID
    public function getAccountById($id) {
        try {
            $sql = "SELECT *, COALESCE(IsDisabled, 0) AS IsDisabled FROM `accounts` WHERE Id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get account by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    // Update account
    public function updateAccount($id, $data) {
        try {
            $sql = "UPDATE `accounts` SET Username = :username, Email = :email, Role = :role, 
                    FirstName = :first_name, LastName = :last_name WHERE Id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':role', $data['role']);
            $stmt->bindParam(':first_name', $data['first_name']);
            $stmt->bindParam(':last_name', $data['last_name']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update account error: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete account
    public function deleteAccount($id) {
        try {
            $sql = "DELETE FROM `accounts` WHERE Id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete account error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get account statistics
    public function getAccountStats() {
        try {
            $stats = [];
            
            // Get total accounts count
            $sql = "SELECT COUNT(*) as total FROM `accounts`";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get accounts by role
            $sql = "SELECT Role, COUNT(*) as count FROM `accounts` GROUP BY Role";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $role_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($role_counts as $role) {
                $stats[strtolower($role['Role'])] = $role['count'];
            }
            
            // Recent accounts (last 30 days)
            $sql = "SELECT COUNT(*) as count FROM `accounts` WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['recent'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get account stats error: " . $e->getMessage());
            return [];
        }
    }

    // Enable/Disable account login access
    public function setAccountDisabled($id, $disabled) {
        try {
            $sql = "UPDATE `accounts` SET IsDisabled = :disabled WHERE Id = :id";
            $stmt = $this->db->prepare($sql);
            $disabledVal = $disabled ? 1 : 0;
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':disabled', $disabledVal, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Set account disabled error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get sales agent profile by account ID
    public function getSalesAgentProfile($accountId) {
        try {
            $query = "SELECT * FROM sales_agent_profiles WHERE account_id = :account_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':account_id', $accountId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching sales agent profile: " . $e->getMessage());
            return null;
        }
    }
}