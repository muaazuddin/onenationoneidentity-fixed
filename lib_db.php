<?php
// lib_db.php
date_default_timezone_set('UTC');

// ------------------------------
// Database Connection
// ------------------------------
if (!function_exists('db_connect')) {
    function db_connect() {
        static $pdo;
        if ($pdo) return $pdo;

        $dsn  = 'mysql:host=127.0.0.1;dbname=onenationoneidentity_cs;charset=utf8mb4';
        $user = 'root';  
        $pass = '';       

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("❌ Database connection failed: " . $e->getMessage());
        }

        return $pdo;
    }
}

// ------------------------------
// Query Wrapper
// ------------------------------
if (!function_exists('db_query')) {
    /**
     * db_query wrapper:
     *  - runs safe prepared queries
     *  - logs reads/writes to data_access_logs if needed
     *  - returns rows for SELECT, lastInsertId for INSERT
     */
    function db_query($sql, $params = [], $opts = []) {
        $pdo = db_connect();

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            die("❌ DB query failed: " . $e->getMessage() . "<br>SQL: " . htmlspecialchars($sql));
        }

        $is_select = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $sql);

        // Audit logging
        $actor_type = $opts['actor_type'] ?? null;
        $actor_nid  = $opts['actor_nid'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sensitive_tables = ['person','patient','prescription','testreport','medical_records','doctor'];

        $should_log = false;
        foreach ($sensitive_tables as $t) {
            if (stripos($sql, $t) !== false) {
                $should_log = true;
                break;
            }
        }

        if (!empty($opts['log_access']) || $should_log) {
            $logSql = "INSERT INTO data_access_logs 
                       (actor_type, actor_nid, action, target_table, query_sample, ip, user_agent) 
                       VALUES (?,?,?,?,?,?,?)";
            $action = $is_select ? 'SELECT' : 'WRITE';
            $target_table = $opts['target_table'] ?? implode(',', $sensitive_tables);

            try {
                $pdo->prepare($logSql)->execute([
                    $actor_type, $actor_nid, $action,
                    $target_table, substr($sql, 0, 1000),
                    $ip, $ua
                ]);
            } catch (PDOException $e) {
                // Ignore logging errors so app doesn’t break
            }
        }

        return $is_select ? $stmt->fetchAll() : $pdo->lastInsertId();
    }
}
