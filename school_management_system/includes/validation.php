<?php
/**
 * Comprehensive Input Validation Module
 * 
 * Provides robust validation functions for student and other data
 */

/**
 * Validate student input data comprehensively
 * 
 * @param array $data Form data to validate
 * @return array [bool $is_valid, array $errors, array $cleaned_data]
 */
function validate_student_input($data) {
    error_log('VALIDATE_STUDENT_START');
    error_log('VALIDATION_INPUT_DATA=' . json_encode($data));
    
    $errors = [];
    $cleaned = [];
    
    // Required field validation
    $required_fields = [
        'first_name' => 'First name',
        'last_name' => 'Last name', 
        'father_name' => "Father's name",
        'mother_name' => "Mother's name",
        'date_of_birth' => 'Date of birth',
        'age' => 'Age',
        'gender' => 'Gender',
        'parent_mobile' => 'Parent mobile number',
        'address' => 'Address',
        'village' => 'Village/City',
        'class_id' => 'Class'
    ];
    
    foreach ($required_fields as $field => $label) {
        $value = trim($data[$field] ?? '');
        if (empty($value)) {
            $errors[] = "$label is required";
        } else {
            $cleaned[$field] = $value;
        }
    }
    
    // Name validation (letters, spaces, apostrophes only)
    $name_pattern = "/^[a-zA-Z\s'\-\.]+$/";
    foreach (['first_name', 'last_name', 'father_name', 'mother_name'] as $field) {
        if (!empty($cleaned[$field])) {
            if (!preg_match($name_pattern, $cleaned[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " can only contain letters, spaces, apostrophes and hyphens";
            } elseif (strlen($cleaned[$field]) > 50) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " cannot exceed 50 characters";
            }
        }
    }
    
    // Date of birth validation
    if (!empty($cleaned['date_of_birth'])) {
        $dob = DateTime::createFromFormat('Y-m-d', $cleaned['date_of_birth']);
        if (!$dob) {
            $errors[] = 'Date of birth must be in YYYY-MM-DD format';
        } else {
            $today = new DateTime();
            $calculated_age = $today->diff($dob)->y;
            
            if ($dob > $today) {
                $errors[] = 'Date of birth cannot be in the future';
            } elseif ($calculated_age < 3) {
                $errors[] = 'Student must be at least 3 years old';
            } elseif ($calculated_age > 25) {
                $errors[] = 'Student cannot be older than 25 years';
            } else {
                // If age field is provided, validate it matches calculated age
                if (!empty($cleaned['age'])) {
                    $provided_age = (int)$cleaned['age'];
                    if ($provided_age !== $calculated_age) {
                        $errors[] = 'Age field does not match the calculated age from date of birth';
                    }
                } else {
                    // Set calculated age if not provided
                    $cleaned['age'] = $calculated_age;
                }
            }
        }
    }
    
    // Age validation (standalone)
    if (!empty($cleaned['age'])) {
        $age = filter_var($cleaned['age'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 3, 'max_range' => 25]]);
        if ($age === false) {
            $errors[] = 'Age must be a number between 3 and 25';
        } else {
            $cleaned['age'] = $age;
        }
    }
    
    // Gender validation
    if (!empty($cleaned['gender'])) {
        if (!in_array($cleaned['gender'], ['male', 'female', 'other'])) {
            $errors[] = 'Invalid gender selection';
        }
    }
    
    // Mobile number validation (Indian format)
    if (!empty($cleaned['parent_mobile'])) {
        $mobile = preg_replace('/[^0-9]/', '', $cleaned['parent_mobile']);
        if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $errors[] = 'Parent mobile number must be a valid 10-digit Indian mobile number starting with 6-9';
        } else {
            $cleaned['parent_mobile'] = $mobile;
        }
    }
    
    // Optional mobile number validation (student's own)
    if (!empty($data['mobile_no'])) {
        $mobile = preg_replace('/[^0-9]/', '', trim($data['mobile_no']));
        if ($mobile && !preg_match('/^[6-9]\d{9}$/', $mobile)) {
            $errors[] = 'Student mobile number must be a valid 10-digit Indian mobile number starting with 6-9';
        } else {
            $cleaned['mobile_no'] = $mobile ?: null;
        }
    }
    
    // Email validation (optional)
    if (!empty($data['email'])) {
        $email = trim($data['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        } elseif (strlen($email) > 100) {
            $errors[] = 'Email address cannot exceed 100 characters';
        } else {
            $cleaned['email'] = $email;
        }
    }
    
    // Aadhar number validation (optional but strict when provided)
    if (!empty($data['aadhar_no'])) {
        $aadhar = preg_replace('/[^0-9]/', '', trim($data['aadhar_no']));
        if (!preg_match('/^\d{12}$/', $aadhar)) {
            $errors[] = 'Aadhar number must be exactly 12 digits';
        } else {
            $cleaned['aadhar_no'] = $aadhar;
        }
    }
    
    // PAN number validation (optional but strict when provided) 
    if (!empty($data['pan_no'])) {
        $pan = strtoupper(trim($data['pan_no']));
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            $errors[] = 'PAN number must be in format: 5 letters + 4 digits + 1 letter (e.g., ABCDE1234F)';
        } else {
            $cleaned['pan_no'] = $pan;
        }
    }
    
    // Pincode validation (optional)
    if (!empty($data['pincode'])) {
        $pincode = preg_replace('/[^0-9]/', '', trim($data['pincode']));
        if (!preg_match('/^\d{6}$/', $pincode)) {
            $errors[] = 'Pincode must be exactly 6 digits';
        } else {
            $cleaned['pincode'] = $pincode;
        }
    }
    
    // Blood group validation (optional)
    if (!empty($data['blood_group'])) {
        $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!in_array($data['blood_group'], $valid_blood_groups)) {
            $errors[] = 'Invalid blood group selection';
        } else {
            $cleaned['blood_group'] = $data['blood_group'];
        }
    }
    
    // Category validation (optional)
    if (!empty($data['category'])) {
        $valid_categories = ['General', 'OBC', 'SC', 'ST', 'EWS'];
        if (!in_array($data['category'], $valid_categories)) {
            $errors[] = 'Invalid category selection';
        } else {
            $cleaned['category'] = $data['category'];
        }
    }
    
    // Address validation
    if (!empty($cleaned['address'])) {
        if (strlen($cleaned['address']) > 500) {
            $errors[] = 'Address cannot exceed 500 characters';
        }
    }
    
    // Village/City validation
    if (!empty($cleaned['village'])) {
        if (strlen($cleaned['village']) > 50) {
            $errors[] = 'Village/City name cannot exceed 50 characters';
        }
    }
    
    // Class ID validation (must be integer)
    if (!empty($cleaned['class_id'])) {
        if (!filter_var($cleaned['class_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            $errors[] = 'Invalid class selection';
        } else {
            $cleaned['class_id'] = (int)$cleaned['class_id'];
        }
    }
    
    // Academic year validation
    if (!empty($data['academic_year'])) {
        if (!preg_match('/^\d{4}-\d{4}$/', $data['academic_year'])) {
            $errors[] = 'Academic year must be in YYYY-YYYY format';
        } else {
            $cleaned['academic_year'] = $data['academic_year'];
        }
    }
    
    // Admission date validation
    if (!empty($data['admission_date'])) {
        $admission_date = DateTime::createFromFormat('Y-m-d', $data['admission_date']);
        if (!$admission_date) {
            $errors[] = 'Admission date must be in YYYY-MM-DD format';
        } else {
            $today = new DateTime();
            if ($admission_date > $today) {
                $errors[] = 'Admission date cannot be in the future';
            } else {
                $cleaned['admission_date'] = $data['admission_date'];
            }
        }
    }
    
    // Optional text fields
    $optional_text_fields = [
        'religion' => 30,
        'samagra_id' => 20,
        'scholar_no' => 50
    ];
    
    foreach ($optional_text_fields as $field => $max_length) {
        if (!empty($data[$field])) {
            $value = trim($data[$field]);
            if (strlen($value) > $max_length) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " cannot exceed $max_length characters";
            } else {
                $cleaned[$field] = $value;
            }
        }
    }
    
    $is_valid = empty($errors);
    error_log('VALIDATE_STUDENT_END is_valid=' . ($is_valid ? 'true' : 'false'));
    error_log('VALIDATION_ERRORS=' . json_encode($errors));
    error_log('CLEANED_DATA=' . json_encode($cleaned));
    
    return [$is_valid, $errors, $cleaned];
}

