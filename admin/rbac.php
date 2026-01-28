<?php
class CompleteRBAC {
    private $conn;
    private $admin_id;
    private $is_even_admin;
    
    public function __construct($db_connection, $admin_id = null) {
        $this->conn = $db_connection;
        if ($admin_id) {
            $this->admin_id = $admin_id;
            $this->is_even_admin = ($admin_id % 2 == 0);
        }
    }
    
    // Check page access
    public function canAccessPage($page_name) {
        // Even ID admins can access everything
        if ($this->is_even_admin) {
            return true;
        }
        
        // Odd ID admins can only access dashboard
        return $page_name === 'dashboard.php';
    }
    
    // Require permission for page access
    public function requirePageAccess($page_name, $redirect_url = 'dashboard.php') {
        $current_page = basename($_SERVER['PHP_SELF']);
        
        if (!$this->canAccessPage($current_page)) {
            $this->logAccessDenied($current_page);
            $_SESSION['error'] = " Access Denied: You don't have permission to access this page.";
            header("Location: $redirect_url");
            exit();
        }
        return true;
    }
    
    // Get admin type
    public function getAdminType() {
        return $this->is_even_admin ? "Full Access Admin" : "Restricted Admin";
    }
    
    // Check if current admin is even ID
    public function isEvenAdmin() {
        return $this->is_even_admin;
    }
    
    // Log access denied attempts
    private function logAccessDenied($page_name) {
        if (!$this->conn) return;
        
        $stmt = $this->conn->prepare("
            INSERT INTO access_logs (admin_id, action, resource, status, ip_address, user_agent) 
            VALUES (?, 'PAGE_ACCESS_DENIED', ?, 'DENIED', ?, ?)
        ");
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $stmt->bind_param("isss", $this->admin_id, $page_name, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
    
    // Get accessible pages for current admin
    public function getAccessiblePages() {
        $all_pages = [
            'dashboard.php' => ['title' => 'Dashboard', 'icon' => 'tachometer-alt'],
            'user_management.php' => ['title' => 'User Management', 'icon' => 'users'],
            'content_management.php' => ['title' => 'Content Management', 'icon' => 'file-alt'],
            'reports.php' => ['title' => 'Reports', 'icon' => 'chart-bar'],
            'settings.php' => ['title' => 'Settings', 'icon' => 'cog'],
            'security_logs.php' => ['title' => 'Security Logs', 'icon' => 'shield-alt']
        ];
        
        $accessible_pages = [];
        
        foreach ($all_pages as $page => $info) {
            if ($this->canAccessPage($page)) {
                $accessible_pages[$page] = $info;
            }
        }
        
        return $accessible_pages;
    }
    
    //  Initialize session after login
    public static function initializeSession($conn, $admin_id, $admin_username, $admin_email) {
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['admin_username'] = $admin_username;
        $_SESSION['admin_email'] = $admin_email;
        
        $rbac = new self($conn, $admin_id);
        $_SESSION['admin_type'] = $rbac->getAdminType();
        $_SESSION['is_even_admin'] = $rbac->isEvenAdmin();
        
        return $rbac;
    }
    
    // Get current RBAC instance
    public static function getInstance($conn) {
        if (!isset($_SESSION['admin_id'])) {
            return null;
        }
        
        return new self($conn, $_SESSION['admin_id']);
    }
}
?>