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

// Get available classes
try {
    $stmt = $pdo->prepare("SELECT DISTINCT class_name FROM classes WHERE is_active = 1 ORDER BY class_name");
    $stmt->execute();
    $available_classes = $stmt->fetchAll();
} catch (Exception $e) {
    $available_classes = [];
}

$breadcrumbs = [
    ['title' => 'Admissions']
];

ob_start();
?>

<div class="container">
    <!-- Admission Overview -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Join Our School Community</h2>
            <p class="section-subtitle">Begin your child's journey towards academic excellence and character development</p>
        </div>
        
        <div class="content-grid">
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h3>Academic Year 2024-25</h3>
                <p>Admissions are now open for the academic year 2024-25. Don't miss this opportunity to be part of our educational excellence.</p>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-mortarboard"></i>
                </div>
                <h3>Classes Available</h3>
                <p>We offer admissions from Nursery to Class 12 with comprehensive curriculum and experienced faculty for each grade level.</p>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3>Limited Seats</h3>
                <p>We maintain small class sizes to ensure personalized attention and quality education for every student.</p>
            </div>
        </div>
    </section>

    <!-- Admission Process -->
    <section class="admission-process">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Admission Process</h2>
                <p class="section-subtitle">Simple steps to secure your child's future with us</p>
            </div>
            
            <div class="process-steps">
                <div class="process-step scroll-reveal">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Application Form</h3>
                    <p class="step-description">Fill out the online application form or visit our office to collect the admission form.</p>
                </div>
                
                <div class="process-step scroll-reveal">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Submit Documents</h3>
                    <p class="step-description">Submit required documents including birth certificate, previous school records, and photographs.</p>
                </div>
                
                <div class="process-step scroll-reveal">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Entrance Test</h3>
                    <p class="step-description">Attend the entrance test and interview (for grades 1 and above). Assessment focuses on age-appropriate skills.</p>
                </div>
                
                <div class="process-step scroll-reveal">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Merit List</h3>
                    <p class="step-description">Merit list will be published on our website and notice board. Selected candidates will be notified.</p>
                </div>
                
                <div class="process-step scroll-reveal">
                    <div class="step-number">5</div>
                    <h3 class="step-title">Fee Payment</h3>
                    <p class="step-description">Complete the admission by paying the required fees and collecting the admission confirmation.</p>
                </div>
                
                <div class="process-step scroll-reveal">
                    <div class="step-number">6</div>
                    <h3 class="step-title">Welcome!</h3>
                    <p class="step-description">Attend the orientation program and begin the exciting educational journey with us.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Required Documents -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Required Documents</h2>
                <p class="section-subtitle">Please prepare the following documents for the admission process</p>
            </div>
            
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="bi bi-file-earmark-text"></i> Academic Documents</h3>
                    <ul style="list-style: none; padding: 0; margin-top: 1rem;">
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Previous school leaving certificate (if applicable)</li>
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Transfer certificate from previous school</li>
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Previous academic records/report cards</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-person-vcard"></i> Identity Documents</h3>
                    <ul style="list-style: none; padding: 0; margin-top: 1rem;">
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Birth certificate (original & photocopy)</li>
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Aadhar card of student and parents</li>
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Passport size photographs (4 copies)</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-house"></i> Address Proof</h3>
                    <ul style="list-style: none; padding: 0; margin-top: 1rem;">
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Residential address proof</li>
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Parent's income certificate</li>
                        <li style="padding: 0.25rem 0;"><i class="bi bi-check-circle" style="color: var(--success-color); margin-right: 0.5rem;"></i> Caste certificate (if applicable)</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Available Classes -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Classes Available</h2>
                <p class="section-subtitle">Choose the right class for your child's educational journey</p>
            </div>
            
            <div class="content-grid">
                <?php if (!empty($available_classes)): ?>
                    <?php foreach ($available_classes as $class): ?>
                        <div class="info-card">
                            <h3><i class="bi bi-book"></i> <?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <p>Comprehensive curriculum designed for age-appropriate learning and development.</p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="info-card">
                        <h3><i class="bi bi-book"></i> Nursery to Class 12</h3>
                        <p>We offer education from foundational years through senior secondary with CBSE curriculum.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Important Dates -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Important Dates</h2>
                <p class="section-subtitle">Mark your calendar for admission-related activities</p>
            </div>
            
            <div class="content-grid">
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-calendar-plus"></i>
                    </div>
                    <h3>Application Start</h3>
                    <p>January 15, 2024</p>
                    <div class="achievement-year">Registration Opens</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h3>Last Date</h3>
                    <p>March 31, 2024</p>
                    <div class="achievement-year">Application Deadline</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <h3>Entrance Test</h3>
                    <p>April 15, 2024</p>
                    <div class="achievement-year">Assessment Day</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <h3>Result Declaration</h3>
                    <p>April 25, 2024</p>
                    <div class="achievement-year">Merit List</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Ready to Apply?</h2>
            <p class="cta-description">Don't wait! Secure your child's future with quality education</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-download"></i> Download Application Form
                </a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-telephone"></i> Call for Information
                </a>
            </div>
        </div>
    </section>
</div>

<?php
$content = ob_get_clean();
renderPublicPage(
    'Admissions',
    'Join our school community. Learn about our admission process, requirements, and important dates.',
    $content,
    null,
    $breadcrumbs
);
?>
