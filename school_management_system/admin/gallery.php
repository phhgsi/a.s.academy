<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_image') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $album_name = trim($_POST['album_name']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            
            if (empty($title) || empty($album_name)) {
                $error_message = "Title and album name are required.";
            } else {
                // Handle file upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $file_type = $_FILES['image']['type'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        $error_message = "Please upload only JPG, PNG, GIF, or WebP images.";
                    } else {
                        // Create upload directory if not exists
                        $upload_dir = '../uploads/gallery/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Generate unique filename
                        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $filename = 'gallery_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            try {
                                $stmt = $pdo->prepare("INSERT INTO gallery (title, description, image_path, album_name, is_featured, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$title, $description, $filename, $album_name, $is_featured, $user_id]);
                                $success_message = "Image uploaded successfully!";
                            } catch (Exception $e) {
                                $error_message = "Error saving image information: " . $e->getMessage();
                                unlink($upload_path); // Delete uploaded file on database error
                            }
                        } else {
                            $error_message = "Error uploading image file.";
                        }
                    }
                } else {
                    $error_message = "Please select an image file to upload.";
                }
            }
        } elseif ($action === 'delete_image') {
            $image_id = (int)$_POST['image_id'];
            
            try {
                // Get image path before deleting
                $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
                $stmt->execute([$image_id]);
                $image = $stmt->fetch();
                
                if ($image) {
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
                    $stmt->execute([$image_id]);
                    
                    // Delete physical file
                    $file_path = '../uploads/gallery/' . $image['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    
                    $success_message = "Image deleted successfully!";
                } else {
                    $error_message = "Image not found.";
                }
            } catch (Exception $e) {
                $error_message = "Error deleting image: " . $e->getMessage();
            }
        } elseif ($action === 'update_featured') {
            $image_id = (int)$_POST['image_id'];
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            
            try {
                $stmt = $pdo->prepare("UPDATE gallery SET is_featured = ? WHERE id = ?");
                $stmt->execute([$is_featured, $image_id]);
                $success_message = "Image featured status updated!";
            } catch (Exception $e) {
                $error_message = "Error updating image: " . $e->getMessage();
            }
        }
    }
}

// Get all gallery images with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$album_filter = isset($_GET['album']) ? $_GET['album'] : '';

try {
    // Build query
    $where_clause = '';
    $params = [];
    
    if (!empty($album_filter)) {
        $where_clause = 'WHERE album_name = ?';
        $params[] = $album_filter;
    }
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM gallery $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_images = $stmt->fetch()['total'];
    
    // Get images for current page
    $sql = "SELECT g.*, u.username as uploaded_by_name FROM gallery g 
            LEFT JOIN users u ON g.uploaded_by = u.id 
            $where_clause 
            ORDER BY g.is_featured DESC, g.created_at DESC 
            LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $gallery_images = $stmt->fetchAll();
    
    // Get album names for filter
    $stmt = $pdo->prepare("SELECT DISTINCT album_name FROM gallery ORDER BY album_name");
    $stmt->execute();
    $albums = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $gallery_images = [];
    $albums = [];
    $total_images = 0;
}

