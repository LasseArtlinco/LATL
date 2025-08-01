/**
 * LATL Admin Integration
 * This file provides integration between the main admin interface and specialized components
 */

// Band Editor Integration
function initBandEditor() {
    // Clear any existing band data
    if (typeof resetBandEditor === 'function') {
        resetBandEditor();
    }

    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const pageId = urlParams.get('page');
    const bandId = urlParams.get('id');

    // Set page in the band editor
    if (pageId) {
        document.getElementById('band-page').value = pageId;
        
        // Disable page selection when editing an existing band
        if (bandId) {
            document.getElementById('band-page').disabled = true;
        } else {
            document.getElementById('band-page').disabled = false;
        }
    }

    // Load band if ID is provided
    if (bandId && pageId) {
        loadBandForEditing(pageId, bandId);
    } else {
        // Initialize a new band
        if (typeof initNewBand === 'function') {
            initNewBand();
        }
    }
}

// Load a band for editing
async function loadBandForEditing(pageId, bandId) {
    try {
        showLoading();
        
        const band = await fetchBand(pageId, bandId);
        
        if (!band) {
            showToast('Båndet blev ikke fundet', true);
            return;
        }
        
        // Set form values
        document.getElementById('band-type').value = band.band_type;
        document.getElementById('band-height').value = band.band_height;
        document.getElementById('band-order').value = band.band_order;
        
        // Toggle editor sections based on band type
        if (typeof toggleEditors === 'function') {
            toggleEditors();
        }
        
        // Load SEO data
        if (band.band_content && band.band_content.seo) {
            document.getElementById('seo-title').value = band.band_content.seo.title || '';
            document.getElementById('seo-description').value = band.band_content.seo.description || '';
            document.getElementById('seo-keywords').value = band.band_content.seo.keywords || '';
        }
        
        // Load specific data based on band type
        if (band.band_type === 'slideshow' && typeof loadSlideshowData === 'function') {
            loadSlideshowData(band);
        } else if (band.band_type === 'product' && typeof loadProductData === 'function') {
            loadProductData(band);
        }
        
        // Update the preview
        if (typeof updatePreview === 'function') {
            updatePreview();
        }
        
        // Store the current band ID
        window.currentBandId = bandId;
    } catch (error) {
        console.error('Error loading band for editing:', error);
        showToast('Fejl ved indlæsning af bånd', true);
    } finally {
        hideLoading();
    }
}

// Reset the band editor to its initial state
function resetBandEditor() {
    // Reset form values
    document.getElementById('band-type').value = 'slideshow';
    document.getElementById('band-height').value = '1';
    document.getElementById('band-order').value = '1';
    
    // Reset SEO fields
    document.getElementById('seo-title').value = '';
    document.getElementById('seo-description').value = '';
    document.getElementById('seo-keywords').value = '';
    
    // Reset slideshow container
    const slidesContainer = document.getElementById('slides-container');
    if (slidesContainer) {
        slidesContainer.innerHTML = '';
    }
    
    // Reset product fields
    document.getElementById('product-title').value = '';
    document.getElementById('product-subtitle').value = '';
    document.getElementById('product-link').value = '';
    document.getElementById('product-background').value = '#ffffff';
    document.getElementById('product-background-hex').value = '#ffffff';
    
    // Reset image previews
    const productImagePreview = document.getElementById('product-image-preview');
    if (productImagePreview) {
        productImagePreview.style.display = 'none';
    }
    
    // Reset current band ID
    window.currentBandId = null;
    
    // Toggle editors based on default type
    if (typeof toggleEditors === 'function') {
        toggleEditors();
    }
    
    // Add initial slide
    if (typeof addSlide === 'function') {
        if (slidesContainer && slidesContainer.children.length === 0) {
            addSlide();
        }
    }
    
    // Update preview
    if (typeof updatePreview === 'function') {
        updatePreview();
    }
}

