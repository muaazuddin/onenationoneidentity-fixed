<?php
// data_logger.php
class DataAccessLogger {
    private $conn;
    private $admin_id;
    private $admin_username;
    private $start_time;
    
    public function __construct($db_connection, $admin_id = null, $admin_username = null) {
        $this->conn = $db_connection;
        $this->admin_id = $admin_id;
        $this->admin_username = $admin_username;
        $this->start_time = microtime(true);
    }
    
    // 📝 Log data access activity
    public function logDataAccess($action_type, $table_name, $record_id = null, $data_before = null, $data_after = null, $status = 'SUCCESS', $error_message = null) {
        // First, check if the table exists, if not create it
        $this->createTableIfNotExists();
        
        $log_id = uniqid('log_', true);
        $ip_address = $this->getRealIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Calculate access duration
        $end_time = microtime(true);
        $access_duration = round(($end_time - $this->start_time) * 1000, 3); // in milliseconds
        
        // Serialize data for storage
        $data_before_json = $data_before ? json_encode($data_before, JSON_UNESCAPED_UNICODE) : null;
        $data_after_json = $data_after ? json_encode($data_after, JSON_UNESCAPED_UNICODE) : null;
        
        // Handle NULL record_id properly
        if ($record_id === null) {
            $stmt = $this->conn->prepare("
                INSERT INTO data_access_logs 
                (log_id, admin_id, admin_username, action_type, table_name, data_before, data_after, ip_address, user_agent, access_duration, status, error_message) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt === false) {
                error_log("Failed to prepare statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param(
                "sisssssssdss", 
                $log_id, $this->admin_id, $this->admin_username, $action_type, $table_name, 
                $data_before_json, $data_after_json, $ip_address, $user_agent, 
                $access_duration, $status, $error_message
            );
        } else {
            $stmt = $this->conn->prepare("
                INSERT INTO data_access_logs 
                (log_id, admin_id, admin_username, action_type, table_name, record_id, data_before, data_after, ip_address, user_agent, access_duration, status, error_message) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt === false) {
                error_log("Failed to prepare statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param(
                "sisssissssdss", 
                $log_id, $this->admin_id, $this->admin_username, $action_type, $table_name, 
                $record_id, $data_before_json, $data_after_json, $ip_address, $user_agent, 
                $access_duration, $status, $error_message
            );
        }
        
        $result = $stmt->execute();
        if (!$result) {
            error_log("Failed to execute statement: " . $stmt->error);
        }
        $stmt->close();
        
        return $result;
    }
    
    // 👁️ Log view operations
    public function logView($table_name, $record_id = null, $search_query = null) {
        $data_after = null;
        if ($search_query) {
            $data_after = ['search_query' => $search_query];
        }
        return $this->logDataAccess('VIEW', $table_name, $record_id, null, $data_after);
    }
    
    // ➕ Log create operations
    public function logCreate($table_name, $record_id, $new_data) {
        return $this->logDataAccess('CREATE', $table_name, $record_id, null, $new_data);
    }
    
    // ✏️ Log update operations
    public function logUpdate($table_name, $record_id, $old_data, $new_data) {
        return $this->logDataAccess('UPDATE', $table_name, $record_id, $old_data, $new_data);
    }
    
    // 🗑️ Log delete operations
    public function logDelete($table_name, $record_id, $deleted_data) {
        return $this->logDataAccess('DELETE', $table_name, $record_id, $deleted_data, null);
    }
    
    // 📤 Log export operations
    public function logExport($table_name, $export_params = null) {
        $data_after = $export_params ? ['export_params' => $export_params] : null;
        return $this->logDataAccess('EXPORT', $table_name, null, null, $data_after);
    }
    
    // 🔍 Log search operations
    public function logSearch($table_name, $search_params) {
        return $this->logDataAccess('SEARCH', $table_name, null, null, $search_params);
    }
    
    // ❌ Log failed attempts
    public function logFailedAttempt($action_type, $table_name, $error_message, $record_id = null) {
        return $this->logDataAccess($action_type, $table_name, $record_id, null, null, 'FAILED', $error_message);
    }
    
    // 🚫 Log unauthorized attempts
    public function logUnauthorizedAttempt($action_type, $table_name, $record_id = null) {
        return $this->logDataAccess($action_type, $table_name, $record_id, null, null, 'UNAUTHORIZED', 'Insufficient permissions');
    }
    
    // Create table if not exists
    private function createTableIfNotExists() {
        $table_exists = $this->conn->query("SHOW TABLES LIKE 'data_access_logs'");
        if ($table_exists->num_rows > 0) {
            return; // Table already exists
        }
        
        $create_table_sql = "
        CREATE TABLE data_access_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            log_id VARCHAR(50) UNIQUE NOT NULL,
            admin_id INT NOT NULL,
            admin_username VARCHAR(100) NOT NULL,
            action_type ENUM('VIEW', 'CREATE', 'UPDATE', 'DELETE', 'EXPORT', 'SEARCH') NOT NULL,
            table_name VARCHAR(100) NOT NULL,
            record_id INT NULL,
            data_before TEXT NULL,
            data_after TEXT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            access_duration DECIMAL(10,3) NULL,
            status ENUM('SUCCESS', 'FAILED', 'UNAUTHORIZED') DEFAULT 'SUCCESS',
            error_message TEXT NULL,
            
            INDEX idx_admin_id (admin_id),
            INDEX idx_timestamp (timestamp),
            INDEX idx_action_table (action_type, table_name),
            INDEX idx_admin_action (admin_id, action_type)
        )";
        
        if ($this->conn->query($create_table_sql) === TRUE) {
            error_log("Data access logs table created successfully");
        } else {
            error_log("Error creating table: " . $this->conn->error);
        }
    }
    
    // 📊 Get real IP address
    private function getRealIP() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
        return $ip === '::1' ? '127.0.0.1' : $ip;
    }
    
