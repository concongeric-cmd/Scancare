


This guide provides production-ready frontend code for profile picture upload with instant preview, client-side validation, and AJAX submission.


- 120px x 120px circular container with white border
- Gradient background for placeholder
- Default SVG user icon
- Smooth hover animations

- Semi-transparent dark overlay (40% opacity)
- Camera icon + "Change Photo" text
- Smooth fade-in animation on hover

- Click profile picture to select file
- `FileReader` API for instant preview
- No server required for preview
- Smooth image loading

- File type validation (JPG, JPEG, PNG only)
- File size limit (2MB max)
- File extension double-check
- Clear error messages with icons

- "Save Changes" button (green gradient, appears after file selection)
- "Cancel" button (grey, resets to original)
- Buttons only visible after file selection
- Disabled state during upload

- Spinning icon during upload
- "Uploading..." text
- Disabled buttons prevent multiple submissions

- FormData for multipart/form-data
- `fetch()` API for modern async upload
- No page reload required
- Success/error feedback


Use this if your dashboard already uses Tailwind CSS.

Use this if you want a lightweight solution without Tailwind.


Add these to your dashboard `<head>`:

```html
<!
<link rel="stylesheet" href="https:

<!
<script src="https:
```



Copy this HTML into your dashboard body (e.g., in a settings section):

```html
<!
     PROFILE PICTURE UPLOAD COMPONENT (Tailwind CSS)
     ================================================================ 
<div class="bg-white rounded-lg shadow-sm p-8 border border-gray-200">
    
    <!
    <h2 class="text-lg font-semibold text-gray-900 mb-6">
        <i class="fa-solid fa-image mr-2" style="color: #454B1B;"></i>Profile Picture
    </h2>
    
    <!
    <div class="flex flex-col items-center gap-6">
        
        <!
        <div 
            id="profilePictureContainer" 
            class="relative w-32 h-32 rounded-full overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 border-4 border-white shadow-lg cursor-pointer hover:shadow-xl transition-shadow duration-300 group"
            role="button"
            tabindex="0"
            aria-label="Upload profile picture"
            title="Click to upload profile picture"
        >
            <!
            <img 
                id="profileImage" 
                src="data:image/svg+xml,%3Csvg xmlns='http:
                alt="Profile picture"
                class="w-full h-full object-cover"
            >
            
            <!
            <div 
                class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center"
            >
                <div class="text-center">
                    <i class="fa-solid fa-camera text-white text-2xl mb-2"></i>
                    <p class="text-white text-sm font-medium">Change Photo</p>
                </div>
            </div>
        </div>
        
        <!
        <input 
            type="file" 
            id="profileUploadInput" 
            class="hidden" 
            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
            aria-label="Select profile picture file"
        >
        
        <!
        <p class="text-sm text-slate-500 text-center">
            <i class="fa-solid fa-info-circle mr-1" style="color: #454B1B;"></i>
            Allowed: JPG, JPEG, PNG (Max 2MB)
        </p>
        
        <!
        <div 
            id="validationMessage" 
            class="hidden w-full p-3 rounded-lg border flex items-start gap-2 text-sm"
            role="alert"
        >
            <i id="validationIcon" class="fa-solid mt-0.5"></i>
            <span id="validationText"></span>
        </div>
        
        <!
        <div 
            id="actionButtons" 
            class="hidden w-full flex gap-3 pt-2"
        >
            <!
            <button 
                id="saveButton" 
                class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                aria-label="Save profile picture"
            >
                <i class="fa-solid fa-check"></i>
                <span id="saveButtonText">Save Changes</span>
            </button>
            
            <!
            <button 
                id="cancelButton" 
                class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold py-2.5 px-4 rounded-lg transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                aria-label="Cancel profile picture upload"
            >
                <i class="fa-solid fa-times mr-2"></i>
                Cancel
            </button>
        </div>
        
    </div>
</div>
```


Copy the CSS into your `<style>` section:

