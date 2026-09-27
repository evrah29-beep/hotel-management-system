<?php
session_start();
require_once '../config/database.php';

// Redirect to rooms if already logged in
if (isset($_SESSION['guest_id'])) {
    header("Location: rooms.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        // Check if the user has a password set (walk-in guests might not have one yet)
        if (empty($customer['password'])) {
            $error = "Account not fully set up. Please register to set a password.";
        } else if (password_verify($password, $customer['password'])) {
            $_SESSION['guest_id'] = $customer['id'];
            $_SESSION['guest_name'] = $customer['full_name'];
            header("Location: rooms.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Guest Login - Afrik Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .auth-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 400px; overflow: hidden; border: 1px solid #edf2f7; }
        .auth-header { background: #0f172a; padding: 30px; text-align: center; color: #fff; }
        .btn-green { background-color: #10b981; color: #fff; border-radius: 8px; font-weight: 600; padding: 12px; transition: all 0.3s; }
        .btn-green:hover { background-color: #059669; color: #fff; }
        .form-control { border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 15px; }
    </style>
</head>
<body>
    <div class="container px-4 py-5 d-flex justify-content-center">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-user-circle fa-3x text-success mb-2"></i>
                <h4 class="font-weight-bold mb-0">Guest Portal</h4>
            </div>
            <div class="p-4">
                <?php if($error): ?><div class="alert alert-danger small font-weight-bold p-2"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-muted text-uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <div class="form-group mb-4">
                        <label class="small font-weight-bold text-muted text-uppercase">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-green btn-block shadow-sm">Secure Login <i class="fas fa-sign-in-alt ml-1"></i></button>
                </form>
                <div class="text-center mt-4">
                    <p class="small text-muted mb-0">Don't have an account? <a href="register.php" class="font-weight-bold text-primary">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>