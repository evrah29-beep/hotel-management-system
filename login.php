<?php
session_start();
require_once 'config/database.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

// Soo qaado Magaca iyo Logoda Hotel-ka ee Settings-ka
$hotel_name = 'Afrik Hotel';
$hotel_logo = '';
if (isset($conn)) {
    $set_query = $conn->query("SELECT hotel_name, hotel_logo FROM settings WHERE id = 1");
    if ($set_query && $set_query->num_rows > 0) {
        $settings = $set_query->fetch_assoc();
        if (!empty($settings['hotel_name'])) {
            $hotel_name = $settings['hotel_name'];
        }
        if (!empty($settings['hotel_logo'])) {
            $hotel_logo = $settings['hotel_logo'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check for user by login name or email
    $stmt = $conn->prepare("SELECT id, name, full_name, password, role, status FROM admin WHERE name = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['status'] == 'Inactive') {
            $error = "Your account has been deactivated. Contact administration.";
        } else if (password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['name'];
            $_SESSION['admin_role'] = $user['role'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "Account not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($hotel_name); ?> Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9; /* Soft dashboard gray */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            border: 1px solid #edf2f7;
        }
        .login-header {
            background-color: #0f172a; /* Dark slate matching sidebar */
            padding: 40px 30px;
            text-align: center;
            color: #fff;
        }
        .login-header i {
            color: #10b981; /* Emerald green accent */
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .login-body {
            padding: 40px 30px;
        }
        .form-control {
            height: 48px;
            border-radius: 8px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            border-color: #10b981;
            background-color: #fff;
        }
        .input-group-text {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            border-radius: 8px 0 0 8px;
            color: #94a3b8;
        }
        .btn-login {
            background-color: #10b981;
            color: #fff;
            height: 48px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: #059669;
            color: #fff;
            transform: translateY(-1px);
        }
        .forgot-link {
            color: #10b981;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .forgot-link:hover {
            color: #059669;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container px-4">
    <div class="row justify-content-center">
        <div class="login-card">
            
            <div class="login-header">
                <!-- Logoda System-ka -->
                <?php if ($hotel_logo): ?>
                    <img src="assets/images/<?php echo htmlspecialchars($hotel_logo); ?>" alt="Logo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; padding: 4px; background: #fff; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                <?php else: ?>
                    <i class="fas fa-hotel"></i>
                <?php endif; ?>
                
                <h3 class="font-weight-bold mb-0"><?php echo htmlspecialchars($hotel_name); ?></h3>
                <p class="text-muted small mb-0" style="color: #94a3b8 !important;">Management System</p>
            </div>

            <div class="login-body">
                <h5 class="font-weight-bold text-dark mb-4 text-center">Sign in to your account</h5>

                <?php if($error): ?>
                    <div class="alert alert-danger small font-weight-bold p-2 text-center" style="border-radius: 8px;">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase mb-2">Login Name or Email</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input type="text" name="username" class="form-control" placeholder="Enter your username" required autofocus>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase mb-2">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <!-- Remember Me & Forgot Password Section -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="rememberMe">
                            <label class="custom-control-label text-muted small font-weight-bold" for="rememberMe" style="cursor: pointer;">Remember me</label>
                        </div>
                        <a href="forgot_password.php" class="small forgot-link">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-login btn-block mt-2 shadow-sm">
                        Secure Login <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>
            </div>
            
        </div>
        
        <div class="w-100 mt-4 text-center">
            <p class="text-muted small">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($hotel_name); ?>. All rights reserved.</p>
        </div>
    </div>
</div>

</body>
</html>