<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

require_once '../config/Database.php';
require_once '../classes/Order.php';

$database = new Database();
$db       = $database->getConnection();
$order    = new Order($db);

// Handle status update form
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order->updateStatus((int)$_POST['order_id'], $_POST['status']);
    header('Location: index.php');
    exit();
}

$stmt = $order->getAllOrders();

$status_colors = [
    'pending'    => '#f59e0b',
    'processing' => '#3b82f6',
    'delivered'  => '#22c55e'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        * { box-sizing:border-box; }
        body  { font-family:Arial; background:#f0f2f5; margin:0; padding:20px; }
        .navbar { display:flex; justify-content:space-between; align-items:center;
                  background:#1e1b4b; padding:12px 25px; border-radius:8px;
                  margin-bottom:25px; }
        .navbar a    { color:#fff; text-decoration:none; margin-left:15px; }
        .navbar span { color:#fff; font-weight:bold; font-size:16px; }
        h1 { text-align:center; color:#333; }

        table { width:100%; border-collapse:collapse; background:#fff;
                border-radius:10px; overflow:hidden;
                box-shadow:0 2px 10px rgba(0,0,0,0.08); }
        th { background:#1e1b4b; color:#fff; padding:13px; text-align:left; }
        td { padding:12px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }

        .badge { padding:4px 12px; border-radius:20px; color:#fff;
                 font-size:12px; font-weight:bold; }
        select { padding:6px 10px; border:1px solid #ddd; border-radius:6px; }
        .btn-update { padding:6px 12px; background:#4f46e5; color:#fff;
                      border:none; border-radius:6px; cursor:pointer; }
    </style>
</head>
<body>

<div class="navbar">
    <span>⚙️ Admin Panel</span>
    <div>
        <a href="add_product.php">➕ Add Product</a>
        <a href="../pages/products.php">🏪 View Shop</a>
        <a href="../pages/logout.php">Logout</a>
    </div>
</div>

<h1>📋 All Orders</h1>

<table>
    <thead>
        <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Items</th>
            <th>Total</th>
            <th>Date</th>
            <th>Status</th>
            <th>Update</th>
        </tr>
    </thead>
    <tbody>
    <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <tr>
            <td><strong>#<?= $row['id'] ?></strong></td>
            <td><?= htmlspecialchars($row['user_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= $row['item_count'] ?> item(s)</td>
            <td>₹<?= number_format($row['total_amount'], 2) ?></td>
            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td>
                <span class="badge"
                      style="background:<?= $status_colors[$row['status']] ?>">
                    <?= ucfirst($row['status']) ?>
                </span>
            </td>
            <td>
                <!-- Admin can update order status inline -->
                <form method="POST" style="display:flex; gap:6px;">
                    <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                    <select name="status">
                        <option value="pending"    
                            <?= $row['status']==='pending'    ? 'selected':'' ?>>Pending</option>
                        <option value="processing" 
                            <?= $row['status']==='processing' ? 'selected':'' ?>>Processing</option>
                        <option value="delivered"  
                            <?= $row['status']==='delivered'  ? 'selected':'' ?>>Delivered</option>
                    </select>
                    <button class="btn-update" type="submit">Update</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>