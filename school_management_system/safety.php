<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

$breadcrumbs = [
    ['title' => 'Safety Measures']
];

ob_start();
?>

<div class="container">
    <!-- Safety Overview -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Student Safety & Security</h2>
            <p class="section-subtitle">Your child's safety and wellbeing is our highest priority</p>
        </div>
        
        <div class="values-grid">
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-camera-video-fill"></i>
                </div>
                <h3 class="value-title">24/7 CCTV Surveillance</h3>
                <p class="value-description">Complete campus monitoring with high-definition cameras ensuring safety and security at all times.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h3 class="value-title">Controlled Access</h3>
                <p class="value-description">Restricted entry with security personnel and visitor management system to maintain a safe environment.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-heart-pulse-fill"></i>
                </div>
                <h3 class="value-title">Medical Support</h3>
                <p class="value-description">On-campus medical facility with qualified nursing staff and first aid equipment for emergencies.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-fire"></i>
                </div>
                <h3 class="value-title">Fire Safety Systems</h3>
                <p class="value-description">Complete fire safety infrastructure with detectors, extinguishers, and emergency evacuation procedures.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-bus-front-fill"></i>
                </div>
                <h3 class="value-title">Safe Transportation</h3>
                <p class="value-description">GPS-enabled school buses with trained drivers and attendants ensuring safe commute for students.</p>
            </div>
            
            <div class="value-card scroll-reveal">
                <div class="value-icon">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <h3 class="value-title">Background Checks</h3>
                <p class="value-description">Thorough background verification for all staff members to ensure a secure learning environment.</p>
            </div>
        </div>
    </section>

    <!-- Emergency Procedures -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Emergency Procedures</h2>
                <p class="section-subtitle">Well-defined protocols for various emergency situations</p>
            </div>
            
            <div class="content-grid">
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h3>Emergency Response Plan</h3>
                    <p>Comprehensive emergency response procedures for natural disasters, medical emergencies, and security threats with regular drills and training.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-telephone-fill"></i>
                    </div>
                    <h3>Emergency Contacts</h3>
                    <p>24/7 emergency helpline and immediate contact system with parents and emergency services for quick response to any situation.</p>
                </div>
                
                <div class="content-card">
                    <div class="value-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3>Trained Staff</h3>
                    <p>All staff members are trained in basic first aid, emergency procedures, and child safety protocols to handle various situations effectively.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Health & Wellbeing -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Health & Wellbeing</h2>
                <p class="section-subtitle">Comprehensive care for student physical and mental health</p>
            </div>
            
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="bi bi-heart-pulse"></i> Regular Health Check-ups</h3>
                    <p>Periodic health examinations by qualified medical professionals to monitor student health and early detection of any issues.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-droplet"></i> Hygiene Protocols</h3>
                    <p>Strict hygiene and sanitation protocols throughout the campus including regular cleaning and sanitization procedures.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-chat-heart"></i> Counseling Support</h3>
                    <p>Professional counseling services for students dealing with academic stress, personal issues, or emotional challenges.</p>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-shield-plus"></i> Anti-Bullying Policy</h3>
                    <p>Zero-tolerance policy against bullying with clear procedures for reporting and addressing any incidents of harassment.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Your Child's Safety is Our Promise</h2>
            <p class="cta-description">Trust us to provide a secure and nurturing environment for your child's growth</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-calendar"></i> Schedule Campus Visit
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
    'Safety Measures',
    'Learn about our comprehensive safety protocols and secure learning environment for student wellbeing.',
    $content,
    null,
    $breadcrumbs
);
?>
