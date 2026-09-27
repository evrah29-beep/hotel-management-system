<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/payments/payments.php');

$error = '';

// Fetch active bookings (not cancelled) to link the payment to
$bookings = $conn->query("
    SELECT b.id, b.total_amount, c.full_name, r.room_number 
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status != 'Cancelled'
    ORDER BY b.id DESC
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = $_POST['booking_id'];
    $amount_paid = $_POST['amount_paid'];
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status']; // 'Paid', 'Partial', 'Unpaid'

    // Call the InsertPayment Stored Procedure (idss = integer, double, string, string)
    $stmt = $conn->prepare("CALL InsertPayment(?, ?, ?, ?)");
    
    if (!$stmt) {
        $error = "SQL Error: " . $conn->error;
    } else {
        $stmt->bind_param("idss", $booking_id, $amount_paid, $payment_method, $status);
        
        if ($stmt->execute()) {
            header("Location: payments.php");
            exit();
        } else {
            $error = "Error saving payment: " . $stmt->error;
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Add New Payment</h1>
        <a href="payments.php" class="btn btn-light border shadow-sm"><i class="fas fa-arrow-left"></i> Back to Payments</a>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0" style="max-width: 700px; border-radius: 12px;">
        <div class="card-body p-4">
            <form method="POST" action="">
                
                <div class="form-group mb-4">
                    <label class="font-weight-bold text-muted small text-uppercase">Select Booking / Guest</label>
                    <select name="booking_id" id="booking_select" class="form-control" required>
                        <option value="" data-amount="0">-- Choose a Booking --</option>
                        <?php while($b = $bookings->fetch_assoc()): ?>
                            <!-- data-amount holds the expected total price -->
                            <option value="<?php echo $b['id']; ?>" data-amount="<?php echo $b['total_amount']; ?>">
                                Booking #<?php echo $b['id']; ?> - <?php echo htmlspecialchars($b['full_name']); ?> (Room <?php echo $b['room_number']; ?>) - Owe: $<?php echo $b['total_amount']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Amount Paid ($)</label>
                        <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control font-weight-bold text-success" required>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Credit / Debit Card</option>
                            <option value="Mobile Money">Mobile Money (EVC Plus, etc.)</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12 form-group">
                        <label class="font-weight-bold text-muted small text-uppercase">Payment Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Paid">Paid (Full)</option>
                            <option value="Partial">Partial</option>
                            <option value="Unpaid">Unpaid</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 border-top pt-3">
                    <button type="submit" class="btn btn-green px-4 py-2 font-weight-bold"><i class="fas fa-save mr-1"></i> Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<!-- Javascript to Auto-Fill the Amount Paid based on the Booking selected -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingSelect = document.getElementById('booking_select');
    const amountInput = document.getElementById('amount_paid');

    bookingSelect.addEventListener('change', function() {
        // Get the amount owed from the selected dropdown option
        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
        const owedAmount = selectedOption.getAttribute('data-amount');
        
        // Auto-fill the amount input box
        if(owedAmount > 0) {
            amountInput.value = parseFloat(owedAmount).toFixed(2);
        } else {
            amountInput.value = '';
        }
    });
});
</script>