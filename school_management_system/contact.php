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

// Check for contact form response
$contact_response = null;
if (isset($_SESSION['contact_response'])) {
    $contact_response = $_SESSION['contact_response'];
    unset($_SESSION['contact_response']);
}

$breadcrumbs = [
    ['title' => 'Contact Us']
];

ob_start();
?>

<div class="container">
    <!-- Contact Information -->
    <section class="content-section">
        <?php if ($contact_response): ?>
            <div class="alert <?php echo $contact_response['success'] ? 'alert-success' : 'alert-danger'; ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--border-radius); text-align: center;">
                <?php echo htmlspecialchars($contact_response['message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="section-header">
            <h2 class="section-title">Get In Touch</h2>
            <p class="section-subtitle">We'd love to hear from you. Contact us for admissions, inquiries, or any assistance you may need</p>
        </div>
        
        <?php if ($school_info): ?>
        <div class="content-grid">
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>
                <h3>Visit Us</h3>
                <p><?php echo htmlspecialchars($school_info['address']); ?></p>
                <div style="margin-top: 1rem;">
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-map"></i> Get Directions
                    </a>
                </div>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-telephone-fill"></i>
                </div>
                <h3>Call Us</h3>
                <p><a href="tel:<?php echo htmlspecialchars($school_info['phone']); ?>"><?php echo htmlspecialchars($school_info['phone']); ?></a></p>
                <?php if (!empty($school_info['mobile'])): ?>
                    <p><a href="tel:<?php echo htmlspecialchars($school_info['mobile']); ?>"><?php echo htmlspecialchars($school_info['mobile']); ?></a></p>
                <?php endif; ?>
                <div style="margin-top: 1rem;">
                    <small style="color: var(--text-secondary);">Office Hours: Mon-Sat, 9:00 AM - 5:00 PM</small>
                </div>
            </div>
            
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-envelope-fill"></i>
                </div>
                <h3>Email Us</h3>
                <p><a href="mailto:<?php echo htmlspecialchars($school_info['email']); ?>"><?php echo htmlspecialchars($school_info['email']); ?></a></p>
                <div style="margin-top: 1rem;">
                    <small style="color: var(--text-secondary);">We typically respond within 24 hours</small>
                </div>
            </div>
            
            <?php if (!empty($school_info['website'])): ?>
            <div class="content-card">
                <div class="value-icon">
                    <i class="bi bi-globe"></i>
                </div>
                <h3>Website</h3>
                <p><a href="<?php echo htmlspecialchars($school_info['website']); ?>" target="_blank"><?php echo htmlspecialchars($school_info['website']); ?></a></p>
                <div style="margin-top: 1rem;">
                    <small style="color: var(--text-secondary);">Follow us for updates and announcements</small>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Contact Form -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Send Us a Message</h2>
                <p class="section-subtitle">Have questions or need more information? We're here to help</p>
            </div>
            
            <div class="contact-form-container">
                <form action="contact-submit.php" method="POST" class="contact-form-grid" id="contactForm">
                    <div class="form-group">
                        <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Full Name *</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" placeholder="Enter your phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Subject *</label>
                        <select name="subject" id="subject" class="form-control" required>
                            <option value="">Select a subject</option>
                            <option value="admissions">Admissions Inquiry</option>
                            <option value="general">General Information</option>
                            <option value="academic">Academic Programs</option>
                            <option value="facilities">Facilities & Infrastructure</option>
                            <option value="fees">Fee Structure</option>
                            <option value="transport">Transportation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="message" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Message *</label>
                        <textarea name="message" id="message" class="form-control" rows="6" placeholder="Enter your message or inquiry" required></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Quick Contact Options -->
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Quick Contact Options</h2>
                <p class="section-subtitle">Choose the best way to reach us based on your needs</p>
            </div>
            
            <div class="content-grid">
                <div class="info-card">
                    <h3><i class="bi bi-journal-plus"></i> Admissions Inquiry</h3>
                    <p>For admission-related questions, application procedures, and fee structure information.</p>
                    <div style="margin-top: 1rem;">
                        <a href="admissions.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-right"></i> Admissions Page
                        </a>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-telephone"></i> Emergency Contact</h3>
                    <p>For urgent matters or emergencies during school hours.</p>
                    <div style="margin-top: 1rem;">
                        <?php if ($school_info): ?>
                            <a href="tel:<?php echo htmlspecialchars($school_info['phone']); ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-telephone"></i> Call Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="bi bi-calendar-check"></i> Schedule Visit</h3>
                    <p>Book an appointment for campus tour or meeting with our academic counselors.</p>
                    <div style="margin-top: 1rem;">
                        <a href="#contactForm" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-calendar"></i> Request Visit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Office Hours -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Office Hours</h2>
                <p class="section-subtitle">Visit us during these hours for in-person assistance</p>
            </div>
            
            <div class="content-grid">
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3>Monday - Friday</h3>
                    <p>9:00 AM - 5:00 PM</p>
                    <div class="achievement-year">Regular Office Hours</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h3>Saturday</h3>
                    <p>9:00 AM - 2:00 PM</p>
                    <div class="achievement-year">Limited Hours</div>
                </div>
                
                <div class="achievement-card">
                    <div class="achievement-icon">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <h3>Sunday</h3>
                    <p>Closed</p>
                    <div class="achievement-year">Weekly Holiday</div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>

<script>
// Enhanced form validation and submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Show loading overlay
            const loadingOverlay = document.getElementById('loading-overlay');
            if (loadingOverlay) {
                loadingOverlay.classList.add('show');
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
renderPublicPage(
    'Contact Us',
    'Get in touch with us for admissions, inquiries, or any assistance you may need. We\'re here to help.',
    $content,
    null,
    $breadcrumbs
);
?>