    // 📈 Get logs for specific admin
    public function getAdminLogs($admin_id, $limit = 100, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT * FROM data_access_logs 
            WHERE admin_id = ? 
            ORDER BY timestamp DESC 
            LIMIT ? OFFSET ?
        ");
        
        if ($stmt === false) {
            error_log("Failed to prepare statement: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("iii", $admin_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();
        
        return $logs;
    }
    
    // 📋 Get recent logs for dashboard
    public function getRecentLogs($limit = 10) {
        // First check if table exists
        $table_exists = $this->conn->query("SHOW TABLES LIKE 'data_access_logs'");
        if ($table_exists->num_rows == 0) {
            return []; // Table doesn't exist yet
        }
        
        $stmt = $this->conn->prepare("
            SELECT l.*, a.username as admin_username 
            FROM data_access_logs l 
            LEFT JOIN admin a ON l.admin_id = a.id 
            ORDER BY l.timestamp DESC 
            LIMIT ?
        ");
        
        if ($stmt === false) {
            error_log("Failed to prepare statement: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        $stmt->close();
        
        return $logs;
    }
    
    // 🗂️ Get statistics
    public function getLogStatistics($days = 30) {
        // First check if table exists
        $table_exists = $this->conn->query("SHOW TABLES LIKE 'data_access_logs'");
        if ($table_exists->num_rows == 0) {
            return ['total_actions' => 0, 'actions_by_type' => []];
        }
        
        $start_date = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $stats = [];
        
        // Total actions
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total_actions 
            FROM data_access_logs 
            WHERE timestamp >= ?
        ");
        
        if ($stmt !== false) {
            $stmt->bind_param("s", $start_date);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['total_actions'] = $result->fetch_assoc()['total_actions'];
            $stmt->close();
        }
        
        // Actions by type
        $stmt = $this->conn->prepare("
            SELECT action_type, COUNT(*) as count 
            FROM data_access_logs 
            WHERE timestamp >= ? 
            GROUP BY action_type 
            ORDER BY count DESC
        ");
        
        if ($stmt !== false) {
            $stmt->bind_param("s", $start_date);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['actions_by_type'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['actions_by_type'][$row['action_type']] = $row['count'];
            }
            $stmt->close();
        }
        
        return $stats;
    }
    
    // Get unique tables from logs
    public function getLoggedTables() {
        // First check if table exists
        $table_exists = $this->conn->query("SHOW TABLES LIKE 'data_access_logs'");
        if ($table_exists->num_rows == 0) {
            return [];
        }
        
        $result = $this->conn->query("
            SELECT DISTINCT table_name 
            FROM data_access_logs 
            ORDER BY table_name
        ");
        
        $tables = [];
        while ($row = $result->fetch_assoc()) {
            $tables[] = $row['table_name'];
        }
        
        return $tables;
    }
    
    // Get unique actions from logs
    public function getLoggedActions() {
        // First check if table exists
        $table_exists = $this->conn->query("SHOW TABLES LIKE 'data_access_logs'");
        if ($table_exists->num_rows == 0) {
            return [];
        }
        
        $result = $this->conn->query("
            SELECT DISTINCT action_type 
            FROM data_access_logs 
            ORDER BY action_type
        ");
        
        $actions = [];
        while ($row = $result->fetch_assoc()) {
            $actions[] = $row['action_type'];
        }
        
        return $actions;
    }
}
?>