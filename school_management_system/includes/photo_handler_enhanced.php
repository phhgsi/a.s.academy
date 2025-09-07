<?php
/**
 * Enhanced Photo handling utilities for student photos
 * Provides comprehensive functionality for uploading, processing, and managing student photos
 * Version: 2.0 - Enhanced with better validation and error handling
 */

class PhotoHandler {
    private $upload_dir = 'uploads/students/';
    private $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    private $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    private $max_size = 5242880; // 5MB default
    private $thumbnail_size = 200; // 200x200 pixels
    private $max_width = 1200; // Maximum width for original images
    private $max_height = 1600; // Maximum height for original images
    
    public function __construct($upload_dir = null, $max_size = null) {
        if ($upload_dir) {
            $this->upload_dir = rtrim($upload_dir, '/') . '/';
        }
        
        if ($max_size) {
            $this->max_size = $max_size;
        } else {
            // Try to get from system settings
            global $conn;
            if (isset($conn)) {
                $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'max_file_upload_size'");
                if ($stmt && $stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $this->max_size = intval($row['setting_value']);
                    }
                }
            }
        }
        
        // Ensure upload directory exists
        $this->ensureUploadDir();
    }
    
    /**
     * Ensure upload directory exists and is writable
     */
    private function ensureUploadDir() {
        $full_path = __DIR__ . '/../' . $this->upload_dir;
        if (!file_exists($full_path)) {
            mkdir($full_path, 0755, true);
        }
        
        // Create thumbnails directory
        $thumb_path = $full_path . 'thumbnails/';
        if (!file_exists($thumb_path)) {
            mkdir($thumb_path, 0755, true);
        }
        
        // Create .htaccess for security
        $htaccess_path = $full_path . '.htaccess';
        if (!file_exists($htaccess_path)) {
            $htaccess_content = "# Prevent direct access to PHP files\n";
            $htaccess_content .= "<Files \"*.php\">\n";
            $htaccess_content .= "    Order allow,deny\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "\n# Set proper MIME types for images\n";
            $htaccess_content .= "AddType image/jpeg .jpg .jpeg\n";
            $htaccess_content .= "AddType image/png .png\n";
            $htaccess_content .= "AddType image/gif .gif\n";
            file_put_contents($htaccess_path, $htaccess_content);
        }
    }
    
    /**
     * Process uploaded photo for a student
     * @param array $file - $_FILES array element
     * @param int $student_id - Student ID (optional for new students)
     * @return array - Result with success/error status
     */
    public function processStudentPhoto($file, $student_id = null) {
        try {
            // Validate file upload
            $validation = $this->validateUpload($file);
            if (!$validation['success']) {
                return $validation;
            }
            
            // Generate unique filename
            $extension = $this->getFileExtension($file['name']);
            $timestamp = time();
            $random = substr(md5(uniqid()), 0, 8);
            
            if ($student_id) {
                $filename = "student_{$student_id}_{$timestamp}_{$random}.{$extension}";
            } else {
                $filename = "temp_{$timestamp}_{$random}.{$extension}";
            }
            
            // Upload paths
            $upload_path = __DIR__ . '/../' . $this->upload_dir . $filename;
            $thumbnail_path = __DIR__ . '/../' . $this->upload_dir . 'thumbnails/' . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                return ['success' => false, 'error' => 'Failed to move uploaded file'];
            }
            
            // Optimize original image if too large
            $this->optimizeImage($upload_path, $this->max_width, $this->max_height);
            
            // Process and create thumbnail
            $thumb_result = $this->createThumbnail($upload_path, $thumbnail_path);
            if (!$thumb_result['success']) {
                // Delete original if thumbnail creation failed
                unlink($upload_path);
                return $thumb_result;
            }
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $this->upload_dir . $filename,
                'thumbnail_path' => $this->upload_dir . 'thumbnails/' . $filename,
                'size' => filesize($upload_path),
                'original_size' => $file['size'],
                'optimized' => $file['size'] !== filesize($upload_path)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Photo processing error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded file with enhanced checks
     */
    private function validateUpload($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No file was uploaded'];
        }
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];
            $error_msg = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'Unknown upload error';
            return ['success' => false, 'error' => $error_msg];
        }
        
        // Check file size
        if ($file['size'] > $this->max_size) {
            $max_mb = round($this->max_size / 1024 / 1024, 1);
            return ['success' => false, 'error' => "File size exceeds limit ({$max_mb}MB)"];
        }
        
        if ($file['size'] < 1024) { // Less than 1KB
            return ['success' => false, 'error' => 'File is too small to be a valid image'];
        }
        
        // Check file extension
        $extension = $this->getFileExtension($file['name']);
        if (!in_array($extension, $this->allowed_extensions)) {
            return ['success' => false, 'error' => 'Invalid file extension. Only JPG, PNG, and GIF allowed'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $this->allowed_types)) {
            return ['success' => false, 'error' => 'Invalid file type detected. Only image files allowed'];
        }
        
        // Additional security: Check if it's actually an image
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            return ['success' => false, 'error' => 'File is not a valid image'];
        }
        
        // Check image dimensions
        if ($image_info[0] < 100 || $image_info[1] < 100) {
            return ['success' => false, 'error' => 'Image is too small. Minimum size is 100x100 pixels'];
        }
        
        if ($image_info[0] > 5000 || $image_info[1] > 5000) {
            return ['success' => false, 'error' => 'Image is too large. Maximum size is 5000x5000 pixels'];
        }
        
        return ['success' => true, 'mime_type' => $mime_type, 'dimensions' => $image_info];
    }
    
    /**
     * Optimize image by resizing if too large
     */
    private function optimizeImage($image_path, $max_width, $max_height) {
        try {
            $image_info = getimagesize($image_path);
            if (!$image_info) {
                return false;
            }
            
            $width = $image_info[0];
            $height = $image_info[1];
            $type = $image_info[2];
            
            // Skip optimization if image is already small enough
            if ($width <= $max_width && $height <= $max_height) {
                return true;
            }
            
            // Calculate new dimensions maintaining aspect ratio
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = round($width * $ratio);
            $new_height = round($height * $ratio);
            
            // Create source image
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($image_path);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($image_path);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($image_path);
                    break;
                default:
                    return false;
            }
            
            if (!$source) {
                return false;
            }
            
            // Create optimized image
            $optimized = imagecreatetruecolor($new_width, $new_height);
            
            // Handle transparency
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                imagealphablending($optimized, false);
                imagesavealpha($optimized, true);
                $transparent = imagecolorallocatealpha($optimized, 255, 255, 255, 127);
                imagefill($optimized, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled($optimized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            
            // Save optimized image
            $success = false;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $success = imagejpeg($optimized, $image_path, 85);
                    break;
                case IMAGETYPE_PNG:
                    $success = imagepng($optimized, $image_path, 6);
                    break;
                case IMAGETYPE_GIF:
                    $success = imagegif($optimized, $image_path);
                    break;
            }
            
            // Clean up
            imagedestroy($source);
            imagedestroy($optimized);
            
            return $success;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Create thumbnail from uploaded image with better handling
     */
    private function createThumbnail($source_path, $thumbnail_path) {
        try {
            $image_info = getimagesize($source_path);
            if (!$image_info) {
                return ['success' => false, 'error' => 'Invalid image file for thumbnail creation'];
            }
            
            $width = $image_info[0];
            $height = $image_info[1];
            $type = $image_info[2];
            
            // Create source image resource
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($source_path);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($source_path);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($source_path);
                    break;
                default:
                    return ['success' => false, 'error' => 'Unsupported image type for thumbnail'];
            }
            
            if (!$source) {
                return ['success' => false, 'error' => 'Failed to create image resource from source'];
            }
            
            // Calculate thumbnail dimensions (square crop from center)
            $size = min($width, $height);
            $x = ($width - $size) / 2;
            $y = ($height - $size) / 2;
            
            // Create thumbnail canvas
            $thumbnail = imagecreatetruecolor($this->thumbnail_size, $this->thumbnail_size);
            
            // Handle transparency for PNG and GIF
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            } else {
                // For JPEG, use white background
                $white = imagecolorallocate($thumbnail, 255, 255, 255);
                imagefill($thumbnail, 0, 0, $white);
            }
            
            // Resample the image with high quality
            imagecopyresampled(
                $thumbnail, $source, 
                0, 0, $x, $y,
                $this->thumbnail_size, $this->thumbnail_size, $size, $size
            );
            
            // Save thumbnail with appropriate quality
            $success = false;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $success = imagejpeg($thumbnail, $thumbnail_path, 90);
                    break;
                case IMAGETYPE_PNG:
                    $success = imagepng($thumbnail, $thumbnail_path, 6);
                    break;
                case IMAGETYPE_GIF:
                    $success = imagegif($thumbnail, $thumbnail_path);
                    break;
            }
            
            // Clean up resources
            imagedestroy($source);
            imagedestroy($thumbnail);
            
            if (!$success) {
                return ['success' => false, 'error' => 'Failed to save thumbnail image'];
            }
            
            return ['success' => true, 'thumbnail_created' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Thumbnail creation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get file extension from filename
     */
    private function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Delete student photo and thumbnail
     */
    public function deleteStudentPhoto($filename) {
        if (empty($filename)) {
            return ['success' => true, 'message' => 'No file to delete'];
        }
        
        $full_path = __DIR__ . '/../' . $this->upload_dir . $filename;
        $thumbnail_path = __DIR__ . '/../' . $this->upload_dir . 'thumbnails/' . $filename;
        
        $deleted = 0;
        $errors = [];
        
        // Delete original
        if (file_exists($full_path)) {
            if (unlink($full_path)) {
                $deleted++;
            } else {
                $errors[] = 'Failed to delete original image';
            }
        }
        
        // Delete thumbnail
        if (file_exists($thumbnail_path)) {
            if (unlink($thumbnail_path)) {
                $deleted++;
            } else {
                $errors[] = 'Failed to delete thumbnail';
            }
        }
        
        return [
            'success' => empty($errors),
            'deleted_files' => $deleted,
            'message' => empty($errors) ? "Successfully deleted {$deleted} file(s)" : implode(', ', $errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get photo URL for display with fallback
     */
    public function getPhotoUrl($filename, $thumbnail = false) {
        if (empty($filename)) {
            return $thumbnail ? 'assets/images/student-placeholder-thumb.svg' : 'assets/images/student-placeholder.svg';
        }
        
        $path = $thumbnail ? $this->upload_dir . 'thumbnails/' . $filename : $this->upload_dir . $filename;
        
        // Check if file exists
        $full_path = __DIR__ . '/../' . $path;
        if (!file_exists($full_path)) {
            return $thumbnail ? 'assets/images/student-placeholder-thumb.svg' : 'assets/images/student-placeholder.svg';
        }
        
        // Return relative URL from document root
        return $path;
    }
    
    /**
     * Get photo display HTML with enhanced features
     */
    public function getPhotoHtml($filename, $alt_text = 'Student Photo', $class = 'student-photo', $show_fallback = true) {
        $url = $this->getPhotoUrl($filename);
        $thumbnail_url = $this->getPhotoUrl($filename, true);
        
        $html = '<img src="' . htmlspecialchars($thumbnail_url) . '" ';
        $html .= 'alt="' . htmlspecialchars($alt_text) . '" ';
        $html .= 'class="' . htmlspecialchars($class) . '" ';
        $html .= 'data-full="' . htmlspecialchars($url) . '" ';
        $html .= 'loading="lazy" ';
        $html .= 'onerror="this.src=\'assets/images/student-placeholder-thumb.svg\'" ';
        $html .= '>';
        
        return $html;
    }
    
    /**
     * Validate if file exists and is accessible
     */
    public function validatePhotoFile($filename) {
        if (empty($filename)) {
            return false;
        }
        
        $full_path = __DIR__ . '/../' . $this->upload_dir . $filename;
        return file_exists($full_path) && is_readable($full_path);
    }
    
    /**
     * Get photo file information
     */
    public function getPhotoInfo($filename) {
        if (!$this->validatePhotoFile($filename)) {
            return null;
        }
        
        $full_path = __DIR__ . '/../' . $this->upload_dir . $filename;
        $image_info = getimagesize($full_path);
        
        return [
            'filename' => $filename,
            'size' => filesize($full_path),
            'width' => $image_info[0],
            'height' => $image_info[1],
            'mime_type' => $image_info['mime'],
            'url' => $this->getPhotoUrl($filename),
            'thumbnail_url' => $this->getPhotoUrl($filename, true)
        ];
    }
}

// Initialize the photo handler if needed
if (!function_exists('getPhotoHandler')) {
    function getPhotoHandler() {
        static $photo_handler = null;
        if ($photo_handler === null) {
            $photo_handler = new PhotoHandler();
        }
        return $photo_handler;
    }
}
?>
