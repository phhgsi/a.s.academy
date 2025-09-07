<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

$breadcrumbs = [
    ['title' => 'Our Values']
];

ob_start();
?>

<div class="container">
    <!-- Core Values -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Our Core Values</h2>
            <p class="section-subtitle">The fundamental principles that guide our educational philosophy and approach</p>
        </div>
        
        <div class="values-grid">
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-star-fill"></i>
                </div>
                <h3 class="value-title">Excellence</h3>
                <p class="value-description">We strive for excellence in all aspects of education, encouraging students to achieve their highest potential in academics, sports, and personal development.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3 class="value-title">Integrity</h3>
                <p class="value-description">We uphold the highest standards of honesty, transparency, and ethical behavior in all our interactions and decision-making processes.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <h3 class="value-title">Respect</h3>
                <p class="value-description">We foster an environment of mutual respect, valuing diversity, individual differences, and treating everyone with dignity and kindness.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-lightbulb-fill"></i>
                </div>
                <h3 class="value-title">Innovation</h3>
                <p class="value-description">We embrace creativity and innovation in teaching methods, encouraging students to think critically and approach problems with fresh perspectives.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h3 class="value-title">Community</h3>
                <p class="value-description">We build strong connections within our school community and the broader society, emphasizing collaboration and social responsibility.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-arrow-up-circle"></i>
                </div>
                <h3 class="value-title">Growth</h3>
                <p class="value-description">We believe in continuous learning and improvement, supporting both students and staff in their personal and professional development journey.</p>
            </div>
        </div>
    </section>

    <!-- Character Development -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Character Development</h2>
                <p class="section-subtitle">Building strong character alongside academic excellence</p>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-compass"></i>
                    </div>
                    <h3>Moral Education</h3>
                    <p>Integrated moral education that helps students develop strong ethical foundations and make responsible choices in life.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-handshake"></i>
                    </div>
                    <h3>Social Responsibility</h3>
                    <p>Encouraging students to be socially responsible citizens who contribute positively to their communities and society.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-person-hearts"></i>
                    </div>
                    <h3>Emotional Intelligence</h3>
                    <p>Developing emotional intelligence and interpersonal skills that help students build meaningful relationships and succeed in life.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Educational Philosophy -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Educational Philosophy</h2>
                <p class="section-subtitle">How we approach learning and student development</p>
            </div>
            
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="bi bi-puzzle"></i> Holistic Development</h3>
                    <p>We believe in nurturing all aspects of a student's personality - intellectual, physical, emotional, and social - to create well-rounded individuals.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-person-workspace"></i> Individual Attention</h3>
                    <p>Every student is unique, and we provide personalized attention to help each child discover their strengths and overcome their challenges.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-globe"></i> Global Perspective</h3>
                    <p>We prepare students for a globally connected world by fostering cultural awareness, critical thinking, and communication skills.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-tree"></i> Environmental Consciousness</h3>
                    <p>We instill environmental awareness and responsibility, teaching students to be stewards of the planet for future generations.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Experience Our Value-Based Education</h2>
            <p class="cta-description">Join us in building character, knowledge, and life skills that last a lifetime</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="admissions.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-journal-plus"></i> Apply Now
                </a>
                <a href="about.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-info-circle"></i> Learn More
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'Our Values',
    'Learn about the core values that guide our educational philosophy and character development approach.',
    $content,
    null,
    $breadcrumbs
);
?>
