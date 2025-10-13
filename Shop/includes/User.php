<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function register($name, $email, $password) {
        try {
            // Check if user already exists
            $escapedEmail = $this->conn->quote($email);
            $check_query = "SELECT id FROM " . $this->table . " WHERE email = " . $escapedEmail;
            $check_stmt = $this->conn->query($check_query);
            $existingUsers = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($existingUsers) > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }

            // Insert new user
            $query = "INSERT INTO " . $this->table . " (name, email, password, created_at) VALUES (:name, :email, :password, NOW())";
            $stmt = $this->conn->prepare($query);
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'User registered successfully'];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function login($email, $password) {
        try {
            // Ensure session is started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            // Use direct query due to SQLite PDO prepared statement bug
            $escapedEmail = $this->conn->quote($email);
            $query = "SELECT id, name, email, password, is_admin FROM " . $this->table . " WHERE email = " . $escapedEmail;
            $stmt = $this->conn->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) == 1) {
                $user = $users[0];
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    
                    return ['success' => true, 'message' => 'Login successful'];
                }
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }

    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'is_admin' => $_SESSION['is_admin'] ?? 0
            ];
        }
        return null;
    }

    public function getId() {
        if ($this->isLoggedIn()) {
            return $_SESSION['user_id'];
        }
        return null;
    }

    public function getUserAddressData($userId) {
        try {
            $query = "SELECT first_name, last_name, address, city, zip, country FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : [
                'first_name' => '',
                'last_name' => '',
                'address' => '',
                'city' => '',
                'zip' => '',
                'country' => 'DE'
            ];
        } catch(PDOException $e) {
            return [
                'first_name' => '',
                'last_name' => '',
                'address' => '',
                'city' => '',
                'zip' => '',
                'country' => 'DE'
            ];
        }
    }

    public function updateUserAddress($userId, $firstName, $lastName, $address, $city, $zip, $country) {
        try {
            $query = "UPDATE " . $this->table . " SET first_name = :first_name, last_name = :last_name, address = :address, city = :city, zip = :zip, country = :country, updated_at = datetime('now') WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':zip', $zip);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':id', $userId);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Address updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Update failed'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getAllUsersWithAddresses() {
        try {
            $query = "SELECT id, name, email, first_name, last_name, address, city, zip, country, created_at FROM " . $this->table . " ORDER BY created_at DESC";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function getEmailById($userId) {
        try {
            $query = "SELECT email FROM " . $this->table . " WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['email'] : null;
        } catch(PDOException $e) {
            return null;
        }
    }

}
?>