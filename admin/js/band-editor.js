/**
 * LATL Bånd Editor JavaScript
 */

// DOM elementer
const bandTypeSelect = document.getElementById('band-type');
const bandHeightSelect = document.getElementById('band-height');
const bandOrderInput = document.getElementById('band-order');
const bandPageSelect = document.getElementById('band-page');
const slideshowEditor = document.getElementById('slideshow-editor');
const productEditor = document.getElementById('product-editor');
const addSlideButton = document.getElementById('add-slide');
const slidesContainer = document.getElementById('slides-container');
const slideTemplate = document.getElementById('slide-template');
const previewContainer = document.getElementById('preview-container');
const saveButton = document.getElementById('save-button');
const cancelButton = document.getElementById('cancel-button');
const productTitleInput = document.getElementById('product-title');
const productSubtitleInput = document.getElementById('product-subtitle');
const productLinkInput = document.getElementById('product-link');
const productBackgroundInput = document.getElementById('product-background');
const productBackgroundHexInput = document.getElementById('product-background-hex');
const productImageInput = document.getElementById('product-image');
const productImagePreview = document.getElementById('product-image-preview');
const seoTitleInput = document.getElementById('seo-title');
const seoDescriptionInput = document.getElementById('seo-description');
const seoKeywordsInput = document.getElementById('seo-keywords');

// Global state
let currentBandId = null;
let slideCount = 0;
let productImageFile = null;
let slideImageFiles = {};

// Event listeners
bandTypeSelect.addEventListener('change', toggleEditors);
addSlideButton.addEventListener('click', addSlide);
saveButton.addEventListener('click', saveBand);
cancelButton.addEventListener('click', goBack);
productImageInput.addEventListener('change', handleProductImageUpload);

// Farve event listeners
productBackgroundInput.addEventListener('input', () => {
    productBackgroundHexInput.value = productBackgroundInput.value;
    updatePreview();
});

productBackgroundHexInput.addEventListener('input', () => {
    if (/^#[0-9A-F]{6}$/i.test(productBackgroundHexInput.value)) {
        productBackgroundInput.value = productBackgroundHexInput.value;
        updatePreview();
    }
});

// Initialiser app
document.addEventListener('DOMContentLoaded', init);

/**
 * Initialiser editoren
 */
async function init() {
    // Indlæs sider fra API
    const pages = await fetchPages();
    
    // Opdater side-dropdown
    bandPageSelect.innerHTML = '<option value="">-- Vælg side --</option>';
    pages.forEach(page => {
        const option = document.createElement('option');
        option.value = page.page_id;
        option.textContent = page.page_id;
        bandPageSelect.appendChild(option);
    });
    
    // Indstil standard editor baseret på valgt båndtype
    toggleEditors();
    
    // Tilføj første slide som standard for slideshow
    if (slideCount === 0) {
        addSlide();
    }
    
    // Opdater forhåndsvisning
    updatePreview();
    
    // Tjek om vi redigerer et eksisterende bånd
    const bandId = getUrlParam('id');
    const pageId = getUrlParam('page');
    
    if (bandId && pageId) {
        currentBandId = bandId;
        bandPageSelect.value = pageId;
        bandPageSelect.disabled = true; // Lås side når vi redigerer
        
        await loadBand(pageId, bandId);
    }
}

/**
 * Skift mellem editorer baseret på båndtype
 */
function toggleEditors() {
    const bandType = bandTypeSelect.value;
    
    if (bandType === 'slideshow') {
        slideshowEditor.classList.remove('hidden-tab');
        productEditor.classList.add('hidden-tab');
    } else if (bandType === 'product') {
        slideshowEditor.classList.add('hidden-tab');
        productEditor.classList.remove('hidden-tab');
    }
    
    updatePreview();
}

/**
 * Tilføj et nyt slide
 */
