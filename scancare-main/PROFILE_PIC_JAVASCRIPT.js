

<script>
    
    class ProfilePictureUploader {
        
        constructor() {
            
            this.container = document.getElementById('profilePictureContainer');
            this.imageElement = document.getElementById('profileImage');
            this.fileInput = document.getElementById('profileUploadInput');
            this.actionButtons = document.getElementById('actionButtons');
            this.saveButton = document.getElementById('saveButton');
            this.cancelButton = document.getElementById('cancelButton');
            this.validationMessage = document.getElementById('validationMessage');
            this.validationIcon = document.getElementById('validationIcon');
            this.validationText = document.getElementById('validationText');
            this.saveButtonText = document.getElementById('saveButtonText');
            
            
            this.originalImageSrc = this.imageElement.src;
            this.selectedFile = null;
            this.isUploading = false;
            
            
            this.config = {
                maxFileSize: 2 * 1024 * 1024, 
                allowedTypes: ['image/jpeg', 'image/png'],
                allowedExtensions: ['jpg', 'jpeg', 'png'],
                uploadEndpoint: 'upload_profile.php'
            };
            
            this.init();
        }
        
        
        init() {
            
            this.container.addEventListener('click', () => this.triggerFileInput());
            this.container.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.triggerFileInput();
                }
            });
            
            
            this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
            
            
            this.saveButton.addEventListener('click', () => this.handleSave());
            this.cancelButton.addEventListener('click', () => this.handleCancel());
        }
        
        
        triggerFileInput() {
            this.fileInput.click();
        }
        
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            
            
            if (!file) return;
            
            
            const validation = this.validateFile(file);
            
            if (!validation.isValid) {
                this.showValidationMessage(validation.message, 'error');
                this.fileInput.value = '';
                return;
            }
            
            
            this.validationMessage.classList.add('hidden');
            
            
            this.selectedFile = file;
            
            
            this.previewImage(file);
        }
        
        
        previewImage(file) {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                
                this.imageElement.src = e.target.result;
                this.imageElement.style.objectFit = 'cover';
                
                
                this.actionButtons.classList.remove('hidden');
                if (!this.actionButtons.classList.contains('visible')) {
                    this.actionButtons.classList.add('visible');
                }
            };
            
            reader.onerror = () => {
                this.showValidationMessage('❌ Error reading file. Please try again.', 'error');
                this.fileInput.value = '';
            };
            
            reader.readAsDataURL(file);
        }
        
        
        validateFile(file) {
            
            if (!this.config.allowedTypes.includes(file.type)) {
                return {
                    isValid: false,
                    message: '❌ Invalid file type. Please upload a JPG or PNG image.'
                };
            }
            
            
            const fileName = file.name.toLowerCase();
            const extension = fileName.split('.').pop();
            if (!this.config.allowedExtensions.includes(extension)) {
                return {
                    isValid: false,
                    message: '❌ Invalid file extension. Please upload a JPG or PNG image.'
                };
            }
            
            
            if (file.size > this.config.maxFileSize) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                return {
                    isValid: false,
                    message: `❌ File size (${sizeMB}MB) exceeds 2MB limit. Please choose a smaller image.`
                };
            }
            
            return { isValid: true, message: '' };
        }
        
        
        showValidationMessage(message, type) {
            this.validationText.textContent = message;
            
            
            this.validationMessage.classList.remove('hidden', 'success', 'error');
            this.validationIcon.classList.remove('fa-check-circle', 'fa-exclamation-circle', 'text-green-600', 'text-red-600');
            
            
            if (type === 'success') {
                this.validationMessage.classList.add('success');
                this.validationIcon.classList.add('fa-check-circle', 'text-green-600');
            } else {
                this.validationMessage.classList.add('error');
                this.validationIcon.classList.add('fa-exclamation-circle', 'text-red-600');
            }
        }
        
        
        async handleSave() {
            if (!this.selectedFile) return;
            
            
            this.isUploading = true;
            this.saveButton.disabled = true;
            this.cancelButton.disabled = true;
            
            
            this.saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Uploading...</span>';
            
            try {
                
                const formData = new FormData();
                formData.append('profile_pic', this.selectedFile);
                
                
                const response = await fetch(this.config.uploadEndpoint, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    
                    this.showValidationMessage('✓ Profile picture uploaded successfully!', 'success');
                    
                    
                    setTimeout(() => {
                        this.resetComponent();
                    }, 2000);
                } else {
                    
                    this.showValidationMessage(
                        `❌ Upload failed: ${data.error || 'Unknown error'}`,
                        'error'
                    );
                    this.resetUploadState();
                }
            } catch (error) {
                
                console.error('Upload error:', error);
                this.showValidationMessage('❌ Network error. Please try again.', 'error');
                this.resetUploadState();
            }
        }
        
        
        handleCancel() {
            this.resetComponent();
        }
        
        
        resetComponent() {
            
            this.imageElement.src = this.originalImageSrc;
            
            
            this.fileInput.value = '';
            
            
            this.selectedFile = null;
            
            
            this.actionButtons.classList.add('hidden');
            this.actionButtons.classList.remove('visible');
            
            
            this.validationMessage.classList.add('hidden');
            
            
            this.resetUploadState();
        }
        
        
        resetUploadState() {
            this.isUploading = false;
            this.saveButton.disabled = false;
            this.cancelButton.disabled = false;
            this.saveButton.innerHTML = '<i class="fa-solid fa-check"></i> <span id="saveButtonText">Save Changes</span>';
        }
    }
    
    
    document.addEventListener('DOMContentLoaded', () => {
        new ProfilePictureUploader();
    });
</script>

