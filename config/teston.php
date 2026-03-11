cart is working

we did

C:\laragon\www\Priyesh Project\ecommerce\classes\Cart.php
<?php

// OOP CONCEPT: CLASS
// Cart class manages everything related to the shopping cart.
// 
// IMPORTANT CONCEPT: We are storing the cart in PHP SESSIONS
// (not database) — this means cart data lives in server memory
// and is available across all pages until user logs out.
//
// Think of SESSION like a temporary locker assigned to each user.

class Cart {

    // OOP CONCEPT: PROPERTIES
    private $conn;       // DB connection — to fetch product details
    private $cart_key;   // Unique session key per user e.g. "cart_5"

    // OOP CONCEPT: CONSTRUCTOR
    // We pass $db AND $user_id so each user gets their OWN cart
    // This is important — user 1 and user 2 should NOT share a cart!

    public function __construct($db, $user_id) {
        $this->conn     = $db;
        // Each user gets a unique cart key in session: cart_1, cart_2 etc.
        $this->cart_key = 'cart_' . $user_id;

        // If this user has no cart yet in session, create empty one
        if(!isset($_SESSION[$this->cart_key])) {
            $_SESSION[$this->cart_key] = [];
        }
    }

    // -------------------------------------------------------
    // METHOD 1: addItem()
    // Adds a product to cart or increases quantity if already exists
    // OOP CONCEPT: Method that manipulates SESSION data
    // -------------------------------------------------------

    public function addItem($product_id, $quantity = 1) {

        // Fetch product from DB to get latest price & stock
        $query = "SELECT id, name, price, stock, image 
                  FROM products 
                  WHERE id = :id AND stock > 0 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // If product doesn't exist or out of stock — stop
        if(!$product) {
            return ['success' => false, 'message' => 'Product not available.'];
        }

        // Reference to our cart in session (& means we edit the real session)
        $cart = &$_SESSION[$this->cart_key];

        // Check if product already in cart
        if(isset($cart[$product_id])) {
            // Calculate new quantity after adding
            $new_qty = $cart[$product_id]['quantity'] + $quantity;

            // Don't allow quantity to exceed available stock
            if($new_qty > $product['stock']) {
                return ['success' => false, 
                        'message' => "Only {$product['stock']} items available."];
            }
            $cart[$product_id]['quantity'] = $new_qty;

        } else {
            // Product not in cart yet — add it as new entry
            $cart[$product_id] = [
                'product_id' => $product['id'],
                'name'       => $product['name'],
                'price'      => $product['price'],
                'image'      => $product['image'],
                'quantity'   => $quantity,
                'stock'      => $product['stock']
            ];
        }

        return ['success' => true, 'message' => "'{$product['name']}' added to cart!"];
    }

    // -------------------------------------------------------
    // METHOD 2: removeItem()
    // Removes a specific product from the cart completely
    // -------------------------------------------------------

    public function removeItem($product_id) {
        if(isset($_SESSION[$this->cart_key][$product_id])) {
            $name = $_SESSION[$this->cart_key][$product_id]['name'];
            unset($_SESSION[$this->cart_key][$product_id]);
            return ['success' => true, 'message' => "'{$name}' removed from cart."];
        }
        return ['success' => false, 'message' => 'Item not found in cart.'];
    }

    // -------------------------------------------------------
    // METHOD 3: updateQuantity()
    // Changes the quantity of an existing cart item
    // -------------------------------------------------------

    public function updateQuantity($product_id, $quantity) {

        if(!isset($_SESSION[$this->cart_key][$product_id])) {
            return ['success' => false, 'message' => 'Item not in cart.'];
        }

        // If quantity is 0 or less — just remove the item
        if($quantity <= 0) {
            return $this->removeItem($product_id);
            // OOP CONCEPT: Calling another method of the SAME class using $this
        }

        $stock = $_SESSION[$this->cart_key][$product_id]['stock'];

        if($quantity > $stock) {
            return ['success' => false, 
                    'message' => "Only $stock items in stock."];
        }

        $_SESSION[$this->cart_key][$product_id]['quantity'] = $quantity;
        return ['success' => true, 'message' => 'Quantity updated.'];
    }

    // -------------------------------------------------------
    // METHOD 4: getItems()
    // Returns all items currently in the cart
    // -------------------------------------------------------

    public function getItems() {
        return $_SESSION[$this->cart_key] ?? [];
        // ?? is the NULL COALESCING operator
        // means: "return cart if it exists, else return empty array"
    }

