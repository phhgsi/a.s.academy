// Photo Capture JavaScript
let currentStream = null;
let capturedImageData = null;

// Initialize photo capture functionality
function initializePhotoCapture() {
    // Handle file upload preview
    const photoFile = document.getElementById('photoFile');
    if (photoFile) {
        photoFile.addEventListener('change', handleFileUpload);
    }
}

// Handle file upload and preview
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            showPhotoPreview(e.target.result);
        };
        reader.readAsDataURL(file);
    }
}

// Show photo preview
function showPhotoPreview(imageSrc) {
    const photoPreview = document.getElementById('photoPreview');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const photoActions = document.getElementById('photoActions');
    
    if (photoPreview && photoPlaceholder && photoActions) {
        photoPreview.src = imageSrc;
        photoPreview.style.display = 'block';
        photoPlaceholder.style.display = 'none';
        photoActions.style.display = 'flex';
    }
}

// Open camera modal
async function openCameraModal() {
    const modal = document.getElementById('cameraModal');
    const video = document.getElementById('cameraVideo');
    const cameraView = document.querySelector('.camera-view');
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Show loading
    showCameraLoading(cameraView, 'Accessing camera...');
    
    try {
        // Request camera access
        const constraints = {
            video: {
                width: { ideal: 1280 },
                height: { ideal: 720 },
                facingMode: 'user' // Front camera preferred for selfies
            }
        };
        
        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = currentStream;
        
        // Wait for video to be ready
        video.onloadedmetadata = function() {
            hideCameraLoading();
            resetCaptureState();
        };
        
    } catch (error) {
        console.error('Error accessing camera:', error);
        showCameraError(cameraView, getErrorMessage(error));
    }
}

// Close camera modal
function closeCameraModal() {
    const modal = document.getElementById('cameraModal');
    
    // Stop camera stream
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset UI state
    resetCaptureState();
    hideCameraLoading();
    hideCameraError();
}

