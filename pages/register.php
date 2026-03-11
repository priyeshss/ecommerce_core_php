<?php
session_start(); // Start session so we can store login info

require_once '../config/Database.php';
require_once '../classes/User.php';

$error   = '';
$success = '';

// Check if form was submitted (POST request)
if($_SERVER['REQUEST_METHOD'] === 'POST') {

    // OOP: Create Database object, get connection
    $database = new Database();
    $db = $database->getConnection();

    // OOP: Create a User object — passing $db into constructor
    $user = new User($db);

    // Set the object's properties from form input
    $user->name     = $_POST['name'];
    $user->email    = $_POST['email'];
    $user->password = $_POST['password'];

    // Validate: check if email already exists
    if($user->emailExists()) {
        $error = "❌ Email already registered. Try logging in.";
    }
    // Validate: passwords must match
    elseif($_POST['password'] !== $_POST['confirm_password']) {
        $error = "❌ Passwords do not match.";
    }
    // All good — register the user
    elseif($user->register()) {
        $success = "✅ Account created! <a href='login.php'>Login here</a>";
    } else {
        $error = "❌ Something went wrong. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        body { font-family: Arial; display:flex; justify-content:center; 
               align-items:center; height:100vh; background:#f0f2f5; margin:0; }
        .box { background:#fff; padding:30px 40px; border-radius:10px; 
               box-shadow:0 2px 15px rgba(0,0,0,0.1); width:360px; }
        h2   { text-align:center; color:#333; }
        input { width:100%; padding:10px; margin:8px 0; 
                border:1px solid #ddd; border-radius:6px; box-sizing:border-box; }
        button { width:100%; padding:11px; background:#4f46e5; color:#fff; 
                 border:none; border-radius:6px; cursor:pointer; font-size:15px; }
        button:hover { background:#4338ca; }
        .error   { color:red;   text-align:center; }
        .success { color:green; text-align:center; }
        p { text-align:center; }
    </style>
</head>
<body>
<div class="box">
    <h2>📦 Create Account</h2>

    <?php if($error)   echo "<p class='error'>$error</p>"; ?>
    <?php if($success) echo "<p class='success'>$success</p>"; ?>

    <form method="POST">
        <input type="text"     name="name"             placeholder="Full Name"        required>
        <input type="email"    name="email"            placeholder="Email Address"    required>
        <input type="password" name="password"         placeholder="Password"         required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>