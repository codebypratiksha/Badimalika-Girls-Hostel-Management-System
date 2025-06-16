<?php
session_start();
require 'config.php';

// Only admin allowed
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_form.php");
    exit();
}

$message = '';

// Mark payment as paid
if (isset($_POST['mark_paid'])) {
    $payment_id = intval($_POST['payment_id']);
    $stmt = $conn->prepare("UPDATE payments SET status='Paid', date_paid=NOW() WHERE id = ?");
    $stmt->bind_param("i", $payment_id);
    if ($stmt->execute()) {
        $message = "Payment marked as paid.";
    } else {
        $message = "Error updating payment status.";
    }
    $stmt->close();
}

// Fetch users with their latest payment record (if any)
$sql = "
    SELECT 
        u.id AS user_id,
        u.name AS student_name,
        p.id AS payment_id,
        p.amount,
        p.status,
        p.date_paid,
        p.payment_date
    FROM users u
    LEFT JOIN payments p ON p.id = (
        SELECT p2.id FROM payments p2 
        WHERE p2.user_id = u.id 
        ORDER BY p2.payment_date DESC LIMIT 1
    )
    WHERE u.role = 'user'
    ORDER BY u.name ASC
";

$students = $conn->query($sql);

// Separate data into paid and unpaid arrays
$paid_data = [];
$unpaid_data = [];

if ($students && $students->num_rows > 0) {
    while ($row = $students->fetch_assoc()) {
        if ($row['status'] === 'Paid') {
            $paid_data[] = $row;
        } else {
            $unpaid_data[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Payments</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f7f9ff; }
        h2 { color: #4d93f6; }
        .tabs {
            margin-bottom: 20px;
        }
        button.tab-btn {
            padding: 10px 25px;
            margin-right: 10px;
            border: 1px solid #4d93f6;
            background: white;
            color: #4d93f6;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button.tab-btn.active, button.tab-btn:hover {
            background: #4d93f6;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #4d93f6;
            color: white;
        }
        form.inline {
            display: inline;
        }
        .message {
            color: green;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .back-link { margin-top: 20px; display: inline-block; color: #4d93f6; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
    <script>
        function showTab(tabName) {
            document.getElementById('paid').style.display = tabName === 'paid' ? 'block' : 'none';
            document.getElementById('unpaid').style.display = tabName === 'unpaid' ? 'block' : 'none';

            document.getElementById('btnPaid').classList.toggle('active', tabName === 'paid');
            document.getElementById('btnUnpaid').classList.toggle('active', tabName === 'unpaid');

            // Save tab to localStorage
            localStorage.setItem('selectedTab', tabName);
        }

        window.onload = function() {
            const savedTab = localStorage.getItem('selectedTab') || 'unpaid';
            showTab(savedTab);
        };
    </script>
</head>
<body>

<h2>Manage Payments</h2>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="tabs">
    <button id="btnUnpaid" class="tab-btn" onclick="showTab('unpaid')">Unpaid Students</button>
    <button id="btnPaid" class="tab-btn" onclick="showTab('paid')">Paid Students</button>
</div>

<div id="unpaid" style="display:none;">
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($unpaid_data) > 0): ?>
                <?php foreach ($unpaid_data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['amount'] ?? 'Not set') ?></td>
                        <td><?= htmlspecialchars($row['payment_date'] ?? '-') ?></td>
                        <td>
                            <?php if ($row['payment_id']): ?>
                                <form method="post" class="inline">
                                    <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>" />
                                    <button type="submit" name="mark_paid">Mark Paid</button>
                                </form>
                            <?php else: ?>
                                No payment record
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">No unpaid students.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="paid" style="display:none;">
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Date Paid</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($paid_data) > 0): ?>
                <?php foreach ($paid_data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['amount']) ?></td>
                        <td><?= htmlspecialchars($row['payment_date']) ?></td>
                        <td><?= htmlspecialchars($row['date_paid']) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No paid students.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<a href="dashboard_admin.php" class="back-link">← Back to Dashboard</a>

</body>
</html>
