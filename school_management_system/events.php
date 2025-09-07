<?php
require_once 'config/database.php';
require_once 'includes/public_layout.php';

// Get upcoming events
try {
    $stmt = $pdo->prepare("
        SELECT * FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC, start_time ASC
    ");
    $stmt->execute();
    $upcoming_events = $stmt->fetchAll();
} catch (Exception $e) {
    $upcoming_events = [];
}

// Get past events
try {
    $stmt = $pdo->prepare("
        SELECT * FROM events 
        WHERE event_date < CURDATE() 
        ORDER BY event_date DESC 
        LIMIT 6
    ");
    $stmt->execute();
    $past_events = $stmt->fetchAll();
} catch (Exception $e) {
    $past_events = [];
}

$breadcrumbs = [
    ['title' => 'Events']
];

ob_start();
?>

<div class="container">
    <!-- Upcoming Events -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Upcoming Events</h2>
            <p class="section-subtitle">Mark your calendar for these exciting school events and activities</p>
        </div>
        
        <div class="events-grid">
            <?php if (!empty($upcoming_events)): ?>
                <?php foreach ($upcoming_events as $event): ?>
                    <div class="event-card scroll-reveal">
                        <div class="facility-placeholder">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="event-content">
                            <div class="event-meta">
                                <span class="event-date"><?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                <span class="event-category"><?php echo ucfirst(htmlspecialchars($event['category'])); ?></span>
                            </div>
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                            <div class="event-details">
                                <?php if ($event['start_time']): ?>
                                    <p><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                                    <?php if ($event['end_time']): ?>
                                        - <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                                    <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($event['location']): ?>
                                    <p><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Sample upcoming events -->
                <div class="event-card scroll-reveal">
                    <div class="facility-placeholder">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="event-content">
                        <div class="event-meta">
                            <span class="event-date">Sep 15, 2024</span>
                            <span class="event-category">Academic</span>
                        </div>
                        <h3 class="event-title">Parent-Teacher Meeting</h3>
                        <p class="event-description">Quarterly parent-teacher meeting to discuss student progress and academic development.</p>
                        <div class="event-details">
                            <p><i class="bi bi-clock"></i> 10:00 AM - 2:00 PM</p>
                            <p><i class="bi bi-geo-alt"></i> School Auditorium</p>
                        </div>
                    </div>
                </div>
                
                <div class="event-card scroll-reveal">
                    <div class="facility-placeholder">
                        <i class="bi bi-palette"></i>
                    </div>
                    <div class="event-content">
                        <div class="event-meta">
                            <span class="event-date">Oct 20, 2024</span>
                            <span class="event-category">Cultural</span>
                        </div>
                        <h3 class="event-title">Cultural Festival</h3>
                        <p class="event-description">Annual cultural festival showcasing student talents in music, dance, drama, and arts.</p>
                        <div class="event-details">
                            <p><i class="bi bi-clock"></i> 9:00 AM - 5:00 PM</p>
                            <p><i class="bi bi-geo-alt"></i> School Grounds</p>
                        </div>
                    </div>
                </div>
                
                <div class="event-card scroll-reveal">
                    <div class="facility-placeholder">
                        <i class="bi bi-flask"></i>
                    </div>
                    <div class="event-content">
                        <div class="event-meta">
                            <span class="event-date">Nov 10, 2024</span>
                            <span class="event-category">Academic</span>
                        </div>
                        <h3 class="event-title">Science Fair</h3>
                        <p class="event-description">Students will present their science projects and innovations to judges and visitors.</p>
                        <div class="event-details">
                            <p><i class="bi bi-clock"></i> 10:30 AM - 4:00 PM</p>
                            <p><i class="bi bi-geo-alt"></i> Science Laboratory</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Event Categories -->
    <section class="content-section" style="background: #f8fafc;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Event Categories</h2>
                <p class="section-subtitle">Different types of events that happen throughout the academic year</p>
            </div>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="bi bi-book"></i>
                    </div>
                    <h3 class="value-title">Academic Events</h3>
                    <p class="value-description">Examinations, parent-teacher meetings, science fairs, academic competitions, and educational workshops.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="bi bi-palette"></i>
                    </div>
                    <h3 class="value-title">Cultural Events</h3>
                    <p class="value-description">Cultural festivals, art exhibitions, music concerts, drama performances, and talent shows.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="bi bi-dribbble"></i>
                    </div>
                    <h3 class="value-title">Sports Events</h3>
                    <p class="value-description">Sports day, inter-house competitions, athletic meets, and various sports tournaments.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="value-title">Community Events</h3>
                    <p class="value-description">School picnics, community service activities, alumni meets, and social awareness programs.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="bi bi-calendar-heart"></i>
                    </div>
                    <h3 class="value-title">Special Occasions</h3>
                    <p class="value-description">Independence Day, Republic Day, Teachers' Day, Children's Day, and other national celebrations.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <h3 class="value-title">Career Events</h3>
                    <p class="value-description">Career guidance sessions, college fairs, skill development workshops, and industry interactions.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Past Events (if any) -->
    <?php if (!empty($past_events)): ?>
    <section class="content-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Recent Events</h2>
                <p class="section-subtitle">Take a look at some of our recent successful events and celebrations</p>
            </div>
            
            <div class="events-grid">
                <?php foreach ($past_events as $event): ?>
                    <div class="event-card scroll-reveal">
                        <div class="facility-placeholder">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="event-content">
                            <div class="event-meta">
                                <span class="event-date"><?php echo date('M j, Y', strtotime($event['event_date'])); ?></span>
                                <span class="event-category"><?php echo ucfirst(htmlspecialchars($event['category'])); ?></span>
                            </div>
                            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                            <div class="event-details">
                                <?php if ($event['location']): ?>
                                    <p><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Don't Miss Our Events</h2>
            <p class="cta-description">Stay connected with our school community and never miss an important event</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="contact.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-bell"></i> Event Notifications
                </a>
                <a href="gallery.php" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-images"></i> Event Gallery
                </a>
            </div>
        </div>
    </section>
</div>

<style>
.event-details {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.event-details p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.event-details i {
    color: var(--primary-color);
}
</style>

<?php
$content = ob_get_clean();
renderPublicPage(
    'School Events',
    'View upcoming events, celebrations, and important dates in our school calendar.',
    $content,
    null,
    $breadcrumbs
);
?>
