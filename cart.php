<?php
require_once("includes/config.php");
// Calculate Cart Details
$cart_items = $_SESSION['cart'] ?? [];
$cart_details = [];
$subtotal = 0;

if (!empty($cart_items)) {
    $product_ids = array_column($cart_items, 'product_id');
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map products to cart
    foreach ($cart_items as $item) {
        foreach ($products as $p) {
            if ($p['id'] == $item['product_id']) {
                $line_total = $p['price'] * $item['quantity'];
                $subtotal += $line_total;
                $cart_details[] = [
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'image_url' => $p['image_url'],
                    'quantity' => $item['quantity'],
                    'line_total' => $line_total
                ];
                break;
            }
        }
    }
}

$shipping = $subtotal > 500 ? 150.00 : 0.00; // Flat shipping rate
$total = $subtotal + $shipping;
?>
<?php include("includes/header.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PiperPens - Cart</title>
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
<main class="storefront">
    <div class="storefront-header">
        <h1>Your Shopping Cart</h1>
        <p>Review your items and proceed to checkout.</p>
    </div>

    <?php if (empty($cart_details)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
            <h2>Your cart is currently empty.</h2>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <a href="product.php" class="btn btn-purple" style="margin-top: 2rem;">Continue Shopping</a>
        </div>
    <?php
else: ?>
        <div class="cart-container">
            <div class="cart-items">
                <h2>Cart Items (<span id="cartCount"><?php echo count($cart_details); ?></span>)</h2>
                
                <?php foreach ($cart_details as $item): ?>
                    <div class="cart-item" id="cart-item-<?php echo $item['id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="item-price">Rs.<?php echo number_format($item['price'], 2); ?> each</p>
                        </div>
                        <div class="item-quantity">
                            <div class="qty-control" style="display: flex; align-items: center; gap: 8px;">
                                <button class="btn btn-icon qty-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, 'decrease')"><i class="fas fa-minus" style="font-size: 0.8rem;"></i></button>
                                <span class="qty-badge" id="qty-val-<?php echo $item['id']; ?>"><?php echo $item['quantity']; ?></span>
                                <button class="btn btn-icon qty-btn" onclick="updateCartQuantity(<?php echo $item['id']; ?>, 'increase')"><i class="fas fa-plus" style="font-size: 0.8rem;"></i></button>
                            </div>
                        </div>
                        <div class="item-line-total">
                            Rs.<?php echo number_format($item['line_total'], 2); ?>
                        </div>
                        <button class="btn btn-icon btn-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)" title="Remove Item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php
    endforeach; ?>
            </div>

            <div class="cart-summary">
                <h2>Order Summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">Rs.<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span id="shipping">Rs.<?php echo number_format($shipping, 2); ?></span>
                </div>
                <hr>
                <div class="summary-row total-row">
                    <span>Total</span>
                    <span id="total">Rs.<?php echo number_format($total, 2); ?></span>
                </div>

                <div class="checkout-actions">
                    <button class="btn btn-purple btn-block" id="btn-checkout" onclick="checkout()">
                        <i class="fas fa-cash-register"></i> Checkout (Cash on Delivery)
                    </button>
                    <a href="product.php" class="btn btn-outline btn-block mt-3" style="text-align: center;">Continue Shopping</a>
                </div>
                
                <p class="text-muted" style="font-size: 0.8rem; margin-top: 1.5rem; text-align: center;">
                    <i class="fas fa-shield-alt"></i> Secure checkout. For now, we only support Cash on Delivery.
                </p>
            </div>
        </div>
    <?php
endif; ?>
</main>

<div id="toast-container" style="position: fixed; top: 80px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;"></div>
<script src="js/cart.js"></script>
</body>
</html>
<?php include("includes/footer.php"); ?>