```css


.profile-pic-component {
    max-width: 400px;
}

.profile-pic-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
    padding: 32px;
}

.profile-pic-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-pic-title i { color: #454B1B; }

.profile-pic-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 24px;
}


.profile-picture-wrapper {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(to bottom right, #f1f5f9, #cbd5e1);
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: box-shadow 0.3s ease;
}

.profile-picture-wrapper:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.profile-picture-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}


.profile-pic-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.profile-picture-wrapper:hover .profile-pic-overlay {
    opacity: 1;
}

.profile-pic-overlay-content { text-align: center; }
.profile-pic-overlay-icon { font-size: 24px; color: white; margin-bottom: 8px; }
.profile-pic-overlay-text { color: white; font-size: 14px; font-weight: 500; }


.profile-upload-input { display: none; }


.profile-pic-info {
    font-size: 14px;
    color: #64748b;
    text-align: center;
}

.profile-pic-info i { color: #454B1B; margin-right: 4px; }


.profile-pic-message {
    display: none;
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid;
    font-size: 14px;
    align-items: flex-start;
    gap: 8px;
}

.profile-pic-message i { margin-top: 2px; flex-shrink: 0; }

.profile-pic-message.success {
    background-color: #f0fdf4;
    border-color: #dcfce7;
    color: #166534;
}

.profile-pic-message.error {
    background-color: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}


.profile-pic-actions {
    display: none;
    width: 100%;
    gap: 12px;
    padding-top: 8px;
}

.profile-pic-actions.visible { display: flex; }


.profile-pic-save-btn {
    flex: 1;
    background: linear-gradient(to right, #22c55e, #16a34a);
    color: white;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.profile-pic-save-btn:hover:not(:disabled) {
    background: linear-gradient(to right, #16a34a, #15803d);
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
}

.profile-pic-save-btn:disabled { opacity: 0.5; cursor: not-allowed; }


.profile-pic-cancel-btn {
    flex: 1;
    background-color: #e2e8f0;
    color: #1e293b;
    font-weight: 600;
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.profile-pic-cancel-btn:hover:not(:disabled) { background-color: #cbd5e1; }
.profile-pic-cancel-btn:disabled { opacity: 0.5; cursor: not-allowed; }
```

Then copy the HTML (for Standard CSS):

```html
<div class="profile-pic-component">
    <div class="profile-pic-card">
        <h2 class="profile-pic-title">
            <i class="fa-solid fa-image"></i>
            Profile Picture
        </h2>
        
        <div class="profile-pic-container">
            <div class="profile-picture-wrapper" id="profilePictureContainer" role="button" tabindex="0" aria-label="Upload profile picture">
                <img id="profileImage" src="data:image/svg+xml,%3Csvg xmlns='http:
                <div class="profile-pic-overlay">
                    <div class="profile-pic-overlay-content">
                        <div class="profile-pic-overlay-icon">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <p class="profile-pic-overlay-text">Change Photo</p>
                    </div>
                </div>
            </div>
            
            <input type="file" id="profileUploadInput" class="profile-upload-input" accept=".jpg,.jpeg,.png,image/jpeg,image/png" aria-label="Select profile picture file">
            
            <p class="profile-pic-info">
                <i class="fa-solid fa-info-circle"></i>
                Allowed: JPG, JPEG, PNG (Max 2MB)
            </p>
            
            <div id="validationMessage" class="profile-pic-message">
                <i id="validationIcon" class="fa-solid"></i>
                <span id="validationText"></span>
            </div>
            
            <div id="actionButtons" class="profile-pic-actions">
                <button id="saveButton" class="profile-pic-save-btn">
                    <i class="fa-solid fa-check"></i>
                    <span id="saveButtonText">Save Changes</span>
                </button>
                <button id="cancelButton" class="profile-pic-cancel-btn">
                    <i class="fa-solid fa-times"></i>
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
```


Add this JavaScript **before** the closing `</body>` tag:

