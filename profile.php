<?php
require_once("includes/config.php");

$user = $_SESSION['username'] ?? 'Guest User';

// Fetch real orders from database
$stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_name = ? ORDER BY created_at DESC");
$stmt->execute([$user]);
$db_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_orders = [];
foreach ($db_orders as $order) {
    // Fetch items with image_url from products table
    $stmtItems = $pdo->prepare("
        SELECT od.product_name, od.price_at_purchase, od.quantity, p.image_url 
        FROM order_details od 
        LEFT JOIN products p ON od.product_id = p.id 
        WHERE od.order_id = ?
    ");
    $stmtItems->execute([$order['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    
    $mapped_items = [];
    foreach ($items as $item) {
        $mapped_items[] = [
            'name' => $item['product_name'],
            'price' => $item['price_at_purchase'],
            'quantity' => $item['quantity'],
            'image_url' => $item['image_url']
        ];
    }
    
    $user_orders[] = [
        'id' => $order['id'],
        'date' => date('M j, Y', strtotime($order['created_at'])),
        'status' => $order['status'],
        'total' => $order['total_amount'],
        'items' => $mapped_items
    ];
}

require_once("includes/header.php");
?>
<link rel="stylesheet" href="css/profile.css">

<main class="profile-page">
    <div class="profile-container">
        <aside class="profile-sidebar">
            <div class="profile-user-info">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user); ?>&background=b57edc&color=fff" alt="Avatar" class="profile-avatar">
                <h2><?php echo htmlspecialchars($user); ?></h2>
                <p>Welcome back!</p>
            </div>
            <nav class="profile-nav">
                <a href="#orders" class="active"><i class="fas fa-shopping-bag"></i> My Orders</a>
                <a href="?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <section class="profile-content">
            <h1 class="page-title">My Orders</h1>
            
            <?php if(empty($user_orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                    <h3>You have no orders yet</h3>
                    <p>Start shopping to see your orders here.</p>
                    <a href="product.php" class="btn-primary" style="margin-top: 1.5rem; display: inline-block;">Shop Now</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach($user_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <span class="order-id">Order #<?php echo $order['id']; ?></span>
                                    <span class="order-date">Placed on <?php echo htmlspecialchars($order['date']); ?></span>
                                </div>
                                <div class="order-status-wrapper">
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-summary">
                                <span><strong>Total:</strong> Rs.<?php echo number_format($order['total'], 2); ?></span>
                                <button class="btn-toggle-details" onclick="toggleDetails(<?php echo $order['id']; ?>)">View Details <i class="fas fa-chevron-down"></i></button>
                            </div>

                            <div class="order-details" id="details-<?php echo $order['id']; ?>" style="display: none;">
                                <h4>Items in this order</h4>
                                <table class="details-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(!empty($order['items'])): foreach($order['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="item-product">
                                                    <img src="<?php echo htmlspecialchars($item['image_url'] ? $item['image_url'] : 'https://placehold.co/40'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <span><?php echo htmlspecialchars($item['name']); ?></span>
                                                </div>
                                            </td>
                                            <td>Rs.<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><strong>Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                                        </tr>
                                        <?php endforeach; else: ?>
                                        <tr><td colspan="4" class="text-center">No details available for legacy orders.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
function toggleDetails(orderId) {
    const detailsDiv = document.getElementById('details-' + orderId);
    if(detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
    }
}
</script>

<?php require_once("includes/footer.php"); ?>
