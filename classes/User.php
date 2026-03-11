<?php

// OOP CONCEPT: CLASS
// A class is a blueprint. Just like a form has fields (name, email, password),
// our User class defines what a "User" looks like and what it can DO.

class User {

    // OOP CONCEPT: PROPERTIES (instance variables)
    // These are the attributes every User object will have.
    // 'private' = ENCAPSULATION — outside code cannot directly touch these.
    
    private $conn;   // Holds the DB connection
    private $table = 'users'; // Which table this class works with

    // Public properties — these will be filled with form data
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;

    // OOP CONCEPT: CONSTRUCTOR
    // This method runs AUTOMATICALLY when you do: new User($db)
    // It's like the "setup" step — we pass the DB connection in
    // and store it inside the object using $this
    
    public function __construct($db) {
        $this->conn = $db;
        // $this->conn means: "THIS object's conn property = $db"
    }

    // -------------------------------------------------------
    // METHOD 1: register()
    // OOP CONCEPT: METHOD = a function that belongs to a class
    // This method handles inserting a new user into the database
    // -------------------------------------------------------
    
    public function register() {

        // SQL query — we use :name, :email etc. as PLACEHOLDERS
        // This is called a PREPARED STATEMENT — protects against SQL Injection
        $query = "INSERT INTO " . $this->table . " 
                  (name, email, password, role) 
                  VALUES (:name, :email, :password, :role)";

        // OOP CONCEPT: Calling a method on an object
        // $this->conn is a PDO object — we call prepare() METHOD on it
        $stmt = $this->conn->prepare($query);

        // SECURITY: Clean the inputs to remove harmful characters
        // htmlspecialchars() converts < > & " to safe versions
        $this->name     = htmlspecialchars(strip_tags($this->name));
        $this->email    = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role     = 'user'; // Default role is always 'user'

        // SECURITY: Hash the password — never store plain text passwords!
        // password_hash() uses bcrypt algorithm to scramble the password
        // Even if DB is hacked, passwords are unreadable
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind the actual values to the placeholders in our SQL
        $stmt->bindParam(':name',     $this->name);
        $stmt->bindParam(':email',    $this->email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role',     $this->role);

        // Execute the query — returns true if successful
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // -------------------------------------------------------
    // METHOD 2: login()
    // Checks if email exists and password matches
    // -------------------------------------------------------
    
    public function login() {

        $query = "SELECT id, name, email, password, role 
                  FROM " . $this->table . " 
                  WHERE email = :email 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);

        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        // OOP CONCEPT: fetch() is a METHOD of the PDO Statement object
        // PDO::FETCH_ASSOC returns the row as an associative array
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            // SECURITY: password_verify() compares entered password
            // with the hashed password stored in DB
            // Returns true if they match, false if not
            if(password_verify($this->password, $row['password'])) {

                // Store user details into object properties
                $this->id    = $row['id'];
                $this->name  = $row['name'];
                $this->role  = $row['role'];
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------
    // METHOD 3: emailExists()
    // Check if an email is already registered (for registration validation)
    // -------------------------------------------------------
    
    public function emailExists() {

        $query = "SELECT id FROM " . $this->table . " 
                  WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        // rowCount() tells how many rows were returned
        // If > 0, email already exists
        return $stmt->rowCount() > 0;
    }
}
?>