<?php
require_once("includes/config.php");
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = []; 
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// 1. Get Product Details
if ($action === 'get_product') {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if ($prod = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success'=>true, 'product'=>$prod]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    exit;
}

// 2. Add to Cart (Just checks stock, doesn't reduce until checkout)
if ($action === 'add_to_cart') {
    $id = intval($_POST['id']);
    
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $product['stock'] > 0) {
        $found = false;
        foreach($_SESSION['cart'] as &$cart_item) {
            if ($cart_item['product_id'] === $id) {
                if ($cart_item['quantity'] + 1 > $product['stock']) {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
                    exit;
                }
                $cart_item['quantity'] += 1;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = ['product_id' => $id, 'quantity' => 1];
        }
        
        echo json_encode(['success' => true, 'message' => 'Added to cart!', 'new_stock' => $product['stock']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sorry, this item is out of stock!']);
    }
    exit;
}

// 3. Remove from Cart
if ($action === 'remove_from_cart') {
    $id = intval($_POST['id']);
    
    foreach($_SESSION['cart'] as $key => $cart_item) {
        if ($cart_item['product_id'] === $id) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); 
            echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Item not in cart.']);
    exit;
}

// 3.5 Update Cart Quantity
if ($action === 'update_cart') {
    $id = intval($_POST['id']);
    $qty_action = $_POST['qty_action'];

    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $found = false;
        foreach ($_SESSION['cart'] as $key => &$cart_item) {
            if ($cart_item['product_id'] === $id) {
                if ($qty_action === 'increase') {
                    if ($cart_item['quantity'] + 1 > $product['stock']) {
                        echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
                        exit;
                    }
                    $cart_item['quantity'] += 1;
                    echo json_encode(['success' => true, 'message' => 'Quantity increased.']);
                } elseif ($qty_action === 'decrease') {
                    if ($cart_item['quantity'] - 1 <= 0) {
                        unset($_SESSION['cart'][$key]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']); 
                        echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
                    } else {
                        $cart_item['quantity'] -= 1;
                        echo json_encode(['success' => true, 'message' => 'Quantity decreased.']);
                    }
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'Item not in cart.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
    }
    exit;
}

// 4. Checkout
if ($action === 'checkout') {
    if (empty($_SESSION['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $total = 0;
        $order_items = [];
        
        foreach($_SESSION['cart'] as $item) {
            $p_id = $item['product_id'];
            $qty = $item['quantity'];
            
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE"); // lock row
            $stmt->execute([$p_id]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($p && $p['stock'] >= $qty) {
                $total += $p['price'] * $qty;
                $order_items[] = [
                    'product_id' => $p['id'],
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'quantity' => $qty,
                    'image_url' => $p['image_url']
                ];
                
                // Deduct stock
                $stmtUpdate = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmtUpdate->execute([$qty, $p_id]);
            } else {
                $pdo->rollBack();
                $name = $p ? $p['name'] : 'Item';
                echo json_encode(['success' => false, 'message' => "Not enough stock for {$name}."]);
                exit;
            }
        }
        
        // Add shipping
        $shipping = $total > 0 ? 5.00 : 0.00;
        $total_amount = $total + $shipping;

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, total_amount, status) VALUES (?, ?, ?, 'Pending')");
        // user_id might be in $_SESSION['user_id']
        $user_id = $_SESSION['user_id'] ?? null;
        $customer_name = $_SESSION['username'] ?? 'Guest User';
        $stmt->execute([$user_id, $customer_name, $total_amount]);
        $real_order_id = $pdo->lastInsertId();

        $stmtDetails = $pdo->prepare("INSERT INTO order_details (order_id, product_id, product_name, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
        foreach($order_items as $db_item) {
            $stmtDetails->execute([
                $real_order_id, 
                $db_item['product_id'], 
                $db_item['name'], 
                $db_item['quantity'], 
                $db_item['price']
            ]);
        }
        
        $pdo->commit();
        $_SESSION['cart'] = [];
        
        echo json_encode(['success' => true, 'message' => 'Success! Order placed via Cash on Delivery.', 'order_id' => $real_order_id]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action endpoint.']);

