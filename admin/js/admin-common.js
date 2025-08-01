/**
 * LATL Admin Common JavaScript Functions
 */

// API URL konfiguration - juster efter din serveropsætning
const API_URL = '/api';

// Global loading state
let isLoading = false;

/**
 * Vis loading spinner
 */
function showLoading() {
    document.querySelector('.loading').classList.add('show');
    isLoading = true;
}

/**
 * Skjul loading spinner
 */
function hideLoading() {
    document.querySelector('.loading').classList.remove('show');
    isLoading = false;
}

/**
 * Vis toast besked
 * @param {string} message - Besked til at vise
 * @param {boolean} isError - Om beskeden er en fejl
 */
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = isError ? 'toast error show' : 'toast show';
    
    setTimeout(() => {
        toast.className = toast.className.replace('show', '');
    }, 3000);
}

/**
 * Sikker fetch funktion med fejlhåndtering
 * @param {string} url - URL at fetche
 * @param {Object} options - Fetch options
 * @returns {Promise<Object>} - API svar
 */
async function safeFetch(url, options = {}) {
    try {
        showLoading();
        
        const response = await fetch(url, options);
        
        // Håndter HTTP fejl
        if (!response.ok) {
            let errorMsg = `HTTP error ${response.status}`;
            try {
                // Forsøg at parse fejlbesked
                const errorData = await response.json();
                if (errorData && errorData.message) {
                    errorMsg = errorData.message;
                }
            } catch (e) {
                // Hvis responsen ikke er JSON, brug tekst
                try {
                    errorMsg = await response.text();
                } catch (e2) {
                    // Hvis alt fejler, brug standard fejlbesked
                }
            }
            throw new Error(errorMsg);
        }
        
        // Håndter tomme svar
        const text = await response.text();
        if (!text) {
            return { status: 'success', data: null };
        }
        
        // Parse JSON svar
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e, 'Response text:', text);
            throw new Error(`Invalid JSON response: ${e.message}`);
        }
    } catch (error) {
        console.error('API Error:', error);
        showToast('Fejl ved kommunikation med serveren: ' + error.message, true);
        throw error;
    } finally {
        hideLoading();
    }
}

/**
 * Hent sider fra API
 * @returns {Promise<Array>} Array af sider
 */
async function fetchPages() {
    try {
        const result = await safeFetch(`${API_URL}/layout`);
        
        if (result.status === 'success') {
            return result.data || [];
        } else {
            throw new Error(result.message || 'Der skete en fejl ved hentning af sider');
        }
    } catch (error) {
        console.error('Fejl ved hentning af sider:', error);
        showToast('Fejl ved hentning af sider: ' + error.message, true);
        return [];
    }
}

/**
 * Hent et specifikt bånd fra API
 * @param {string} pageId - Side ID
 * @param {string} bandId - Bånd ID
 * @returns {Promise<Object>} Bånd-objekt
 */
async function fetchBand(pageId, bandId) {
    try {
        const result = await safeFetch(`${API_URL}/bands/${pageId}/${bandId}`);
        
        if (result.status === 'success') {
            return result.data || null;
        } else {
            throw new Error(result.message || 'Der skete en fejl ved hentning af bånd');
        }
    } catch (error) {
        console.error('Fejl ved hentning af bånd:', error);
        showToast('Fejl ved hentning af bånd: ' + error.message, true);
        return null;
    }
}

/**
 * Konverter fil til data URL
 * @param {File} file - Fil objekt
 * @returns {Promise<string>} Data URL
 */
function fileToDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            resolve(e.target.result);
        };
        
        reader.onerror = function(e) {
            reject(e);
        };
        
        reader.readAsDataURL(file);
    });
}

/**
 * Gå tilbage til forrige side eller specificeret URL
 * @param {string} url - URL at gå til (valgfri)
 */
function goBack(url) {
    if (url) {
        window.location.href = url;
    } else {
        window.history.back();
    }
}

/**
 * Hent URL parameter
 * @param {string} name - Parameter navn
 * @returns {string|null} Parameter værdi eller null
 */
function getUrlParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Valider input objekt
 * @param {Object} input - Input objekt at validere
 * @param {Array} requiredFields - Påkrævede felter
 * @returns {boolean} Om input er gyldigt
 */
function validateInput(input, requiredFields) {
    for (const field of requiredFields) {
        if (!input[field]) {
            showToast(`Felt '${field}' er påkrævet`, true);
            return false;
        }
    }
    return true;
}

/**
 * Formatér dato til dansk format
 * @param {Date|string} date - Dato objekt eller dato-streng
 * @returns {string} Formateret dato
 */
function formatDate(date) {
    const d = new Date(date);
    return d.toLocaleDateString('da-DK', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * Format pris til dansk format
 * @param {number} price - Pris
 * @returns {string} Formateret pris
 */
function formatPrice(price) {
    return new Intl.NumberFormat('da-DK', {
        style: 'currency',
        currency: 'DKK'
    }).format(price);
}

/**
 * Fetch og opdater global styles
 */
async function fetchAndUpdateGlobalStyles() {
    try {
        const result = await safeFetch(`${API_URL}/layout/global/styles`);
        
        if (result.status === 'success' && result.data) {
            // Opdater CSS variabler på :root element
            const root = document.documentElement;
            const colorPalette = result.data.color_palette || {};
            
            if (colorPalette.primary) root.style.setProperty('--primary-color', colorPalette.primary);
            if (colorPalette.secondary) root.style.setProperty('--secondary-color', colorPalette.secondary);
            if (colorPalette.accent) root.style.setProperty('--accent-color', colorPalette.accent);
            if (colorPalette.bright) root.style.setProperty('--bright-color', colorPalette.bright);
            if (colorPalette.background) root.style.setProperty('--background-color', colorPalette.background);
            if (colorPalette.text) root.style.setProperty('--text-color', colorPalette.text);
            
            return result.data;
        }
        return null;
    } catch (error) {
        console.error('Fejl ved hentning af globale styles:', error);
        return null;
    }
}

// Initialiser globale styles når siden indlæses
document.addEventListener('DOMContentLoaded', function() {
    fetchAndUpdateGlobalStyles().catch(console.error);
});
