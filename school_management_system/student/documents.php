<?php
require_once '../config/database.php';

// Check if user is student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student information
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, c.section 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    WHERE s.user_id = ? AND s.is_active = 1
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    $error = 'Student profile not found. Please contact administrator.';
} else {
    // Check if documents table exists, if not create basic file list
    $documents = [];
    
    // Check for common document files in uploads folder
    $document_types = [
        'Admission Form' => ['admission_form', 'admission'],
        'Birth Certificate' => ['birth_certificate', 'birth_cert'],
        'Aadhar Card' => ['aadhar', 'aadhar_card'],
        'Samagra ID' => ['samagra', 'samagra_id'],
        'Caste Certificate' => ['caste_certificate', 'caste_cert'],
        'Income Certificate' => ['income_certificate', 'income_cert'],
        'TC/LC' => ['tc', 'lc', 'transfer_certificate'],
        'Mark Sheets' => ['marksheet', 'mark_sheet', 'marks'],
        'Medical Certificate' => ['medical', 'medical_certificate'],
        'Other Documents' => ['other', 'misc']
    ];
    
    // Scan documents folder for student files
    $upload_path = '../uploads/documents/';
    $student_files = [];
    
    if (is_dir($upload_path)) {
        $files = glob($upload_path . '*' . $student['admission_no'] . '*');
        foreach ($files as $file) {
            $filename = basename($file);
            $file_info = [
                'filename' => $filename,
                'path' => $file,
                'size' => filesize($file),
                'date' => filemtime($file),
                'type' => 'Unknown'
            ];
            
            // Determine document type based on filename
            foreach ($document_types as $type => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($filename, $keyword) !== false) {
                        $file_info['type'] = $type;
                        break 2;
                    }
                }
            }
            
            $student_files[] = $file_info;
        }
    }
}

