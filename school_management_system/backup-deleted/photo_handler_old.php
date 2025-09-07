<?php
/**
 * Photo Processing and Upload Handler
 * 
 * Handles secure photo uploads, resizing, and storage
 */

/**
 * Process and save student photo
 * 
 * @param string $admission_no Student admission number
 * @param array|null $file $_FILES array for uploaded photo
 * @param string|null $photo_data Base64 photo data from camera
 * @return array [bool $success, string|null $photo_path, array $errors]
 */
function process_student_photo($admission_no, $file = null, $photo_data = null) {
    $errors = [];
    
    // Validate photo first
    list($is_valid, $photo_errors, $image_data) = validate_photo_upload($file, $photo_data);
    
    if (!$is_valid) {
        return [false, null, $photo_errors];
    }
    
    // If no photo provided, return success with null path
    if (!$image_data) {
        return [true, null, []];
    }
    
    try {
        // Create uploads directory structure
        $base_dir = dirname(__DIR__) . '/uploads';
        $year = date('Y');
        $student_dir = "$base_dir/students/$year";
        
        if (!is_dir($student_dir)) {
            if (!mkdir($student_dir, 0755, true)) {
                return [false, null, ['Failed to create upload directory']];
            }
        }
        
        // Secure the uploads directory
        secure_upload_directory($base_dir);
        
        // Generate secure filename
        $filename = preg_replace('/[^a-zA-Z0-9]/', '', $admission_no) . '.jpg';
        $file_path = "$student_dir/$filename";
        $relative_path = "uploads/students/$year/$filename";
        
        // Create image resource from data
        $source_image = imagecreatefromstring($image_data);
        if (!$source_image) {
            return [false, null, ['Failed to process image data']];
        }
        
        // Get original dimensions
        $orig_width = imagesx($source_image);
        $orig_height = imagesy($source_image);
        
        // Calculate passport photo dimensions (3.5:4.5 ratio)
        $target_width = 350;
        $target_height = 450;
        
        // Calculate crop dimensions to maintain aspect ratio
        $aspect_ratio = $target_width / $target_height;
        $source_aspect = $orig_width / $orig_height;
        
        if ($source_aspect > $aspect_ratio) {
            // Source is wider, crop width
            $crop_height = $orig_height;
            $crop_width = $crop_height * $aspect_ratio;
            $crop_x = ($orig_width - $crop_width) / 2;
            $crop_y = 0;
        } else {
            // Source is taller, crop height
            $crop_width = $orig_width;
            $crop_height = $crop_width / $aspect_ratio;
            $crop_x = 0;
            $crop_y = ($orig_height - $crop_height) / 2;
        }
        
        // Create passport-sized image
        $passport_image = imagecreatetruecolor($target_width, $target_height);
        
        // Set white background
        $white = imagecolorallocate($passport_image, 255, 255, 255);
        imagefill($passport_image, 0, 0, $white);
        
        // Resample and crop the image
        imagecopyresampled(
            $passport_image, $source_image,
            0, 0, $crop_x, $crop_y,
            $target_width, $target_height, $crop_width, $crop_height
        );
        
        // Save as JPEG with 90% quality
        if (imagejpeg($passport_image, $file_path, 90)) {
            // Clean up memory
            imagedestroy($source_image);
            imagedestroy($passport_image);
            
            // Set proper file permissions
            chmod($file_path, 0644);
            
            return [true, $relative_path, []];
        } else {
            // Clean up memory
            imagedestroy($source_image);
            imagedestroy($passport_image);
            
            return [false, null, ['Failed to save processed photo']];
        }
        
    } catch (Exception $e) {
        error_log("Photo processing error: " . $e->getMessage());
        return [false, null, ['Photo processing failed: ' . $e->getMessage()]];
    }
}

/**
 * Secure the uploads directory against PHP execution
 * 
 * @param string $base_dir Base uploads directory
 */
