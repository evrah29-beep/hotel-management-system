<?php
session_start();
require_once 'config/database.php';

// Haddii uu horay u sii login-gareysan yahay, ku celi dashboard-ka
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$msg = '';
$msg_type = '';

// Soo qaado Magaca iyo Logoda Hotel-ka
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

// Hubi haddii form-ka la soo diray
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Hubi in Email-kan uu ku jiro database-ka Admin-ka
    $stmt = $conn->prepare("SELECT id, name FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // F.G: Halkan waxaa la geliyaa koodhka diraya Email-ka rasmiga ah (Sida PHPMailer).
        // Maadaama uu system-ku yahay Localhost, waxaan kaliya muujinaynaa fariin (Success).
        
        $msg = "A password reset link has been sent to your email address.";
        $msg_type = "success";
    } else {
        $msg = "We couldn't find an account associated with that email.";
        $msg_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo htmlspecialchars($hotel_name); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
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
            background-color: #0f172a;
            padding: 40px 30px;
            text-align: center;
            color: #fff;
        }
        .login-header i {
            color: #10b981;
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
        .btn-reset {
            background-color: #10b981;
            color: #fff;
            height: 48px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            background-color: #059669;
            color: #fff;
            transform: translateY(-1px);
        }
        .back-link {
            color: #64748b;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #0f172a;
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
                    <i class="fas fa-lock"></i>
                <?php endif; ?>
                
                <h3 class="font-weight-bold mb-0"><?php echo htmlspecialchars($hotel_name); ?></h3>
                <p class="text-muted small mb-0" style="color: #94a3b8 !important;">Password Recovery</p>
            </div>

            <div class="login-body">
                <h5 class="font-weight-bold text-dark mb-2 text-center">Forgot Password?</h5>
                <p class="text-muted small text-center mb-4">Enter your email address and we'll send you instructions to reset your password.</p>

                <?php if($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> small font-weight-bold p-2 text-center" style="border-radius: 8px;">
                        <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-1"></i> <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase mb-2">Email Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="email" name="email" class="form-control" placeholder="admin@example.com" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-reset btn-block mt-2 shadow-sm">
                        Send Reset Link <i class="fas fa-paper-plane ml-2"></i>
                    </button>
                    
                    <div class="text-center mt-4">
                        <a href="login.php" class="small back-link">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Login
                        </a>
                    </div>
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