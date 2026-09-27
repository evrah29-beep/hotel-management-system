<?php
require_once '../../includes/auth_check.php';
require_once '../../config/database.php';
requirePermission('admin/settings/import_data.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success_msg = '';
$warning_msg = '';
$error_msg = '';
$details = [];

function extract_zip_any($zipPath, $dest) {
    if (class_exists('ZipArchive')) {
        $z = new ZipArchive();
        if ($z->open($zipPath) === true) {
            $z->extractTo($dest);
            $z->close();
            return true;
        }
    }
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $zipEsc = str_replace("'", "''", $zipPath);
        $destEsc = str_replace("'", "''", $dest);
        $cmd = "powershell -NoProfile -ExecutionPolicy Bypass -Command \"Expand-Archive -LiteralPath '$zipEsc' -DestinationPath '$destEsc' -Force\"";
        exec($cmd, $o, $code);
        if ($code === 0) return true;
    }
    return false;
}

function csv_rows($file) {
    $h = fopen($file, 'r');
    if (!$h) throw new RuntimeException('Unable to open CSV: ' . basename($file));
    $headers = fgetcsv($h);
    if (!$headers) {
        fclose($h);
        throw new RuntimeException('CSV has no header row: ' . basename($file));
    }
    $headers = array_map(function($v){ return trim((string)$v); }, $headers);
    $rows = [];
    $line = 1;
    while (($r = fgetcsv($h)) !== false) {
        $line++;
        if (count($r) === 1 && trim((string)$r[0]) === '') continue;
        $row = ['__line' => $line];
        foreach ($headers as $i => $k) $row[$k] = trim((string)($r[$i] ?? ''));
        $rows[] = $row;
    }
    fclose($h);
    return [$headers, $rows];
}

function require_headers($fileName, $headers, $required) {
    $missing = array_values(array_diff($required, $headers));
    if ($missing) throw new RuntimeException($fileName . ' is missing required column(s): ' . implode(', ', $missing));
}

function one_id($conn, $table, $whereCol, $value) {
    if ($value === '' || $value === null) return null;
    $s = $conn->prepare("SELECT id FROM `$table` WHERE `$whereCol` = ? LIMIT 1");
    $s->bind_param('s', $value);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    $s->close();
    return $r['id'] ?? null;
}

function booking_id_by_match($conn, $customerId, $roomId, $checkIn, $checkOut, $totalPrice) {
    $s = $conn->prepare('SELECT id FROM bookings WHERE customer_id=? AND room_id=? AND check_in=? AND check_out=? AND total_price=? LIMIT 1');
    $s->bind_param('iissd', $customerId, $roomId, $checkIn, $checkOut, $totalPrice);
    $s->execute();
    $r = $s->get_result()->fetch_assoc();
    $s->close();
    return $r['id'] ?? null;
}

function payment_exists($conn, $bookingId, $amount, $paymentDate) {
    $s = $conn->prepare('SELECT id FROM payments WHERE booking_id=? AND amount=? AND DATE(payment_date)=DATE(?) LIMIT 1');
    $s->bind_param('ids', $bookingId, $amount, $paymentDate);
    $s->execute();
    $exists = (bool)$s->get_result()->fetch_assoc();
    $s->close();
    return $exists;
}

function add_stat(&$stats, $file, $kind, $message = '') {
    if (!isset($stats[$file])) $stats[$file] = ['inserted'=>0, 'existing'=>0, 'skipped'=>0, 'notes'=>[]];
    if (isset($stats[$file][$kind]) && is_int($stats[$file][$kind])) $stats[$file][$kind]++;
    if ($message !== '' && count($stats[$file]['notes']) < 12) $stats[$file]['notes'][] = $message;
}

