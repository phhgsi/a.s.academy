<?php
function renderPublicPage($page_title, $meta_description, $content, $hero_image = null, $breadcrumbs = []) {
    // Get school information
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM school_info WHERE id = 1");
        $stmt->execute();
        $school_info = $stmt->fetch();
    } catch (Exception $e) {
        $school_info = null;
    }
    
    $full_title = $page_title . ' - ' . ($school_info ? $school_info['school_name'] : 'School Management System');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($full_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/homepage.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Performance optimizations -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Open Graph meta tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($full_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:type" content="website">
    
    <?php if ($hero_image): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($hero_image); ?>">
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="homepage-navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">
                    <?php if ($school_info && $school_info['logo']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($school_info['logo']); ?>" alt="School Logo" class="navbar-logo">
                    <?php endif; ?>
                    <div class="brand-text">
                        <h1><?php echo $school_info ? htmlspecialchars($school_info['school_name']) : 'School Management System'; ?></h1>
                        <span class="brand-tagline">Excellence in Education</span>
                    </div>
                </div>
                
                <div class="navbar-menu">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="gallery.php" class="nav-link">Gallery</a>
                    <a href="contact.php" class="nav-link">Contact</a>
                    <a href="admissions.php" class="nav-link">Admissions</a>
                    <a href="login.php" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                </div>
                
                <div class="mobile-menu-toggle">
                    <i class="bi bi-list"></i>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Hero Section -->
    <section class="page-hero">
        <div class="hero-background">
            <?php if ($hero_image): ?>
                <div class="hero-image" style="background-image: url('<?php echo htmlspecialchars($hero_image); ?>');"></div>
            <?php endif; ?>
            <div class="hero-overlay"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                
                <?php if (!empty($breadcrumbs)): ?>
                <nav class="breadcrumb">
                    <a href="index.php" class="breadcrumb-item">Home</a>
                    <?php foreach ($breadcrumbs as $crumb): ?>
                        <?php if (isset($crumb['url'])): ?>
                            <a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="breadcrumb-item"><?php echo htmlspecialchars($crumb['title']); ?></a>
                        <?php else: ?>
                            <span class="breadcrumb-item active"><?php echo htmlspecialchars($crumb['title']); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Page Content -->
    <main class="page-content">
        <?php echo $content; ?>
    </main>

    <!-- Footer -->
    <footer class="homepage-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <?php if ($school_info && $school_info['logo']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($school_info['logo']); ?>" alt="School Logo" class="footer-logo">
                        <?php endif; ?>
                        <h3><?php echo $school_info ? htmlspecialchars($school_info['school_name']) : 'School Management System'; ?></h3>
                        <p>Excellence in Education, Character in Development</p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="admissions.php">Admissions</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="news.php">News</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Academic</h4>
                    <ul class="footer-links">
                        <li><a href="curriculum.php">Curriculum</a></li>
                        <li><a href="faculty.php">Faculty</a></li>
                        <li><a href="facilities.php">Facilities</a></li>
                        <li><a href="achievements.php">Achievements</a></li>
                        <li><a href="login.php">Student Portal</a></li>
                    </ul>
                </div>
                
                <?php if ($school_info): ?>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="footer-contact">
                        <p><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($school_info['address']); ?></p>
                        <p><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($school_info['phone']); ?></p>
                        <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($school_info['email']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $school_info ? htmlspecialchars($school_info['school_name']) : 'School Management System'; ?>. All rights reserved.</p>
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/homepage.js" defer></script>
    
    <!-- Loading overlay for better transitions -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>
</body>
</html>
<?php } ?>
