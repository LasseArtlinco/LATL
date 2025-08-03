/**
 * upload.js - Håndtering af billedupload for LATL admin panel
 * Placeres i: public/js/upload.js
 */

// Initier upload-funktionalitet når DOM er klar
document.addEventListener('DOMContentLoaded', function() {
    setupUploadAreas();
});

/**
 * Opsætter alle upload-områder på siden
 */
function setupUploadAreas() {
    const uploadAreas = document.querySelectorAll('.upload-area');
    
    uploadAreas.forEach(area => {
        const targetId = area.dataset.target;
        const fileInput = document.getElementById(targetId + '_upload');
        const hiddenInput = document.getElementById(targetId);
        const progressBar = area.nextElementSibling.nextElementSibling;
        
        if (!fileInput) return;
        
        // Klik for at uploade
        area.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Drag and drop
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('drag-over');
        });
        
        area.addEventListener('dragleave', () => {
            area.classList.remove('drag-over');
        });
        
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0], area, hiddenInput, progressBar);
            }
        });
        
        // Fil valgt via input
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0], area, hiddenInput, progressBar);
            }
        });
    });
}

/**
 * Håndterer upload af en fil
 * 
 * @param {File} file - Filen der skal uploades
 * @param {HTMLElement} uploadArea - Upload-området
 * @param {HTMLInputElement} hiddenInput - Det skjulte input-felt til filsti
 * @param {HTMLElement} progressBar - Progress bar element
 */
function handleFileUpload(file, uploadArea, hiddenInput, progressBar) {
    // Valider filtype
    if (!file.type.startsWith('image/')) {
        alert('Kun billedfiler er tilladt');
        return;
    }
    
    // Vis progress bar
    if (progressBar) {
        progressBar.classList.add('active');
        const progressFill = progressBar.querySelector('.progress-bar-fill');
        progressFill.style.width = '0%';
    }
    
    // Opret FormData
    const formData = new FormData();
    formData.append('file', file); // VIGTIGT: parameter-navnet skal være 'file'
    
    // Korrekt tilføjelse af type og target
    const targetField = uploadArea.dataset.target;
    formData.append('type', targetField.includes('slide') ? 'slideshow' : 'product');
    formData.append('target', targetField);
    
    // Upload via AJAX
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable && progressBar) {
            const percentComplete = (e.loaded / e.total) * 100;
            const progressFill = progressBar.querySelector('.progress-bar-fill');
            progressFill.style.width = percentComplete + '%';
        }
    });
    
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                console.log('Server response:', response);
                
                if (response.success) {
                    // Opdater hidden input med filstien
                    hiddenInput.value = response.path.startsWith('/') ? 
                        response.path.substring(1) : response.path;
                    
                    // Vis preview
                    let preview = uploadArea.parentElement.querySelector('.image-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview';
                        uploadArea.parentElement.appendChild(preview);
                    }
                    
                    // Korrekt preview URL
                    preview.innerHTML = `<img src="${response.path}" alt="Preview">`;
                    
                    // Skjul progress bar
                    if (progressBar) {
                        setTimeout(() => {
                            progressBar.classList.remove('active');
                        }, 500);
                    }
                } else {
                    console.error('Upload error:', response);
                    alert('Upload fejlede: ' + (response.error || 'Ukendt fejl'));
                }
            } catch (e) {
                console.error('Parsing error:', e, xhr.responseText);
                alert('Upload fejlede: Kunne ikke læse server-svaret. Tjek konsollen for detaljer.');
            }
        } else {
            console.error('HTTP error:', xhr.status, xhr.statusText, xhr.responseText);
            alert('Upload fejlede: HTTP ' + xhr.status + ' ' + xhr.statusText);
        }
    });
    
    xhr.addEventListener('error', function(e) {
        console.error('Network error:', e);
        alert('Upload fejlede: Netværksfejl. Tjek din forbindelse og prøv igen.');
    });
    
    // Korrekt URL til upload API - dette fungerer fra admin-siden
    xhr.open('POST', '../api/upload.php');
    xhr.send(formData);
}