function cleanup_dir($dir) {
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    @rmdir($dir);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['data_file'])) {
    $name = $_FILES['data_file']['name'] ?? '';
    $tmp = $_FILES['data_file']['tmp_name'] ?? '';
    $uploadError = $_FILES['data_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($uploadError !== UPLOAD_ERR_OK || !$tmp || !in_array($ext, ['csv','zip'], true)) {
        $error_msg = 'Please upload a readable CSV file or a ZIP containing the hotel historical CSV files.';
    } else {
        $dir = sys_get_temp_dir() . '/hotel_import_' . uniqid('', true);
        mkdir($dir, 0777, true);
        $files = [];
        try {
            if ($ext === 'zip') {
                if (!extract_zip_any($tmp, $dir)) throw new RuntimeException('ZIP extraction failed. Enable PHP ZipArchive or PowerShell Expand-Archive in Windows/XAMPP.');
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if (strtolower($f->getExtension()) === 'csv') $files[strtolower($f->getBasename())] = $f->getPathname();
                }
            } else {
                $files[strtolower($name)] = $tmp;
            }

            $supported = ['room_types.csv','customers.csv','rooms.csv','staff.csv','attendance.csv','expenses.csv','inventory.csv','housekeeping.csv','maintenance.csv','bookings.csv','payments.csv'];
            if (!array_intersect(array_keys($files), $supported)) throw new RuntimeException('No supported hotel CSV file was found in the upload.');

            $conn->begin_transaction();
            $stats = [];
            $staffMap = [];
            $bookingMap = [];

            // Parents first: room types, customers, rooms, staff.
            if (isset($files['room_types.csv'])) {
                [$h,$rows] = csv_rows($files['room_types.csv']);
                require_headers('room_types.csv', $h, ['type_name','price_per_night','description']);
                foreach ($rows as $r) {
                    if ($r['type_name'] === '') { add_stat($stats,'room_types.csv','skipped','Line '.$r['__line'].': empty type_name.'); continue; }
                    if (one_id($conn,'room_types','type_name',$r['type_name'])) { add_stat($stats,'room_types.csv','existing'); continue; }
                    $price=(float)$r['price_per_night'];
                    $s=$conn->prepare('INSERT INTO room_types(type_name,price_per_night,description) VALUES(?,?,?)');
                    $s->bind_param('sds',$r['type_name'],$price,$r['description']); $s->execute(); $s->close();
                    add_stat($stats,'room_types.csv','inserted');
                }
            }

            if (isset($files['customers.csv'])) {
                [$h,$rows] = csv_rows($files['customers.csv']);
                require_headers('customers.csv', $h, ['full_name','email','phone','id_number','address','nationality']);
                foreach ($rows as $r) {
                    if ($r['email'] === '') { add_stat($stats,'customers.csv','skipped','Line '.$r['__line'].': email is required for booking matching.'); continue; }
                    if (one_id($conn,'customers','email',$r['email'])) { add_stat($stats,'customers.csv','existing'); continue; }
                    $s=$conn->prepare("INSERT INTO customers(full_name,email,phone,id_number,address,nationality,password) VALUES(?,?,?,?,?,?,'')");
                    $s->bind_param('ssssss',$r['full_name'],$r['email'],$r['phone'],$r['id_number'],$r['address'],$r['nationality']); $s->execute(); $s->close();
                    add_stat($stats,'customers.csv','inserted');
                }
            }

            if (isset($files['rooms.csv'])) {
                [$h,$rows] = csv_rows($files['rooms.csv']);
                require_headers('rooms.csv', $h, ['room_number','type_name','floor','status']);
                foreach ($rows as $r) {
                    if (one_id($conn,'rooms','room_number',$r['room_number'])) { add_stat($stats,'rooms.csv','existing'); continue; }
                    $type=one_id($conn,'room_types','type_name',$r['type_name']);
                    if (!$type) { add_stat($stats,'rooms.csv','skipped','Line '.$r['__line'].': room type not found: '.$r['type_name']); continue; }
                    $floor=(int)$r['floor'];
                    $s=$conn->prepare('INSERT INTO rooms(room_number,room_type_id,floor,status) VALUES(?,?,?,?)');
                    $s->bind_param('siis',$r['room_number'],$type,$floor,$r['status']); $s->execute(); $s->close();
                    add_stat($stats,'rooms.csv','inserted');
                }
            }

            if (isset($files['staff.csv'])) {
                [$h,$rows] = csv_rows($files['staff.csv']);
                require_headers('staff.csv', $h, ['employee_code','full_name','phone','job_title','department','salary','status']);
                foreach ($rows as $r) {
                    $id=one_id($conn,'staff','employee_no',$r['employee_code']);
                    if (!$id) {
                        $salary=(float)$r['salary'];
                        $s=$conn->prepare('INSERT INTO staff(employee_no,full_name,department,job_title,phone,salary,status) VALUES(?,?,?,?,?,?,?)');
                        $s->bind_param('sssssds',$r['employee_code'],$r['full_name'],$r['department'],$r['job_title'],$r['phone'],$salary,$r['status']);
                        $s->execute(); $id=$conn->insert_id; $s->close();
                        add_stat($stats,'staff.csv','inserted');
                    } else add_stat($stats,'staff.csv','existing');
                    $staffMap[$r['employee_code']]=$id;
                }
                if (in_array('email',$h,true) || in_array('hire_date',$h,true)) {
                    $stats['staff.csv']['notes'][] = 'CSV email/hire_date are not imported because those columns do not exist in the real staff table.';
                }
            }

            // Child/operational data.
            if (isset($files['attendance.csv'])) {
                [$h,$rows] = csv_rows($files['attendance.csv']);
                require_headers('attendance.csv', $h, ['employee_code','attendance_date','check_in','check_out','status','notes']);
                foreach ($rows as $r) {
                    $sid=$staffMap[$r['employee_code']] ?? one_id($conn,'staff','employee_no',$r['employee_code']);
                    if (!$sid) { add_stat($stats,'attendance.csv','skipped','Line '.$r['__line'].': employee not found: '.$r['employee_code']); continue; }
                    $s=$conn->prepare('INSERT IGNORE INTO staff_attendance(staff_id,attendance_date,check_in,check_out,status,notes) VALUES(?,?,?,?,?,?)');
                    $s->bind_param('isssss',$sid,$r['attendance_date'],$r['check_in'],$r['check_out'],$r['status'],$r['notes']); $s->execute();
                    $affected=$s->affected_rows; $s->close();
                    add_stat($stats,'attendance.csv',$affected ? 'inserted' : 'existing');
                }
            }

            if (isset($files['expenses.csv'])) {
                [$h,$rows] = csv_rows($files['expenses.csv']);
                require_headers('expenses.csv', $h, ['expense_date','category','description','amount','payment_method']);
                foreach ($rows as $r) {
                    $amount=(float)$r['amount'];
                    $vendor=$r['vendor'] ?? '';
                    $notes=$vendor !== '' ? 'Vendor: '.$vendor : null;
                    $s=$conn->prepare('INSERT INTO hotel_expenses(expense_date,category,description,amount,payment_method,notes) VALUES(?,?,?,?,?,?)');
                    $s->bind_param('sssdss',$r['expense_date'],$r['category'],$r['description'],$amount,$r['payment_method'],$notes); $s->execute(); $s->close();
                    add_stat($stats,'expenses.csv','inserted');
                }
            }

            if (isset($files['inventory.csv'])) {
                [$h,$rows] = csv_rows($files['inventory.csv']);
                require_headers('inventory.csv', $h, ['sku','item_name','category','unit','quantity','reorder_level','unit_cost','supplier']);
                foreach ($rows as $r) {
                    if (one_id($conn,'inventory_items','sku',$r['sku'])) { add_stat($stats,'inventory.csv','existing'); continue; }
                    $supid=null;
                    if ($r['supplier'] !== '') {
                        $supid=one_id($conn,'hotel_suppliers','supplier_name',$r['supplier']);
                        if (!$supid) { $ss=$conn->prepare('INSERT INTO hotel_suppliers(supplier_name) VALUES(?)'); $ss->bind_param('s',$r['supplier']); $ss->execute(); $supid=$conn->insert_id; $ss->close(); }
                    }
                    $qty=(float)$r['quantity']; $reorder=(float)$r['reorder_level']; $cost=(float)$r['unit_cost'];
                    $status=$qty <= $reorder ? 'Low Stock' : 'In Stock';
                    $s=$conn->prepare('INSERT INTO inventory_items(sku,item_name,category,unit,quantity,reorder_level,unit_cost,supplier_id,status) VALUES(?,?,?,?,?,?,?,?,?)');
                    $s->bind_param('ssssdddis',$r['sku'],$r['item_name'],$r['category'],$r['unit'],$qty,$reorder,$cost,$supid,$status); $s->execute(); $s->close();
                    add_stat($stats,'inventory.csv','inserted');
                }
            }

            if (isset($files['housekeeping.csv'])) {
                [$h,$rows] = csv_rows($files['housekeeping.csv']);
                require_headers('housekeeping.csv', $h, ['room_number','employee_code','task_date','task_type','status','notes']);
                foreach ($rows as $r) {
                    $rid=one_id($conn,'rooms','room_number',$r['room_number']);
                    $sid=$staffMap[$r['employee_code']] ?? one_id($conn,'staff','employee_no',$r['employee_code']);
                    if (!$rid || !$sid) { add_stat($stats,'housekeeping.csv','skipped','Line '.$r['__line'].': room or employee reference not found.'); continue; }
                    $s=$conn->prepare('INSERT INTO housekeeping_tasks(room_id,task_date,task_type,assigned_to,status,notes) VALUES(?,?,?,?,?,?)');
                    $s->bind_param('ississ',$rid,$r['task_date'],$r['task_type'],$sid,$r['status'],$r['notes']); $s->execute(); $s->close();
                    add_stat($stats,'housekeeping.csv','inserted');
                }
            }

            if (isset($files['maintenance.csv'])) {
                [$h,$rows] = csv_rows($files['maintenance.csv']);
                require_headers('maintenance.csv', $h, ['room_number','description','priority','status','employee_code']);
                if (!in_array('request_date',$h,true)) {
                    foreach ($rows as $r) add_stat($stats,'maintenance.csv','skipped');
                    $stats['maintenance.csv']['notes'][] = 'All rows skipped: maintenance.csv has no request_date, but maintenance_requests.request_date is NOT NULL. A historical date cannot be safely invented.';
                } else {
                    foreach ($rows as $r) {
                        $rid=one_id($conn,'rooms','room_number',$r['room_number']);
                        $sid=$staffMap[$r['employee_code']] ?? one_id($conn,'staff','employee_no',$r['employee_code']);
                        if (!$rid) { add_stat($stats,'maintenance.csv','skipped','Line '.$r['__line'].': room not found: '.$r['room_number']); continue; }
                        $category=$r['title'] ?? null;
                        $s=$conn->prepare('INSERT INTO maintenance_requests(room_id,reported_by,request_date,category,priority,status,description) VALUES(?,?,?,?,?,?,?)');
                        $s->bind_param('iisssss',$rid,$sid,$r['request_date'],$category,$r['priority'],$r['status'],$r['description']); $s->execute(); $s->close();
                        add_stat($stats,'maintenance.csv','inserted');
                    }
                }
            }

            // Bookings before payments; legacy_ref is kept in memory for payment foreign-key resolution.
            if (isset($files['bookings.csv'])) {
                [$h,$rows] = csv_rows($files['bookings.csv']);
                require_headers('bookings.csv', $h, ['legacy_ref','customer_email','room_number','check_in','check_out','total_price','status','num_guests']);
                foreach ($rows as $r) {
                    $cid=one_id($conn,'customers','email',$r['customer_email']);
                    $rid=one_id($conn,'rooms','room_number',$r['room_number']);
                    if (!$cid || !$rid) { add_stat($stats,'bookings.csv','skipped','Line '.$r['__line'].': customer or room reference not found.'); continue; }
                    $price=(float)$r['total_price'];
                    $id=booking_id_by_match($conn,$cid,$rid,$r['check_in'],$r['check_out'],$price);
                    if (!$id) {
                        $guests=max(1,(int)$r['num_guests']);
                        $s=$conn->prepare('INSERT INTO bookings(customer_id,room_id,check_in,check_out,total_price,status,num_guests) VALUES(?,?,?,?,?,?,?)');
                        $s->bind_param('iissdsi',$cid,$rid,$r['check_in'],$r['check_out'],$price,$r['status'],$guests); $s->execute(); $id=$conn->insert_id; $s->close();
                        add_stat($stats,'bookings.csv','inserted');
                    } else add_stat($stats,'bookings.csv','existing');
                    $bookingMap[$r['legacy_ref']]=$id;
                }
            }

            if (isset($files['payments.csv'])) {
                [$h,$rows] = csv_rows($files['payments.csv']);
                require_headers('payments.csv', $h, ['legacy_ref','amount','payment_method','status','payment_date']);
                $validMethods=['Cash','Card','Mobile Money'];
                foreach ($rows as $r) {
                    $bid=$bookingMap[$r['legacy_ref']] ?? null;
                    if (!$bid) { add_stat($stats,'payments.csv','skipped','Line '.$r['__line'].': booking legacy_ref is not available in this import: '.$r['legacy_ref']); continue; }
                    if (!in_array($r['payment_method'],$validMethods,true)) { add_stat($stats,'payments.csv','skipped','Line '.$r['__line'].': unsupported payment_method for current schema: '.$r['payment_method']); continue; }
                    $amount=(float)$r['amount'];
                    if (payment_exists($conn,$bid,$amount,$r['payment_date'])) { add_stat($stats,'payments.csv','existing'); continue; }
                    $s=$conn->prepare('INSERT INTO payments(booking_id,amount,payment_method,payment_date,status) VALUES(?,?,?,?,?)');
                    $s->bind_param('idsss',$bid,$amount,$r['payment_method'],$r['payment_date'],$r['status']); $s->execute(); $s->close();
                    add_stat($stats,'payments.csv','inserted');
                }
            }

            $conn->commit();

            $inserted=$existing=$skipped=0;
            foreach ($stats as $file=>$st) {
                $inserted += $st['inserted']; $existing += $st['existing']; $skipped += $st['skipped'];
                $details[] = $file . ': ' . $st['inserted'] . ' inserted, ' . $st['existing'] . ' already present, ' . $st['skipped'] . ' skipped.';
                foreach ($st['notes'] as $note) $details[] = '  - ' . $note;
            }
            $success_msg = "Import transaction committed: $inserted rows inserted and $existing existing rows matched.";
            if ($skipped > 0) $warning_msg = "$skipped rows were skipped. Review the details below; skipped rows are not reported as imported.";
        } catch (Throwable $e) {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
            $error_msg = 'Import failed and all database changes were rolled back: ' . $e->getMessage();
        } finally {
            cleanup_dir($dir);
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
include '../../includes/navbar.php';
?>
<div class="container-fluid p-0">
    <div class="mb-4">
        <h1 class="h3 font-weight-bold text-dark mb-1">Historical Hotel Data Import</h1>
        <p class="text-muted small">Import CSV/ZIP data using the columns and relationships in the current hotel_management schema.</p>
    </div>
    <?php if($success_msg):?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_msg);?></div><?php endif;?>
    <?php if($warning_msg):?><div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($warning_msg);?></div><?php endif;?>
    <?php if($error_msg):?><div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error_msg);?></div><?php endif;?>
    <?php if($details):?><div class="card border-0 shadow-sm mb-4"><div class="card-body"><h6 class="font-weight-bold">Import details</h6><pre class="small mb-0" style="white-space:pre-wrap"><?php echo htmlspecialchars(implode("\n",$details));?></pre></div></div><?php endif;?>
    <div class="row"><div class="col-lg-7"><div class="card border-0 shadow-sm"><div class="card-body p-4">
        <h5 class="font-weight-bold">Import CSV / ZIP</h5>
        <form method="post" enctype="multipart/form-data"><div class="form-group"><label class="font-weight-bold">Dataset file</label><input type="file" name="data_file" class="form-control-file" accept=".csv,.zip" required><small class="text-muted">Supported: room types, customers, rooms, staff, attendance, bookings, payments, expenses, inventory, housekeeping and maintenance.</small></div><button class="btn btn-success"><i class="fas fa-upload mr-1"></i> Import Historical Data</button></form>
    </div></div></div></div>
</div>
<?php include '../../includes/footer.php'; ?>
