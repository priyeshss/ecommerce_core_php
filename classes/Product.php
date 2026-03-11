<?php

// OOP CONCEPT: CLASS
// Product class is the blueprint for everything related to products
// It handles: fetching all products, fetching single product, 
// creating, updating and deleting products (Full CRUD)

class Product {

    // OOP CONCEPT: PROPERTIES
    // private $conn — only this class can use the DB connection (ENCAPSULATION)
    // public properties — can be set from outside (form data)
    
    private $conn;
    private $table = 'products';

    // Public properties — map to database columns
    public $id;
    public $name;
    public $description;
    public $price;
    public $stock;
    public $image;
    public $created_at;

    // OOP CONCEPT: CONSTRUCTOR
    // Whenever we write new Product($db), this runs automatically
    // and stores the database connection inside the object
    
    public function __construct($db) {
        $this->conn = $db;
    }

    // -------------------------------------------------------
    // METHOD 1: getAllProducts()
    // Returns ALL products from DB — used on the shop/homepage
    // OOP CONCEPT: A method that QUERIES and returns data
    // -------------------------------------------------------

    public function getAllProducts() {
        $query = "SELECT id, name, description, price, stock, image 
                  FROM " . $this->table . " 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Returns the PDO statement object — we loop through it in the view
        return $stmt;
    }

    // -------------------------------------------------------
    // METHOD 2: getSingleProduct()
    // Fetches ONE product by its ID — used on product detail page
    // -------------------------------------------------------

    public function getSingleProduct() {
        $query = "SELECT id, name, description, price, stock, image 
                  FROM " . $this->table . " 
                  WHERE id = :id 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        // Sanitize the ID input
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        // Fetch the single row as associative array
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            // Assign fetched values to object properties
            // So we can access them as $product->name, $product->price etc.
            $this->name        = $row['name'];
            $this->description = $row['description'];
            $this->price       = $row['price'];
            $this->stock       = $row['stock'];
            $this->image       = $row['image'];
            return true;
        }
        return false;
    }

    // -------------------------------------------------------
    // METHOD 3: createProduct()
    // Admin only — Insert a new product into DB
    // -------------------------------------------------------

    public function createProduct() {
        $query = "INSERT INTO " . $this->table . "
                  (name, description, price, stock, image)
                  VALUES (:name, :description, :price, :stock, :image)";

        $stmt = $this->conn->prepare($query);

        // Sanitize all inputs before saving to DB
        $this->name        = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price       = htmlspecialchars(strip_tags($this->price));
        $this->stock       = htmlspecialchars(strip_tags($this->stock));
        $this->image       = htmlspecialchars(strip_tags($this->image));

        $stmt->bindParam(':name',        $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price',       $this->price);
        $stmt->bindParam(':stock',       $this->stock);
        $stmt->bindParam(':image',       $this->image);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // -------------------------------------------------------
    // METHOD 4: updateProduct()
    // Admin only — Update existing product details
    // -------------------------------------------------------

    public function updateProduct() {
        $query = "UPDATE " . $this->table . "
                  SET name = :name,
                      description = :description,
                      price = :price,
                      stock = :stock,
                      image = :image
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->name        = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price       = htmlspecialchars(strip_tags($this->price));
        $this->stock       = htmlspecialchars(strip_tags($this->stock));
        $this->image       = htmlspecialchars(strip_tags($this->image));
        $this->id          = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(':name',        $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price',       $this->price);
        $stmt->bindParam(':stock',       $this->stock);
        $stmt->bindParam(':image',       $this->image);
        $stmt->bindParam(':id',          $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // -------------------------------------------------------
    // METHOD 5: deleteProduct()
    // Admin only — Delete a product by ID
    // -------------------------------------------------------

    public function deleteProduct() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>