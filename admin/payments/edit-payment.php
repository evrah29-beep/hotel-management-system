<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/payments/payments.php');

$error = '';
$success = '';

// Check if an ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: payments.php");
    exit();
}

$payment_id = intval($_GET['id']);

// Fetch the existing payment details
$stmt = $conn->prepare("
    SELECT p.*, b.total_amount, c.full_name, r.room_number 
    FROM payments p 
    JOIN bookings b ON p.booking_id = b.id 
    JOIN customers c ON b.customer_id = c.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: payments.php");
    exit();
}

$payment = $result->fetch_assoc();

// Handle the Update Request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_method = trim($_POST['payment_method']);
    $status = trim($_POST['status']);
    $payment_date = trim($_POST['payment_date']);

    // Server-side validation
    if ($amount_paid < 0) {
        $error = "Amount paid cannot be negative.";
    } elseif (empty($payment_method) || empty($status) || empty($payment_date)) {
        $error = "All fields are required.";
    } else {
        // Update the payment securely using a prepared statement
        $update_stmt = $conn->prepare("UPDATE payments SET amount_paid = ?, payment_method = ?, status = ?, payment_date = ? WHERE id = ?");
        
        if (!$update_stmt) {
            $error = "SQL Prepare Error: " . $conn->error;
        } else {
            $update_stmt->bind_param("dsssi", $amount_paid, $payment_method, $status, $payment_date, $payment_id);
            
            if ($update_stmt->execute()) {
                header("Location: payments.php");
                exit();
            } else {
                $error = "Error updating payment: " . $conn->error;
            }
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Edit Payment #TRX-<?php echo $payment_id; ?></h1>
        <a href="payments.php" class="btn btn-light border shadow-sm"><i class="fas fa-arrow-left"></i> Back to Payments</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 800px; border-radius: 12px;">
        <div class="card-body p-4">
            
            <!-- Reference Info -->
            <div class="bg-light p-3 rounded mb-4 border">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Guest</small>
                        <span class="text-dark font-weight-bold"><?php echo htmlspecialchars($payment['full_name']); ?></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Booking / Room</small>
                        <span class="text-dark">#<?php echo $payment['booking_id']; ?> (Room <?php echo $payment['room_number']; ?>)</span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Expected Total</small>
                        <span class="text-success font-weight-bold">$<?php echo number_format($payment['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Amount Paid ($)</label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control font-weight-bold text-success" value="<?php echo htmlspecialchars($payment['amount_paid']); ?>" required>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="Cash" <?php if($payment['payment_method'] == 'Cash') echo 'selected'; ?>>Cash</option>
                            <option value="Credit Card" <?php if($payment['payment_method'] == 'Credit Card') echo 'selected'; ?>>Credit Card</option>
                            <option value="Bank Transfer" <?php if($payment['payment_method'] == 'Bank Transfer') echo 'selected'; ?>>Bank Transfer</option>
                            <option value="Mobile Money" <?php if($payment['payment_method'] == 'Mobile Money') echo 'selected'; ?>>Mobile Money</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Payment Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Paid" <?php if($payment['status'] == 'Paid') echo 'selected'; ?>>Paid</option>
                            <option value="Partial" <?php if($payment['status'] == 'Partial') echo 'selected'; ?>>Partial</option>
                            <option value="Unpaid" <?php if($payment['status'] == 'Unpaid') echo 'selected'; ?>>Unpaid</option>
                        </select>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Payment Date</label>
                        <!-- Format the existing timestamp for the datetime-local input -->
                        <input type="datetime-local" name="payment_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($payment['payment_date'])); ?>" required>
                    </div>
                </div>

                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>