$total_pages = ceil($total_images / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - School Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modern-ui.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="page-header">
                <h1><i class="bi bi-images"></i> Gallery Management</h1>
                <p>Manage school photo gallery and albums</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Gallery Controls -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-upload"></i> Upload New Image</h3>
                        <div class="gallery-stats">
                            <span class="badge badge-primary"><?php echo $total_images; ?> Total Images</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="action" value="add_image">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Image File *</label>
                                <input type="file" name="image" class="form-control" accept="image/*" required>
                                <small class="text-muted">Accepted formats: JPG, PNG, GIF, WebP. Max size: 5MB</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Title *</label>
                                <input type="text" name="title" class="form-control" required placeholder="Enter image title">
                            </div>
                            
                            <div class="form-group">
                                <label>Album</label>
                                <select name="album_name" class="form-control" required>
                                    <option value="">Select Album</option>
                                    <option value="academics">Academics</option>
                                    <option value="events">Events</option>
                                    <option value="sports">Sports</option>
                                    <option value="cultural">Cultural Programs</option>
                                    <option value="infrastructure">Infrastructure</option>
                                    <option value="general">General</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_featured"> 
                                    Feature on Homepage
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter image description (optional)"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload Image
                        </button>
                    </form>
                </div>
            </div>

            <!-- Gallery Filter and Display -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-collection"></i> Gallery Images</h3>
                        
                        <!-- Album Filter -->
                        <div class="gallery-filters">
                            <select onchange="filterByAlbum(this.value)" class="form-control" style="width: auto; display: inline-block;">
                                <option value="">All Albums</option>
                                <?php foreach ($albums as $album): ?>
                                    <option value="<?php echo htmlspecialchars($album); ?>" 
                                            <?php echo $album_filter === $album ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($album); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($gallery_images)): ?>
                        <div class="empty-state">
                            <i class="bi bi-images"></i>
                            <h4>No Images Found</h4>
                            <p>Upload your first image to get started with the gallery.</p>
                        </div>
                    <?php else: ?>
                        <div class="gallery-admin-grid">
                            <?php foreach ($gallery_images as $image): ?>
                                <div class="gallery-admin-item">
                                    <div class="image-container">
                                        <img src="../uploads/gallery/<?php echo $image['image_path']; ?>" 
                                             alt="<?php echo htmlspecialchars($image['title']); ?>"
                                             onerror="this.src='../assets/images/placeholder.jpg'">
                                        
                                        <?php if ($image['is_featured']): ?>
                                            <div class="featured-badge">
                                                <i class="bi bi-star-fill"></i> Featured
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="image-overlay">
                                            <div class="image-actions">
                                                <button onclick="viewImage('../uploads/gallery/<?php echo $image['image_path']; ?>', '<?php echo addslashes($image['title']); ?>')" 
                                                        class="btn btn-sm btn-secondary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button onclick="editImage(<?php echo $image['id']; ?>)" 
                                                        class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button onclick="deleteImage(<?php echo $image['id']; ?>)" 
                                                        class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="image-info">
                                        <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                                        <p class="image-description"><?php echo htmlspecialchars($image['description'] ?: 'No description'); ?></p>
                                        <div class="image-meta">
                                            <span class="album-tag"><?php echo ucfirst($image['album_name']); ?></span>
                                            <span class="upload-date"><?php echo date('M j, Y', strtotime($image['created_at'])); ?></span>
                                        </div>
                                        <div class="image-controls">
                                            <label class="featured-toggle">
                                                <input type="checkbox" <?php echo $image['is_featured'] ? 'checked' : ''; ?>
                                                       onchange="toggleFeatured(<?php echo $image['id']; ?>, this.checked)">
                                                <span class="slider"></span>
                                                Featured
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-container">
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?><?php echo $album_filter ? '&album=' . urlencode($album_filter) : ''; ?>" 
                                           class="pagination-btn">
                                            <i class="bi bi-chevron-left"></i> Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $album_filter ? '&album=' . urlencode($album_filter) : ''; ?>" 
                                           class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?><?php echo $album_filter ? '&album=' . urlencode($album_filter) : ''; ?>" 
                                           class="pagination-btn">
                                            Next <i class="bi bi-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Image View Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>View Image</h3>
                <button class="close-btn" onclick="closeModal('imageModal')">&times;</button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="" style="max-width: 100%; max-height: 70vh; border-radius: 0.5rem;">
                <h4 id="modalTitle" style="margin-top: 1rem;"></h4>
            </div>
        </div>
    </div>

    <script src="../assets/js/modern-ui.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script>
        function filterByAlbum(album) {
            const url = new URL(window.location);
            if (album) {
                url.searchParams.set('album', album);
            } else {
                url.searchParams.delete('album');
            }
            url.searchParams.delete('page'); // Reset to first page
            window.location.href = url.toString();
        }
        
        function viewImage(imageSrc, title) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalTitle').textContent = title;
            openModal('imageModal');
        }
        
        function editImage(imageId) {
            // For now, just show alert - can implement edit modal later
            alert('Edit functionality will be implemented soon');
        }
        
        function deleteImage(imageId) {
            if (confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_image">
                    <input type="hidden" name="image_id" value="${imageId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleFeatured(imageId, isFeatured) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_featured">
                <input type="hidden" name="image_id" value="${imageId}">
                ${isFeatured ? '<input type="hidden" name="is_featured" value="1">' : ''}
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // File upload preview
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Show file size
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                console.log(`File selected: ${file.name} (${fileSize}MB)`);
                
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size should not exceed 5MB');
                    e.target.value = '';
                }
            }
        });
    </script>
    
    <style>
        .upload-form {
            background: #f8fafc;
            padding: 2rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
            margin-bottom: 1rem;
        }
        
        .gallery-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .gallery-admin-item {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .gallery-admin-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .image-container {
            position: relative;
            aspect-ratio: 4/3;
            overflow: hidden;
        }
        
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }
        
        .image-container:hover img {
            transform: scale(1.05);
        }
        
        .featured-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: #ffd700;
            color: #1a202c;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .image-container:hover .image-overlay {
            opacity: 1;
        }
        
        .image-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .image-info {
            padding: 1.5rem;
        }
        
        .image-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .image-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .image-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.8rem;
        }
        
        .album-tag {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
        }
        
        .upload-date {
            color: var(--text-secondary);
        }
        
        .featured-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .featured-toggle input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .pagination-container {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .pagination-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover,
        .pagination-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .gallery-stats .badge {
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .gallery-admin-grid {
                grid-template-columns: 1fr;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>