/**
 * Validate photo upload
 * 
 * @param array $file $_FILES array for photo
 * @param string|null $photo_data Base64 photo data from camera
 * @return array [bool $is_valid, array $errors, string|null $processed_photo_data]
 */
function validate_photo_upload($file = null, $photo_data = null) {
    $errors = [];
    $processed_data = null;
    
    // Check if either file upload or camera data is provided
    if (empty($file['tmp_name']) && empty($photo_data)) {
        // Photo is optional, return success with no data
        return [true, [], null];
    }
    
    // Validate file upload if provided
    if (!empty($file['tmp_name'])) {
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'Photo file is too large. Maximum size is 2MB.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'Photo upload was interrupted. Please try again.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                    $errors[] = 'Server error during photo upload. Please contact administrator.';
                    break;
                default:
                    $errors[] = 'Photo upload failed. Please try again.';
            }
            return [false, $errors, null];
        }
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Photo file size cannot exceed 2MB';
        }
        
        // Validate file type using getimagesize (more secure than mime type)
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            $errors[] = 'Uploaded file is not a valid image';
        } else {
            $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
            if (!in_array($image_info[2], $allowed_types)) {
                $errors[] = 'Photo must be in JPEG or PNG format';
            }
        }
        
        // If validation passed, read file data
        if (empty($errors)) {
            $processed_data = file_get_contents($file['tmp_name']);
        }
    }
    
    // Validate camera data if provided
    if (!empty($photo_data) && empty($errors)) {
        // Check if it's a valid base64 image
        if (strpos($photo_data, 'data:image/') !== 0) {
            $errors[] = 'Invalid camera photo data format';
        } else {
            // Extract and decode base64 data
            $base64_parts = explode(',', $photo_data);
            if (count($base64_parts) !== 2) {
                $errors[] = 'Invalid camera photo data format';
            } else {
                $image_data = base64_decode($base64_parts[1]);
                if (!$image_data) {
                    $errors[] = 'Failed to decode camera photo data';
                } else {
                    // Validate decoded image
                    $temp_file = tempnam(sys_get_temp_dir(), 'photo_validation');
                    file_put_contents($temp_file, $image_data);
                    
                    $image_info = getimagesize($temp_file);
                    unlink($temp_file);
                    
                    if (!$image_info) {
                        $errors[] = 'Camera photo data is not a valid image';
                    } else {
                        $processed_data = $image_data;
                    }
                }
            }
        }
    }
    
    return [empty($errors), $errors, $processed_data];
}