function secure_upload_directory($base_dir) {
    $htaccess_file = "$base_dir/.htaccess";
    
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "# Security rules for uploads directory\n";
        $htaccess_content .= "# Deny execution of PHP files\n";
        $htaccess_content .= "php_flag engine off\n";
        $htaccess_content .= "AddType text/plain .php .php3 .phtml .pht\n";
        $htaccess_content .= "\n# Prevent access to sensitive files\n";
        $htaccess_content .= "<Files ~ \"\\.(htaccess|htpasswd)$\">\n";
        $htaccess_content .= "Order allow,deny\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "</Files>\n";
        $htaccess_content .= "\n# Only allow image files\n";
        $htaccess_content .= "<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n";
        $htaccess_content .= "Order allow,deny\n";
        $htaccess_content .= "Allow from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        $htaccess_content .= "\n# Block everything else\n";
        $htaccess_content .= "<FilesMatch \"^(?!.*\\.(jpg|jpeg|png|gif)$).*$\">\n";
        $htaccess_content .= "Order allow,deny\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "</FilesMatch>\n";
        
        file_put_contents($htaccess_file, $htaccess_content);
    }
    
    // Create index.php to prevent directory browsing
    $index_file = "$base_dir/index.php";
    if (!file_exists($index_file)) {
        file_put_contents($index_file, "<?php\n// Access denied\nhttp_response_code(403);\nexit('Access denied');\n?>");
    }
}

/**
 * Delete student photo
 * 
 * @param string $photo_path Relative path to photo
 * @return bool Success status
 */
function delete_student_photo($photo_path) {
    if (!$photo_path) {
        return true;
    }
    
    $full_path = dirname(__DIR__) . '/' . $photo_path;
    
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    
    return true; // Consider non-existent file as successful deletion
}

/**
 * Get photo URL for display
 * 
 * @param string|null $photo_path Relative path to photo
 * @param string $default_photo Default photo path
 * @return string Photo URL
 */
function get_photo_url($photo_path, $default_photo = 'assets/images/default-avatar.jpg') {
    if (!$photo_path) {
        return $default_photo;
    }
    
    $full_path = dirname(__DIR__) . '/' . $photo_path;
    
    if (file_exists($full_path)) {
        return $photo_path;
    }
    
    return $default_photo;
}

/**
 * Generate photo thumbnail
 * 
 * @param string $photo_path Path to original photo
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @return string|null Thumbnail path or null on failure
 */
function generate_thumbnail($photo_path, $width = 100, $height = 100) {
    $full_path = dirname(__DIR__) . '/' . $photo_path;
    
    if (!file_exists($full_path)) {
        return null;
    }
    
    try {
        $source = imagecreatefromjpeg($full_path);
        if (!$source) {
            return null;
        }
        
        $thumb = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
        
        // Get source dimensions
        $src_width = imagesx($source);
        $src_height = imagesy($source);
        
        // Calculate dimensions maintaining aspect ratio
        $src_aspect = $src_width / $src_height;
        $thumb_aspect = $width / $height;
        
        if ($src_aspect > $thumb_aspect) {
            $new_height = $height;
            $new_width = $height * $src_aspect;
        } else {
            $new_width = $width;
            $new_height = $width / $src_aspect;
        }
        
        $x = ($width - $new_width) / 2;
        $y = ($height - $new_height) / 2;
        
        imagecopyresampled($thumb, $source, $x, $y, 0, 0, $new_width, $new_height, $src_width, $src_height);
        
        // Generate thumbnail path
        $path_info = pathinfo($photo_path);
        $thumb_path = $path_info['dirname'] . '/thumb_' . $path_info['basename'];
        $thumb_full_path = dirname(__DIR__) . '/' . $thumb_path;
        
        if (imagejpeg($thumb, $thumb_full_path, 85)) {
            imagedestroy($source);
            imagedestroy($thumb);
            return $thumb_path;
        }
        
        imagedestroy($source);
        imagedestroy($thumb);
        
    } catch (Exception $e) {
        error_log("Thumbnail generation error: " . $e->getMessage());
    }
    
    return null;
}
?>
