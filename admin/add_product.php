<?php
session_start();

// SECURITY: Only admin can access this page
// If not logged in OR not admin — kick them out
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}

require_once '../config/Database.php';
require_once '../classes/Product.php';

$error   = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $database = new Database();
    $db = $database->getConnection();

    // OOP: Create Product object — inject DB connection
    $product = new Product($db);

    // Handle image upload
    $image_name = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $allowed     = ['jpg','jpeg','png','webp'];
        $file_ext    = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if(in_array($file_ext, $allowed)) {
            // Create unique filename to avoid overwrites
            $image_name = uniqid('prod_', true) . '.' . $file_ext;
            $upload_dir = '../uploads/products/';

            // Create folder if it doesn't exist
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
        } else {
            $error = "❌ Only JPG, PNG, WEBP images allowed.";
        }
    }

    if(!$error) {
        // Set product properties from form
        $product->name        = $_POST['name'];
        $product->description = $_POST['description'];
        $product->price       = $_POST['price'];
        $product->stock       = $_POST['stock'];
        $product->image       = $image_name;

        if($product->createProduct()) {
            $success = "✅ Product added successfully!";
        } else {
            $error = "❌ Failed to add product.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <style>
        body { font-family: Arial; background:#f0f2f5; margin:0; padding:30px; }
        .container { max-width:550px; margin:auto; background:#fff; 
                     padding:30px; border-radius:10px; 
                     box-shadow:0 2px 15px rgba(0,0,0,0.1); }
        h2  { color:#333; text-align:center; }
        input, textarea { width:100%; padding:10px; margin:8px 0; 
                          border:1px solid #ddd; border-radius:6px; 
                          box-sizing:border-box; }
        textarea { height:100px; resize:vertical; }
        button { width:100%; padding:11px; background:#4f46e5; color:#fff; 
                 border:none; border-radius:6px; cursor:pointer; font-size:15px; }
        button:hover { background:#4338ca; }
        .error   { color:red;   text-align:center; }
        .success { color:green; text-align:center; }
        .nav { text-align:center; margin-bottom:15px; }
        .nav a { margin:0 10px; color:#4f46e5; text-decoration:none; font-weight:bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="../pages/products.php">View Shop</a>
    </div>

    <h2>➕ Add New Product</h2>

    <?php if($error)   echo "<p class='error'>$error</p>"; ?>
    <?php if($success) echo "<p class='success'>$success</p>"; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="text"   name="name"        placeholder="Product Name"  required>
        <textarea            name="description" placeholder="Description"   required></textarea>
        <input type="number" name="price"        placeholder="Price (₹)"    step="0.01" required>
        <input type="number" name="stock"        placeholder="Stock Quantity" required>
        <label>Product Image:</label>
        <input type="file"   name="image"        accept="image/*">
        <button type="submit">Add Product</button>
    </form>
</div>
</body>
</html>