<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

// Get all published news
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.full_name as author_name 
        FROM news n 
        LEFT JOIN users u ON n.created_by = u.id 
        WHERE n.is_published = 1 
        ORDER BY n.published_date DESC, n.created_at DESC
    ");
    $stmt->execute();
    $news_items = $stmt->fetchAll();
} catch (Exception $e) {
    $news_items = [];
}

// Get featured news
try {
    $stmt = $pdo->prepare("
        SELECT * FROM news 
        WHERE is_published = 1 AND is_featured = 1 
        ORDER BY published_date DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $featured_news = $stmt->fetchAll();
} catch (Exception $e) {
    $featured_news = [];
}

$breadcrumbs = [
    ['title' => 'News & Announcements']
];

ob_start();
?>

<div class="container">
    <!-- Featured News Section -->
    <?php if (!empty($featured_news)): ?>
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Featured News</h2>
            <p class="section-subtitle">Important announcements and highlights from our school</p>
        </div>
        
        <div class="news-grid">
            <?php foreach ($featured_news as $news): ?>
                <article class="news-card scroll-reveal">
                    <?php if ($news['featured_image']): ?>
                        <img src="uploads/news/<?php echo htmlspecialchars($news['featured_image']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-image">
                    <?php else: ?>
                        <div class="facility-placeholder">
                            <i class="bi bi-newspaper"></i>
                        </div>
                    <?php endif; ?>
                    <div class="news-content">
                        <div class="news-meta">
                            <span class="news-date"><?php echo date('M j, Y', strtotime($news['published_date'] ?: $news['created_at'])); ?></span>
                            <span class="news-category"><?php echo ucfirst(htmlspecialchars($news['category'])); ?></span>
                        </div>
                        <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p class="news-excerpt"><?php echo htmlspecialchars($news['excerpt'] ?: substr(strip_tags($news['content']), 0, 150) . '...'); ?></p>
                        <a href="news-detail.php?id=<?php echo $news['id']; ?>" class="news-link">
                            Read More <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- All News Section -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Latest News & Announcements</h2>
                <p class="section-subtitle">Stay updated with all school activities and important information</p>
            </div>
            
            <div class="news-grid">
                <?php if (!empty($news_items)): ?>
                    <?php foreach ($news_items as $news): ?>
                        <article class="news-card scroll-reveal">
                            <?php if ($news['featured_image']): ?>
                                <img src="uploads/news/<?php echo htmlspecialchars($news['featured_image']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-image">
                            <?php else: ?>
                                <div class="facility-placeholder">
                                    <i class="bi bi-newspaper"></i>
                                </div>
                            <?php endif; ?>
                            <div class="news-content">
                                <div class="news-meta">
                                    <span class="news-date"><?php echo date('M j, Y', strtotime($news['published_date'] ?: $news['created_at'])); ?></span>
                                    <span class="news-category"><?php echo ucfirst(htmlspecialchars($news['category'])); ?></span>
                                </div>
                                <h3 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                                <p class="news-excerpt"><?php echo htmlspecialchars($news['excerpt'] ?: substr(strip_tags($news['content']), 0, 150) . '...'); ?></p>
                                <a href="news-detail.php?id=<?php echo $news['id']; ?>" class="news-link">
                                    Read More <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Sample news when no data available -->
                    <article class="news-card scroll-reveal">
                        <div class="facility-placeholder">
                            <i class="bi bi-newspaper"></i>
                        </div>
                        <div class="news-content">
                            <div class="news-meta">
                                <span class="news-date">Aug 30, 2024</span>
                                <span class="news-category">Academic</span>
                            </div>
                            <h3 class="news-title">Welcome to New Academic Year 2024-25</h3>
                            <p class="news-excerpt">We are excited to welcome all students and parents to the new academic year 2024-25. This year brings new opportunities and exciting programs...</p>
                            <a href="#" class="news-link">
                                Read More <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                    
                    <article class="news-card scroll-reveal">
                        <div class="facility-placeholder">
                            <i class="bi bi-trophy"></i>
                        </div>
                        <div class="news-content">
                            <div class="news-meta">
                                <span class="news-date">Aug 25, 2024</span>
                                <span class="news-category">Events</span>
                            </div>
                            <h3 class="news-title">Annual Sports Day Celebrations</h3>
                            <p class="news-excerpt">Our annual sports day was a huge success with participation from students across all grades showcasing amazing talent and teamwork...</p>
                            <a href="#" class="news-link">
                                Read More <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                    
                    <article class="news-card scroll-reveal">
                        <div class="facility-placeholder">
                            <i class="bi bi-lightbulb"></i>
                        </div>
                        <div class="news-content">
                            <div class="news-meta">
                                <span class="news-date">Aug 20, 2024</span>
                                <span class="news-category">Academic</span>
                            </div>
                            <h3 class="news-title">Science Exhibition 2024</h3>
                            <p class="news-excerpt">Students demonstrated incredible innovation and creativity in our recent science exhibition with projects ranging from environmental solutions...</p>
                            <a href="#" class="news-link">
                                Read More <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter Subscription -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Stay Updated</h2>
            <p class="cta-description">Subscribe to our newsletter to receive the latest news and announcements</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-envelope"></i> Subscribe to Newsletter
                </a>
                <a href="events.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-calendar-event"></i> View Events
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'News & Announcements',
    'Stay updated with the latest news, announcements, and happenings at our school.',
    $content,
    null,
    $breadcrumbs
);
?>