function addSlide() {
    const slideIndex = slideCount++;
    const slideHtml = slideTemplate.innerHTML
        .replace(/{index}/g, slideIndex)
        .replace(/{index_human}/g, slideIndex + 1);
    
    const slideContainer = document.createElement('div');
    slideContainer.innerHTML = slideHtml;
    slidesContainer.appendChild(slideContainer.firstElementChild);
    
    // Tilføj event listeners til det nye slide
    const newSlide = slidesContainer.lastElementChild;
    const removeButton = newSlide.querySelector('.remove-slide');
    const moveUpButton = newSlide.querySelector('.move-slide-up');
    const moveDownButton = newSlide.querySelector('.move-slide-down');
    const slideImage = newSlide.querySelector('.slide-image');
    
    removeButton.addEventListener('click', () => removeSlide(newSlide));
    moveUpButton.addEventListener('click', () => moveSlide(newSlide, -1));
    moveDownButton.addEventListener('click', () => moveSlide(newSlide, 1));
    slideImage.addEventListener('change', (e) => handleSlideImageUpload(e, slideIndex));
    
    // Tilføj event listeners til alle input felter for at opdatere forhåndsvisningen
    newSlide.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', updatePreview);
    });
    
    updateSlideMoveButtons();
    updatePreview();
    
    return newSlide;
}

/**
 * Fjern et slide
 */
function removeSlide(slideElement) {
    if (slidesContainer.children.length <= 1) {
        showToast('Du skal have mindst ét slide', true);
        return;
    }
    
    slidesContainer.removeChild(slideElement);
    updateSlideMoveButtons();
    updatePreview();
}

/**
 * Flyt et slide op eller ned
 */
function moveSlide(slideElement, direction) {
    const slides = Array.from(slidesContainer.children);
    const currentIndex = slides.indexOf(slideElement);
    const newIndex = currentIndex + direction;
    
    if (newIndex < 0 || newIndex >= slides.length) {
        return;
    }
    
    if (direction < 0) {
        slidesContainer.insertBefore(slideElement, slides[newIndex]);
    } else {
        slidesContainer.insertBefore(slideElement, slides[newIndex].nextElementSibling);
    }
    
    updateSlideMoveButtons();
    updatePreview();
}

/**
 * Opdater op/ned knapper for slides
 */
function updateSlideMoveButtons() {
    const slides = Array.from(slidesContainer.children);
    
    slides.forEach((slide, index) => {
        const moveUpButton = slide.querySelector('.move-slide-up');
        const moveDownButton = slide.querySelector('.move-slide-down');
        
        moveUpButton.disabled = index === 0;
        moveDownButton.disabled = index === slides.length - 1;
    });
}

/**
 * Håndter upload af produkt billede
 */
function handleProductImageUpload(event) {
    const file = event.target.files[0];
    
    if (file) {
        productImageFile = file;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = productImagePreview.querySelector('img');
            img.src = e.target.result;
            productImagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        updatePreview();
    }
}

/**
 * Håndter upload af slide billede
 */
async function handleSlideImageUpload(event, slideIndex) {
    const file = event.target.files[0];
    
    if (file) {
        slideImageFiles[slideIndex] = file;
        
        const slideElement = event.target.closest('.slide-container');
        const preview = slideElement.querySelector('.slide-image-preview');
        const img = preview.querySelector('img');
        
        try {
            const dataUrl = await fileToDataUrl(file);
            img.src = dataUrl;
            preview.style.display = 'block';
            updatePreview();
        } catch (error) {
            console.error('Fejl ved læsning af billede:', error);
        }
    }
}

/**
 * Opdater forhåndsvisning
 */
function updatePreview() {
    const bandType = bandTypeSelect.value;
    
    if (bandType === 'slideshow') {
        updateSlideshowPreview();
    } else if (bandType === 'product') {
        updateProductPreview();
    }
}

/**
 * Opdater slideshow forhåndsvisning
 */
