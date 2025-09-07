<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

// Get school information
try {
    $stmt = $pdo->prepare("SELECT * FROM school_info WHERE id = 1");
    $stmt->execute();
    $school_info = $stmt->fetch();
} catch (Exception $e) {
    $school_info = null;
}

// Get statistics
try {
    $stats = [];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE is_active = 1");
    $stmt->execute();
    $stats['students'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'teacher' AND is_active = 1");
    $stmt->execute();
    $stats['teachers'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classes");
    $stmt->execute();
    $stats['classes'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    $stats = ['students' => 0, 'teachers' => 0, 'classes' => 0];
}

$breadcrumbs = [
    ['title' => 'About Us']
];

ob_start();
?>

<div class="container">
    <!-- Mission & Vision Section -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Our Mission & Vision</h2>
            <p class="section-subtitle">Dedicated to nurturing young minds and building future leaders through quality education</p>
        </div>
        
        <div class="content-grid">
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-eye-fill"></i>
                </div>
                <h3>Our Vision</h3>
                <p>To be a premier educational institution that empowers students to become confident, creative, and compassionate global citizens who contribute meaningfully to society.</p>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-target"></i>
                </div>
                <h3>Our Mission</h3>
                <p>To provide holistic education that develops intellectual curiosity, critical thinking, and strong character while fostering an inclusive environment where every student can thrive.</p>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <h3>Our Values</h3>
                <p>Excellence, Integrity, Respect, Innovation, and Community - these core values guide everything we do and shape the character of our students.</p>
            </div>
        </div>
    </section>
    
    <!-- School Information -->
    <?php if ($school_info): ?>
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">About <?php echo htmlspecialchars($school_info['school_name']); ?></h2>
                <p class="section-subtitle"><?php echo htmlspecialchars($school_info['description']); ?></p>
            </div>
            
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="bi bi-calendar-event"></i> Established</h3>
                    <p>Founded in <?php echo htmlspecialchars($school_info['established_year']); ?>, our school has been committed to educational excellence for over <?php echo date('Y') - $school_info['established_year']; ?> years.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-award"></i> Affiliation</h3>
                    <p>Affiliated with <?php echo htmlspecialchars($school_info['board']); ?> (<?php echo htmlspecialchars($school_info['affiliation']); ?>)</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-person-badge"></i> Leadership</h3>
                    <p>Under the guidance of Principal <?php echo htmlspecialchars($school_info['principal_name']); ?>, our school continues to achieve new heights in academic excellence.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-geo-alt"></i> Location</h3>
                    <p><?php echo htmlspecialchars($school_info['address']); ?></p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Statistics Section -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our School in Numbers</h2>
                <p class="section-subtitle">Building a strong educational community</p>
            </div>
            
            <div class="content-grid">
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="achievement-title"><?php echo number_format($stats['students']); ?>+</div>
                    <div class="achievement-description">Active Students</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div class="achievement-title"><?php echo number_format($stats['teachers']); ?>+</div>
                    <div class="achievement-description">Qualified Teachers</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="achievement-title"><?php echo number_format($stats['classes']); ?>+</div>
                    <div class="achievement-description">Classes</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <div class="achievement-title">100%</div>
                    <div class="achievement-description">Pass Rate</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Ready to Join Our School Community?</h2>
            <p class="cta-description">Take the first step towards your child's bright future with us</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="admissions.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-journal-plus"></i> Apply for Admission
                </a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-telephone"></i> Contact Us
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'About Us',
    'Learn about our school\'s mission, vision, history, and commitment to educational excellence.',
    $content,
    null,
    $breadcrumbs
);
?>