/**
 * Sanitize and validate text input
 * 
 * @param string $input Input text
 * @param int $max_length Maximum allowed length
 * @param string $pattern Optional regex pattern
 * @return string|null Cleaned input or null if invalid
 */
function sanitize_text($input, $max_length = 255, $pattern = null) {
    $input = trim($input);
    
    if (strlen($input) > $max_length) {
        return null;
    }
    
    if ($pattern && !preg_match($pattern, $input)) {
        return null;
    }
    
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Indian mobile number
 * 
 * @param string $mobile Mobile number
 * @return bool True if valid
 */
function is_valid_indian_mobile($mobile) {
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    return preg_match('/^[6-9]\d{9}$/', $mobile);
}

/**
 * Validate email address
 * 
 * @param string $email Email address
 * @return bool True if valid
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Aadhar number
 * 
 * @param string $aadhar Aadhar number
 * @return bool True if valid
 */
function is_valid_aadhar($aadhar) {
    $aadhar = preg_replace('/[^0-9]/', '', $aadhar);
    return preg_match('/^\d{12}$/', $aadhar);
}

/**
 * Validate PAN number
 * 
 * @param string $pan PAN number
 * @return bool True if valid
 */
function is_valid_pan($pan) {
    $pan = strtoupper(trim($pan));
    return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan);
}

/**
 * Validate Indian pincode
 * 
 * @param string $pincode Pincode
 * @return bool True if valid
 */
function is_valid_pincode($pincode) {
    $pincode = preg_replace('/[^0-9]/', '', $pincode);
    return preg_match('/^\d{6}$/', $pincode);
}

/**
 * Check if academic year format is valid
 * 
 * @param string $academic_year Academic year string
 * @return bool True if valid
 */
function is_valid_academic_year($academic_year) {
    if (!preg_match('/^(\d{4})-(\d{4})$/', $academic_year, $matches)) {
        return false;
    }
    
    $start_year = (int)$matches[1];
    $end_year = (int)$matches[2];
    
    // End year should be exactly start year + 1
    return $end_year === $start_year + 1;
}

/**
 * Generate validation errors as HTML
 * 
 * @param array $errors Array of error messages
 * @return string HTML formatted error list
 */
function format_validation_errors($errors) {
    if (empty($errors)) {
        return '';
    }
    
    $html = '<div class="alert alert-danger" role="alert">';
    $html .= '<h5><i class="bi bi-exclamation-triangle"></i> Please correct the following errors:</h5>';
    $html .= '<ul class="mb-0">';
    
    foreach ($errors as $error) {
        $html .= '<li>' . htmlspecialchars($error) . '</li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Preserve form data in session for repopulation after validation failure
 * 
 * @param array $data Form data to preserve
 */
function preserve_form_data($data) {
    $_SESSION['form_old'] = array_map(function($value) {
        return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
    }, $data);
}

/**
 * Get preserved form data and clear it from session
 * 
 * @param string $field Field name
 * @param mixed $default Default value
 * @return mixed Preserved value or default
 */
function old($field, $default = '') {
    if (isset($_SESSION['form_old'][$field])) {
        $value = $_SESSION['form_old'][$field];
        unset($_SESSION['form_old'][$field]);
        return $value;
    }
    return $default;
}

/**
 * Clear all preserved form data
 */
function clear_old_data() {
    unset($_SESSION['form_old']);
}
?>
