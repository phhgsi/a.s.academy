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

// Get gallery images with album filtering
$album_filter = isset($_GET['album']) ? $_GET['album'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

try {
    // Build query
    $where_clause = '';
    $params = [];
    
    if (!empty($album_filter)) {
        $where_clause = 'WHERE album_name = ?';
        $params[] = $album_filter;
    }
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM gallery $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_images = $stmt->fetch()['total'];
    
    // Get images for current page
    $sql = "SELECT * FROM gallery 
            $where_clause 
            ORDER BY is_featured DESC, created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $gallery_images = $stmt->fetchAll();
    
    // Get album names for filter
    $stmt = $pdo->prepare("SELECT DISTINCT album_name FROM gallery ORDER BY album_name");
    $stmt->execute();
    $albums = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $gallery_images = [];
    $albums = [];
    $total_images = 0;
}

$total_pages = ceil($total_images / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - <?php echo $school_info ? $school_info['school_name'] : 'School Management System'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/homepage.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Navigation -->
    <nav class="homepage-navbar">
        <div class="container">
            <div class="navbar-content">
                <div class="navbar-brand">
                    <?php if ($school_info && $school_info['logo']): ?>
                        <img src="uploads/<?php echo $school_info['logo']; ?>" alt="School Logo" class="navbar-logo">
                    <?php endif; ?>
                    <div class="brand-text">
                        <h1><?php echo $school_info ? $school_info['school_name'] : 'School Management System'; ?></h1>
                        <span class="brand-tagline">Excellence in Education</span>
                    </div>
                </div>
                
                <div class="navbar-menu">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="about.php" class="nav-link">About</a>
                    <a href="gallery.php" class="nav-link active">Gallery</a>
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

    <!-- Page Header -->
    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">School Gallery</h1>
            <p class="page-subtitle">Capturing moments, preserving memories of our school life</p>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-main">
        <div class="container">
            <!-- Album Filter -->
            <div class="gallery-controls">
                <div class="album-filters">
                    <button class="filter-btn <?php echo empty($album_filter) ? 'active' : ''; ?>" 
                            onclick="filterAlbum('')">All Photos</button>
                    <?php foreach ($albums as $album): ?>
                        <button class="filter-btn <?php echo $album_filter === $album ? 'active' : ''; ?>" 
                                onclick="filterAlbum('<?php echo $album; ?>')">
                            <?php echo ucfirst($album); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <div class="gallery-stats">
                    <span class="result-count"><?php echo $total_images; ?> Photos</span>
                </div>
            </div>

            <!-- Gallery Grid -->
            <?php if (empty($gallery_images)): ?>
                <div class="empty-gallery">
                    <i class="bi bi-images"></i>
                    <h3>No Images Found</h3>
                    <p>Photos will be displayed here once they are uploaded.</p>
                </div>
            <?php else: ?>
                <div class="public-gallery-grid">
                    <?php foreach ($gallery_images as $image): ?>
                        <div class="public-gallery-item" onclick="openGalleryLightbox('<?php echo $image['image_path']; ?>', '<?php echo addslashes($image['title']); ?>', '<?php echo addslashes($image['description']); ?>')">
                            <img src="uploads/gallery/<?php echo $image['image_path']; ?>" 
                                 alt="<?php echo htmlspecialchars($image['title']); ?>"
                                 onerror="this.src='assets/images/placeholder.jpg'">
                            
                            <?php if ($image['is_featured']): ?>
                                <div class="featured-indicator">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="gallery-item-overlay">
                                <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                                <p><?php echo htmlspecialchars($image['description'] ?: 'View Image'); ?></p>
                                <div class="item-meta">
                                    <span class="album-label"><?php echo ucfirst($image['album_name']); ?></span>
                                    <span class="date-label"><?php echo date('M Y', strtotime($image['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="gallery-pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $album_filter ? '&album=' . urlencode($album_filter) : ''; ?>" 
                               class="pagination-btn">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <div class="pagination-numbers">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $album_filter ? '&album=' . urlencode($album_filter) : ''; ?>" 
                                   class="pagination-number <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $album_filter ? '&album=' . urlencode($album_filter) : ''; ?>" 
                               class="pagination-btn">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="admissions.php">Admissions</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="contact.php">Contact</a></li>
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

    <script src="assets/js/homepage.js"></script>
    <script>
        function filterAlbum(album) {
            const url = new URL(window.location);
            if (album) {
                url.searchParams.set('album', album);
            } else {
                url.searchParams.delete('album');
            }
            url.searchParams.delete('page'); // Reset to first page
            window.location.href = url.toString();
        }
        
        function openGalleryLightbox(imagePath, title, description) {
            openLightbox('uploads/gallery/' + imagePath, title, description);
        }
        
        // Initialize gallery animations
        document.addEventListener('DOMContentLoaded', function() {
            const galleryItems = document.querySelectorAll('.public-gallery-item');
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                        }, index * 100);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });
            
            galleryItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                observer.observe(item);
            });
        });
    </script>

    <style>
        body {
            padding-top: 80px; /* Account for fixed navbar */
        }
        
        .page-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        
        .page-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .page-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .gallery-main {
            padding: 4rem 0;
            background: white;
            min-height: 60vh;
        }
        
        .gallery-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .album-filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.75rem 1.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .result-count {
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .public-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .public-gallery-item {
            position: relative;
            aspect-ratio: 4/3;
            border-radius: 1rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .public-gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .public-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }
        
        .featured-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #ffd700;
            color: #1a202c;
            padding: 0.5rem;
            border-radius: 50%;
            font-size: 0.8rem;
            z-index: 2;
        }
        
        .gallery-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.8) 100%);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem;
            color: white;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .public-gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }
        
        .gallery-item-overlay h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .gallery-item-overlay p {
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 1rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        .item-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .album-label {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
        }
        
        .date-label {
            font-weight: 500;
        }
        
        .empty-gallery {
            text-align: center;
            padding: 6rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-gallery i {
            font-size: 5rem;
            margin-bottom: 2rem;
            opacity: 0.5;
        }
        
        .empty-gallery h3 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .gallery-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 4rem;
        }
        
        .pagination-btn {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 2rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .pagination-numbers {
            display: flex;
            gap: 0.5rem;
        }
        
        .pagination-number {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .pagination-number:hover,
        .pagination-number.active {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .gallery-controls {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .album-filters {
                justify-content: center;
            }
            
            .public-gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
            
            .gallery-pagination {
                flex-direction: column;
                gap: 1rem;
            }
            
            .pagination-numbers {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .public-gallery-grid {
                grid-template-columns: 1fr;
            }
            
            .album-filters {
                flex-direction: column;
                width: 100%;
            }
            
            .filter-btn {
                width: 100%;
            }
        }
    </style>
</body>
</html>
