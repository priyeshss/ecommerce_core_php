<?php

// OOP CONCEPT: CLASS
// Order class handles everything after the user clicks "Proceed to Checkout"
// It is responsible for:
// 1. Placing the order (saving to DB)
// 2. Fetching a user's past orders
// 3. Fetching order details (admin use)

class Order {

    // OOP CONCEPT: PROPERTIES + ENCAPSULATION
    // private = only this class can use these directly
    private $conn;
    private $orders_table      = 'orders';
    private $order_items_table = 'order_items';

    // Public properties — filled before calling placeOrder()
    public $id;
    public $user_id;
    public $total_amount;
    public $status;

    // OOP CONCEPT: CONSTRUCTOR — Dependency Injection
    // We inject $db so this class doesn't create its own connection
    // This makes the class reusable and testable
    public function __construct($db) {
        $this->conn = $db;
    }

    // -------------------------------------------------------
    // METHOD 1: placeOrder()
    // This is the MOST IMPORTANT method — saves order + items to DB
    //
    // OOP CONCEPT: TRANSACTION
    // A transaction means: "Do ALL these DB operations together.
    // If even ONE fails, UNDO everything." — like an all-or-nothing deal.
    // -------------------------------------------------------

    public function placeOrder($cart_items) {

        try {
            // START TRANSACTION — begin the all-or-nothing block
            $this->conn->beginTransaction();

            // STEP 1: Insert into orders table
            $query = "INSERT INTO " . $this->orders_table . "
                      (user_id, total_amount, status)
                      VALUES (:user_id, :total_amount, 'pending')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id',      $this->user_id);
            $stmt->bindParam(':total_amount', $this->total_amount);
            $stmt->execute();

            // Get the ID of the order we just created
            // lastInsertId() is a PDO method that returns the new row's ID
            $order_id = $this->conn->lastInsertId();

            // STEP 2: Insert each cart item into order_items table
            // Also REDUCE stock for each product purchased
            foreach($cart_items as $product_id => $item) {

                // Insert order item
                $item_query = "INSERT INTO " . $this->order_items_table . "
                               (order_id, product_id, quantity, price)
                               VALUES (:order_id, :product_id, :quantity, :price)";

                $item_stmt = $this->conn->prepare($item_query);
                $item_stmt->bindParam(':order_id',   $order_id);
                $item_stmt->bindParam(':product_id', $item['product_id']);
                $item_stmt->bindParam(':quantity',   $item['quantity']);
                $item_stmt->bindParam(':price',      $item['price']);
                $item_stmt->execute();

                // Reduce stock — subtract quantity purchased from products table
                $stock_query = "UPDATE products 
                                SET stock = stock - :quantity 
                                WHERE id = :product_id AND stock >= :quantity";

                $stock_stmt = $this->conn->prepare($stock_query);
                $stock_stmt->bindParam(':quantity',   $item['quantity']);
                $stock_stmt->bindParam(':product_id', $item['product_id']);
                $stock_stmt->execute();

                // If no rows updated — stock issue — rollback everything
                if($stock_stmt->rowCount() === 0) {
                    $this->conn->rollBack();
                    return [
                        'success' => false,
                        'message' => "Sorry! '{$item['name']}' is out of stock."
                    ];
                }
            }

            // All good — COMMIT the transaction (make it permanent)
            $this->conn->commit();

            return [
                'success'  => true,
                'order_id' => $order_id,
                'message'  => 'Order placed successfully!'
            ];

        } catch(Exception $e) {
            // Something went wrong — ROLLBACK all changes
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Order failed: ' . $e->getMessage()
            ];
        }
    }

    // -------------------------------------------------------
    // METHOD 2: getUserOrders()
    // Fetch all orders placed by a specific user
    // OOP CONCEPT: Method that returns data for display
    // -------------------------------------------------------

    public function getUserOrders($user_id) {
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at,
                         COUNT(oi.id) as item_count
                  FROM " . $this->orders_table . " o
                  LEFT JOIN " . $this->order_items_table . " oi ON o.id = oi.order_id
                  WHERE o.user_id = :user_id
                  GROUP BY o.id
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // -------------------------------------------------------
    // METHOD 3: getOrderDetails()
    // Fetch all items inside a specific order (for order detail page)
    // OOP CONCEPT: JOIN query — combining two tables
    // -------------------------------------------------------

    public function getOrderDetails($order_id) {
        $query = "SELECT oi.quantity, oi.price,
                         p.name, p.image
                  FROM " . $this->order_items_table . " oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = :order_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        return $stmt;
    }

    // -------------------------------------------------------
    // METHOD 4: getAllOrders() — Admin only
    // Fetch ALL orders from ALL users with user name
    // -------------------------------------------------------

    public function getAllOrders() {
        $query = "SELECT o.id, o.total_amount, o.status, o.created_at,
                         u.name as user_name, u.email,
                         COUNT(oi.id) as item_count
                  FROM " . $this->orders_table . " o
                  JOIN users u  ON o.user_id  = u.id
                  LEFT JOIN " . $this->order_items_table . " oi ON o.id = oi.order_id
                  GROUP BY o.id
                  ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // -------------------------------------------------------
    // METHOD 5: updateStatus() — Admin only
    // Change order status: pending → processing → delivered
    // -------------------------------------------------------

    public function updateStatus($order_id, $status) {
        $allowed = ['pending', 'processing', 'delivered'];

        // Validate status — only accept known values
        if(!in_array($status, $allowed)) {
            return false;
        }

        $query = "UPDATE " . $this->orders_table . "
                  SET status = :status
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id',     $order_id);
        return $stmt->execute();
    }
}
?>