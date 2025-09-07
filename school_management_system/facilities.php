<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

$breadcrumbs = [
    ['title' => 'Facilities']
];

ob_start();
?>

<div class="container">
    <!-- Facilities Overview -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">World-Class Facilities</h2>
            <p class="section-subtitle">State-of-the-art infrastructure designed to enhance learning and student development</p>
        </div>
        
        <div class="facility-grid">
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-laptop"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Smart Classrooms</h3>
                    <p class="facility-description">Modern classrooms equipped with interactive whiteboards, projectors, and digital learning tools for enhanced education.</p>
                    <ul class="facility-features">
                        <li>Interactive whiteboards in every classroom</li>
                        <li>High-speed internet connectivity</li>
                        <li>Audio-visual learning systems</li>
                        <li>Air-conditioned environment</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-flask"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Science Laboratories</h3>
                    <p class="facility-description">Well-equipped laboratories for Physics, Chemistry, and Biology with modern instruments and safety equipment.</p>
                    <ul class="facility-features">
                        <li>Separate labs for each science subject</li>
                        <li>Modern scientific equipment</li>
                        <li>Safety protocols and equipment</li>
                        <li>Experienced lab assistants</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-book"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Library & Resource Center</h3>
                    <p class="facility-description">Extensive collection of books, digital resources, and quiet study areas to support academic research and reading.</p>
                    <ul class="facility-features">
                        <li>Over 10,000 books and journals</li>
                        <li>Digital library with e-books</li>
                        <li>Quiet study zones</li>
                        <li>Research assistance</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-cpu"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Computer Laboratory</h3>
                    <p class="facility-description">Modern computer lab with latest software and high-speed internet for digital literacy and programming education.</p>
                    <ul class="facility-features">
                        <li>Latest computers with modern software</li>
                        <li>Programming and coding tools</li>
                        <li>Internet access for research</li>
                        <li>Technical support staff</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-dribbble"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Sports Complex</h3>
                    <p class="facility-description">Comprehensive sports facilities including playground, gymnasium, and courts for various indoor and outdoor games.</p>
                    <ul class="facility-features">
                        <li>Large playground for outdoor sports</li>
                        <li>Indoor gymnasium</li>
                        <li>Basketball and badminton courts</li>
                        <li>Sports equipment and gear</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-palette"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Art & Music Rooms</h3>
                    <p class="facility-description">Dedicated spaces for creative expression with art supplies, musical instruments, and performance areas.</p>
                    <ul class="facility-features">
                        <li>Art studio with supplies</li>
                        <li>Music room with instruments</li>
                        <li>Dance and drama hall</li>
                        <li>Exhibition and performance spaces</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-cup-hot"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Cafeteria</h3>
                    <p class="facility-description">Clean and hygienic cafeteria serving nutritious meals and snacks prepared with fresh ingredients.</p>
                    <ul class="facility-features">
                        <li>Nutritious meal plans</li>
                        <li>Hygienic food preparation</li>
                        <li>Comfortable seating area</li>
                        <li>Special dietary accommodations</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-bus-front"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Transportation</h3>
                    <p class="facility-description">Safe and reliable school bus service covering major areas of the city with trained drivers and attendants.</p>
                    <ul class="facility-features">
                        <li>GPS-enabled school buses</li>
                        <li>Trained drivers and attendants</li>
                        <li>Multiple routes across the city</li>
                        <li>Safety protocols and monitoring</li>
                    </ul>
                </div>
            </div>
            
            <div class="facility-card scroll-reveal">
                <div class="facility-placeholder">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div class="facility-content">
                    <h3 class="facility-title">Medical Facility</h3>
                    <p class="facility-description">On-campus medical facility with qualified nurse and first aid equipment for emergency situations.</p>
                    <ul class="facility-features">
                        <li>Qualified nursing staff</li>
                        <li>First aid and emergency care</li>
                        <li>Health monitoring programs</li>
                        <li>Regular health check-ups</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Safety & Security -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Safety & Security</h2>
                <p class="section-subtitle">Your child's safety is our top priority</p>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-camera-video"></i>
                    </div>
                    <h3>CCTV Surveillance</h3>
                    <p>24/7 CCTV monitoring throughout the campus to ensure safety and security of all students and staff.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h3>Controlled Access</h3>
                    <p>Restricted entry with security personnel and visitor management system to maintain a safe environment.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-fire"></i>
                    </div>
                    <h3>Fire Safety</h3>
                    <p>Complete fire safety systems including smoke detectors, fire extinguishers, and emergency evacuation procedures.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Experience Our Facilities</h2>
            <p class="cta-description">Schedule a campus tour to see our world-class facilities in person</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-calendar"></i> Schedule Campus Tour
                </a>
                <a href="admissions.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-journal-plus"></i> Apply for Admission
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'School Facilities',
    'Explore our modern facilities and infrastructure designed to enhance learning and student development.',
    $content,
    null,
    $breadcrumbs
);
?>
