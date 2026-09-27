<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if email or phone already exists
        $check = $conn->prepare("SELECT id FROM customers WHERE email = ? OR phone = ?");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "An account with this email or phone already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // nationality, id_number, address left blank for now; updated at booking
            $stmt = $conn->prepare("INSERT INTO customers (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['guest_id'] = $conn->insert_id;
                $_SESSION['guest_name'] = $full_name;
                header("Location: rooms.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sign Up - Afrik Hotel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .auth-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 450px; overflow: hidden; border: 1px solid #edf2f7; }
        .auth-header { background: #0f172a; padding: 30px; text-align: center; color: #fff; }
        .btn-green { background-color: #10b981; color: #fff; border-radius: 8px; font-weight: 600; padding: 12px; }
        .btn-green:hover { background-color: #059669; color: #fff; }
        .form-control { border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 15px; }
    </style>
</head>
<body>
    <div class="container px-4 py-5 d-flex justify-content-center">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-user-plus fa-3x text-success mb-2"></i>
                <h4 class="font-weight-bold mb-0">Create Account</h4>
            </div>
            <div class="p-4">
                <?php if($error): ?><div class="alert alert-danger small font-weight-bold p-2"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="small font-weight-bold text-muted text-uppercase">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold text-muted text-uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold text-muted text-uppercase">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 061XXXXXXX" required>
                    </div>
                    <div class="row">
                        <div class="col-6 form-group">
                            <label class="small font-weight-bold text-muted text-uppercase">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-6 form-group">
                            <label class="small font-weight-bold text-muted text-uppercase">Confirm</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-green btn-block mt-3 shadow-sm">Sign Up <i class="fas fa-arrow-right ml-1"></i></button>
                </form>
                <div class="text-center mt-4">
                    <p class="small text-muted mb-0">Already have an account? <a href="login.php" class="font-weight-bold text-primary">Log in here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>