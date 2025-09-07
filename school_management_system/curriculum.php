<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

// Get subjects by class
try {
    $stmt = $pdo->prepare("
        SELECT s.*, c.class_name, u.full_name as teacher_name 
        FROM subjects s 
        LEFT JOIN classes c ON s.class_id = c.id 
        LEFT JOIN users u ON s.teacher_id = u.id 
        WHERE s.is_active = 1 
        ORDER BY c.class_name, s.subject_name
    ");
    $stmt->execute();
    $subjects = $stmt->fetchAll();
} catch (Exception $e) {
    $subjects = [];
}

$breadcrumbs = [
    ['title' => 'Curriculum']
];

ob_start();
?>

<div class="container">
    <!-- Curriculum Overview -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Academic Curriculum</h2>
            <p class="section-subtitle">Comprehensive academic program designed for holistic student development</p>
        </div>
        
        <div class="content-grid">
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-book-fill"></i>
                </div>
                <h3>CBSE Curriculum</h3>
                <p>Following the Central Board of Secondary Education (CBSE) curriculum with modern teaching methodologies and updated syllabi.</p>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-globe"></i>
                </div>
                <h3>Global Standards</h3>
                <p>Our curriculum meets international educational standards while maintaining strong cultural and ethical foundations.</p>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h3>Progressive Learning</h3>
                <p>Age-appropriate curriculum that builds skills progressively from foundational concepts to advanced knowledge.</p>
            </div>
        </div>
    </section>

    <!-- Subject Categories -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Subject Categories</h2>
                <p class="section-subtitle">Comprehensive range of subjects for well-rounded education</p>
            </div>
            
            <div class="values-grid">
                <div class="value-card scroll-reveal">
                    <div class="value-icon">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <h3 class="value-title">Mathematics & Science</h3>
                    <p class="value-description">Strong foundation in mathematics, physics, chemistry, and biology with practical laboratory sessions and problem-solving approach.</p>
                </div>
                
                <div class="value-card scroll-reveal">
                    <div class="value-icon">
                        <i class="bi bi-translate"></i>
                    </div>
                    <h3 class="value-title">Languages</h3>
                    <p class="value-description">English, Hindi, and regional languages with focus on communication skills, literature, and cultural appreciation.</p>
                </div>
                
                <div class="value-card scroll-reveal">
                    <div class="value-icon">
                        <i class="bi bi-globe-americas"></i>
                    </div>
                    <h3 class="value-title">Social Studies</h3>
                    <p class="value-description">History, geography, civics, and economics to develop understanding of society, culture, and global perspectives.</p>
                </div>
                
                <div class="value-card scroll-reveal">
                    <div class="value-icon">
                        <i class="bi bi-palette"></i>
                    </div>
                    <h3 class="value-title">Arts & Creativity</h3>
                    <p class="value-description">Visual arts, music, dance, and drama to nurture creativity and artistic expression in students.</p>
                </div>
                
                <div class="value-card scroll-reveal">
                    <div class="value-icon">
                        <i class="bi bi-activity"></i>
                    </div>
                    <h3 class="value-title">Physical Education</h3>
                    <p class="value-description">Comprehensive physical education program including sports, yoga, and health education for overall fitness.</p>
                </div>
                
                <div class="value-card scroll-reveal">
                    <div class="value-icon">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h3 class="value-title">Computer Science</h3>
                    <p class="value-description">Digital literacy, programming, and technology skills to prepare students for the digital age.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Learning Approach -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Learning Approach</h2>
                <p class="section-subtitle">Modern teaching methodologies for effective learning</p>
            </div>
            
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="bi bi-people"></i> Interactive Learning</h3>
                    <p>Collaborative learning environment with group discussions, peer learning, and interactive classroom sessions.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-laptop"></i> Technology Integration</h3>
                    <p>Smart classrooms with digital tools, online resources, and multimedia content to enhance learning experience.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-flask"></i> Practical Learning</h3>
                    <p>Hands-on experiments, field trips, and real-world applications to make learning relevant and engaging.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-graph-up"></i> Continuous Assessment</h3>
                    <p>Regular evaluation through tests, projects, and assignments to track progress and provide timely feedback.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Assessment & Evaluation -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Assessment & Evaluation</h2>
                <p class="section-subtitle">Comprehensive evaluation system to track student progress</p>
            </div>
            
            <div class="content-grid">
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <h3>Formative Assessment</h3>
                    <p>Regular quizzes, assignments, and class participation to monitor ongoing learning and provide immediate feedback.</p>
                    <div class="achievement-year">Continuous</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <h3>Summative Assessment</h3>
                    <p>Periodic tests and final examinations to evaluate cumulative learning and academic achievement.</p>
                    <div class="achievement-year">Term-wise</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <h3>Skill Assessment</h3>
                    <p>Evaluation of practical skills, creative abilities, and life skills development through projects and activities.</p>
                    <div class="achievement-year">Project-based</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Discover Our Academic Excellence</h2>
            <p class="cta-description">Experience world-class education with our comprehensive curriculum</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="admissions.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-journal-plus"></i> Apply for Admission
                </a>
                <a href="faculty.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-people"></i> Meet Our Faculty
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'Academic Curriculum',
    'Discover our comprehensive academic program designed for holistic student development and excellence.',
    $content,
    null,
    $breadcrumbs
);
?>
