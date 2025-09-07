<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

$breadcrumbs = [
    ['title' => 'Achievements']
];

ob_start();
?>

<div class="container">
    <!-- Academic Achievements -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Our Achievements</h2>
            <p class="section-subtitle">Celebrating excellence in academics, sports, and extracurricular activities</p>
        </div>
        
        <div class="achievement-grid">
            <div class="achievement-card scroll-reveal">
                <div class="achievement-icon">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                <h3 class="achievement-title">100% Pass Rate</h3>
                <p class="achievement-description">Consistent 100% pass rate in board examinations for the past 5 years with many students securing distinction.</p>
                <div class="achievement-year">2019 - 2024</div>
            </div>
            
            <div class="achievement-card scroll-reveal">
                <div class="achievement-icon">
                    <i class="bi bi-award-fill"></i>
                </div>
                <h3 class="achievement-title">Best School Award</h3>
                <p class="achievement-description">Recognized as the "Best School for Academic Excellence" by the State Education Board.</p>
                <div class="achievement-year">2023</div>
            </div>
            
            <div class="achievement-card scroll-reveal">
                <div class="achievement-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h3 class="achievement-title">Top Rank Holders</h3>
                <p class="achievement-description">15 students secured positions in top 100 state merit list in board examinations.</p>
                <div class="achievement-year">2024</div>
            </div>
            
            <div class="achievement-card scroll-reveal">
                <div class="achievement-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h3 class="achievement-title">Academic Improvement</h3>
                <p class="achievement-description">95% students showed significant improvement in academic performance compared to previous year.</p>
                <div class="achievement-year">2024</div>
            </div>
        </div>
    </section>

    <!-- Sports Achievements -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Sports Excellence</h2>
                <p class="section-subtitle">Outstanding performance in various sports competitions and events</p>
            </div>
            
            <div class="achievement-grid">
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-dribbble"></i>
                    </div>
                    <h3 class="achievement-title">Basketball Champions</h3>
                    <p class="achievement-description">Won the Inter-School Basketball Championship for three consecutive years.</p>
                    <div class="achievement-year">2022 - 2024</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-activity"></i>
                    </div>
                    <h3 class="achievement-title">Athletics Excellence</h3>
                    <p class="achievement-description">12 students qualified for state-level athletics competitions with 5 winning medals.</p>
                    <div class="achievement-year">2024</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-bicycle"></i>
                    </div>
                    <h3 class="achievement-title">Cycling Tournament</h3>
                    <p class="achievement-description">First place in district-level cycling competition and third place at state level.</p>
                    <div class="achievement-year">2023</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-flag-fill"></i>
                    </div>
                    <h3 class="achievement-title">Overall Sports Champion</h3>
                    <p class="achievement-description">Best performing school in overall sports activities at district level competitions.</p>
                    <div class="achievement-year">2023</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Cultural & Extracurricular -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Cultural & Extracurricular</h2>
                <p class="section-subtitle">Recognition in arts, culture, and various competitions</p>
            </div>
            
            <div class="achievement-grid">
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-palette-fill"></i>
                    </div>
                    <h3 class="achievement-title">Art Competition Winners</h3>
                    <p class="achievement-description">Students won multiple prizes in national and state-level art and painting competitions.</p>
                    <div class="achievement-year">2024</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-music-note-beamed"></i>
                    </div>
                    <h3 class="achievement-title">Music Festival Champions</h3>
                    <p class="achievement-description">School choir and instrumental groups won first place in inter-school music festival.</p>
                    <div class="achievement-year">2023</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <h3 class="achievement-title">Debate Competition</h3>
                    <p class="achievement-description">Students excelled in debate and elocution competitions at regional and national levels.</p>
                    <div class="achievement-year">2024</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                    <h3 class="achievement-title">Photography Contest</h3>
                    <p class="achievement-description">First prize in state-level student photography competition on environmental awareness.</p>
                    <div class="achievement-year">2023</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Innovation & Technology -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Innovation & Technology</h2>
                <p class="section-subtitle">Leading in science, technology, and innovation competitions</p>
            </div>
            
            <div class="achievement-grid">
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-lightbulb-fill"></i>
                    </div>
                    <h3 class="achievement-title">Science Olympiad</h3>
                    <p class="achievement-description">25 students qualified for National Science Olympiad with 5 students receiving gold medals.</p>
                    <div class="achievement-year">2024</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <h3 class="achievement-title">Robotics Competition</h3>
                    <p class="achievement-description">Second place in national robotics competition for innovative robot design and programming.</p>
                    <div class="achievement-year">2023</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-code-slash"></i>
                    </div>
                    <h3 class="achievement-title">Coding Championship</h3>
                    <p class="achievement-description">Students won multiple prizes in programming contests and app development competitions.</p>
                    <div class="achievement-year">2024</div>
                </div>
                
                <div class="achievement-card scroll-reveal">
                    <div class="achievement-icon">
                        <i class="bi bi-globe"></i>
                    </div>
                    <h3 class="achievement-title">Environmental Project</h3>
                    <p class="achievement-description">School's eco-friendly initiative received recognition from UNESCO for environmental conservation efforts.</p>
                    <div class="achievement-year">2023</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recognition & Awards */
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Recognition & Awards</h2>
                <p class="section-subtitle">Official recognition and certifications received by our institution</p>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                    <h3>CBSE Affiliation</h3>
                    <p>Officially affiliated with Central Board of Secondary Education (CBSE) maintaining high educational standards.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3>ISO Certification</h3>
                    <p>ISO 9001:2015 certified for quality management systems in educational services and administration.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <h3>Excellence Award</h3>
                    <p>Received "School of Excellence" award from the District Education Department for outstanding performance.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Be Part of Our Success Story</h2>
            <p class="cta-description">Join our achievers and create your own success story with us</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="admissions.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-journal-plus"></i> Apply for Admission
                </a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-telephone"></i> Learn More
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'Our Achievements',
    'Discover the academic and extracurricular achievements of our students and school in various competitions.',
    $content,
    null,
    $breadcrumbs
);
?>