function updateSlideshowPreview() {
    const slides = Array.from(slidesContainer.children);
    
    let slideshowHtml = '<div class="slideshow-container">';
    
    slides.forEach((slideElement, index) => {
        const titleInput = slideElement.querySelector('.slide-title');
        const subtitleInput = slideElement.querySelector('.slide-subtitle');
        const linkInput = slideElement.querySelector('.slide-link');
        const imagePreview = slideElement.querySelector('.slide-image-preview img');
        
        const title = titleInput.value || '';
        const subtitle = subtitleInput.value || '';
        const link = linkInput.value || '';
        const imageSrc = imagePreview?.src || '';
        
        slideshowHtml += `<div class="slide" data-slide-index="${index}" style="display: ${index === 0 ? 'block' : 'none'}">`;
        
        if (link) {
            slideshowHtml += `<a href="${link}" class="slide-link">`;
        }
        
        if (imageSrc) {
            slideshowHtml += `<img src="${imageSrc}" alt="${title}" class="slide-image">`;
        } else {
            slideshowHtml += '<div class="placeholder-image" style="height: 300px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;"><span>Ingen billede valgt</span></div>';
        }
        
        slideshowHtml += '<div class="slide-content">';
        
        if (title) {
            slideshowHtml += `<h2 class="slide-title">${title}</h2>`;
        }
        
        if (subtitle) {
            slideshowHtml += `<p class="slide-subtitle">${subtitle}</p>`;
        }
        
        slideshowHtml += '</div>'; // .slide-content
        
        if (link) {
            slideshowHtml += '</a>';
        }
        
        slideshowHtml += '</div>'; // .slide
    });
    
    // Tilføj navigationsknapper hvis der er flere slides
    if (slides.length > 1) {
        slideshowHtml += '<div class="slideshow-controls">';
        slideshowHtml += '<button class="prev-slide" aria-label="Forrige slide">&#10094;</button>';
        slideshowHtml += '<button class="next-slide" aria-label="Næste slide">&#10095;</button>';
        
        // Tilføj prikker for hvert slide
        slideshowHtml += '<div class="slideshow-dots">';
        slides.forEach((_, index) => {
            slideshowHtml += `<button class="slideshow-dot ${index === 0 ? 'active' : ''}" data-slide="${index}" aria-label="Gå til slide ${index + 1}"></button>`;
        });
        slideshowHtml += '</div>'; // .slideshow-dots
        
        slideshowHtml += '</div>'; // .slideshow-controls
    }
    
    slideshowHtml += '</div>'; // .slideshow-container
    
    previewContainer.innerHTML = slideshowHtml;
    
    // Tilføj event listeners til preview navigation
    const prevButton = previewContainer.querySelector('.prev-slide');
    const nextButton = previewContainer.querySelector('.next-slide');
    const dots = previewContainer.querySelectorAll('.slideshow-dot');
    
    if (prevButton) {
        prevButton.addEventListener('click', () => {
            const currentSlide = previewContainer.querySelector('.slide[style*="display: block"]');
            const currentIndex = parseInt(currentSlide.dataset.slideIndex);
            const newIndex = (currentIndex - 1 + slides.length) % slides.length;
            showPreviewSlide(newIndex);
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', () => {
            const currentSlide = previewContainer.querySelector('.slide[style*="display: block"]');
            const currentIndex = parseInt(currentSlide.dataset.slideIndex);
            const newIndex = (currentIndex + 1) % slides.length;
            showPreviewSlide(newIndex);
        });
    }
    
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            const slideIndex = parseInt(dot.dataset.slide);
            showPreviewSlide(slideIndex);
        });
    });
}

/**
 * Vis et specifikt slide i forhåndsvisningen
 */
function showPreviewSlide(index) {
    const slides = previewContainer.querySelectorAll('.slide');
    const dots = previewContainer.querySelectorAll('.slideshow-dot');
    
    slides.forEach((slide, i) => {
        slide.style.display = i === index ? 'block' : 'none';
    });
    
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });
}

/**
 * Opdater produkt forhåndsvisning
 */
