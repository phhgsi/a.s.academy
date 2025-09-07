<?php
/**
 * Students List - Admin Panel
 * Modern student listing with search, filter, pagination and export features
 */

require_once '../includes/simple_db.php';
require_once '../includes/photo_handler_enhanced.php';
check_admin();

$message = '';
$errors = [];

// Handle flash messages from session
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = $_SESSION['success_type'] ?? 'success';
    unset($_SESSION['success_message']);
    unset($_SESSION['success_type']);
}

// Get filter parameters
$search = trim($_GET['q'] ?? '');
$filter_class_id = trim($_GET['class_id'] ?? '');
$filter_academic_year = trim($_GET['academic_year'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE conditions for filtering
$where_conditions = ["s.is_active = 1"];
$bind_params = [];
$bind_types = "";

if (!empty($search)) {
    $where_conditions[] = "(s.admission_no LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.father_name LIKE ? OR s.mother_name LIKE ?)";
    $search_param = "%$search%";
    $bind_params = array_merge($bind_params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $bind_types .= "sssss";
}

if (!empty($filter_class_id)) {
    $where_conditions[] = "s.class_id = ?";
    $bind_params[] = $filter_class_id;
    $bind_types .= "i";
}

if (!empty($filter_academic_year)) {
    $where_conditions[] = "s.academic_year = ?";
    $bind_params[] = $filter_academic_year;
    $bind_types .= "s";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total 
    FROM students s 
    LEFT JOIN classes c ON s.class_id = c.id 
    $where_clause
";

$count_stmt = $conn->prepare($count_query);
if (!empty($bind_params)) {
    $count_stmt->bind_param($bind_types, ...$bind_params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);
$count_stmt->close();

// Get students with pagination
$students_query = "
    SELECT 
        s.id,
        s.admission_no,
        s.first_name,
        s.last_name,
        s.father_name,
        s.mother_name,
        s.date_of_birth,
        s.age,
        s.gender,
        s.mobile_no,
        s.parent_mobile,
        s.email,
        s.village,
        s.academic_year,
        s.admission_date,
        s.photo,
        c.class_name,
        c.section
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id 
    $where_clause 
    ORDER BY c.class_name ASC, s.admission_no ASC
    LIMIT ?, ?
";

$students_stmt = $conn->prepare($students_query);
$pagination_params = array_merge($bind_params, [$offset, $per_page]);
$pagination_types = $bind_types . "ii";
if (!empty($pagination_params)) {
    $students_stmt->bind_param($pagination_types, ...$pagination_params);
}
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}
$students_stmt->close();

// Get classes for filter dropdown
$classes_result = $conn->query("SELECT id, class_name, section FROM classes WHERE is_active = 1 ORDER BY class_name");
$classes = [];
if ($classes_result) {
    while ($row = $classes_result->fetch_assoc()) {
        $classes[] = $row;
    }
}

// Get summary statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_students,
        COUNT(CASE WHEN s.academic_year = ? THEN 1 END) as current_year_students
    FROM students s 
    WHERE s.is_active = 1
";

$current_academic_year = date('Y') . '-' . (date('Y') + 1);
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("s", $current_academic_year);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="../assets/js/sidebar.js" defer></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php if (file_exists('../includes/header.php')): ?>
                <?php include '../includes/header.php'; ?>
            <?php endif; ?>
            
            <div class="content-wrapper fade-in">
                <div class="page-header d-flex justify-between align-center">
                    <div>
                        <h1 class="page-title"><i class="bi bi-people-fill"></i> Students Management</h1>
                        <p class="page-subtitle">View and manage all students in the system</p>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="students_add.php" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Add Student</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert <?php echo (isset($message_type) && $message_type === 'error') ? 'alert-danger' : 'alert-success'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong>Errors:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Summary Statistics -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); margin-bottom: 2rem;">
                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Total Students</div>
                            <div class="stat-icon" style="background: var(--primary-color);"><i class="bi bi-people-fill"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_students']); ?></div>
                        <div class="stat-change">Active students</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header d-flex justify-between align-center">
                            <div class="stat-title">Current Year</div>
                            <div class="stat-icon" style="background: var(--success-color);"><i class="bi bi-mortarboard-fill"></i></div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['current_year_students']); ?></div>
                        <div class="stat-change">Academic year <?php echo $current_academic_year; ?></div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card" style="margin-bottom: 1.5rem;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="bi bi-search"></i> Search & Filter</h3>
                        <button type="button" class="btn btn-outline" onclick="resetFilters()"><i class="bi bi-arrow-clockwise"></i> Reset</button>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="filter-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                            <div class="form-group">
                                <label class="form-label">Search Students</label>
                                <input type="text" name="q" class="form-input" 
                                       placeholder="Search name, admission # or father name"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Class</label>
                                <select name="class_id" class="form-select">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo ($filter_class_id == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name'] . ' ' . $class['section']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Academic Year</label>
                                <select name="academic_year" class="form-select">
                                    <option value="">All Years</option>
                                    <?php 
                                    // Generate academic year options
                                    $current_year = date('Y');
                                    for ($i = -2; $i <= 2; $i++) {
                                        $year = $current_year + $i;
                                        $academic_year = $year . '-' . ($year + 1);
                                        $selected = ($filter_academic_year === $academic_year) ? 'selected' : '';
                                        echo '<option value="' . $academic_year . '" ' . $selected . '>' . $academic_year . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filter</button>
                                <a href="?" class="btn btn-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Students List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            All Students 
                            <?php if (!empty($search) || !empty($filter_class_id) || !empty($filter_academic_year)): ?>
                                <span class="badge badge-info"><?php echo $total_records; ?> filtered results</span>
                            <?php endif; ?>
                        </h3>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline" onclick="exportToCSV('studentsTable', 'students_list')"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
                            <button class="btn btn-outline" onclick="printReport()"><i class="bi bi-printer"></i> Print</button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table data-table" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Father's Name</th>
                                    <th>Class</th>
                                    <th>Academic Year</th>
                                    <th>Contact</th>
                                    <th>Village</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <?php if (!empty($search) || !empty($filter_class_id) || !empty($filter_academic_year)): ?>
                                                No students found matching your filters. <a href="?">Show all students</a>
                                            <?php else: ?>
                                                No students found. <a href="students_add.php">Add the first student</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $photo_handler = new PhotoHandler();
                                    foreach ($students as $student): 
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="student-photo-cell">
                                                    <?php if (!empty($student['photo'])): ?>
                                                        <img src="<?php echo $photo_handler->getPhotoUrl($student['photo'], true); ?>" 
                                                             alt="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>" 
                                                             class="student-photo-thumb" 
                                                             style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef;" 
                                                             onerror="this.src='assets/images/student-placeholder-thumb.svg'">
                                                    <?php else: ?>
                                                        <div class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--primary-color); color: white; font-weight: bold;">
                                                            <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-outline"><?php echo htmlspecialchars($student['admission_no']); ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                                    <?php if ($student['age']): ?>
                                                        <br><small class="text-muted"><i class="bi bi-calendar-date"></i> <?php echo $student['age']; ?> years old</small>
                                                    <?php endif; ?>
                                                    <?php if ($student['gender']): ?>
                                                        <br><small class="text-muted">
                                                            <i class="bi bi-<?php echo $student['gender'] === 'male' ? 'person' : ($student['gender'] === 'female' ? 'person-dress' : 'person-question'); ?>"></i> 
                                                            <?php echo ucfirst($student['gender']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($student['father_name']); ?></td>
                                            <td>
                                                <?php if ($student['class_name']): ?>
                                                    <span class="badge badge-secondary">
                                                        <?php echo htmlspecialchars($student['class_name'] . ' ' . $student['section']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($student['academic_year']); ?></span>
                                            </td>
                                            <td>
                                                <div class="contact-info">
                                                    <?php if ($student['mobile_no']): ?>
                                                        <div>
                                                            <i class="bi bi-telephone"></i>
                                                            <a href="tel:<?php echo $student['mobile_no']; ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($student['mobile_no']); ?>
                                                            </a>
                                                            <small class="text-muted"> (Student)</small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($student['parent_mobile']): ?>
                                                        <div>
                                                            <i class="bi bi-phone"></i>
                                                            <a href="tel:<?php echo $student['parent_mobile']; ?>" class="text-decoration-none">
                                                                <?php echo htmlspecialchars($student['parent_mobile']); ?>
                                                            </a>
                                                            <small class="text-muted"> (Parent)</small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!$student['mobile_no'] && !$student['parent_mobile']): ?>
                                                        <span class="text-muted">No contact info</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($student['village']); ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="students_view.php?id=<?php echo $student['id']; ?>" 
                                                       class="btn btn-outline btn-sm" 
                                                       title="View Student">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="students_add.php?id=<?php echo $student['id']; ?>" 
                                                       class="btn btn-outline btn-sm" 
                                                       title="Edit Student">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="print_student.php?id=<?php echo $student['id']; ?>" 
                                                       class="btn btn-outline btn-sm" 
                                                       title="Print Student Card" target="_blank">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <div class="pagination-info">
                                Showing <?php echo number_format(($page - 1) * $per_page + 1); ?> to 
                                <?php echo number_format(min($page * $per_page, $total_records)); ?> 
                                of <?php echo number_format($total_records); ?> students
                            </div>
                            
                            <div class="pagination">
                                <?php
                                // Build query string for pagination links
                                $query_params = [];
                                if (!empty($search)) $query_params['q'] = $search;
                                if (!empty($filter_class_id)) $query_params['class_id'] = $filter_class_id;
                                if (!empty($filter_academic_year)) $query_params['academic_year'] = $filter_academic_year;
                                $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
                                ?>
                                
                                <?php if ($page > 1): ?>
                                    <a href="?page=1<?php echo $query_string; ?>" class="pagination-btn">First</a>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="pagination-btn">Previous</a>
                                <?php endif; ?>

                                <?php
                                // Show page numbers
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" 
                                       class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="pagination-btn">Next</a>
                                    <a href="?page=<?php echo $total_pages; ?><?php echo $query_string; ?>" class="pagination-btn">Last</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/modern-ui.js"></script>

    <script>
        function exportToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let row of rows) {
                const cols = row.querySelectorAll('td, th');
                const csvRow = [];
                for (let col of cols) {
                    // Skip the Actions column (last column)
                    if (col.cellIndex === cols.length - 1) continue;
                    
                    // Clean up text content
                    let text = col.textContent.trim();
                    text = text.replace(/\s+/g, ' '); // Replace multiple spaces with single space
                    csvRow.push('"' + text.replace(/"/g, '""') + '"');
                }
                if (csvRow.length > 0) {
                    csv.push(csvRow.join(','));
                }
            }
            
            const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename + '_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        function printReport() {
            window.print();
        }
        
        function resetFilters() {
            window.location.href = '?';
        }

        // Enhanced search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.form.submit();
                    }
                });
            }

            // Auto-submit filter form after selection change (optional)
            const filterSelects = document.querySelectorAll('.filter-form select');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    // Uncomment the next line if you want auto-submit on filter change
                    // this.form.submit();
                });
            });

            // Highlight search terms
            const searchTerm = '<?php echo addslashes($search); ?>';
            if (searchTerm.length > 2) {
                highlightSearchTerms(searchTerm);
            }
        });

        function highlightSearchTerms(term) {
            const tableBody = document.querySelector('#studentsTable tbody');
            if (!tableBody || !term) return;

            const regex = new RegExp(`(${term})`, 'gi');
            const walker = document.createTreeWalker(
                tableBody,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );

            const textNodes = [];
            let node;
            while (node = walker.nextNode()) {
                textNodes.push(node);
            }

            textNodes.forEach(textNode => {
                const parent = textNode.parentNode;
                if (parent.tagName !== 'SCRIPT' && parent.tagName !== 'STYLE') {
                    const highlighted = textNode.textContent.replace(regex, '<mark>$1</mark>');
                    if (highlighted !== textNode.textContent) {
                        const wrapper = document.createElement('span');
                        wrapper.innerHTML = highlighted;
                        parent.replaceChild(wrapper, textNode);
                    }
                }
            });
        }

        // Quick stats update
        function updateStats() {
            const totalRows = document.querySelectorAll('#studentsTable tbody tr:not([style*="display: none"])').length;
            const emptyRow = document.querySelector('#studentsTable tbody tr td[colspan]');
            
            if (!emptyRow && totalRows > 0) {
                console.log(`Displaying ${totalRows} students on current page`);
            }
        }
    </script>

    <style>
        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            background: white;
            color: var(--text-color);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }

        .pagination-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-info {
            text-align: center;
            color: var(--muted-color);
            margin-bottom: 1rem;
        }

        mark {
            background-color: #ffeb3b;
            color: #000;
            padding: 0.1rem 0.2rem;
            border-radius: 0.2rem;
        }

        .filter-form .btn {
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
            
            .pagination-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
        }

        /* Print styles */
        @media print {
            .btn, .pagination, .card-header .d-flex {
                display: none !important;
            }
            
            .card {
                border: 1px solid #000;
                page-break-inside: avoid;
            }
            
            .table {
                font-size: 12px;
            }
            
            .page-header {
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
            }
        }
    </style>
</body>
</html>
