<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/payments/payments.php');

// FIX: Automatically convert the restricted ENUM column to a flexible VARCHAR so 'Failed' and 'Refunded' will save successfully.
$conn->query("ALTER TABLE payments MODIFY COLUMN status VARCHAR(50) DEFAULT 'Pending'");

$success_msg = '';
$error_msg = '';

// Handle Payment Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $payment_id = intval($_POST['payment_id']);
    $new_status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE payments SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $payment_id);
    
    if ($stmt->execute()) {
        $success_msg = "Payment #$payment_id successfully updated to $new_status.";
        
        // SMART AUTOMATION: If payment fails, cancel the booking and free up the room!
        if ($new_status === 'Failed' || $new_status === 'Refunded') {
            $b_stmt = $conn->query("SELECT booking_id FROM payments WHERE id = $payment_id");
            if ($b_stmt && $b_stmt->num_rows > 0) {
                $b_id = $b_stmt->fetch_assoc()['booking_id'];
                
                // Find the associated room
                $r_stmt = $conn->query("SELECT room_id FROM bookings WHERE id = $b_id");
                if ($r_stmt && $r_stmt->num_rows > 0) {
                    $r_id = $r_stmt->fetch_assoc()['room_id'];
                    
                    // Cancel the booking and mark room as Available
                    $conn->query("UPDATE bookings SET status = 'Cancelled' WHERE id = $b_id");
                    $conn->query("UPDATE rooms SET status = 'Available' WHERE id = $r_id");
                    
                    $success_msg .= " The reservation was auto-cancelled and the room is now available.";
                }
            }
        }
    } else {
        $error_msg = "Database Error: " . $conn->error;
    }
}

// Fetch all payments linked with booking and customer details
$query = "
    SELECT p.id as payment_id, p.amount, p.payment_method, p.status as payment_status, p.payment_date,
           b.id as booking_id, 
           c.full_name, c.phone
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN customers c ON b.customer_id = c.id
    ORDER BY p.payment_date DESC
";
$payments_result = $conn->query($query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 font-weight-bold text-dark mb-1">Manage Payments</h1>
            <p class="text-muted small">Verify guest transactions and update payment statuses.</p>
        </div>
    </div>

    <?php if($success_msg): ?>
        <div class="alert alert-success font-weight-bold shadow-sm border-0" style="border-radius: 8px;">
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    
    <?php if($error_msg): ?>
        <div class="alert alert-danger font-weight-bold shadow-sm border-0" style="border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="pl-4 py-3">Txn ID</th>
                            <th>Guest Info</th>
                            <th>Linked Order</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-right pr-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($payments_result && $payments_result->num_rows > 0): ?>
                            <?php while($row = $payments_result->fetch_assoc()): ?>
                            <tr>
                                <td class="pl-4 font-weight-bold text-dark">
                                    TXN-<?php echo str_pad($row['payment_id'], 6, '0', STR_PAD_LEFT); ?><br>
                                    <small class="text-muted"><?php echo date('M d, g:i A', strtotime($row['payment_date'])); ?></small>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                    <div class="small text-muted"><i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($row['phone']); ?></div>
                                </td>
                                <td>
                                    <a href="../bookings/bookings.php" class="badge badge-light border text-primary px-2 py-1">
                                        Order #<?php echo str_pad($row['booking_id'], 5, '0', STR_PAD_LEFT); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-dark" style="font-size: 1.1rem;">$<?php echo number_format($row['amount'], 2); ?></div>
                                </td>
                                <td>
                                    <span class="text-muted font-weight-bold small text-uppercase">
                                        <i class="fas <?php echo ($row['payment_method'] == 'EVC Plus') ? 'fa-mobile-alt text-success' : 'fa-credit-card text-primary'; ?> mr-1"></i> 
                                        <?php echo htmlspecialchars($row['payment_method']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $bg = 'badge-secondary';
                                        if($row['payment_status'] == 'Pending') $bg = 'badge-warning text-dark';
                                        if($row['payment_status'] == 'Paid' || $row['payment_status'] == 'Completed') $bg = 'badge-success';
                                        if($row['payment_status'] == 'Failed' || $row['payment_status'] == 'Refunded') $bg = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $bg; ?> px-2 py-1" style="font-size: 0.75rem; border-radius: 4px;"><?php echo $row['payment_status']; ?></span>
                                </td>
                                <td class="text-right pr-4">
                                    <button class="btn btn-sm btn-light border text-primary font-weight-bold shadow-sm" data-toggle="modal" data-target="#payModal<?php echo $row['payment_id']; ?>">
                                        Verify
                                    </button>
                                </td>
                            </tr>

                            <!-- Verify Payment Modal -->
                            <div class="modal fade" id="payModal<?php echo $row['payment_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content border-0" style="border-radius: 12px;">
                                        <div class="modal-header bg-light border-0">
                                            <h5 class="modal-title font-weight-bold">Verify Transaction</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body p-4">
                                                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                                
                                                <div class="alert alert-info small mb-4">
                                                    Check your Merchant device for a payment of <strong>$<?php echo number_format($row['amount'], 2); ?></strong> from <strong><?php echo htmlspecialchars($row['phone']); ?></strong> before marking this as Paid.
                                                </div>

                                                <div class="form-group">
                                                    <label class="font-weight-bold text-muted small text-uppercase">Payment Status</label>
                                                    <select name="status" class="form-control font-weight-bold">
                                                        <option value="Pending" <?php if($row['payment_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                                        <option value="Paid" <?php if($row['payment_status'] == 'Paid' || $row['payment_status'] == 'Completed') echo 'selected'; ?>>Paid (Funds Received)</option>
                                                        <option value="Failed" <?php if($row['payment_status'] == 'Failed') echo 'selected'; ?>>Failed / Rejected</option>
                                                        <option value="Refunded" <?php if($row['payment_status'] == 'Refunded') echo 'selected'; ?>>Refunded</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 12px 12px;">
                                                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Close</button>
                                                <button type="submit" name="update_payment" class="btn btn-success px-4" style="background-color: #10b981; border: none; font-weight: bold;">Save Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">No payments found in the system.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>