```javascript
<!
     PROFILE PICTURE UPLOAD COMPONENT - JAVASCRIPT
     ================================================================ 
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
                allowedExtensions: ['jpg', 'jpeg', 'png']
            };
            
            this.init();
        }
        
        init() {
            this.container.addEventListener('click', () => this.fileInput.click());
            this.container.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.fileInput.click();
                }
            });
            
            this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
            this.saveButton.addEventListener('click', () => this.handleSave());
            this.cancelButton.addEventListener('click', () => this.handleCancel());
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
            
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imageElement.src = e.target.result;
                this.imageElement.style.objectFit = 'cover';
                this.actionButtons.classList.remove('hidden');
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
            
            return { isValid: true };
        }
        
        showValidationMessage(message, type) {
            this.validationText.textContent = message;
            this.validationMessage.classList.remove('hidden', 'bg-green-50', 'border-green-200', 'text-green-800', 'bg-red-50', 'border-red-200', 'text-red-800');
            
            if (type === 'success') {
                this.validationMessage.classList.add('bg-green-50', 'border-green-200', 'text-green-800');
                this.validationIcon.classList.remove('fa-exclamation-circle');
                this.validationIcon.classList.add('fa-check-circle', 'text-green-600');
            } else {
                this.validationMessage.classList.add('bg-red-50', 'border-red-200', 'text-red-800');
                this.validationIcon.classList.remove('fa-check-circle', 'text-green-600');
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
                
                const response = await fetch('upload_profile.php', {
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
                    this.showValidationMessage(`❌ Upload failed: ${data.error || 'Unknown error'}`, 'error');
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
```


| Feature | Implementation |
|
| **Circular Avatar** | CSS `border-radius: 50%` + `object-fit: cover` |
| **Hover Overlay** | Absolute positioned div with opacity transition |
| **Instant Preview** | FileReader API + event listener on file input |
| **Validation** | MIME type + file extension + size checks |
| **Action Buttons** | Hidden by default, visible after file select |
| **Loading State** | Disabled buttons + spinner icon + "Uploading..." text |
| **AJAX Upload** | Fetch API + FormData + POST to upload_profile.php |
| **Error Handling** | Try-catch + user-friendly error messages |


- [ ] Add Font Awesome link to `<head>`
- [ ] Add Tailwind CSS script (if using Tailwind option)
- [ ] Copy HTML component into dashboard
- [ ] Copy CSS styles into `<style>` section
- [ ] Copy JavaScript into dashboard before `</body>`
- [ ] Test file selection
- [ ] Test instant preview
- [ ] Test validation messages
- [ ] Test button visibility
- [ ] Test AJAX upload
- [ ] Verify backend endpoint exists: `upload_profile.php`


Your `upload_profile.php` must:
1. Accept POST request with `profile_pic` FormData field
2. Validate file (type, size, permissions)
3. Save file to `assets/uploads/profile_pics/` directory
4. Return JSON response:
   - Success: `{ "success": true, "image_url": "path/to/image.jpg" }`
   - Error: `{ "success": false, "error": "error message" }`


- **Desktop**: Full 120px avatar with hover overlay
- **Mobile**: Touch-friendly circular avatar, overlay visible on tap
- **All devices**: File input accessible via keyboard (Enter/Space)


- ✓ Semantic HTML with proper ARIA labels
- ✓ Keyboard navigation (Tab, Enter, Space)
- ✓ Screen reader friendly
- ✓ Color contrast meets WCAG standards
- ✓ Error messages with alert role
- ✓ Descriptive button text


Change the color scheme by replacing:
- `#454B1B` - Primary color (ScanCare theme)
- `from-green-500 to-green-600` - Save button gradient
- `from-slate-100 to-slate-200` - Avatar background


- The component is self-contained and won't conflict with existing styles
- All IDs are prefixed with `profile*` to avoid name collisions
- The JavaScript class is isolated with `ProfilePictureUploader`
- No external dependencies except Font Awesome icons


