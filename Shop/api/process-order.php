<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Cart.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }

    $database = new Database();
    $db = $database->getConnection();
    
    // Create orders table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            paypal_order_id TEXT NOT NULL,
            paypal_payer_id TEXT NOT NULL,
            customer_first_name TEXT NOT NULL,
            customer_last_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            customer_address TEXT NOT NULL,
            customer_zip TEXT NOT NULL,
            customer_city TEXT NOT NULL,
            customer_country TEXT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status TEXT DEFAULT 'completed',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $db->exec($createTableSQL);
    
    // Create order_items table if it doesn't exist
    $createItemsTableSQL = "
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            game_id INTEGER NOT NULL,
            game_name TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders (id)
        )
    ";
    $db->exec($createItemsTableSQL);

    // Start transaction
    $db->beginTransaction();

    // Insert order
    $stmt = $db->prepare("
        INSERT INTO orders (
            paypal_order_id, paypal_payer_id, customer_first_name, customer_last_name, 
            customer_email, customer_address, customer_zip, customer_city, 
            customer_country, total_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $input['paypal_order_id'],
        $input['paypal_payer_id'],
        $input['customer_data']['first_name'],
        $input['customer_data']['last_name'],
        $input['customer_data']['email'],
        $input['customer_data']['address'],
        $input['customer_data']['zip'],
        $input['customer_data']['city'],
        $input['customer_data']['country'],
        $input['total_amount']
    ]);

    $orderId = $db->lastInsertId();

    // Insert order items
    $itemStmt = $db->prepare("
        INSERT INTO order_items (order_id, game_id, game_name, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($input['cart_items'] as $item) {
        $itemStmt->execute([
            $orderId,
            $item['id'],
            $item['name'],
            $item['quantity'],
            $item['price']
        ]);
    }

    // Clear cart
    $cart = new Cart();
    $cart->clear();

    // Commit transaction
    $db->commit();

    // Create server automatically if user is logged in
    if (isset($_SESSION['user_id'])) {
        try {
            require_once __DIR__ . '/../includes/ServerManager.php';
            $serverManager = new ServerManager();
            $serverResult = $serverManager->createServerFromOrder($orderId, $_SESSION['user_id']);
            
            if (!$serverResult['success']) {
                error_log('Server creation failed for order ' . $orderId . ': ' . $serverResult['error']);
            }
        } catch (Exception $e) {
            error_log('Server creation error for order ' . $orderId . ': ' . $e->getMessage());
        }
    }

    // Send confirmation email (optional)
    $customerEmail = $input['customer_data']['email'];
    $customerName = $input['customer_data']['first_name'] . ' ' . $input['customer_data']['last_name'];
    
    $subject = "Bestellbestätigung - Ocean Hosting";
    $message = "
        Liebe/r {$customerName},
        
        vielen Dank für Ihre Bestellung bei Ocean Hosting!
        
        Bestellnummer: #{$orderId}
        PayPal Transaction ID: {$input['paypal_order_id']}
        Gesamtbetrag: " . number_format($input['total_amount'], 2, ',', '.') . "€
        
        Ihre Bestellung wird in Kürze bearbeitet.
        
        Mit freundlichen Grüßen
        Ihr Ocean Hosting Team
    ";
    
    $headers = "From: noreply@ocean-hosting.com\r\n";
    $headers .= "Reply-To: support@ocean-hosting.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Uncomment to send email
    // mail($customerEmail, $subject, $message, $headers);

    echo json_encode([
        'success' => true,
        'message' => 'Order processed successfully',
        'order_id' => $orderId
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log('Order processing error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
}
?>