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
 * Hent sider fra API
 * @returns {Promise<Array>} Array af sider
 */
async function fetchPages() {
    try {
        showLoading();
        
        const response = await fetch(`${API_URL}/layout`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'success') {
            return result.data || [];
        } else {
            throw new Error(result.message || 'Der skete en fejl ved hentning af sider');
        }
    } catch (error) {
        console.error('Fejl ved hentning af sider:', error);
        showToast('Fejl ved hentning af sider', true);
        return [];
    } finally {
        hideLoading();
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
        showLoading();
        
        const response = await fetch(`${API_URL}/bands/${pageId}/${bandId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.status === 'success') {
            return result.data || null;
        } else {
            throw new Error(result.message || 'Der skete en fejl ved hentning af bånd');
        }
    } catch (error) {
        console.error('Fejl ved hentning af bånd:', error);
        showToast('Fejl ved hentning af bånd', true);
        return null;
    } finally {
        hideLoading();
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
