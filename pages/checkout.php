<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/Database.php';
require_once '../classes/Cart.php';
require_once '../classes/Order.php';

$database = new Database();
$db       = $database->getConnection();

// OOP: Create Cart and Order objects
$cart  = new Cart($db, $_SESSION['user_id']);
$order = new Order($db);

// If cart is empty — redirect back to shop
if($cart->isEmpty()) {
    header('Location: products.php');
    exit();
}

$items   = $cart->getItems();
$total   = $cart->getTotal();
$message = '';
$msg_type = '';
$order_placed = false;
$placed_order_id = null;

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Set order properties
    $order->user_id      = $_SESSION['user_id'];
    $order->total_amount = $total;

    // OOP: Call placeOrder() — pass cart items
    $result = $order->placeOrder($items);

    if($result['success']) {
        // Clear cart after successful order
        $cart->clearCart();
        $order_placed    = true;
        $placed_order_id = $result['order_id'];
    } else {
        $message  = $result['message'];
        $msg_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <style>
        * { box-sizing:border-box; }
        body { font-family:Arial; background:#f0f2f5; margin:0; padding:20px; }

        .navbar { display:flex; justify-content:space-between; align-items:center;
                  background:#4f46e5; padding:12px 25px; border-radius:8px;
                  margin-bottom:25px; }
        .navbar a    { color:#fff; text-decoration:none; margin-left:15px; }
        .navbar span { color:#fff; font-weight:bold; font-size:16px; }

        h1 { text-align:center; color:#333; }
        .container { max-width:750px; margin:auto; }

        /* Order review table */
        table { width:100%; border-collapse:collapse; background:#fff;
                border-radius:10px; overflow:hidden;
                box-shadow:0 2px 10px rgba(0,0,0,0.08); margin-bottom:20px; }
        th { background:#4f46e5; color:#fff; padding:13px; text-align:left; }
        td { padding:13px; border-bottom:1px solid #f0f0f0; }
        tr:last-child td { border-bottom:none; }

        .product-img { width:50px; height:50px; object-fit:cover; border-radius:6px; }

        /* Summary */
        .summary { background:#fff; border-radius:10px; padding:25px;
                   box-shadow:0 2px 10px rgba(0,0,0,0.08); }
        .summary-row { display:flex; justify-content:space-between;
                       padding:9px 0; border-bottom:1px solid #f5f5f5; }
        .total-row { font-size:20px; font-weight:bold; color:#4f46e5; border:none; }

        .btn-place { width:100%; padding:14px; background:#22c55e; color:#fff;
                     border:none; border-radius:8px; font-size:16px; 
                     font-weight:bold; cursor:pointer; margin-top:15px; }
        .btn-place:hover { background:#16a34a; }

        /* Success box */
        .success-box { background:#d1fae5; border:2px solid #22c55e; 
                       border-radius:10px; padding:40px; text-align:center; }
        .success-box h2 { color:#065f46; font-size:26px; }
        .success-box p  { color:#064e3b; font-size:16px; }
        .btn-link { display:inline-block; margin:8px; padding:11px 24px;
                    background:#4f46e5; color:#fff; border-radius:8px;
                    text-decoration:none; font-weight:bold; }

        .message { padding:12px; border-radius:8px; margin-bottom:15px;
                   text-align:center; font-weight:bold; }
        .error   { background:#fee2e2; color:#991b1b; }
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

<?php if($order_placed): ?>

    <!-- SUCCESS STATE — shown after order is placed -->
    <div class="success-box">
        <h2>🎉 Order Placed Successfully!</h2>
        <p>Your Order ID is: <strong>#<?= $placed_order_id ?></strong></p>
        <p>Thank you, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>!<br>
           Your order is being processed.</p>
        <a href="products.php"  class="btn-link">🏪 Continue Shopping</a>
        <a href="my_orders.php" class="btn-link" 
           style="background:#22c55e;">📦 View My Orders</a>
    </div>

<?php else: ?>

    <h1>✅ Checkout</h1>

    <?php if($message): ?>
        <div class="message <?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Review items before placing order -->
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($items as $item): ?>
            <tr>
                <td>
                    <?php if($item['image']): ?>
                        <img class="product-img"
                             src="../uploads/products/<?= htmlspecialchars($item['image']) ?>"
                             alt="">
                    <?php else: ?>
                        <img class="product-img"
                             src="https://via.placeholder.com/50?text=N/A" alt="">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>₹<?= number_format($item['price'], 2) ?></td>
                <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Summary + Place Order -->
    <div class="summary">
        <h3>📋 Order Summary</h3>
        <div class="summary-row">
            <span>Items</span>
            <span><?= $cart->getItemCount() ?> items</span>
        </div>
        <div class="summary-row">
            <span>Shipping</span>
            <span style="color:green;">FREE ✅</span>
        </div>
        <div class="summary-row total-row">
            <span>Total</span>
            <span>₹<?= number_format($total, 2) ?></span>
        </div>

        <form method="POST">
            <button class="btn-place" type="submit">
                🛍️ Place Order — ₹<?= number_format($total, 2) ?>
            </button>
        </form>
    </div>

<?php endif; ?>
</div>
</body>
</html>