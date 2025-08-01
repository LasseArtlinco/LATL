/**
 * LATL Admin Integration
 * This file provides integration between the main admin interface and specialized components
 * without redefining functions already available in admin-common.js and band-editor.js
 */

// Global variables for integration
let bandEditorInitialized = false;

// Function to switch between sections
function showSection(sectionId) {
    const contentSections = document.querySelectorAll('.content-section');
    contentSections.forEach(section => section.classList.remove('active'));
    
    const section = document.getElementById(sectionId + '-section');
    if (section) {
        section.classList.add('active');
        
        // Update URL hash
        window.location.hash = sectionId;
        
        // Initialize specific sections if needed
        if (sectionId === 'bands-management') {
            loadPagesForBands();
        } else if (sectionId === 'band-editor' && !bandEditorInitialized) {
            initBandEditor();
        }
    }
}

// Load pages for bands management section
async function loadPagesForBands() {
    try {
        showLoading();
        
        const pages = await fetchPages();
        
        // Update band page dropdown
        const bandPageSelect = document.getElementById('band-page-select');
        
        // Clear existing options
        bandPageSelect.innerHTML = '<option value="">-- Vælg side --</option>';
        
        // Add pages to dropdown
        pages.forEach(page => {
            if (page.page_id !== 'global') {
                const option = document.createElement('option');
                option.value = page.page_id;
                option.textContent = page.page_id;
                bandPageSelect.appendChild(option);
            }
        });
        
        // Check URL for page selection
        const urlParams = new URLSearchParams(window.location.search);
        const pageId = urlParams.get('page');
        
        if (pageId) {
            bandPageSelect.value = pageId;
            loadBandsForPage(pageId);
        }
    } catch (error) {
        console.error('Error loading pages for bands:', error);
        showToast('Fejl ved indlæsning af sider', true);
    } finally {
        hideLoading();
    }
}

// Load bands for a specific page in the band management section
async function loadBandsForPage(pageId) {
    if (!pageId) {
        document.getElementById('bands-container').style.display = 'none';
        document.getElementById('no-bands-message').style.display = 'none';
        return;
    }
    
    try {
        showLoading();
        
        const bands = await fetchBands(pageId);
        
        // Update URL with page selection
        const url = new URL(window.location);
        url.searchParams.set('page', pageId);
        window.history.pushState({}, '', url);
        
        if (bands.length === 0) {
            document.getElementById('bands-container').style.display = 'none';
            document.getElementById('no-bands-message').style.display = 'block';
        } else {
            renderBands(bands, pageId);
            document.getElementById('bands-container').style.display = 'block';
            document.getElementById('no-bands-message').style.display = 'none';
        }
    } catch (error) {
        console.error('Error loading bands:', error);
        showToast('Fejl ved indlæsning af bånd', true);
        document.getElementById('bands-container').style.display = 'none';
        document.getElementById('no-bands-message').style.display = 'block';
    } finally {
        hideLoading();
    }
}

