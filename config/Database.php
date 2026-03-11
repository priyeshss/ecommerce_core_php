<?php

class Database {
    // OOP CONCEPT: Properties (Class Variables)
    // These are like attributes that belong to the Database object
    // 'private' means only this class can access them (ENCAPSULATION)
    
    private $host     = 'localhost:3307';
    private $db_name  = 'ecommerce_db';
    private $username = 'root';
    private $password = '';
    private $conn;    // This will hold our connection object

    // OOP CONCEPT: Method (Class Function)
    // 'public' means anyone can call this method from outside the class
    // This method returns the database connection
    
    public function getConnection() {
    
        $this->conn = null;
        // '$this' means "the current object itself"
        // Like saying "MY connection is null right now"

        try {
            // PDO = PHP Data Objects — a safe way to connect to DB
            // OOP CONCEPT: we are creating a NEW OBJECT of the PDO class
            // PDO is a built-in PHP class — we are using it (DEPENDENCY)
            
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );

            // Tell PDO to throw exceptions if something goes wrong
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            // OOP CONCEPT: EXCEPTION HANDLING
            // PDOException is a class — when an error occurs, PHP creates
            // an object of PDOException and passes it to $e
            // We "catch" that object and read its message
            
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>