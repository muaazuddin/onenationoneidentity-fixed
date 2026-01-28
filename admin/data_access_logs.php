<?php
// data_access_logs.php
session_start();
include_once('../dbconn.php');
require_once 'rbac.php';
require_once 'data_logger.php';

// RBAC Protection
$rbac = CompleteRBAC::getInstance($conn);
if (!$rbac) {
    header("Location: index.php");
    exit();
}

// Only Even ID admins can view access logs
if (!$rbac->isEvenAdmin()) {
    header("Location: dashboard.php");
    exit();
}

$dataLogger = new DataAccessLogger($conn, $_SESSION['admin_id'], $_SESSION['admin_username']);

// Get filter parameters
$filter_admin = $_GET['admin'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_table = $_GET['table'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if ($filter_admin) {
    $where_conditions[] = "admin_username LIKE ?";
    $params[] = "%$filter_admin%";
    $types .= 's';
}

if ($filter_action) {
    $where_conditions[] = "action_type = ?";
    $params[] = $filter_action;
    $types .= 's';
}

if ($filter_table) {
    $where_conditions[] = "table_name = ?";
    $params[] = $filter_table;
    $types .= 's';
}

if ($filter_date) {
    $where_conditions[] = "DATE(timestamp) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

$where_sql = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get logs
$stmt = $conn->prepare("
    SELECT * FROM data_access_logs 
    $where_sql 
    ORDER BY timestamp DESC 
    LIMIT 1000
");

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Log the access to this page
$dataLogger->logView('data_access_logs', null, 'Viewed data access logs');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Access Logs</title>
    <!-- Include your CSS and JS files -->
</head>
<body>
    <!-- Add a comprehensive logs viewer interface -->
    <div class="container-fluid">
        <h2>Data Access Logs</h2>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label>Admin Username</label>
                        <input type="text" name="admin" class="form-control" value="<?= htmlspecialchars($filter_admin) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Action Type</label>
                        <select name="action" class="form-control">
                            <option value="">All Actions</option>
                            <option value="VIEW" <?= $filter_action=='VIEW'?'selected':'' ?>>View</option>
                            <option value="CREATE" <?= $filter_action=='CREATE'?'selected':'' ?>>Create</option>
                            <option value="UPDATE" <?= $filter_action=='UPDATE'?'selected':'' ?>>Update</option>
                            <option value="DELETE" <?= $filter_action=='DELETE'?'selected':'' ?>>Delete</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Table</label>
                        <input type="text" name="table" class="form-control" value="<?= htmlspecialchars($filter_table) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="data_access_logs.php" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Record ID</th>
                                <th>IP Address</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
                                <td><?= htmlspecialchars($log['admin_username']) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $log['action_type'] == 'VIEW' ? 'primary' :
                                        ($log['action_type'] == 'CREATE' ? 'success' :
                                        ($log['action_type'] == 'UPDATE' ? 'warning' :
                                        ($log['action_type'] == 'DELETE' ? 'danger' : 'info')))
                                    ?>">
                                        <?= $log['action_type'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($log['table_name']) ?></td>
                                <td><?= $log['record_id'] ?: '-' ?></td>
                                <td><?= $log['ip_address'] ?></td>
                                <td><?= $log['access_duration'] ? $log['access_duration'] . 'ms' : '-' ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $log['status'] == 'SUCCESS' ? 'success' :
                                        ($log['status'] == 'FAILED' ? 'danger' : 'warning')
                                    ?>">
                                        <?= $log['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>