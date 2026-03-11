<?php
session_start();
require_once 'config/Database.php';
require_once 'classes/Product.php';

$database = new Database();
$db       = $database->getConnection();

// OOP: Fetch featured products to show on homepage
$product  = new Product($db);
$stmt     = $product->getAllProducts();

// Cart item count for navbar badge
$cart_count = 0;
if(isset($_SESSION['user_id'])) {
    $cart_key   = 'cart_' . $_SESSION['user_id'];
    $cart_items = $_SESSION[$cart_key] ?? [];
    foreach($cart_items as $item) {
        $cart_count += $item['quantity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyShop — Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:    #0f0e0d;
            --cream:  #faf7f2;
            --accent: #c8402f;
            --gold:   #d4a847;
            --muted:  #7a7570;
            --card:   #ffffff;
        }
        * { box-sizing:border-box; margin:0; padding:0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--ink);
            min-height: 100vh;
        }

        /* ── NAVBAR ─────────────────────────────── */
        nav {
            position: sticky; top: 0; z-index: 100;
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 50px;
            background: var(--ink);
            border-bottom: 3px solid var(--accent);
        }
        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 24px; font-weight: 900;
            color: var(--cream);
            letter-spacing: -0.5px;
        }
        .nav-logo span { color: var(--gold); }
        .nav-links { display:flex; align-items:center; gap:28px; }
        .nav-links a {
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--gold); }

        .cart-badge {
            position: relative;
            background: var(--accent);
            color: #fff;
            padding: 7px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none !important;
            transition: background 0.2s;
        }
        .cart-badge:hover { background: #a33224 !important; color:#fff; }
        .badge-count {
            background: var(--gold);
            color: var(--ink);
            font-size: 11px; font-weight: 700;
            border-radius: 50%;
            width: 18px; height: 18px;
            display: inline-flex; align-items:center; justify-content:center;
            margin-left: 5px;
        }

        /* ── HERO ────────────────────────────────── */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 480px;
            overflow: hidden;
        }
        .hero-left {
            background: var(--ink);
            padding: 70px 60px;
            display: flex; flex-direction: column; justify-content: center;
        }
        .hero-tag {
            font-size: 11px; font-weight: 600;
            letter-spacing: 3px; text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 18px;
        }
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 56px; font-weight: 900;
            line-height: 1.08;
            color: var(--cream);
            margin-bottom: 22px;
        }
        .hero-title em { color: var(--gold); font-style: italic; }
        .hero-sub {
            color: #aaa;
            font-size: 16px; line-height: 1.6;
            max-width: 360px;
            margin-bottom: 36px;
        }
        .hero-btn {
            display: inline-block;
            background: var(--accent);
            color: #fff;
            padding: 14px 36px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.3px;
            transition: transform 0.2s, background 0.2s;
            align-self: flex-start;
        }
        .hero-btn:hover { background: #a33224; transform: translateY(-2px); }

        .hero-right {
            background: linear-gradient(135deg, #e8e0d4 0%, #d4c9b8 100%);
            display: flex; align-items: center; justify-content: center;
            font-size: 120px;
            position: relative;
            overflow: hidden;
        }
        .hero-right::before {
            content: '';
            position: absolute; inset: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 30px,
                rgba(0,0,0,0.03) 30px,
                rgba(0,0,0,0.03) 31px
            );
        }

        /* ── STATS BAR ───────────────────────────── */
        .stats-bar {
            background: var(--accent);
            display: flex; justify-content: center; gap: 60px;
            padding: 20px 50px;
        }
        .stat { text-align: center; color: #fff; }
        .stat-num {
            font-family: 'Playfair Display', serif;
            font-size: 28px; font-weight: 700;
        }
        .stat-label {
            font-size: 12px; letter-spacing: 1.5px;
            text-transform: uppercase; opacity: 0.85;
        }

        /* ── PRODUCTS SECTION ────────────────────── */
        .section { padding: 60px 50px; }
        .section-header {
            display: flex; justify-content: space-between; align-items: flex-end;
            margin-bottom: 36px;
            border-bottom: 2px solid var(--ink);
            padding-bottom: 16px;
        }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 36px; font-weight: 900;
        }
        .section-link {
            color: var(--accent);
            text-decoration: none;
            font-size: 14px; font-weight: 600;
            letter-spacing: 0.5px;
        }
        .section-link:hover { text-decoration: underline; }

        /* Product grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 24px;
        }
        .card {
            background: var(--card);
            border: 1px solid #e8e2da;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        .card-img {
            width: 100%; height: 200px;
            object-fit: cover;
            background: #ede8e0;
            display: block;
        }
        .card-body { padding: 18px; }
        .card-name {
            font-family: 'Playfair Display', serif;
            font-size: 17px; font-weight: 700;
            margin-bottom: 6px;
            color: var(--ink);
        }
        .card-desc {
            font-size: 13px; color: var(--muted);
            line-height: 1.5; margin-bottom: 14px;
        }
        .card-footer {
            display: flex; justify-content: space-between; align-items: center;
        }
        .card-price {
            font-size: 20px; font-weight: 700;
            color: var(--accent);
            font-family: 'Playfair Display', serif;
        }
        .card-stock {
            font-size: 11px; color: var(--muted);
            margin-top: 3px;
        }
        .btn-cart {
            background: var(--ink);
            color: var(--cream);
            padding: 9px 18px;
            border-radius: 3px;
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-cart:hover { background: var(--accent); }
        .btn-oos {
            background: #ddd; color: #999;
            padding: 9px 18px; border-radius: 3px;
            font-size: 13px; font-weight: 600;
            cursor: not-allowed;
        }

        /* ── GUEST BANNER ────────────────────────── */
        .guest-banner {
            margin: 0 50px 40px;
            background: var(--ink);
            border-left: 5px solid var(--gold);
            padding: 22px 30px;
            border-radius: 4px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .guest-banner p { color: #ccc; font-size: 15px; }
        .guest-banner p strong { color: var(--gold); }
        .banner-btns { display:flex; gap:12px; }
        .btn-login {
            padding: 9px 22px; border-radius: 3px;
            text-decoration: none; font-weight: 600; font-size: 14px;
        }
        .btn-login.outline {
            border: 2px solid var(--cream); color: var(--cream);
            background: transparent;
            transition: background 0.2s;
        }
        .btn-login.outline:hover { background: rgba(255,255,255,0.1); }
        .btn-login.filled {
            background: var(--accent); color: #fff;
            transition: background 0.2s;
        }
        .btn-login.filled:hover { background: #a33224; }

        /* ── FOOTER ──────────────────────────────── */
        footer {
            background: var(--ink);
            color: #888;
            text-align: center;
            padding: 28px;
            font-size: 13px;
            border-top: 3px solid var(--accent);
        }
        footer strong { color: var(--gold); }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav>
    <div class="nav-logo">My<span>Shop</span></div>
    <div class="nav-links">
        <a href="pages/products.php">🏪 Shop</a>

        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="#">👋 <?= htmlspecialchars($_SESSION['user_name']) ?></a>
            <a href="pages/my_orders.php">📦 My Orders</a>
            <?php if($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin/index.php">⚙️ Admin</a>
            <?php endif; ?>
            <a href="pages/cart.php" class="cart-badge">
                🛒 Cart
                <?php if($cart_count > 0): ?>
                    <span class="badge-count"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <a href="pages/logout.php" style="color:#e88; font-size:13px;">Logout</a>

        <?php else: ?>
            <a href="pages/login.php">Login</a>
            <a href="pages/register.php" class="cart-badge">Register Free</a>
        <?php endif; ?>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <div class="hero-left">
        <p class="hero-tag">✦ New arrivals every week</p>
        <h1 class="hero-title">
            Shop Smart,<br>
            Live <em>Better.</em>
        </h1>
        <p class="hero-sub">
            Discover quality products at unbeatable prices.
            Fast delivery, easy returns, and a shopping experience you'll love.
        </p>
        <a href="pages/products.php" class="hero-btn">Browse Products →</a>
    </div>
    <div class="hero-right">🛍️</div>
</div>

<!-- STATS BAR -->
<div class="stats-bar">
    <div class="stat">
        <div class="stat-num"><?= $stmt->rowCount() ?>+</div>
        <div class="stat-label">Products</div>
    </div>
    <div class="stat">
        <div class="stat-num">100%</div>
        <div class="stat-label">Secure Checkout</div>
    </div>
    <div class="stat">
        <div class="stat-num">FREE</div>
        <div class="stat-label">Shipping</div>
    </div>
    <div class="stat">
        <div class="stat-num">24/7</div>
        <div class="stat-label">Support</div>
    </div>
</div>

<!-- GUEST BANNER — only shown to logged-out users -->
<?php if(!isset($_SESSION['user_id'])): ?>
<div class="guest-banner">
    <p>👋 Welcome! <strong>Create a free account</strong> to start shopping and track your orders.</p>
    <div class="banner-btns">
        <a href="pages/login.php"    class="btn-login outline">Login</a>
        <a href="pages/register.php" class="btn-login filled">Register Free</a>
    </div>
</div>
<?php endif; ?>

<!-- FEATURED PRODUCTS -->
<div class="section">
    <div class="section-header">
        <h2 class="section-title">Featured Products</h2>
        <a href="pages/products.php" class="section-link">View all products →</a>
    </div>

    <?php
    // Reset and fetch again for homepage display (limit to 8)
    $stmt2 = $product->getAllProducts();
    $count = 0;
    ?>

    <div class="grid">
    <?php while($row = $stmt2->fetch(PDO::FETCH_ASSOC)): ?>
        <?php if($count >= 8) break; $count++; ?>
        <div class="card">
            <?php if($row['image']): ?>
                <img class="card-img"
                     src="uploads/products/<?= htmlspecialchars($row['image']) ?>"
                     alt="<?= htmlspecialchars($row['name']) ?>">
            <?php else: ?>
                <img class="card-img"
                     src="https://via.placeholder.com/240x200?text=No+Image" alt="">
            <?php endif; ?>

            <div class="card-body">
                <div class="card-name"><?= htmlspecialchars($row['name']) ?></div>
                <div class="card-desc">
                    <?= htmlspecialchars(substr($row['description'], 0, 65)) ?>...
                </div>
                <div class="card-footer">
                    <div>
                        <div class="card-price">₹<?= number_format($row['price'], 2) ?></div>
                        <div class="card-stock">
                            <?= $row['stock'] > 0
                                ? "✅ {$row['stock']} in stock"
                                : "❌ Out of stock" ?>
                        </div>
                    </div>

                    <?php if($row['stock'] > 0 && isset($_SESSION['user_id'])): ?>
                        <a href="pages/cart.php?action=add&id=<?= $row['id'] ?>"
                           class="btn-cart">Add to Cart</a>
                    <?php elseif($row['stock'] > 0): ?>
                        <a href="pages/login.php" class="btn-cart">Login to Buy</a>
                    <?php else: ?>
                        <span class="btn-oos">Out of Stock</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <p>© <?= date('Y') ?> <strong>MyShop</strong> — Built with PHP & MySQL</p>
</footer>

</body>
</html>