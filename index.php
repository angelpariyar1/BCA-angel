<?php 
require_once("includes/config.php");
include("includes/header.php"); 

$stmt = $pdo->query("SELECT * FROM products WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 4");
$display_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_featured = 1");
$total_featured = $countStmt->fetchColumn();
$has_more = $total_featured > 4;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PiperPens - Home Page</title>
    <link rel="stylesheet" href="css/index.css">
</head>
   <body>
    <main>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        
        <div class="hero-content">
            <span class="hero-badge">Premium Collection</span>
            <h1>Elevate Your <span>Writing</span> Experience</h1>
            <p>Discover our meticulously curated collection of fine pens, elegant notebooks, and professional stationery designed to inspire creativity.</p>
            <div class="hero-actions">
                <a href="product.php" class="btn-primary">Shop Now</a>
            </div>
        </div>
        
        <div class="hero-image">
            <img src="https://images.unsplash.com/photo-1456735190827-d1262f71b8a3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Stationery Shop">
        </div>
    </section>

    <section id="featured-products" class="features-section">
        <div class="section-header">
            <h2>Crafted for Excellence</h2>
            <p>Discover our exclusive and handpicked selections designed to inspire your creativity.</p>
        </div>
        
        <div class="category-grid">
            <?php foreach($display_products as $product): ?>
            <div class="category-card" onclick="window.location.href='product.php'">
                <div class="category-image">
                    <img src="<?php echo htmlspecialchars($product['image_url'] ? $product['image_url'] : 'https://placehold.co/600'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="featured-banner">★ Featured</div>
                </div>
                <div class="category-content">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p>Rs. <?php echo number_format($product['price'], 2); ?> • <?php echo htmlspecialchars($product['category']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($display_products)): ?>
                <div class="empty-state" style="text-align: center; grid-column: 1 / -1; padding: 2rem; color: var(--text-muted);">
                    Check back soon for our exclusive collections!
                </div>
            <?php endif; ?>
        </div>
        
        <?php if($has_more): ?>
        <div style="text-align: center; margin-top: 3rem;">
            <a href="product.php" class="btn-primary">Continue Explore</a>
        </div>
        <?php endif; ?>
    </section>
    </main>
    </body>
</html>

<?php include("includes/footer.php"); ?>