    // -------------------------------------------------------
    // METHOD 5: getTotal()
    // Calculates the total price of everything in cart
    // OOP CONCEPT: Method that computes and returns a value
    // -------------------------------------------------------

    public function getTotal() {
        $total = 0;
        foreach($_SESSION[$this->cart_key] as $item) {
            // Total = sum of (price × quantity) for each item
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    // -------------------------------------------------------
    // METHOD 6: getItemCount()
    // Returns total number of individual items in cart
    // -------------------------------------------------------

    public function getItemCount() {
        $count = 0;
        foreach($_SESSION[$this->cart_key] as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    // -------------------------------------------------------
    // METHOD 7: clearCart()
    // Empties the entire cart — called after order is placed
    // -------------------------------------------------------

    public function clearCart() {
        $_SESSION[$this->cart_key] = [];
    }

    // -------------------------------------------------------
    // METHOD 8: isEmpty()
    // Returns true if cart has no items
    // -------------------------------------------------------

    public function isEmpty() {
        return empty($_SESSION[$this->cart_key]);
    }
}
?>

C:\laragon\www\Priyesh Project\ecommerce\pages\cart.php
<?php
session_start();

// Only logged-in users can have a cart
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=cart');
    exit();
}

require_once '../config/Database.php';
require_once '../classes/Cart.php';

$database = new Database();
$db       = $database->getConnection();

// OOP: Create Cart object — pass DB + current user's ID
// Each user gets their own isolated cart
$cart = new Cart($db, $_SESSION['user_id']);

$message = '';
$msg_type = '';

// -------------------------------------------------------
// Handle all cart ACTIONS via URL parameter ?action=...
// OOP CONCEPT: One page handles multiple actions cleanly
// -------------------------------------------------------

$action = $_GET['action'] ?? '';

switch($action) {

    case 'add':
        $product_id = (int)($_GET['id'] ?? 0);
        if($product_id > 0) {
            $result   = $cart->addItem($product_id);
            $message  = $result['message'];
            $msg_type = $result['success'] ? 'success' : 'error';
        }
        break;

    case 'remove':
        $product_id = (int)($_GET['id'] ?? 0);
        if($product_id > 0) {
            $result   = $cart->removeItem($product_id);
            $message  = $result['message'];
            $msg_type = $result['success'] ? 'success' : 'error';
        }
        break;

    case 'update':
        // This is triggered by the quantity form
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity   = (int)($_POST['quantity']   ?? 0);
            $result     = $cart->updateQuantity($product_id, $quantity);
            $message    = $result['message'];
            $msg_type   = $result['success'] ? 'success' : 'error';
        }
        break;

    case 'clear':
        $cart->clearCart();
        $message  = '🗑️ Cart cleared.';
        $msg_type = 'success';
        break;
}

// Get current cart items and totals for display
$items      = $cart->getItems();
$total      = $cart->getTotal();
$item_count = $cart->getItemCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart</title>
    <style>
        * { box-sizing:border-box; }
        body  { font-family:Arial; background:#f0f2f5; margin:0; padding:20px; }

        .navbar { display:flex; justify-content:space-between; align-items:center;
                  background:#4f46e5; padding:12px 25px; border-radius:8px; 
                  margin-bottom:25px; }
        .navbar a    { color:#fff; text-decoration:none; margin-left:15px; font-size:14px; }
        .navbar span { color:#fff; font-weight:bold; font-size:16px; }

        h1 { text-align:center; color:#333; }

        .message { padding:12px 20px; border-radius:8px; margin-bottom:20px; 
                   text-align:center; font-weight:bold; }
        .success { background:#d1fae5; color:#065f46; }
        .error   { background:#fee2e2; color:#991b1b; }

        /* Cart Table */
        .cart-container { max-width:850px; margin:auto; }
        table  { width:100%; border-collapse:collapse; background:#fff;
                 border-radius:10px; overflow:hidden;
                 box-shadow:0 2px 10px rgba(0,0,0,0.08); }
        th     { background:#4f46e5; color:#fff; padding:14px; text-align:left; }
        td     { padding:14px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }

        .product-img { width:60px; height:60px; object-fit:cover; 
                       border-radius:6px; background:#eee; }

        /* Quantity form */
        .qty-form   { display:flex; align-items:center; gap:6px; }
        .qty-input  { width:60px; padding:6px; border:1px solid #ddd; 
                      border-radius:6px; text-align:center; }
        .btn-update { padding:6px 12px; background:#4f46e5; color:#fff; 
                      border:none; border-radius:6px; cursor:pointer; font-size:13px; }
        .btn-remove { padding:6px 12px; background:#ef4444; color:#fff; 
                      border:none; border-radius:6px; cursor:pointer; 
                      text-decoration:none; font-size:13px; }

        /* Summary box */
        .summary { background:#fff; border-radius:10px; padding:25px;
                   box-shadow:0 2px 10px rgba(0,0,0,0.08); 
                   max-width:850px; margin:20px auto; }
        .summary-row { display:flex; justify-content:space-between; 
                        padding:8px 0; border-bottom:1px solid #f0f0f0; }
        .summary-row:last-child { border-bottom:none; }
        .total-row { font-size:20px; font-weight:bold; color:#4f46e5; }

        .btn-checkout { display:block; text-align:center; background:#22c55e; 
                        color:#fff; padding:14px; border-radius:8px; 
                        text-decoration:none; font-size:16px; font-weight:bold;
                        margin-top:15px; }
        .btn-checkout:hover { background:#16a34a; }
        .btn-shop     { display:block; text-align:center; background:#4f46e5;
                        color:#fff; padding:12px; border-radius:8px; 
                        text-decoration:none; margin-top:10px; }
        .btn-clear    { display:block; text-align:center; background:#ef4444;
                        color:#fff; padding:10px; border-radius:8px; 
                        text-decoration:none; margin-top:10px; font-size:14px; }

        .empty-cart { text-align:center; padding:60px 20px; color:#888; font-size:18px; }
    </style>
</head>
<body>

<div class="navbar">
    <span>🛍️ MyShop</span>
    <div>
        <a href="products.php">🏪 Shop</a>
        <a href="#">👋 <?= htmlspecialchars($_SESSION['user_name']) ?></a>
        <a href="cart.php">🛒 Cart (<?= $item_count ?>)</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<h1>🛒 My Cart</h1>

<div class="cart-container">

    <?php if($message): ?>
        <div class="message <?= $msg_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if(!$cart->isEmpty()): ?>

        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($items as $product_id => $item): ?>
                <tr>
                    <td>
                        <?php if($item['image']): ?>
                            <img class="product-img" 
                                 src="../uploads/products/<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>">
                        <?php else: ?>
                            <img class="product-img" 
                                 src="https://via.placeholder.com/60?text=N/A" alt="No image">
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td>
                        <!-- Quantity update form -->
                        <form class="qty-form" method="POST" 
                              action="cart.php?action=update">
                            <input type="hidden" name="product_id" 
                                   value="<?= $product_id ?>">
                            <input class="qty-input" type="number" 
                                   name="quantity" 
                                   value="<?= $item['quantity'] ?>" 
                                   min="1" 
                                   max="<?= $item['stock'] ?>">
                            <button class="btn-update" type="submit">↻</button>
                        </form>
                    </td>
                    <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    <td>
                        <a class="btn-remove" 
                           href="cart.php?action=remove&id=<?= $product_id ?>"
                           onclick="return confirm('Remove this item?')">✕</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Order Summary -->
        <div class="summary">
            <h3>📋 Order Summary</h3>
            <div class="summary-row">
                <span>Total Items</span>
                <span><?= $item_count ?> items</span>
            </div>
            <div class="summary-row total-row">
                <span>Total Amount</span>
                <span>₹<?= number_format($total, 2) ?></span>
            </div>
            <a href="checkout.php"           class="btn-checkout">✅ Proceed to Checkout</a>
            <a href="products.php"           class="btn-shop">🏪 Continue Shopping</a>
            <a href="cart.php?action=clear"  class="btn-clear"
               onclick="return confirm('Clear entire cart?')">🗑️ Clear Cart</a>
        </div>

    <?php else: ?>
        <div class="empty-cart">
            <p>😕 Your cart is empty!</p>
            <a href="products.php" class="btn-shop" 
               style="display:inline-block; margin-top:15px;">
               🏪 Start Shopping
            </a>
        </div>
    <?php endif; ?>

</div>
</body>
</html>

C:\laragon\www\Priyesh Project\ecommerce\pages\logout.php
<?php
session_start();
session_destroy(); // Destroys ALL session data including cart
header('Location: login.php');
exit();
?>