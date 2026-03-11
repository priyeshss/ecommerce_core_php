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