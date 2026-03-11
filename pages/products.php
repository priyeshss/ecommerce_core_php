<?php
session_start();
require_once '../config/Database.php';
require_once '../classes/Product.php';

$database = new Database();
$db       = $database->getConnection();

// OOP: Instantiate Product object
$product  = new Product($db);

// Call getAllProducts() — returns PDO statement
$stmt     = $product->getAllProducts();
$count    = $stmt->rowCount(); // How many products found
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop</title>
    <style>
        body  { font-family:Arial; background:#f0f2f5; margin:0; padding:20px; }
        h1    { text-align:center; color:#333; }

        /* Top nav bar */
        .navbar { display:flex; justify-content:space-between; align-items:center;
                  background:#4f46e5; padding:12px 25px; border-radius:8px; 
                  margin-bottom:25px; }
        .navbar a    { color:#fff; text-decoration:none; margin-left:15px; font-size:14px; }
        .navbar span { color:#fff; font-weight:bold; font-size:16px; }

        /* Product grid */
        .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(230px,1fr));
                gap:20px; }
        .card { background:#fff; border-radius:10px; overflow:hidden;
                box-shadow:0 2px 10px rgba(0,0,0,0.08); transition:transform 0.2s; }
        .card:hover { transform:translateY(-4px); }
        .card img   { width:100%; height:190px; object-fit:cover; 
                      background:#eee; }
        .card-body  { padding:14px; }
        .card-body h3  { margin:0 0 6px; font-size:15px; color:#222; }
        .card-body p   { margin:0 0 10px; font-size:13px; color:#666; 
                         line-height:1.4; }
        .price   { font-size:17px; font-weight:bold; color:#4f46e5; }
        .stock   { font-size:12px; color:#888; margin-bottom:10px; }
        .btn-cart { display:block; text-align:center; background:#4f46e5; 
                    color:#fff; padding:9px; border-radius:6px; 
                    text-decoration:none; font-size:14px; }
        .btn-cart:hover  { background:#4338ca; }
        .out-of-stock    { background:#ccc; pointer-events:none; }
        .no-products     { text-align:center; color:#888; margin-top:60px; font-size:18px; }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<div class="navbar">
    <span>🛍️ MyShop</span>
    <div>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="#">👋 <?= htmlspecialchars($_SESSION['user_name']) ?></a>
            <a href="cart.php">🛒 Cart</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <a href="../admin/index.php">⚙️ Admin</a>
        <?php endif; ?>
    </div>
</div>

<h1>🛒 Our Products</h1>

<?php if($count > 0): ?>
    <div class="grid">
    <?php while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
        <div class="card">
            <!-- Show image if exists, else show placeholder -->
            <?php if($row['image']): ?>
                <img src="../uploads/products/<?= htmlspecialchars($row['image']) ?>" 
                     alt="<?= htmlspecialchars($row['name']) ?>">
            <?php else: ?>
                <img src="https://via.placeholder.com/230x190?text=No+Image" 
                     alt="No Image">
            <?php endif; ?>

            <div class="card-body">
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                <p><?= htmlspecialchars(substr($row['description'], 0, 70)) ?>...</p>
                <div class="price">₹<?= number_format($row['price'], 2) ?></div>
                <div class="stock">
                    <?= $row['stock'] > 0 ? "✅ In Stock ({$row['stock']})" : "❌ Out of Stock" ?>
                </div>

                <!-- Add to Cart — only if logged in and in stock -->
                <?php if($row['stock'] > 0): ?>
                    <a href="cart.php?action=add&id=<?= $row['id'] ?>" 
                       class="btn-cart">🛒 Add to Cart</a>
                <?php else: ?>
                    <a href="#" class="btn-cart out-of-stock">Out of Stock</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

<?php else: ?>
    <p class="no-products">😕 No products found. Check back later!</p>
<?php endif; ?>

</body>
</html>