function updateProductPreview() {
    const title = productTitleInput.value || 'Produkttitel';
    const subtitle = productSubtitleInput.value || '';
    const link = productLinkInput.value || '';
    const backgroundColor = productBackgroundInput.value;
    const imageSrc = productImagePreview.style.display !== 'none' ? productImagePreview.querySelector('img').src : '';
    
    let productHtml = `<div class="product-container" style="background-color: ${backgroundColor};">`;
    
    if (link) {
        productHtml += `<a href="${link}" class="product-link">`;
    }
    
    productHtml += '<div class="product-image-container">';
    
    if (imageSrc) {
        productHtml += `<img src="${imageSrc}" alt="${title}" class="product-image">`;
    } else {
        productHtml += '<div class="placeholder-image" style="height: 200px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;"><span>Ingen billede valgt</span></div>';
    }
    
    productHtml += '</div>'; // .product-image-container
    
    productHtml += '<div class="product-info">';
    productHtml += `<h2 class="product-title">${title}</h2>`;
    
    if (subtitle) {
        productHtml += `<p class="product-subtitle">${subtitle}</p>`;
    }
    
    productHtml += '</div>'; // .product-info
    
    if (link) {
        productHtml += '</a>';
    }
    
    productHtml += '</div>'; // .product-container
    
    previewContainer.innerHTML = productHtml;
}

/**
 * Indlæs et specifikt bånd
 */
async function loadBand(pageId, bandId) {
    const band = await fetchBand(pageId, bandId);
    
    if (!band) {
        showToast('Båndet blev ikke fundet', true);
        return;
    }
    
    // Opdater formular med båndets data
    bandTypeSelect.value = band.band_type;
    bandHeightSelect.value = band.band_height;
    bandOrderInput.value = band.band_order;
    
    // Skift mellem editorer
    toggleEditors();
    
    // Indlæs SEO data
    if (band.band_content && band.band_content.seo) {
        seoTitleInput.value = band.band_content.seo.title || '';
        seoDescriptionInput.value = band.band_content.seo.description || '';
        seoKeywordsInput.value = band.band_content.seo.keywords || '';
    }
    
    // Indlæs specifikke data baseret på båndtype
    if (band.band_type === 'slideshow') {
        loadSlideshowData(band);
    } else if (band.band_type === 'product') {
        loadProductData(band);
    }
    
    // Opdater forhåndsvisning
    updatePreview();
}

/**
 * Indlæs data for et slideshow bånd
 */
function loadSlideshowData(band) {
    const slides = band.band_content.slides || [];
    
    // Ryd eksisterende slides
    slidesContainer.innerHTML = '';
    slideCount = 0;
    
    // Tilføj slides fra båndet
    slides.forEach(slideData => {
        const slideElement = addSlide();
        
        const titleInput = slideElement.querySelector('.slide-title');
        const subtitleInput = slideElement.querySelector('.slide-subtitle');
        const linkInput = slideElement.querySelector('.slide-link');
        const altInput = slideElement.querySelector('.slide-alt');
        const imagePreview = slideElement.querySelector('.slide-image-preview');
        const img = imagePreview.querySelector('img');
        
        if (slideData.link) {
            linkInput.value = slideData.link;
        }
        
        if (slideData.alt) {
            altInput.value = slideData.alt;
        }
        
        if (slideData.image) {
            img.src = slideData.image;
            imagePreview.style.display = 'block';
        }
    });
    
    // Hvis ingen slides blev tilføjet, tilføj et tomt slide
    if (slides.length === 0) {
        addSlide();
    }
}

/**
 * Indlæs data for et produkt bånd
 */
function loadProductData(band) {
    const content = band.band_content;
    
    if (content.title) {
        productTitleInput.value = content.title;
    }
    
    if (content.subtitle) {
        productSubtitleInput.value = content.subtitle;
    }
    
    if (content.link) {
        productLinkInput.value = content.link;
    }
    
    if (content.background_color) {
        productBackgroundInput.value = content.background_color;
        productBackgroundHexInput.value = content.background_color;
    }
    
    if (content.image) {
        const img = productImagePreview.querySelector('img');
        img.src = content.image;
        productImagePreview.style.display = 'block';
    }
}

/**
 * Gem båndet
 */