// Reset capture state
function resetCaptureState() {
    const captureBtn = document.getElementById('captureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const usePhotoBtn = document.getElementById('usePhotoBtn');
    const captureCanvas = document.getElementById('captureCanvas');
    const capturePreview = document.getElementById('capturePreview');
    
    if (captureBtn) captureBtn.style.display = 'block';
    if (retakeBtn) retakeBtn.style.display = 'none';
    if (usePhotoBtn) usePhotoBtn.style.display = 'none';
    if (captureCanvas) captureCanvas.style.display = 'none';
    if (capturePreview) capturePreview.style.display = 'flex';
    
    capturedImageData = null;
}

// Capture photo from camera
function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('captureCanvas');
    const capturePreview = document.getElementById('capturePreview');
    const captureBtn = document.getElementById('captureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const usePhotoBtn = document.getElementById('usePhotoBtn');
    const cameraView = document.querySelector('.camera-view');
    
    if (!video || !canvas) return;
    
    // Add flash effect
    const flash = document.createElement('div');
    flash.className = 'photo-capture-flash';
    cameraView.appendChild(flash);
    setTimeout(() => flash.remove(), 300);
    
    // Get video dimensions
    const videoWidth = video.videoWidth;
    const videoHeight = video.videoHeight;
    
    // Calculate crop area for passport photo (3.5:4.5 ratio)
    const aspectRatio = 3.5 / 4.5;
    let cropWidth, cropHeight, cropX, cropY;
    
    if (videoWidth / videoHeight > aspectRatio) {
        // Video is wider, crop width
        cropHeight = videoHeight * 0.8; // Use 80% of video height
        cropWidth = cropHeight * aspectRatio;
        cropX = (videoWidth - cropWidth) / 2;
        cropY = (videoHeight - cropHeight) / 2;
    } else {
        // Video is taller, crop height
        cropWidth = videoWidth * 0.8; // Use 80% of video width
        cropHeight = cropWidth / aspectRatio;
        cropX = (videoWidth - cropWidth) / 2;
        cropY = (videoHeight - cropHeight) / 2;
    }
    
    // Set canvas size for passport photo
    canvas.width = 280;
    canvas.height = 360;
    
    const ctx = canvas.getContext('2d');
    
    // Draw cropped image on canvas
    ctx.drawImage(
        video,
        cropX, cropY, cropWidth, cropHeight,  // Source rectangle
        0, 0, canvas.width, canvas.height     // Destination rectangle
    );
    
    // Convert to data URL
    capturedImageData = canvas.toDataURL('image/jpeg', 0.9);
    
    // Show preview
    canvas.style.display = 'block';
    capturePreview.style.display = 'none';
    
    // Update button states
    captureBtn.style.display = 'none';
    retakeBtn.style.display = 'block';
    usePhotoBtn.style.display = 'block';
}

// Retake photo
function retakePhoto() {
    resetCaptureState();
}

// Use captured photo
function usePhoto() {
    if (!capturedImageData) return;
    
    // Set the captured image data to the hidden field
    const photoDataField = document.getElementById('photoDataField');
    if (photoDataField) {
        photoDataField.value = capturedImageData;
    }
    
    // Show preview in main form
    showPhotoPreview(capturedImageData);
    
    // Close modal
    closeCameraModal();
}

// Convert data URL to File object
function dataURLToFile(dataURL, filename) {
    return new Promise((resolve) => {
        const arr = dataURL.split(',');
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        
        resolve(new File([u8arr], filename, { type: mime }));
    });
}

// Show camera loading state
function showCameraLoading(container, message) {
    const existingLoader = container.querySelector('.camera-loader');
    if (existingLoader) return;
    
    const loader = document.createElement('div');
    loader.className = 'camera-loader';
    loader.innerHTML = `
        <div class="loading-spinner"></div>
        <p>${message}</p>
    `;
    loader.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        z-index: 1000;
    `;
    
    container.appendChild(loader);
}

// Hide camera loading state
function hideCameraLoading() {
    const loaders = document.querySelectorAll('.camera-loader');
    loaders.forEach(loader => loader.remove());
}

// Show camera error
function showCameraError(container, message) {
    hideCameraLoading();
    
    const error = document.createElement('div');
    error.className = 'camera-error';
    error.innerHTML = `
        <div class="error-icon">⚠️</div>
        <p>${message}</p>
        <button onclick="closeCameraModal()" class="btn btn-secondary">Close</button>
    `;
    error.style.cssText = `
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 1000;
    `;
    
    container.appendChild(error);
}

// Hide camera error
function hideCameraError() {
    const errors = document.querySelectorAll('.camera-error');
    errors.forEach(error => error.remove());
}

// Get user-friendly error message
function getErrorMessage(error) {
    switch (error.name) {
        case 'NotAllowedError':
            return 'Camera access was denied. Please allow camera access and try again.';
        case 'NotFoundError':
            return 'No camera found. Please check if your device has a camera.';
        case 'NotSupportedError':
            return 'Camera is not supported on this device or browser.';
        case 'NotReadableError':
            return 'Camera is busy or unavailable. Please close other applications using the camera.';
        case 'OverconstrainedError':
            return 'Camera constraints could not be satisfied. Please try again.';
        default:
            return 'An error occurred while accessing the camera: ' + error.message;
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializePhotoCapture();
});

// Update the modal body to include capture controls
function updateCameraModalControls() {
    const modalBody = document.querySelector('#cameraModal .modal-body');
    if (!modalBody) return;
    
    const controlsHTML = `
        <div class="camera-controls" id="cameraControls" style="display: none;">
            <button type="button" class="btn btn-primary" id="captureBtn" onclick="capturePhoto()">
                <i class="bi bi-camera"></i> Capture Photo
            </button>
            <button type="button" class="btn btn-outline" id="retakeBtn" onclick="retakePhoto()" style="display: none;">
                <i class="bi bi-arrow-clockwise"></i> Retake
            </button>
            <button type="button" class="btn btn-success" id="usePhotoBtn" onclick="usePhoto()" style="display: none;">
                <i class="bi bi-check"></i> Use This Photo
            </button>
        </div>
        <canvas id="captureCanvas" style="display: none;"></canvas>
        <div id="capturePreview" style="display: flex; align-items: center; justify-content: center; height: 300px; background: #f8f9fa; border: 2px dashed #dee2e6; margin-top: 10px;">
            <span style="color: #6c757d;">Camera preview will appear here</span>
        </div>
    `;
    
    modalBody.insertAdjacentHTML('beforeend', controlsHTML);
}
            
            // Trigger change event to update preview
            const changeEvent = new Event('change', { bubbles: true });
            photoFile.dispatchEvent(changeEvent);
            
            // Show preview in form
            showPhotoPreview(capturedImageData);
            
            // Close modal
            closeCameraModal();
        })
        .catch(error => {
            console.error('Error setting captured photo:', error);
            alert('Error processing captured photo. Please try again.');
        });
}

// Convert data URL to File object
function dataURLToFile(dataURL, filename) {
    return fetch(dataURL)
        .then(res => res.arrayBuffer())
        .then(buf => new File([buf], filename, { type: 'image/jpeg' }));
}

// Edit photo (basic crop functionality)
function editPhoto() {
    // For now, just open camera modal to recapture
    // In future, could implement more advanced editing
    openCameraModal();
}

// Remove photo
function removePhoto() {
    const photoPreview = document.getElementById('photoPreview');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const photoActions = document.getElementById('photoActions');
    const photoFile = document.getElementById('photoFile');
    
    if (photoPreview && photoPlaceholder && photoActions && photoFile) {
        photoPreview.style.display = 'none';
        photoPreview.src = '#';
        photoPlaceholder.style.display = 'flex';
        photoActions.style.display = 'none';
        photoFile.value = '';
    }
}

// Show camera loading
function showCameraLoading(container, message = 'Loading...') {
    hideCameraError(); // Hide any existing error
    
    const loading = document.createElement('div');
    loading.className = 'camera-loading';
    loading.id = 'cameraLoading';
    loading.innerHTML = `
        <i class="bi bi-camera-video" style="margin-right: 0.5rem;"></i>
        ${message}
    `;
    container.appendChild(loading);
}

// Hide camera loading
function hideCameraLoading() {
    const loading = document.getElementById('cameraLoading');
    if (loading) {
        loading.remove();
    }
}

// Show camera error
function showCameraError(container, message) {
    hideCameraLoading(); // Hide loading if shown
    
    const error = document.createElement('div');
    error.className = 'camera-error';
    error.id = 'cameraError';
    error.innerHTML = `
        <i class="bi bi-exclamation-triangle" style="margin-bottom: 0.5rem; font-size: 1.5rem;"></i><br>
        <strong>Camera Access Error</strong><br>
        ${message}
    `;
    container.appendChild(error);
}

// Hide camera error
function hideCameraError() {
    const error = document.getElementById('cameraError');
    if (error) {
        error.remove();
    }
}

// Get user-friendly error message
function getErrorMessage(error) {
    switch (error.name) {
        case 'NotAllowedError':
            return 'Please allow camera access to capture photos.';
        case 'NotFoundError':
            return 'No camera found. Please connect a camera and try again.';
        case 'NotSupportedError':
            return 'Camera capture is not supported in this browser.';
        case 'NotReadableError':
            return 'Camera is being used by another application.';
        case 'OverconstrainedError':
            return 'Camera does not meet the required specifications.';
        case 'SecurityError':
            return 'Camera access blocked due to security restrictions.';
        default:
            return 'Unable to access camera. Please check your permissions and try again.';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('cameraModal');
    if (event.target === modal) {
        closeCameraModal();
    }
});

// Handle escape key to close modal
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('cameraModal');
        if (modal && modal.style.display === 'block') {
            closeCameraModal();
        }
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
    }
});
