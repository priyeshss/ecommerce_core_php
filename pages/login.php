<?php
session_start();

// If already logged in, redirect to homepage
if(isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/Database.php';
require_once '../classes/User.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $database = new Database();
    $db = $database->getConnection();

    // OOP: Instantiate User object
    $user = new User($db);
    $user->email    = $_POST['email'];
    $user->password = $_POST['password'];

    // Call login() method — returns true/false
    if($user->login()) {

        // SESSION: Store user data in session
        // Sessions persist data across pages (like a temporary memory)
        $_SESSION['user_id']   = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;

        // Redirect admin to admin panel, users to homepage
        if($user->role === 'admin') {
            header('Location: ../admin/index.php');
        } else {
            header('Location: ../index.php');
        }
        exit();

    } else {
        $error = "❌ Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
        .error { color:red; text-align:center; }
        p { text-align:center; }
    </style>
</head>
<body>
<div class="box">
    <h2>🔐 Login</h2>

    <?php if($error) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input type="email"    name="email"    placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password"      required>
        <button type="submit">Login</button>
    </form>
    <p>No account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>