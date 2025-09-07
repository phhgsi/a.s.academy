<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

$breadcrumbs = [
    ['title' => 'International Programs']
];

ob_start();
?>

<div class="container">
    <!-- International Programs Overview -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">International Programs</h2>
            <p class="section-subtitle">Preparing students for a globally connected world through international collaboration and exposure</p>
        </div>
        
        <div class="values-grid">
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-globe-americas"></i>
                </div>
                <h3 class="value-title">Global Curriculum</h3>
                <p class="value-description">International curriculum components that broaden students' perspectives and prepare them for global opportunities.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h3 class="value-title">Cultural Exchange</h3>
                <p class="value-description">Student exchange programs and cultural immersion activities with partner schools from different countries.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-translate"></i>
                </div>
                <h3 class="value-title">Language Programs</h3>
                <p class="value-description">Foreign language learning opportunities including French, German, and Spanish to enhance global communication skills.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-laptop"></i>
                </div>
                <h3 class="value-title">Virtual Collaborations</h3>
                <p class="value-description">Online collaborative projects with international schools and virtual classrooms connecting students worldwide.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-award"></i>
                </div>
                <h3 class="value-title">International Certifications</h3>
                <p class="value-description">Preparation for international examinations and certifications that open doors to global educational opportunities.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-compass"></i>
                </div>
                <h3 class="value-title">Global Citizenship</h3>
                <p class="value-description">Developing global awareness, cultural sensitivity, and understanding of international issues and perspectives.</p>
            </div>
        </div>
    </section>

    <!-- Partnership Schools -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">International Partnerships</h2>
                <p class="section-subtitle">Collaborations with schools and institutions worldwide</p>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-flag"></i>
                    </div>
                    <h3>Sister Schools Program</h3>
                    <p>Partnerships with schools in USA, UK, Australia, and Singapore for student exchange and collaborative projects.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-camera-video"></i>
                    </div>
                    <h3>Virtual Exchange</h3>
                    <p>Regular virtual meetings and collaborative sessions with international students through video conferencing and online platforms.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <h3>International Resources</h3>
                    <p>Access to international educational resources, online libraries, and digital content from partner institutions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Program Benefits -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Program Benefits</h2>
                <p class="section-subtitle">How international exposure benefits our students</p>
            </div>
            
            <div class="content-grid">
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                    <h3>Broadened Perspectives</h3>
                    <p>Exposure to different cultures and viewpoints enhances critical thinking and global awareness.</p>
                    <div class="achievement-year">Cultural Growth</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <h3>Language Skills</h3>
                    <p>Improved communication skills and proficiency in multiple languages through immersive experiences.</p>
                    <div class="achievement-year">Communication</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <h3>Future Opportunities</h3>
                    <p>Better preparation for international higher education and career opportunities in global markets.</p>
                    <div class="achievement-year">Career Ready</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Prepare for a Global Future</h2>
            <p class="cta-description">Give your child the advantage of international exposure and global education</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="admissions.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-journal-plus"></i> Apply for Admission
                </a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-info-circle"></i> Learn More
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'International Programs',
    'Explore our global education initiatives and international collaboration programs for student development.',
    $content,
    null,
    $breadcrumbs
);
?>