// Initialize a new band
function initNewBand() {
    // Get the page ID
    const pageId = document.getElementById('band-page').value;
    
    // Get next available band order
    if (pageId) {
        fetchBands(pageId).then(bands => {
            // Set band order to next available position
            if (bands && bands.length > 0) {
                document.getElementById('band-order').value = bands.length + 1;
            } else {
                document.getElementById('band-order').value = 1;
            }
        });
    }
    
    // Reset the editor
    resetBandEditor();
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on the band editor page
    if (window.location.hash === '#band-editor') {
        initBandEditor();
    }
    
    // Add event listeners to cancel and save buttons in band editor
    const cancelButton = document.getElementById('cancel-button');
    const saveButton = document.getElementById('save-button');
    
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            // Navigate back to bands management
            const pageId = document.getElementById('band-page').value;
            
            if (pageId) {
                // Update URL
                const url = new URL(window.location);
                url.hash = 'bands-management';
                url.searchParams.set('page', pageId);
                url.searchParams.delete('id');
                window.history.pushState({}, '', url);
                
                // Switch to bands management section
                showSection('bands-management');
                
                // Update the active menu item
                document.querySelectorAll('.sidebar-menu-item').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelector('[data-section="bands-management"]').classList.add('active');
                
                // Load bands for the page
                loadBandsForPage(pageId);
            } else {
                // Just go back to bands management
                showSection('bands-management');
                
                // Update the active menu item
                document.querySelectorAll('.sidebar-menu-item').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelector('[data-section="bands-management"]').classList.add('active');
            }
        });
    }
    
    if (saveButton && typeof saveBand !== 'function') {
        // Define saveBand if it doesn't exist yet (from band-editor.js)
        saveButton.addEventListener('click', async function() {
            try {
                // Validate input
                const pageId = document.getElementById('band-page').value;
                if (!pageId) {
                    showToast('Vælg venligst en side', true);
                    return;
                }
                
                // Create FormData object for files
                const formData = new FormData();
                
                // Add band data
                const bandData = {
                    band_type: document.getElementById('band-type').value,
                    band_height: document.getElementById('band-height').value,
                    band_order: document.getElementById('band-order').value,
                    band_content: {}
                };
                
                // Add SEO data
                const seoTitle = document.getElementById('seo-title').value;
                const seoDescription = document.getElementById('seo-description').value;
                const seoKeywords = document.getElementById('seo-keywords').value;
                
                if (seoTitle || seoDescription || seoKeywords) {
                    bandData.band_content.seo = {
                        title: seoTitle,
                        description: seoDescription,
                        keywords: seoKeywords
                    };
                }
                
                // Collect data based on band type
                if (bandData.band_type === 'slideshow') {
                    // Collect slideshow data
                    const slidesData = [];
                    const slideElements = document.querySelectorAll('.slide-container');
                    
                    slideElements.forEach((slideElement, index) => {
                        const titleInput = slideElement.querySelector('.slide-title');
                        const subtitleInput = slideElement.querySelector('.slide-subtitle');
                        const linkInput = slideElement.querySelector('.slide-link');
                        const altInput = slideElement.querySelector('.slide-alt');
                        const imagePreview = slideElement.querySelector('.slide-image-preview');
                        
                        const slideData = {
                            position: index,
                            title: titleInput.value,
                            subtitle: subtitleInput.value,
                            link: linkInput.value,
                            alt: altInput.value
                        };
                        
                        // Add existing image if it exists
                        if (imagePreview.style.display !== 'none') {
                            const img = imagePreview.querySelector('img');
                            const imageUrl = img.src;
                            
                            // Only add image if it's not a data URL (new upload)
                            if (!imageUrl.startsWith('data:')) {
                                slideData.image = imageUrl;
                            }
                        }
                        
                        slidesData.push(slideData);
                        
                        // Add slide image to FormData if it exists
                        const slideImage = slideElement.querySelector('.slide-image').files[0];
                        if (slideImage) {
                            formData.append(`slide_images[${index}]`, slideImage);
                        }
                    });
                    
                    bandData.band_content.slides = slidesData;
                } else if (bandData.band_type === 'product') {
                    // Collect product data
                    bandData.band_content = {
                        title: document.getElementById('product-title').value,
                        subtitle: document.getElementById('product-subtitle').value,
                        link: document.getElementById('product-link').value,
                        background_color: document.getElementById('product-background').value
                    };
                    
                    // Add SEO data
                    if (seoTitle || seoDescription || seoKeywords) {
                        bandData.band_content.seo = {
                            title: seoTitle,
                            description: seoDescription,
                            keywords: seoKeywords
                        };
                    }
                    
                    // Add existing product image if it exists
                    const productImagePreview = document.getElementById('product-image-preview');
                    if (productImagePreview.style.display !== 'none') {
                        const img = productImagePreview.querySelector('img');
                        const imageUrl = img.src;
                        
                        // Only add image if it's not a data URL (new upload)
                        if (!imageUrl.startsWith('data:')) {
                            bandData.band_content.image = imageUrl;
                        }
                    }
                    
                    // Add product image to FormData if it exists
                    const productImage = document.getElementById('product-image').files[0];
                    if (productImage) {
                        formData.append('product_image', productImage);
                    }
                }
                
                // Add bandData as JSON to FormData
                formData.append('band_data', JSON.stringify(bandData));
                
                showLoading();
                
                let url, method;
                
                if (window.currentBandId) {
                    // Update existing band
                    url = `${API_URL}/bands/${pageId}/${window.currentBandId}`;
                    method = 'PUT';
                } else {
                    // Create new band
                    url = `${API_URL}/bands/${pageId}`;
                    method = 'POST';
                }
                
                const response = await fetch(url, {
                    method: method,
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    showToast('Båndet blev gemt');
                    
                    // Switch to bands management and reload bands
                    setTimeout(() => {
                        // Update URL
                        const url = new URL(window.location);
                        url.hash = 'bands-management';
                        url.searchParams.set('page', pageId);
                        url.searchParams.delete('id');
                        window.history.pushState({}, '', url);
                        
                        // Switch to bands management section
                        showSection('bands-management');
                        
                        // Update the active menu item
                        document.querySelectorAll('.sidebar-menu-item').forEach(item => {
                            item.classList.remove('active');
                        });
                        document.querySelector('[data-section="bands-management"]').classList.add('active');
                        
                        // Load bands for the page
                        loadBandsForPage(pageId);
                    }, 1000);
                } else {
                    showToast(result.message || 'Fejl ved lagring af bånd', true);
                }
            } catch (error) {
                console.error('Error saving band:', error);
                showToast('Fejl ved lagring af bånd', true);
            } finally {
                hideLoading();
            }
        });
    }
});
