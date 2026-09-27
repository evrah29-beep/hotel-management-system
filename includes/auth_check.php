<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BasePath Calculation
$scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
$basePath = '';
while (!file_exists($scriptDir . '/config/database.php') && strlen($scriptDir) > 3) {
    $basePath .= '../';
    $scriptDir = dirname($scriptDir);
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: {$basePath}login.php");
    exit();
}

// Dynamic Permission Engine
function hasPermission($url_path) {
    global $conn;
    $role = $_SESSION['admin_role'] ?? '';
    
    // Admins bypass all checks
    if ($role === 'Admin' || $role === 'Administrator') {
        return true;
    }
    
    // Cache allowed URLs in session
    if (!isset($_SESSION['allowed_urls'])) {
        $_SESSION['allowed_urls'] = [];
        if (isset($conn)) {
            $stmt = $conn->prepare("
                SELECT sp.url FROM user_permissions up 
                JOIN system_pages sp ON up.page_id = sp.id 
                WHERE up.admin_id = ?
            ");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $_SESSION['allowed_urls'][] = $row['url'];
            }
            $stmt->close();
        }
    }
    
    return in_array($url_path, $_SESSION['allowed_urls']);
}

function requirePermission($url_path) {
    global $basePath;
    if (!hasPermission($url_path)) {
        echo "<div style='font-family: sans-serif; text-align: center; margin-top: 100px;'>
                <h2 style='color: #e11d48;'>Access Denied</h2>
                <p>You do not have permission to view this page.</p>
                <a href='{$basePath}index.php' style='color: #2563eb; font-weight: bold;'>Return to Dashboard</a>
              </div>";
        exit();
    }
}
?>