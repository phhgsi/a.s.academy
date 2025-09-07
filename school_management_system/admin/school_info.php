<?php
require_once '../config/database.php';
require_once '../includes/school_logo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_school_info'])) {
    // Handle logo upload
    $logo_update = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "../uploads/";
        $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo = 'school_logo.' . $file_extension;
        $target_file = $target_dir . $logo;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
            $logo_update = ", logo = '$logo'";
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE school_info SET 
                school_name = ?, school_code = ?, address = ?, phone = ?, 
                email = ?, website = ?, principal_name = ?, established_year = ?, 
                affiliation = ?, board = ?, description = ?
                $logo_update
            WHERE id = 1
        ");
        
        $stmt->execute([
            $_POST['school_name'], $_POST['school_code'], $_POST['address'], 
            $_POST['phone'], $_POST['email'], $_POST['website'], 
            $_POST['principal_name'], $_POST['established_year'], 
            $_POST['affiliation'], $_POST['board'], $_POST['description']
        ]);
        
        // Clear school logo cache to ensure updates are reflected immediately
        clearSchoolLogoCache();
        $_SESSION['school_info_updated'] = true;
        
        $message = 'School information updated successfully!';
    } catch (Exception $e) {
        $message = 'Error updating school information: ' . $e->getMessage();
    }
}

// Get current school information
$stmt = $pdo->prepare("SELECT * FROM school_info WHERE id = 1");
$stmt->execute();
$school = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Information - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">School Information</h1>
                    <p class="page-subtitle">Manage school details and settings</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <h3 style="margin-bottom: 2rem; color: var(--primary-color);">🏢 School Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">School Name *</label>
                                <input type="text" name="school_name" class="form-input" required
                                       value="<?php echo htmlspecialchars($school['school_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">School Code</label>
                                <input type="text" name="school_code" class="form-input"
                                       value="<?php echo htmlspecialchars($school['school_code'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-input" required
                                       value="<?php echo htmlspecialchars($school['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-input" required
                                       value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-input"
                                       value="<?php echo htmlspecialchars($school['website'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Principal Name *</label>
                                <input type="text" name="principal_name" class="form-input" required
                                       value="<?php echo htmlspecialchars($school['principal_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Established Year</label>
                                <input type="number" name="established_year" class="form-input" min="1900" max="<?php echo date('Y'); ?>"
                                       value="<?php echo htmlspecialchars($school['established_year'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Affiliation</label>
                                <input type="text" name="affiliation" class="form-input"
                                       value="<?php echo htmlspecialchars($school['affiliation'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Board</label>
                                <select name="board" class="form-select">
                                    <option value="">Select Board</option>
                                    <option value="CBSE" <?php echo (isset($school) && $school['board'] == 'CBSE') ? 'selected' : ''; ?>>CBSE</option>
                                    <option value="ICSE" <?php echo (isset($school) && $school['board'] == 'ICSE') ? 'selected' : ''; ?>>ICSE</option>
                                    <option value="State Board" <?php echo (isset($school) && $school['board'] == 'State Board') ? 'selected' : ''; ?>>State Board</option>
                                    <option value="IB" <?php echo (isset($school) && $school['board'] == 'IB') ? 'selected' : ''; ?>>IB</option>
                                    <option value="Other" <?php echo (isset($school) && $school['board'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">Address *</label>
                                <textarea name="address" class="form-textarea" required><?php echo htmlspecialchars($school['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">School Description</label>
                                <textarea name="description" class="form-textarea" rows="4"><?php echo htmlspecialchars($school['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label class="form-label">School Logo</label>
                                <div class="file-upload">
                                    <input type="file" name="logo" accept="image/*" class="file-upload-input">
                                    <label class="file-upload-label">
                                        🖼️ Click to upload school logo
                                    </label>
                                </div>
                                <?php if (isset($school) && $school['logo']): ?>
                                    <div style="margin-top: 1rem;">
                                        <p><strong>Current Logo:</strong></p>
                                        <img src="../uploads/<?php echo $school['logo']; ?>" 
                                             alt="Current Logo" style="max-width: 200px; border: 1px solid var(--border-color); border-radius: var(--border-radius);">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="update_school_info" class="btn btn-primary">💾 Update School Information</button>
                        </div>
                    </form>
                </div>

                <!-- School Information Preview -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Current School Information</h3>
                        <button class="btn btn-outline" onclick="printSection('schoolPreview')">🖨️ Print Info</button>
                    </div>
                    
                    <div id="schoolPreview">
                        <?php if ($school): ?>
                            <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem;">
                                <div class="text-center">
                                    <?php if ($school['logo']): ?>
                                        <img src="../uploads/<?php echo $school['logo']; ?>" 
                                             alt="School Logo" style="max-width: 180px; border-radius: var(--border-radius);">
                                    <?php else: ?>
                                        <div style="width: 180px; height: 180px; background: var(--primary-color); border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 600;">
                                            <?php echo strtoupper(substr($school['school_name'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <h2 style="color: var(--primary-color); margin-bottom: 1rem;"><?php echo htmlspecialchars($school['school_name']); ?></h2>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                                        <div><strong>School Code:</strong> <?php echo htmlspecialchars($school['school_code'] ?: 'Not set'); ?></div>
                                        <div><strong>Principal:</strong> <?php echo htmlspecialchars($school['principal_name']); ?></div>
                                        <div><strong>Established:</strong> <?php echo htmlspecialchars($school['established_year'] ?: 'Not set'); ?></div>
                                        <div><strong>Board:</strong> <?php echo htmlspecialchars($school['board'] ?: 'Not set'); ?></div>
                                        <div><strong>Affiliation:</strong> <?php echo htmlspecialchars($school['affiliation'] ?: 'Not set'); ?></div>
                                        <div><strong>Phone:</strong> <?php echo htmlspecialchars($school['phone']); ?></div>
                                        <div><strong>Email:</strong> <?php echo htmlspecialchars($school['email']); ?></div>
                                        <?php if ($school['website']): ?>
                                            <div><strong>Website:</strong> <a href="<?php echo htmlspecialchars($school['website']); ?>" target="_blank"><?php echo htmlspecialchars($school['website']); ?></a></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($school['address']): ?>
                                        <div style="margin-top: 1.5rem;">
                                            <strong>Address:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($school['address'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($school['description']): ?>
                                        <div style="margin-top: 1.5rem;">
                                            <strong>About School:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($school['description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No school information available. Please add school details.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    
    <script>
        // Auto-refresh logo if school info was updated
        <?php if (isset($_SESSION['school_info_updated']) && $_SESSION['school_info_updated']): ?>
            // Clear the flag
            <?php unset($_SESSION['school_info_updated']); ?>
            
            // Trigger logo refresh across the page
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        <?php endif; ?>
        
        // Initialize print functionality
        function setupPrintFunctionality() {
            // Add print button handlers
            const printBtns = document.querySelectorAll('[onclick*="printSection"]');
            printBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sectionId = this.getAttribute('onclick').match(/printSection\('(.+?)'\)/)[1];
                    printSection(sectionId);
                });
            });
        }
        
        document.addEventListener('DOMContentLoaded', setupPrintFunctionality);
    </script>
</body>
</html>
