/**
 * LATL Admin Common JS
 * Fælles JavaScript funktioner til admin området
 */

// Konstanter
const API_URL = '/api';

/**
 * Vis loading indikator
 */
function showLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.classList.add('show');
    }
}

/**
 * Skjul loading indikator
 */
function hideLoading() {
    const loading = document.querySelector('.loading');
    if (loading) {
        loading.classList.remove('show');
    }
}

/**
 * Vis toast notifikation
 * @param {string} message - Besked der skal vises
 * @param {boolean} isError - Om det er en fejlbesked
 * @param {number} duration - Hvor længe beskeden skal vises (ms)
 */
function showToast(message, isError = false, duration = 3000) {
    const toast = document.getElementById('toast');
    if (!toast) return;
    
    toast.textContent = message;
    toast.className = isError ? 'toast error show' : 'toast show';
    
    setTimeout(() => {
        toast.className = toast.className.replace('show', '');
    }, duration);
}

/**
 * Hent sider fra API
 * @returns {Promise<Array>} - Array af sider
 */
async function fetchPages() {
    try {
        showLoading();
        
        const response = await fetch(`${API_URL}/layout`);
        const result = await response.json();
        
        if (result.status === 'success' && result.data) {
            return result.data.filter(page => page.page_id !== 'global');
        }
        
        return [];
    } catch (error) {
        console.error('Fejl ved indlæsning af sider:', error);
        showToast('Fejl ved indlæsning af sider', true);
        return [];
    } finally {
        hideLoading();
    }
}

/**
 * Hent bånd for en side
 * @param {string} pageId - Sidens ID
 * @returns {Promise<Array>} - Array af bånd
 */
async function fetchBands(pageId) {
    try {
        showLoading();
        
        const response = await fetch(`${API_URL}/bands/${pageId}`);
        const result = await response.json();
        
        if (result.status === 'success') {
            return result.data || [];
        }
        
        showToast(result.message || 'Fejl ved indlæsning af bånd', true);
        return [];
    } catch (error) {
        console.error('Fejl ved indlæsning af bånd:', error);
        showToast('Fejl ved indlæsning af bånd', true);
        return [];
    } finally {
        hideLoading();
    }
}

/**
 * Hent et specifikt bånd
 * @param {string} pageId - Sidens ID
 * @param {string} bandId - Båndets ID
 * @returns {Promise<Object>} - Bånd objekt
 */
async function fetchBand(pageId, bandId) {
    try {
        showLoading();
        
        const response = await fetch(`${API_URL}/bands/${pageId}/${bandId}`);
        const result = await response.json();
        
        if (result.status === 'success') {
            return result.data;
        }
        
        showToast(result.message || 'Fejl ved indlæsning af bånd', true);
        return null;
    } catch (error) {
        console.error('Fejl ved indlæsning af bånd:', error);
        showToast('Fejl ved indlæsning af bånd', true);
        return null;
    } finally {
        hideLoading();
    }
}

/**
 * Gå tilbage til forrige side
 */
function goBack() {
    window.history.back();
}

/**
 * Få URL parameter
 * @param {string} name - Parameter navn
 * @returns {string|null} - Parameter værdi
 */
function getUrlParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Generer et unikt ID
 * @returns {string} - Unikt ID
 */
function generateUniqueId() {
    return 'band_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Konverter en fil til en data URL
 * @param {File} file - Fil der skal konverteres
 * @returns {Promise<string>} - Data URL
 */
function fileToDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = e => resolve(e.target.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}