function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unit = 0;
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    return round($size, 2) . ' ' . $units[$unit];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents - Student Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include '../includes/header.php'; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header">
                    <h1 class="page-title">📄 My Documents</h1>
                    <p class="page-subtitle">View your uploaded documents and certificates</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php else: ?>
                    <!-- Student Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Student Information</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                            <div><strong>Admission No:</strong> <?php echo htmlspecialchars($student['admission_no']); ?></div>
                            <div><strong>Class:</strong> <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?></div>
                        </div>
                    </div>

                    <!-- Documents List -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📁 Uploaded Documents</h3>
                            <div class="card-actions">
                                <span class="text-muted"><?php echo count($student_files); ?> document(s) found</span>
                            </div>
                        </div>

                        <?php if (empty($student_files)): ?>
                            <div class="alert alert-info">
                                <h4>No Documents Found</h4>
                                <p>No documents have been uploaded for your profile yet. Please contact the school administration if you need to submit documents.</p>
                                
                                <div style="margin-top: 1rem;">
                                    <strong>Required Documents:</strong>
                                    <ul style="margin-top: 0.5rem;">
                                        <li>Birth Certificate</li>
                                        <li>Aadhar Card</li>
                                        <li>Previous School Transfer Certificate</li>
                                        <li>Previous Year Mark Sheet</li>
                                        <li>Caste/Income Certificate (if applicable)</li>
                                        <li>Passport Size Photos</li>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="documents-grid">
                                <?php foreach ($student_files as $file): ?>
                                    <div class="document-card">
                                        <div class="document-icon">
                                            <?php
                                            $extension = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
                                            switch($extension) {
                                                case 'pdf':
                                                    echo '📄';
                                                    break;
                                                case 'jpg':
                                                case 'jpeg':
                                                case 'png':
                                                case 'gif':
                                                    echo '🖼️';
                                                    break;
                                                case 'doc':
                                                case 'docx':
                                                    echo '📝';
                                                    break;
                                                default:
                                                    echo '📎';
                                            }
                                            ?>
                                        </div>
                                        
                                        <div class="document-info">
                                            <h4 class="document-title"><?php echo htmlspecialchars($file['type']); ?></h4>
                                            <div class="document-filename"><?php echo htmlspecialchars($file['filename']); ?></div>
                                            <div class="document-meta">
                                                <span>Size: <?php echo formatFileSize($file['size']); ?></span>
                                                <span>Uploaded: <?php echo date('d/m/Y', $file['date']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="document-actions">
                                            <a href="<?php echo '../uploads/documents/' . basename($file['path']); ?>" 
                                               target="_blank" class="btn btn-outline btn-sm">
                                                👁️ View
                                            </a>
                                            <a href="<?php echo '../uploads/documents/' . basename($file['path']); ?>" 
                                               download class="btn btn-primary btn-sm">
                                                💾 Download
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Document Status -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📋 Document Checklist</h3>
                        </div>
                        
                        <?php
                        $required_docs = [
                            'Birth Certificate' => false,
                            'Aadhar Card' => false,
                            'TC/LC' => false,
                            'Mark Sheets' => false,
                            'Caste Certificate' => false,
                            'Income Certificate' => false
                        ];
                        
                        // Mark available documents
                        foreach ($student_files as $file) {
                            if (isset($required_docs[$file['type']])) {
                                $required_docs[$file['type']] = true;
                            }
                        }
                        ?>
                        
                        <div class="checklist">
                            <?php foreach ($required_docs as $doc_type => $available): ?>
                                <div class="checklist-item">
                                    <span class="checklist-icon <?php echo $available ? 'available' : 'missing'; ?>">
                                        <?php echo $available ? '✅' : '❌'; ?>
                                    </span>
                                    <span class="checklist-text"><?php echo $doc_type; ?></span>
                                    <span class="checklist-status <?php echo $available ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $available ? 'Available' : 'Missing'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <strong>Note:</strong> If any required documents are missing, please contact the school administration. 
                            Students cannot upload documents directly through this portal.
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📞 Need Help with Documents?</h3>
                        </div>
                        <div class="alert alert-info">
                            <h5>Contact School Administration:</h5>
                            <ul style="margin-bottom: 0;">
                                <li><strong>Office Hours:</strong> Monday to Friday, 9:00 AM - 4:00 PM</li>
                                <li><strong>For Document Submission:</strong> Visit the main office with original documents</li>
                                <li><strong>For Document Queries:</strong> Contact your class teacher or admin office</li>
                                <li><strong>Emergency Documents:</strong> Medical certificates can be submitted within 3 days of absence</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <style>
    .documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .document-card {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .document-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }

    .document-icon {
        font-size: 2.5rem;
        min-width: 60px;
        text-align: center;
    }

    .document-info {
        flex: 1;
    }

    .document-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .document-filename {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
        word-break: break-all;
    }

    .document-meta {
        font-size: 0.8rem;
        color: var(--text-muted);
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .document-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .checklist {
        display: grid;
        gap: 1rem;
    }

    .checklist-item {
        display: grid;
        grid-template-columns: 30px 1fr auto;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--light-color);
        border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
    }

    .checklist-icon.available {
        color: #28a745;
    }

    .checklist-icon.missing {
        color: #dc3545;
    }

    .checklist-text {
        font-weight: 600;
    }

    .checklist-status {
        font-size: 0.9rem;
        font-weight: 600;
    }

    .text-success { color: #28a745; }
    .text-danger { color: #dc3545; }

    @media (max-width: 768px) {
        .documents-grid {
            grid-template-columns: 1fr;
        }
        
        .document-card {
            flex-direction: column;
            text-align: center;
        }
        
        .document-actions {
            flex-direction: row;
            justify-content: center;
            width: 100%;
        }
        
        .checklist-item {
            grid-template-columns: 30px 1fr;
            grid-template-rows: auto auto;
        }
        
        .checklist-status {
            grid-column: 2;
            justify-self: start;
            margin-top: 0.25rem;
        }
    }
    </style>
</body>
</html>
