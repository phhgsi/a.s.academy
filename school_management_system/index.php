<?php
session_start();
require_once 'config/database.php';

// Get school information
try {
    $stmt = $pdo->prepare("SELECT * FROM school_info WHERE id = 1");
    $stmt->execute();
    $school_info = $stmt->fetch();
} catch (Exception $e) {
    $school_info = null;
}

// Get some statistics for homepage
try {
    $stats = [];
    
    // Total students
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
    $stmt->execute();
    $stats['students'] = $stmt->fetch()['count'];
    
    // Total teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND is_active = 1");
    $stmt->execute();
    $stats['teachers'] = $stmt->fetch()['count'];
    
    // Total classes
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes");
    $stmt->execute();
    $stats['classes'] = $stmt->fetch()['count'];
    
    // Current academic year
    $stmt = $pdo->prepare("SELECT year_name FROM academic_years WHERE is_current = 1 LIMIT 1");
    $stmt->execute();
    $current_year = $stmt->fetch();
    $stats['current_year'] = $current_year ? $current_year['year_name'] : date('Y');
    
} catch (Exception $e) {
    $stats = ['students' => 0, 'teachers' => 0, 'classes' => 0, 'current_year' => date('Y')];
}

// Get recent news/announcements (from optional CMS tables)
try {
    // Check if news table exists before querying
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'news'");
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $recent_news = $stmt->fetchAll();
    } else {
        $recent_news = [];
    }
} catch (Exception $e) {
    $recent_news = [];
}