// Render bands in the band management section
function renderBands(bands, pageId) {
    // Clear bands list
    const bandsList = document.getElementById('bands-list');
    bandsList.innerHTML = '';
    
    // Sort bands by order
    bands.sort((a, b) => a.band_order - b.band_order);
    
    // Create HTML for each band
    bands.forEach((band, index) => {
        const bandEl = document.createElement('div');
        bandEl.className = 'band-card';
        bandEl.dataset.bandId = band.band_id;
        
        // Band type icon/image
        let typeIcon = '🖼️';
        if (band.band_type === 'product') {
            typeIcon = '🛍️';
        } else if (band.band_type === 'html') {
            typeIcon = '📝';
        } else if (band.band_type === 'related_products') {
            typeIcon = '🔄';
        } else if (band.band_type === 'link') {
            typeIcon = '🔗';
        } else if (band.band_type === 'cta') {
            typeIcon = '📢';
        } else if (band.band_type === 'product_card') {
            typeIcon = '📇';
        } else if (band.band_type === 'product_full') {
            typeIcon = '📦';
        }
        
        // Band content preview
        let contentPreview = '';
        if (band.band_type === 'slideshow') {
            const slides = band.band_content.slides || [];
            contentPreview = `${slides.length} slide(s)`;
        } else if (band.band_type === 'product') {
            const title = band.band_content.title || 'Uden titel';
            contentPreview = title;
        } else if (band.band_type === 'html') {
            contentPreview = 'HTML indhold';
        } else {
            contentPreview = band.band_type;
        }
        
        // Band HTML
        bandEl.innerHTML = `
            <div class="band-card-preview">
                ${typeIcon}
                <span class="band-card-badge">${band.band_height}x højde</span>
            </div>
            <div class="band-card-body">
                <h3 class="band-card-title">${band.band_type.charAt(0).toUpperCase() + band.band_type.slice(1)}</h3>
                <div class="band-card-meta">
                    <div>Position: ${band.band_order}</div>
                    <div>${contentPreview}</div>
                </div>
                <div class="band-card-actions">
                    <button class="action-button edit-band-btn" data-band-id="${band.band_id}">Rediger</button>
                    <button class="action-button move-up-btn" data-band-id="${band.band_id}" ${index === 0 ? 'disabled' : ''}>↑</button>
                    <button class="action-button move-down-btn" data-band-id="${band.band_id}" ${index === bands.length - 1 ? 'disabled' : ''}>↓</button>
                    <button class="action-button danger delete-band-btn" data-band-id="${band.band_id}">Slet</button>
                </div>
            </div>
        `;
        
        bandsList.appendChild(bandEl);
        
        // Add event listeners to buttons
        const editBtn = bandEl.querySelector('.edit-band-btn');
        const moveUpBtn = bandEl.querySelector('.move-up-btn');
        const moveDownBtn = bandEl.querySelector('.move-down-btn');
        const deleteBtn = bandEl.querySelector('.delete-band-btn');
        
        editBtn.addEventListener('click', () => switchToBandEditor(band.band_id, pageId));
        moveUpBtn.addEventListener('click', () => moveBand(band.band_id, -1, pageId));
        moveDownBtn.addEventListener('click', () => moveBand(band.band_id, 1, pageId));
        deleteBtn.addEventListener('click', () => deleteBand(band.band_id, pageId));
    });
}

// Function to switch to the band editor for a specific band
function switchToBandEditor(bandId, pageId) {
    // Set URL parameters
    const url = new URL(window.location);
    url.hash = 'band-editor';
    url.searchParams.set('page', pageId);
    if (bandId) {
        url.searchParams.set('id', bandId);
    } else {
        url.searchParams.delete('id');
    }
    window.history.pushState({}, '', url);
    
    // Switch to band editor section
    showSection('band-editor');
    
    // Update the active menu item
    document.querySelectorAll('.sidebar-menu-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector('[data-section="band-editor"]').classList.add('active');
    
    // Initialize the band editor
    initBandEditor();
}

// Add new band from band management section
function addNewBand() {
    const selectedPageId = document.getElementById('band-page-select').value;
    
    if (!selectedPageId) {
        showToast('Vælg venligst en side først', true);
        return;
    }
    
    // Switch to band editor for a new band
    switchToBandEditor(null, selectedPageId);
}

// Move a band up or down in the order
async function moveBand(bandId, direction, pageId) {
    try {
        showLoading();
        
        // Get all bands
        const bands = await fetchBands(pageId);
        
        // Find the band and its neighbor
        let bandIndex = -1;
        let band = null;
        
        for (let i = 0; i < bands.length; i++) {
            if (bands[i].band_id === bandId) {
                bandIndex = i;
                band = bands[i];
                break;
            }
        }
        
        if (bandIndex === -1 || !band) {
            showToast('Bånd ikke fundet', true);
            return;
        }
        
        const newIndex = bandIndex + direction;
        
        // Check if the new index is valid
        if (newIndex < 0 || newIndex >= bands.length) {
            return;
        }
        
        const neighborBand = bands[newIndex];
        
        // Swap the order
        const tempOrder = band.band_order;
        band.band_order = neighborBand.band_order;
        neighborBand.band_order = tempOrder;
        
        // Update both bands
        await fetch(`${API_URL}/bands/${pageId}/${band.band_id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ band_order: band.band_order })
        });
        
        await fetch(`${API_URL}/bands/${pageId}/${neighborBand.band_id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ band_order: neighborBand.band_order })
        });
        
        // Reload bands
        await loadBandsForPage(pageId);
        
        showToast('Båndets position blev ændret');
    } catch (error) {
        console.error('Error moving band:', error);
        showToast('Fejl ved flytning af bånd', true);
    } finally {
        hideLoading();
    }
}

