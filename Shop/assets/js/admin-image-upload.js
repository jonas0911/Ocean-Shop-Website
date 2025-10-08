// Image Upload Handler for Admin Panel
class ImageUploadHandler {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Initialize for Add Modal
        this.initializeModalEventListeners('', 'imageFileInput', 'imageDropZone', 'image_url', 'previewImage', 'imagePreview');
        
        // Initialize for Edit Modal  
        this.initializeModalEventListeners('edit', 'editImageFileInput', 'editImageDropZone', 'editImage', 'editPreviewImage', 'editImagePreview');
        
        // Global paste event - works anywhere in the modal
        this.initializeGlobalPasteEvents();
    }

    initializeModalEventListeners(prefix, fileInputId, dropZoneId, urlInputId, previewImgId, previewDivId) {
        // File input change
        const fileInput = document.getElementById(fileInputId);
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e, previewImgId, previewDivId, urlInputId));
        }

        // Drop zone events
        const dropZone = document.getElementById(dropZoneId);
        if (dropZone) {
            dropZone.addEventListener('click', () => fileInput?.click());
            dropZone.addEventListener('dragover', (e) => this.handleDragOver(e));
            dropZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
            dropZone.addEventListener('drop', (e) => this.handleDrop(e, previewImgId, previewDivId, urlInputId));
        }

        // URL input change
        const urlInput = document.getElementById(urlInputId);
        if (urlInput) {
            urlInput.addEventListener('input', (e) => this.handleUrlInput(e.target.value, previewImgId, previewDivId));
        }
    }

    initializeGlobalPasteEvents() {
        // Global paste handler for modals
        document.addEventListener('paste', (e) => {
            // Check if we're in an Add Game modal
            const addModal = document.getElementById('addGameModal');
            const editModal = document.getElementById('editGameModal');
            
            if (addModal && addModal.classList.contains('show')) {
                // We're in Add modal
                this.handlePaste(e, 'previewImage', 'imagePreview', 'image_url');
            } else if (editModal && editModal.classList.contains('show')) {
                // We're in Edit modal
                this.handlePaste(e, 'editPreviewImage', 'editImagePreview', 'editImage');
            }
        });

    }

    handleFileSelect(event, previewImgId, previewDivId, urlInputId) {
        const file = event.target.files[0];
        if (file) {
            this.processImageFile(file, previewImgId, previewDivId, urlInputId);
        }
    }

    handleDragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('dragover');
    }

    handleDragLeave(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragover');
    }

    handleDrop(event, previewImgId, previewDivId, urlInputId) {
        event.preventDefault();
        event.currentTarget.classList.remove('dragover');

        const files = event.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (file.type.startsWith('image/')) {
                this.processImageFile(file, previewImgId, previewDivId, urlInputId);
            } else {
                console.log('Invalid file type selected');
                // Show a subtle notification instead of alert
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-warning alert-dismissible fade show mt-2';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Bitte wähle eine gültige Bilddatei aus. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                event.target.parentNode.appendChild(errorDiv);
                setTimeout(() => errorDiv.remove(), 3000);
            }
        }
    }

    handlePaste(event, previewImgId, previewDivId, urlInputId) {
        const items = event.clipboardData?.items;
        if (!items) return;

        for (let item of items) {
            if (item.type.startsWith('image/')) {
                event.preventDefault();
                const file = item.getAsFile();
                if (file) {
                    this.processImageFile(file, previewImgId, previewDivId, urlInputId);
                }
                break;
            }
        }
    }

    handleUrlInput(url, previewImgId, previewDivId) {
        if (url && this.isValidImageUrl(url)) {
            this.showImagePreview(url, previewImgId, previewDivId);
        } else {
            this.hideImagePreview(previewDivId);
        }
    }

    processImageFile(file, previewImgId, previewDivId, urlInputId) {
        // Convert file to data URL for preview
        const reader = new FileReader();
        reader.onload = (e) => {
            const dataUrl = e.target.result;
            this.showImagePreview(dataUrl, previewImgId, previewDivId);
            // Update URL input with data URL
            const urlInput = document.getElementById(urlInputId);
            if (urlInput) {
                urlInput.value = dataUrl;
            }
            
            // Set the data URL in the hidden input or upload to server
            document.getElementById('image_url').value = dataUrl;
        };
        reader.readAsDataURL(file);
    }

    showImagePreview(imageSrc, previewImgId, previewDivId) {
        const preview = document.getElementById(previewDivId);
        const previewImg = document.getElementById(previewImgId);
        
        if (preview && previewImg) {
            previewImg.src = imageSrc;
            preview.style.display = 'block';
        }
    }

    hideImagePreview(previewDivId) {
        const preview = document.getElementById(previewDivId);
        if (preview) {
            preview.style.display = 'none';
        }
    }

    isValidImageUrl(url) {
        const imageExtensions = /\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i;
        return imageExtensions.test(url) || url.startsWith('data:image/');
    }
}

// Global function to clear image preview
function clearImagePreview() {
    const urlInput = document.getElementById('image_url');
    const fileInput = document.getElementById('imageFileInput');
    const preview = document.getElementById('imagePreview');
    
    if (urlInput) urlInput.value = '';
    if (fileInput) fileInput.value = '';
    if (preview) preview.style.display = 'none';
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ImageUploadHandler();
});