// Get gallery images (from optional CMS tables)
try {
    // Check if gallery table exists before querying
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'gallery'");
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare("SELECT * FROM gallery WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 6");
        $stmt->execute();
        $featured_images = $stmt->fetchAll();
    } else {
        $featured_images = [];
    }
} catch (Exception $e) {
    $featured_images = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $school_info ? $school_info['school_name'] : 'School Management System'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/homepage.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <meta name="description" content="<?php echo $school_info ? htmlspecialchars($school_info['description']) : 'Modern School Management System for Educational Excellence'; ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="homepage-navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">
                    <?php if ($school_info && $school_info['logo'] && file_exists('uploads/' . $school_info['logo'])): ?>
                        <img src="uploads/<?php echo $school_info['logo']; ?>" alt="School Logo" class="navbar-logo">
                    <?php else: ?>
                        <div class="default-logo">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                    <?php endif; ?>
                    <div class="brand-text">
                        <h1><?php echo $school_info ? htmlspecialchars($school_info['school_name']) : 'School Management System'; ?></h1>
                        <span class="brand-tagline">Excellence in Education</span>
                    </div>
                </div>
                
                <div class="navbar-menu">
                    <a href="#home" class="nav-link active">Home</a>
                    <a href="#about" class="nav-link">About</a>
                    <a href="#gallery" class="nav-link">Gallery</a>
                    <a href="#contact" class="nav-link">Contact</a>
                    <a href="admissions.php" class="nav-link">Admissions</a>
                    <a href="login.php" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                </div>
                
                <div class="mobile-menu-toggle">
                    <i class="bi bi-list"></i>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-background">
            <div class="hero-overlay"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title animate-fade-in">
                        Welcome to<br>
                        <span class="highlight"><?php echo $school_info ? $school_info['school_name'] : 'Our School'; ?></span>
                    </h1>
                    
                    <p class="hero-subtitle animate-fade-in-delay">
                        <?php echo $school_info ? $school_info['description'] : 'Nurturing minds, building futures, and creating tomorrow\'s leaders through innovative education and character development.'; ?>
                    </p>
                    
                    <div class="hero-actions animate-fade-in-delay-2">
                        <a href="#about" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-right"></i> Learn More
                        </a>
                        <a href="admissions.php" class="btn btn-secondary btn-lg">
                            <i class="bi bi-journal-plus"></i> Admissions
                        </a>
                    </div>
                </div>
                
                <div class="hero-stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['students']); ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['teachers']); ?></div>
                        <div class="stat-label">Teachers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($stats['classes']); ?></div>
                        <div class="stat-label">Classes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['current_year']; ?></div>
                        <div class="stat-label">Academic Year</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Choose Our School?</h2>
                <p class="section-subtitle">Discover what makes our educational approach unique and effective</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3>Expert Faculty</h3>
                    <p>Highly qualified and experienced teachers dedicated to student success and character development.</p>
                    <div class="feature-link">
                        <a href="faculty.php">Meet Our Team <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h3>Modern Technology</h3>
                    <p>State-of-the-art digital classrooms and learning resources for 21st-century education.</p>
                    <div class="feature-link">
                        <a href="facilities.php">View Facilities <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <h3>Academic Excellence</h3>
                    <p>Proven track record of academic achievements and student success in various competitions.</p>
                    <div class="feature-link">
                        <a href="achievements.php">Our Achievements <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-heart-fill"></i>
                    </div>
                    <h3>Character Building</h3>
                    <p>Focus on moral values, ethics, and character development alongside academic excellence.</p>
                    <div class="feature-link">
                        <a href="values.php">Our Values <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-globe"></i>
                    </div>
                    <h3>Global Perspective</h3>
                    <p>Preparing students for a connected world with international curriculum and cultural awareness.</p>
                    <div class="feature-link">
                        <a href="international.php">Learn More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>Safe Environment</h3>
                    <p>Secure and nurturing environment where every child can learn, grow, and thrive safely.</p>
                    <div class="feature-link">
                        <a href="safety.php">Safety Measures <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Preview Section -->
    <section id="gallery" class="gallery-preview-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">School Life Gallery</h2>
                <p class="section-subtitle">Glimpses of our vibrant school community and activities</p>
            </div>
            
            <div class="gallery-grid">
                <?php if (!empty($featured_images)): ?>
                    <?php foreach ($featured_images as $image): ?>
                        <div class="gallery-item">
                            <img src="uploads/gallery/<?php echo $image['image_path']; ?>" alt="<?php echo htmlspecialchars($image['title']); ?>">
                            <div class="gallery-overlay">
                                <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                                <p><?php echo htmlspecialchars($image['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Placeholder gallery items with CSS-based backgrounds -->
                    <div class="gallery-item placeholder-item" data-category="activities">
                        <div class="placeholder-content">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="gallery-overlay">
                            <h4>School Activities</h4>
                            <p>Students engaging in various educational activities</p>
                        </div>
                    </div>
                    <div class="gallery-item placeholder-item" data-category="sports">
                        <div class="placeholder-content">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <div class="gallery-overlay">
                            <h4>Sports Day</h4>
                            <p>Annual sports day celebrations and competitions</p>
                        </div>
                    </div>
                    <div class="gallery-item placeholder-item" data-category="cultural">
                        <div class="placeholder-content">
                            <i class="bi bi-music-note-beamed"></i>
                        </div>
                        <div class="gallery-overlay">
                            <h4>Cultural Program</h4>
                            <p>Students showcasing their talents in cultural events</p>
                        </div>
                    </div>
                    <div class="gallery-item placeholder-item" data-category="science">
                        <div class="placeholder-content">
                            <i class="bi bi-microscope"></i>
                        </div>
                        <div class="gallery-overlay">
                            <h4>Science Fair</h4>
                            <p>Innovation and creativity in science exhibitions</p>
                        </div>
                    </div>
                    <div class="gallery-item placeholder-item" data-category="graduation">
                        <div class="placeholder-content">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div class="gallery-overlay">
                            <h4>Graduation Ceremony</h4>
                            <p>Celebrating achievements and new beginnings</p>
                        </div>
                    </div>
                    <div class="gallery-item placeholder-item" data-category="field-trip">
                        <div class="placeholder-content">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div class="gallery-overlay">
                            <h4>Educational Trip</h4>
                            <p>Learning beyond classroom boundaries</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center" style="margin-top: 3rem;">
                <a href="gallery.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-images"></i> View Full Gallery
                </a>
            </div>
        </div>
    </section>

    <!-- News & Events Section -->
    <?php if (!empty($recent_news)): ?>
    <section class="news-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Latest News & Events</h2>
                <p class="section-subtitle">Stay updated with our school activities and announcements</p>
            </div>
            
            <div class="news-grid">
                <?php foreach ($recent_news as $news): ?>
                    <article class="news-card">
                        <?php if ($news['featured_image']): ?>
                            <img src="uploads/news/<?php echo $news['featured_image']; ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-image">
                        <?php endif; ?>
                        <div class="news-content">
                            <div class="news-meta">
                                <span class="news-date"><?php echo date('M j, Y', strtotime($news['created_at'])); ?></span>
                                <span class="news-category"><?php echo ucfirst($news['category']); ?></span>
                            </div>
                            <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                            <p class="news-excerpt"><?php echo htmlspecialchars(substr($news['content'], 0, 150)) . '...'; ?></p>
                            <a href="news.php?id=<?php echo $news['id']; ?>" class="news-link">
                                Read More <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center" style="margin-top: 3rem;">
                <a href="news.php" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-newspaper"></i> View All News
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- About Section -->
    <?php if ($school_info): ?>
    <section id="about" class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2 class="section-title">About Our Institution</h2>
                    <p class="lead"><?php echo htmlspecialchars($school_info['description']); ?></p>
                    
                    <div class="school-info-grid">
                        <div class="info-item">
                            <i class="bi bi-calendar-event info-icon"></i>
                            <div>
                                <strong>Established</strong>
                                <span><?php echo $school_info['established_year']; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-award info-icon"></i>
                            <div>
                                <strong>Board</strong>
                                <span><?php echo $school_info['board']; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-person-badge info-icon"></i>
                            <div>
                                <strong>Principal</strong>
                                <span><?php echo $school_info['principal_name']; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-mortarboard info-icon"></i>
                            <div>
                                <strong>Current Year</strong>
                                <span><?php echo $stats['current_year']; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cta-section">
                        <a href="about.php" class="btn btn-primary">
                            <i class="bi bi-info-circle"></i> Learn More About Us
                        </a>
                    </div>
                </div>
                
                <div class="about-image">
                    <div class="image-placeholder">
                        <i class="bi bi-building"></i>
                        <p>School Campus</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Get In Touch</h2>
                <p class="section-subtitle">We'd love to hear from you. Contact us for admissions or any inquiries.</p>
            </div>
            
            <?php if ($school_info): ?>
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="bi bi-geo-alt-fill contact-icon"></i>
                        <div>
                            <h4>Visit Us</h4>
                            <p><?php echo $school_info['address']; ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="bi bi-telephone-fill contact-icon"></i>
                        <div>
                            <h4>Call Us</h4>
                            <p><?php echo $school_info['phone']; ?></p>
                            <?php if ($school_info['mobile']): ?>
                                <p><?php echo $school_info['mobile']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <i class="bi bi-envelope-fill contact-icon"></i>
                        <div>
                            <h4>Email Us</h4>
                            <p><a href="mailto:<?php echo $school_info['email']; ?>"><?php echo $school_info['email']; ?></a></p>
                        </div>
                    </div>
                    
                    <?php if ($school_info['website']): ?>
                    <div class="contact-item">
                        <i class="bi bi-globe contact-icon"></i>
                        <div>
                            <h4>Website</h4>
                            <p><a href="<?php echo $school_info['website']; ?>" target="_blank"><?php echo $school_info['website']; ?></a></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="contact-form">
                    <h3>Send us a Message</h3>
                    <form action="contact-submit.php" method="POST" class="contact-form-grid">
                        <div class="form-group">
                            <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="phone" class="form-control" placeholder="Your Phone">
                        </div>
                        <div class="form-group">
                            <select name="subject" class="form-control" required>
                                <option value="">Select Subject</option>
                                <option value="admissions">Admissions Inquiry</option>
                                <option value="general">General Information</option>
                                <option value="academic">Academic Programs</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <textarea name="message" class="form-control" rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <div class="form-group full-width">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="homepage-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <?php if ($school_info && $school_info['logo']): ?>
                            <img src="uploads/<?php echo $school_info['logo']; ?>" alt="School Logo" class="footer-logo">
                        <?php endif; ?>
                        <h3><?php echo $school_info ? $school_info['school_name'] : 'School Management System'; ?></h3>
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
                        <li><a href="student-portal.php">Student Portal</a></li>
                    </ul>
                </div>
                
                <?php if ($school_info): ?>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="footer-contact">
                        <p><i class="bi bi-geo-alt"></i> <?php echo $school_info['address']; ?></p>
                        <p><i class="bi bi-telephone"></i> <?php echo $school_info['phone']; ?></p>
                        <p><i class="bi bi-envelope"></i> <?php echo $school_info['email']; ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $school_info ? $school_info['school_name'] : 'School Management System'; ?>. All rights reserved.</p>
                <div class="footer-social">
                    <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/homepage.js"></script>
</body>
</html>
