/**
 * band-editor.js - JavaScript til band-editor.php
 * Placeres i: public/js/band-editor.js
 */

document.addEventListener('DOMContentLoaded', function() {
    // Band type ændring
    const bandTypeSelect = document.getElementById('band_type');
    const bandTypeForms = document.querySelectorAll('.band-type-form');
    
    if (bandTypeSelect) {
        bandTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            
            bandTypeForms.forEach(form => {
                form.style.display = 'none';
            });
            
            if (selectedType) {
                document.getElementById(selectedType + '_form').style.display = 'block';
            }
        });
    }
    
    // Farveværktøj
    const colorPickers = document.querySelectorAll('.color-picker');
    colorPickers.forEach(picker => {
        picker.addEventListener('input', function() {
            const targetId = this.dataset.target;
            document.getElementById(targetId).value = this.value;
        });

        // Synkroniser input-felt med color-picker
        const targetId = picker.dataset.target;
        const inputField = document.getElementById(targetId);
        if (inputField) {
            inputField.addEventListener('input', function() {
                picker.value = this.value;
            });
        }
    });
    
    // Slideshow håndtering
    const addSlideBtn = document.getElementById('add_slide');
    const slideList = document.getElementById('slide_list');
    const slideCountInput = document.getElementById('slide_count');
    
    if (addSlideBtn && slideList && slideCountInput) {
        let slideCount = parseInt(slideCountInput.value) || 0;
        
        addSlideBtn.addEventListener('click', function() {
            const index = slideCount;
            const slideHtml = `
                <div class="slide-item" data-index="${index}">
                    <i class="slide-drag-handle fas fa-grip-vertical"></i>
                    <div class="slide-header">
                        <h4 class="slide-title">Slide ${index + 1}</h4>
                        <div class="slide-actions">
                            <button type="button" class="toggle-slide">Vis/skjul</button>
                            <button type="button" class="remove-slide">Fjern</button>
                        </div>
                    </div>
                    <div class="slide-content active">
                        <div class="form-group">
                            <label for="slide_${index}_image">Billede:</label>
                            <div class="upload-area" data-target="slide_${index}_image">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Klik for at uploade eller træk en fil hertil</p>
                            </div>
                            <input type="file" class="upload-input" id="slide_${index}_image_upload" accept="image/*">
                            <input type="hidden" name="slide_${index}_image" id="slide_${index}_image" value="">
                            <div class="upload-progress">
                                <div class="progress-bar">
                                    <div class="progress-bar-fill"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="slide_${index}_alt">Alt-tekst (SEO):</label>
                            <input type="text" name="slide_${index}_alt" id="slide_${index}_alt" value="">
                            <div class="help-text">Beskrivende tekst til billedet for SEO og tilgængelighed</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="slide_${index}_title">Titel:</label>
                            <input type="text" name="slide_${index}_title" id="slide_${index}_title" value="">
                        </div>
                        
                        <div class="form-group">
                            <label for="slide_${index}_subtitle">Undertitel:</label>
                            <input type="text" name="slide_${index}_subtitle" id="slide_${index}_subtitle" value="">
                        </div>
                        
                        <div class="form-group">
                            <label for="slide_${index}_link">Link:</label>
                            <input type="text" name="slide_${index}_link" id="slide_${index}_link" value="">
                        </div>

                        <div class="seo-section">
                            <h4>SEO-metadata</h4>
                            <div class="form-group">
                                <label for="slide_${index}_seo_title">SEO Titel:</label>
                                <input type="text" name="slide_${index}_seo_title" id="slide_${index}_seo_title" value="">
                                <div class="help-text">Titel til brug i struktureret data (JSON-LD)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="slide_${index}_seo_description">SEO Beskrivelse:</label>
                                <textarea name="slide_${index}_seo_description" id="slide_${index}_seo_description"></textarea>
                                <div class="help-text">Beskrivelse til brug i struktureret data og meta-tags</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            slideList.insertAdjacentHTML('beforeend', slideHtml);
            slideCount++;
            slideCountInput.value = slideCount;
            
            // Tilføj event listeners til den nye slide
            initSlideEvents();
            
            // Opdater upload-områder for den nye slide
            // setupUploadAreas() findes i upload.js
            setupUploadAreas();
            
            // Initialiser sortable på den nye slide-liste
            initSortableSlides();
        });
    }
    
    // Initialiser event listeners for slides
    function initSlideEvents() {
        document.querySelectorAll('.toggle-slide').forEach(btn => {
            btn.removeEventListener('click', toggleSlideContent);
            btn.addEventListener('click', toggleSlideContent);
        });
        
        document.querySelectorAll('.remove-slide').forEach(btn => {
            btn.removeEventListener('click', removeSlide);
            btn.addEventListener('click', removeSlide);
        });
    }
    
    // Toggle slide content
    function toggleSlideContent() {
        const content = this.closest('.slide-item').querySelector('.slide-content');
        content.classList.toggle('active');
        this.textContent = content.classList.contains('active') ? 'Skjul' : 'Vis';
    }
    
    // Fjern slide
    function removeSlide() {
        if (confirm('Er du sikker på, at du vil fjerne dette slide?')) {
            const slideItem = this.closest('.slide-item');
            slideItem.remove();
            updateSlideIndices();
        }
    }
    
    // Opdater slide indekser
    function updateSlideIndices() {
        const slides = slideList.querySelectorAll('.slide-item');
        slideCount = slides.length;
        slideCountInput.value = slideCount;
        
        slides.forEach((slide, i) => {
            slide.dataset.index = i;
            slide.querySelector('.slide-title').textContent = `Slide ${i + 1}`;
            
            // Opdater indekser i input-navne
            const inputs = slide.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.name;
                if (name) {
                    input.name = name.replace(/slide_\d+/, `slide_${i}`);
                    input.id = input.id.replace(/slide_\d+/, `slide_${i}`);
                }
            });
            
            // Opdater labels
            const labels = slide.querySelectorAll('label');
            labels.forEach(label => {
                const forAttr = label.getAttribute('for');
                if (forAttr) {
                    label.setAttribute('for', forAttr.replace(/slide_\d+/, `slide_${i}`));
                }
            });
            
            // Opdater upload area
            const uploadArea = slide.querySelector('.upload-area');
            if (uploadArea) {
                uploadArea.dataset.target = `slide_${i}_image`;
            }
        });
    }
    
    // Initialiser events
    initSlideEvents();
    
    // Visning/skjul af båndindhold
    document.querySelectorAll('.toggle-preview').forEach(btn => {
        btn.addEventListener('click', function() {
            const content = this.closest('.band-item').querySelector('.band-content');
            content.classList.toggle('active');
            const icon = this.querySelector('i');
            if (content.classList.contains('active')) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Sletning af bånd
    document.querySelectorAll('.band-action.delete').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Er du sikker på, at du vil slette dette bånd? Denne handling kan ikke fortrydes.')) {
                const bandId = this.dataset.id;
                document.getElementById('delete_band_id').value = bandId;
                document.getElementById('delete_form').submit();
            }
        });
    });
    
    // Drag-and-drop for slides
    function initSortableSlides() {
        const slideSortableList = document.querySelector('.sortable-slides');
        if (slideSortableList) {
            // Fjern eventuelle eksisterende sortable instanser
            if (slideSortableList._sortable) {
                slideSortableList._sortable.destroy();
            }
            
            slideSortableList._sortable = new Sortable(slideSortableList, {
                handle: '.slide-drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    updateSlideIndices();
                }
            });
        }
    }
    
    // Initialiser sortable for slides
    initSortableSlides();
    
    // Drag-and-drop rækkefølge for bånd
    const sortableList = document.querySelector('.sortable');
    if (sortableList) {
        const sortable = new Sortable(sortableList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                // Opdater rækkefølgen i databasen via AJAX
                const items = sortableList.querySelectorAll('.band-item');
                const bandOrders = {};
                
                items.forEach((item, index) => {
                    const bandId = item.dataset.id;
                    bandOrders[bandId] = index + 1;
                    
                    // Opdater den synlige rækkefølge
                    const title = item.querySelector('.band-title small');
                    if (title) {
                        const regex = /Rækkefølge: \d+/;
                        title.textContent = title.textContent.replace(regex, `Rækkefølge: ${index + 1}`);
                    }
                });
                
                // Send opdatering til server
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'band-editor.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.error) {
                                    alert('Fejl: ' + response.error);
                                }
                            } catch (e) {
                                console.error('Fejl ved parsing af svar:', e);
                            }
                        } else {
                            alert('Fejl ved opdatering af rækkefølge.');
                        }
                    }
                };
                
                // Brug pageId-variablen der blev defineret i band-editor.php
                xhr.send('action=update_order&page_id=' + encodeURIComponent(pageId) + '&band_orders=' + encodeURIComponent(JSON.stringify(bandOrders)));
            }
        });
    }
});