async function saveBand() {
    try {
        // Valider input
        if (!bandPageSelect.value) {
            showToast('Vælg venligst en side', true);
            return;
        }
        
        // Opret FormData objekt til filer
        const formData = new FormData();
        
        // Tilføj bånd data
        const bandData = {
            band_type: bandTypeSelect.value,
            band_height: bandHeightSelect.value,
            band_order: bandOrderInput.value,
            band_content: {}
        };
        
        // Tilføj SEO data
        if (seoTitleInput.value || seoDescriptionInput.value || seoKeywordsInput.value) {
            bandData.band_content.seo = {
                title: seoTitleInput.value,
                description: seoDescriptionInput.value,
                keywords: seoKeywordsInput.value
            };
        }
        
        // Indsaml data baseret på båndtype
        if (bandData.band_type === 'slideshow') {
            const slidesData = [];
            const slideElements = slidesContainer.querySelectorAll('.slide-container');
            
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
                
                // Tilføj eksisterende billede hvis det findes
                if (imagePreview.style.display !== 'none') {
                    const img = imagePreview.querySelector('img');
                    const imageUrl = img.src;
                    
                    // Kun tilføj billede hvis det ikke er en data URL (nyt upload)
                    if (!imageUrl.startsWith('data:')) {
                        slideData.image = imageUrl;
                    }
                }
                
                slidesData.push(slideData);
                
                // Tilføj slide billede til FormData hvis det findes
                const slideIndexStr = slideElement.dataset.slideIndex;
                if (slideImageFiles[slideIndexStr]) {
                    formData.append(`slide_images[${index}]`, slideImageFiles[slideIndexStr]);
                }
            });
            
            bandData.band_content.slides = slidesData;
        } else if (bandData.band_type === 'product') {
            bandData.band_content = {
                title: productTitleInput.value,
                subtitle: productSubtitleInput.value,
                link: productLinkInput.value,
                background_color: productBackgroundInput.value
            };
            
            // Tilføj SEO data
            if (seoTitleInput.value || seoDescriptionInput.value || seoKeywordsInput.value) {
                bandData.band_content.seo = {
                    title: seoTitleInput.value,
                    description: seoDescriptionInput.value,
                    keywords: seoKeywordsInput.value
                };
            }
            
            // Tilføj eksisterende billede hvis det findes
            if (productImagePreview.style.display !== 'none') {
                const img = productImagePreview.querySelector('img');
                const imageUrl = img.src;
                
                // Kun tilføj billede hvis det ikke er en data URL (nyt upload)
                if (!imageUrl.startsWith('data:')) {
                    bandData.band_content.image = imageUrl;
                }
            }
            
            // Tilføj produkt billede til FormData hvis det findes
            if (productImageFile) {
                formData.append('product_image', productImageFile);
            }
        }
        
        // Tilføj bandData som JSON til FormData
        formData.append('band_data', JSON.stringify(bandData));
        
        showLoading();
        
        let url, method;
        
        if (currentBandId) {
            // Opdaterer et eksisterende bånd
            url = `${API_URL}/bands/${bandPageSelect.value}/${currentBandId}`;
            method = 'PUT';
        } else {
            // Opretter et nyt bånd
            url = `${API_URL}/bands/${bandPageSelect.value}`;
            method = 'POST';
        }
        
        const response = await fetch(url, {
            method: method,
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showToast('Båndet blev gemt');
            
            // Redirect til båndoversigten
            setTimeout(() => {
                window.location.href = 'bands.html?page=' + bandPageSelect.value;
            }, 1500);
        } else {
            showToast(result.message || 'Fejl ved lagring af bånd', true);
        }
    } catch (error) {
        console.error('Fejl ved lagring af bånd:', error);
        showToast('Fejl ved lagring af bånd', true);
    } finally {
        hideLoading();
    }
}title) {
            titleInput.value = slideData.title;
        }
        
        if (slideData.subtitle) {
            subtitleInput.value = slideData.subtitle;
        }
        
        if (slideData.