// Delete a band
async function deleteBand(bandId, pageId) {
    if (!confirm('Er du sikker på, at du vil slette dette bånd?')) {
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch(`${API_URL}/bands/${pageId}/${bandId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showToast('Båndet blev slettet');
            await loadBandsForPage(pageId);
        } else {
            showToast(result.message || 'Fejl ved sletning af bånd', true);
        }
    } catch (error) {
        console.error('Error deleting band:', error);
        showToast('Fejl ved sletning af bånd', true);
    } finally {
        hideLoading();
    }
}

// Initialize the band editor
function initBandEditor() {
    bandEditorInitialized = true;
    
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const pageId = urlParams.get('page');
    const bandId = urlParams.get('id');
    
    // Set page in band editor
    if (pageId) {
        document.getElementById('band-page').value = pageId;
        
        // Disable page selection when editing existing band
        document.getElementById('band-page').disabled = bandId ? true : false;
    }
    
    // Handle cancel button
    document.getElementById('cancel-button').addEventListener('click', function() {
        // Switch back to bands management
        const pageId = document.getElementById('band-page').value;
        if (pageId) {
            // Update URL
            const url = new URL(window.location);
            url.hash = 'bands-management';
            url.searchParams.set('page', pageId);
            url.searchParams.delete('id');
            window.history.pushState({}, '', url);
            
            // Switch to bands management
            showSection('bands-management');
            
            // Update active menu item
            document.querySelectorAll('.sidebar-menu-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector('[data-section="bands-management"]').classList.add('active');
            
            // Load bands for the page
            loadBandsForPage(pageId);
        } else {
            showSection('bands-management');
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners for sidebar menu items
    document.querySelectorAll('.sidebar-menu-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Remove active class from all menu items
            document.querySelectorAll('.sidebar-menu-item').forEach(menuItem => {
                menuItem.classList.remove('active');
            });
            
            // Add active class to selected menu item
            item.classList.add('active');
            
            // Show selected section
            const sectionId = item.dataset.section;
            showSection(sectionId);
        });
    });
    
    // Set up event listeners for submenu toggles
    document.querySelectorAll('.sidebar-submenu-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const submenuId = toggle.dataset.submenu;
            const submenu = document.getElementById(submenuId + '-submenu');
            
            // Toggle expanded class
            submenu.classList.toggle('expanded');
            toggle.classList.toggle('active');
        });
    });
    
    // Set up event listener for add band button
    document.getElementById('add-band-button').addEventListener('click', addNewBand);
    
    // Set up event listener for band page select
    document.getElementById('band-page-select').addEventListener('change', function() {
        loadBandsForPage(this.value);
    });
    
    // Set up event listener for view bands button
    document.getElementById('viewBandsButton').addEventListener('click', () => {
        const pageId = document.getElementById('currentPageId').value;
        if (pageId) {
            document.getElementById('band-page-select').value = pageId;
            
            // Switch to bands management
            document.querySelectorAll('.sidebar-menu-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector('[data-section="bands-management"]').classList.add('active');
            
            // Expand bands submenu
            const bandsSubmenu = document.getElementById('bands-submenu');
            if (!bandsSubmenu.classList.contains('expanded')) {
                bandsSubmenu.classList.add('expanded');
                document.querySelector('[data-submenu="bands"]').classList.add('active');
            }
            
            showSection('bands-management');
            loadBandsForPage(pageId);
        }
    });
    
    // Check URL hash for initial section
    if (window.location.hash) {
        const sectionId = window.location.hash.substring(1);
        const menuItem = document.querySelector(`[data-section="${sectionId}"]`);
        
        if (menuItem) {
            // Update active menu item
            document.querySelectorAll('.sidebar-menu-item').forEach(item => {
                item.classList.remove('active');
            });
            menuItem.classList.add('active');
            
            // Show section
            showSection(sectionId);
            
            // If on band-editor section, initialize it
            if (sectionId === 'band-editor') {
                initBandEditor();
            }
        }
    }
});
