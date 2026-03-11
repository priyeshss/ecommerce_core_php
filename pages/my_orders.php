<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/Database.php';
require_once '../classes/Order.php';

$database = new Database();
$db       = $database->getConnection();

$order = new Order($db);
$stmt  = $order->getUserOrders($_SESSION['user_id']);

// Status badge colors
// OOP CONCEPT: Using an associative array as a lookup map
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
    <title>My Orders</title>
    <style>
        * { box-sizing:border-box; }
        body  { font-family:Arial; background:#f0f2f5; margin:0; padding:20px; }
        .navbar { display:flex; justify-content:space-between; align-items:center;
                  background:#4f46e5; padding:12px 25px; border-radius:8px;
                  margin-bottom:25px; }
        .navbar a    { color:#fff; text-decoration:none; margin-left:15px; }
        .navbar span { color:#fff; font-weight:bold; font-size:16px; }
        h1 { text-align:center; color:#333; }

        .container { max-width:850px; margin:auto; }
        table { width:100%; border-collapse:collapse; background:#fff;
                border-radius:10px; overflow:hidden;
                box-shadow:0 2px 10px rgba(0,0,0,0.08); }
        th { background:#4f46e5; color:#fff; padding:13px; text-align:left; }
        td { padding:13px; border-bottom:1px solid #f0f0f0; }
        tr:last-child td { border-bottom:none; }

        .badge { padding:4px 12px; border-radius:20px; color:#fff; 
                 font-size:12px; font-weight:bold; }
        .btn-view { padding:6px 14px; background:#4f46e5; color:#fff;
                    border-radius:6px; text-decoration:none; font-size:13px; }
        .empty { text-align:center; padding:60px; color:#888; font-size:18px; }
    </style>
</head>
<body>

<div class="navbar">
    <span>🛍️ MyShop</span>
    <div>
        <a href="products.php">🏪 Shop</a>
        <a href="cart.php">🛒 Cart</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h1>📦 My Orders</h1>

    <?php if($stmt->rowCount() > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><strong>#<?= $row['id'] ?></strong></td>
                <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                <td><?= $row['item_count'] ?> item(s)</td>
                <td>₹<?= number_format($row['total_amount'], 2) ?></td>
                <td>
                    <span class="badge" 
                          style="background:<?= $status_colors[$row['status']] ?>">
                        <?= ucfirst($row['status']) ?>
                    </span>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php else: ?>
        <div class="empty">
            😕 You haven't placed any orders yet.<br>
            <a href="products.php" 
               style="color:#4f46e5; font-weight:bold;">Start Shopping →</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>