<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

// Get faculty information
try {
    $stmt = $pdo->prepare("
        SELECT u.*, s.subject_name 
        FROM users u 
        LEFT JOIN subjects s ON u.id = s.teacher_id 
        WHERE u.role = 'teacher' AND u.is_active = 1
        GROUP BY u.id
        ORDER BY u.full_name
    ");
    $stmt->execute();
    $faculty = $stmt->fetchAll();
} catch (Exception $e) {
    $faculty = [];
}

$breadcrumbs = [
    ['title' => 'Faculty']
];

ob_start();
?>

<div class="container">
    <!-- Faculty Overview -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Meet Our Dedicated Faculty</h2>
            <p class="section-subtitle">Our team of qualified educators are committed to nurturing young minds and fostering academic excellence</p>
        </div>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="bi bi-mortarboard"></i>
                </div>
                <h3 class="value-title">Qualified Educators</h3>
                <p class="value-description">Our teachers hold advanced degrees and professional certifications in their respective subjects.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="bi bi-lightbulb"></i>
                </div>
                <h3 class="value-title">Innovative Teaching</h3>
                <p class="value-description">We employ modern teaching methodologies and technology to enhance learning experiences.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="bi bi-heart"></i>
                </div>
                <h3 class="value-title">Caring Mentors</h3>
                <p class="value-description">Beyond academics, our teachers serve as mentors, guiding students' personal and social development.</p>
            </div>
        </div>
    </section>

    <!-- Faculty Members -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Teaching Staff</h2>
                <p class="section-subtitle">Get to know the passionate educators who make learning an exciting journey</p>
            </div>
            
            <div class="staff-grid">
                <?php if (!empty($faculty)): ?>
                    <?php foreach ($faculty as $teacher): ?>
                        <div class="staff-card scroll-reveal">
                            <div class="staff-placeholder">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="staff-info">
                                <h3 class="staff-name"><?php echo htmlspecialchars($teacher['full_name']); ?></h3>
                                <div class="staff-role">Teacher</div>
                                <?php if ($teacher['subject_name']): ?>
                                    <div class="staff-subject"><?php echo htmlspecialchars($teacher['subject_name']); ?></div>
                                <?php endif; ?>
                                <div class="staff-contact">
                                    <?php if ($teacher['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>" class="contact-link" title="Email">
                                            <i class="bi bi-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Sample faculty members when no data available -->
                    <div class="staff-card scroll-reveal">
                        <div class="staff-placeholder">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="staff-info">
                            <h3 class="staff-name">Dr. Priya Sharma</h3>
                            <div class="staff-role">Principal</div>
                            <div class="staff-subject">M.Ed, Ph.D in Education</div>
                            <div class="staff-contact">
                                <a href="mailto:principal@school.edu" class="contact-link" title="Email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-card scroll-reveal">
                        <div class="staff-placeholder">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="staff-info">
                            <h3 class="staff-name">Mr. Rajesh Kumar</h3>
                            <div class="staff-role">Mathematics Teacher</div>
                            <div class="staff-subject">M.Sc Mathematics, B.Ed</div>
                            <div class="staff-contact">
                                <a href="mailto:rajesh@school.edu" class="contact-link" title="Email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-card scroll-reveal">
                        <div class="staff-placeholder">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="staff-info">
                            <h3 class="staff-name">Ms. Anita Singh</h3>
                            <div class="staff-role">English Teacher</div>
                            <div class="staff-subject">M.A English Literature, B.Ed</div>
                            <div class="staff-contact">
                                <a href="mailto:anita@school.edu" class="contact-link" title="Email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-card scroll-reveal">
                        <div class="staff-placeholder">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="staff-info">
                            <h3 class="staff-name">Dr. Vikram Patel</h3>
                            <div class="staff-role">Science Teacher</div>
                            <div class="staff-subject">M.Sc Physics, Ph.D, B.Ed</div>
                            <div class="staff-contact">
                                <a href="mailto:vikram@school.edu" class="contact-link" title="Email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-card scroll-reveal">
                        <div class="staff-placeholder">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="staff-info">
                            <h3 class="staff-name">Mrs. Meera Gupta</h3>
                            <div class="staff-role">Social Studies Teacher</div>
                            <div class="staff-subject">M.A History, B.Ed</div>
                            <div class="staff-contact">
                                <a href="mailto:meera@school.edu" class="contact-link" title="Email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="staff-card scroll-reveal">
                        <div class="staff-placeholder">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="staff-info">
                            <h3 class="staff-name">Mr. Arjun Reddy</h3>
                            <div class="staff-role">Physical Education Teacher</div>
                            <div class="staff-subject">M.P.Ed, Sports Certification</div>
                            <div class="staff-contact">
                                <a href="mailto:arjun@school.edu" class="contact-link" title="Email">
                                    <i class="bi bi-envelope"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Teaching Philosophy -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Teaching Philosophy</h2>
                <p class="section-subtitle">How we approach education and student development</p>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-puzzle"></i>
                    </div>
                    <h3>Student-Centered Learning</h3>
                    <p>We place students at the center of the learning process, encouraging active participation, critical thinking, and collaborative learning.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h3>Continuous Assessment</h3>
                    <p>Regular evaluation and feedback help students understand their progress and areas for improvement throughout their academic journey.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3>Collaborative Environment</h3>
                    <p>We foster a supportive environment where teachers, students, and parents work together to achieve educational goals.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Join Our Teaching Community</h2>
            <p class="cta-description">Interested in becoming part of our faculty? We welcome passionate educators</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-person-plus"></i> Teaching Opportunities
                </a>
                <a href="admissions.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-journal-plus"></i> Student Admissions
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'Our Faculty',
    'Meet our dedicated team of qualified educators committed to student success and educational excellence.',
    $content,
    null,
    $breadcrumbs
);
?>
