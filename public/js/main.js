/**
 * LATL.dk - Frontend JavaScript
 * Forbedret slideshow-funktionalitet med touch support
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired - initializing slideshows');
    
    // Initialiser slideshows
    initSlideshows();
    
    // Initialiser mobile menu
    initMobileMenu();
    
    // Initialiser cookie banner
    initCookieBanner();
    
    // Initialiser lazy loading fallback
    initLazyLoading();
});

/**
 * Initialiser alle slideshow elementer på siden
 */
function initSlideshows() {
    var slideshows = document.querySelectorAll('.slideshow');
    console.log('Found ' + slideshows.length + ' slideshows on page');
    
    slideshows.forEach(function(slideshow, index) {
        setupSlideshow(slideshow, index);
    });
}

/**
 * Opsæt et enkelt slideshow
 */
function setupSlideshow(slideshow, slideshowIndex) {
    console.log('Setting up slideshow:', slideshow.id || 'slideshow-' + slideshowIndex);
    
    var slides = slideshow.querySelectorAll('.slide');
    var slidesContainer = slideshow.querySelector('.slides');
    var indicators = slideshow.querySelectorAll('.indicator');
    var prevButton = slideshow.querySelector('.prev');
    var nextButton = slideshow.querySelector('.next');
    
    console.log('Found ' + slides.length + ' slides in slideshow ' + slideshowIndex);
    
    if (!slides.length || !slidesContainer) {
        console.log('No slides or container found, aborting setup');
        return;
    }
    
    var currentIndex = 0;
    var autoplayInterval = null;
    var autoplay = slideshow.dataset.autoplay === 'true';
    var interval = parseInt(slideshow.dataset.interval) || 5000;
    var isTransitioning = false;
    
    // Touch support variabler
    var touchStartX = 0;
    var touchEndX = 0;
    var touchStartY = 0;
    var touchEndY = 0;
    var minSwipeDistance = 50;
    
    /**
     * Skift til specifikt slide
     */
    function goToSlide(index) {
        if (isTransitioning) return;
        
        // Håndter cirkulært loop
        if (index < 0) index = slides.length - 1;
        if (index >= slides.length) index = 0;
        
        // Start transition
        isTransitioning = true;
        
        // Opdater currentIndex
        currentIndex = index;
        
        // Flyt slidesContainer med transform
        var translateValue = -(currentIndex * 100);
        slidesContainer.style.transform = 'translateX(' + translateValue + '%)';
        
        // Opdater active-klasse på slides
        slides.forEach(function(slide, i) {
            if (i === currentIndex) {
                slide.classList.add('active');
                // Load billede hvis det er lazy loaded
                var img = slide.querySelector('img[loading="lazy"]');
                if (img && !img.src) {
                    img.src = img.dataset.src || img.src;
                }
            } else {
                slide.classList.remove('active');
            }
        });
        
        // Opdater indicators
        indicators.forEach(function(indicator, i) {
            if (i === currentIndex) {
                indicator.classList.add('active');
                indicator.setAttribute('aria-selected', 'true');
            } else {
                indicator.classList.remove('active');
                indicator.setAttribute('aria-selected', 'false');
            }
        });
        
        // Fjern transition lock efter animation
        setTimeout(function() {
            isTransitioning = false;
        }, 500);
        
        // Nulstil autoplay hvis aktiveret
        if (autoplay) {
            clearInterval(autoplayInterval);
            startAutoplay();
        }
    }
    
    /**
     * Start autoplay
     */
    function startAutoplay() {
        if (autoplay && slides.length > 1) {
            autoplayInterval = setInterval(function() {
                goToSlide(currentIndex + 1);
            }, interval);
        }
    }
    
    /**
     * Stop autoplay
     */
    function stopAutoplay() {
        if (autoplayInterval) {
            clearInterval(autoplayInterval);
            autoplayInterval = null;
        }
    }
    
    // Event listeners til navigationsknapper
    if (prevButton) {
        prevButton.addEventListener('click', function(e) {
            e.preventDefault();
            goToSlide(currentIndex - 1);
        });
    }
    
    if (nextButton) {
        nextButton.addEventListener('click', function(e) {
            e.preventDefault();
            goToSlide(currentIndex + 1);
        });
    }
    
    // Event listeners til indicators
    indicators.forEach(function(indicator, index) {
        indicator.addEventListener('click', function(e) {
            e.preventDefault();
            goToSlide(index);
        });
    });
    
    // Keyboard navigation
    slideshow.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            goToSlide(currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
            goToSlide(currentIndex + 1);
        }
    });
    
    // Touch support
    slidesContainer.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
        stopAutoplay();
    }, { passive: true });
    
    slidesContainer.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
        startAutoplay();
    }, { passive: true });
    
    /**
     * Håndter swipe gestus
     */
    function handleSwipe() {
        var swipeDistanceX = touchEndX - touchStartX;
        var swipeDistanceY = touchEndY - touchStartY;
        
        // Check om det er et horisontalt swipe
        if (Math.abs(swipeDistanceX) > Math.abs(swipeDistanceY)) {
            if (Math.abs(swipeDistanceX) > minSwipeDistance) {
                if (swipeDistanceX > 0) {
                    // Swipe højre - gå til forrige slide
                    goToSlide(currentIndex - 1);
                } else {
                    // Swipe venstre - gå til næste slide
                    goToSlide(currentIndex + 1);
                }
            }
        }
    }
    
    // Pause ved hover (kun desktop)
    if (autoplay && window.matchMedia('(hover: hover)').matches) {
        slideshow.addEventListener('mouseenter', stopAutoplay);
        slideshow.addEventListener('mouseleave', startAutoplay);
    }
    
    // Pause når vinduet ikke er synligt
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoplay();
        } else {
            startAutoplay();
        }
    });
    
    // Preload næste slide billede
    function preloadNextSlide() {
        var nextIndex = (currentIndex + 1) % slides.length;
        var nextSlide = slides[nextIndex];
        var nextImg = nextSlide.querySelector('img');
        
        if (nextImg && nextImg.dataset.src && !nextImg.src) {
            var preloader = new Image();
            preloader.src = nextImg.dataset.src;
        }
    }
    
    // Start med første slide
    goToSlide(0);
    
    // Start autoplay hvis aktiveret
    if (autoplay && slides.length > 1) {
        startAutoplay();
        // Preload næste slide efter kort delay
        setTimeout(preloadNextSlide, 1000);
    }
}

/**
 * Initialiser mobile menu
 */
function initMobileMenu() {
    var mobileToggle = document.querySelector('.mobile-nav-toggle');
    var nav = document.querySelector('nav');
    
    if (!mobileToggle || !nav) return;
    
    mobileToggle.addEventListener('click', function() {
        nav.classList.toggle('active');
        var isOpen = nav.classList.contains('active');
        mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        mobileToggle.textContent = isOpen ? '✕' : '☰';
    });
    
    // Luk menu når der klikkes på et link
    var navLinks = nav.querySelectorAll('a');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            nav.classList.remove('active');
            mobileToggle.setAttribute('aria-expanded', 'false');
            mobileToggle.textContent = '☰';
        });
    });
    
    // Luk menu når der klikkes udenfor
    document.addEventListener('click', function(e) {
        if (!nav.contains(e.target) && !mobileToggle.contains(e.target) && nav.classList.contains('active')) {
            nav.classList.remove('active');
            mobileToggle.setAttribute('aria-expanded', 'false');
            mobileToggle.textContent = '☰';
        }
    });
}

/**
 * Initialiser cookie banner
 */
function initCookieBanner() {
    var banner = document.getElementById('cookieBanner');
    var acceptBtn = document.getElementById('acceptCookies');
    var declineBtn = document.getElementById('declineCookies');
    var settingsLink = document.getElementById('cookieSettings');
    
    if (!banner) return;
    
    // Check om cookies allerede er accepteret
    var cookieConsent = localStorage.getItem('cookieConsent');
    
    if (!cookieConsent) {
        // Vis banner efter kort delay
        setTimeout(function() {
            banner.classList.add('show');
        }, 1000);
    }
    
    // Accepter cookies
    if (acceptBtn) {
        acceptBtn.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'accepted');
            banner.classList.remove('show');
            // Her kan du aktivere analytics osv.
            enableAnalytics();
        });
    }
    
    // Afvis cookies
    if (declineBtn) {
        declineBtn.addEventListener('click', function() {
            localStorage.setItem('cookieConsent', 'declined');
            banner.classList.remove('show');
        });
    }
    
    // Genåbn cookie settings
    if (settingsLink) {
        settingsLink.addEventListener('click', function(e) {
            e.preventDefault();
            banner.classList.add('show');
        });
    }
}

/**
 * Aktiver analytics (kun hvis cookies er accepteret)
 */
function enableAnalytics() {
    // Placeholder for analytics kode
    console.log('Analytics enabled');
    
    // Her kan du tilføje Google Analytics, Facebook Pixel osv.
}

/**
 * Initialiser lazy loading fallback for ældre browsere
 */
function initLazyLoading() {
    // Check om browser understøtter native lazy loading
    if ('loading' in HTMLImageElement.prototype) {
        // Browser understøtter lazy loading
        console.log('Native lazy loading supported');
        return;
    }
    
    // Fallback for ældre browsere
    console.log('Using lazy loading fallback');
    
    var lazyImages = document.querySelectorAll('img[loading="lazy"]');
    var imageObserver = null;
    
    if ('IntersectionObserver' in window) {
        // Brug IntersectionObserver
        imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.removeAttribute('loading');
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for meget gamle browsere
        var lazyLoad = function() {
            lazyImages.forEach(function(img) {
                if (img.getBoundingClientRect().top < window.innerHeight + 100) {
                    img.src = img.dataset.src || img.src;
                    img.removeAttribute('loading');
                }
            });
            
            // Opdater listen
            lazyImages = document.querySelectorAll('img[loading="lazy"]');
            
            if (lazyImages.length === 0) {
                window.removeEventListener('scroll', lazyLoad);
                window.removeEventListener('resize', lazyLoad);
            }
        };
        
        window.addEventListener('scroll', lazyLoad);
        window.addEventListener('resize', lazyLoad);
        lazyLoad(); // Kør med det samme
    }
}

/**
 * Hjælpefunktion til at håndtere billede fejl
 */
window.handleImageError = function(img) {
    console.log('Image error:', img.src);
    
    // Prøv forskellige størrelser som fallback
    var src = img.src;
    
    if (src.includes('/large/')) {
        // Prøv medium
        img.src = src.replace('/large/', '/medium/');
    } else if (src.includes('/medium/')) {
        // Prøv small
        img.src = src.replace('/medium/', '/small/');
    } else if (src.includes('/small/')) {
        // Prøv original uden størrelse
        img.src = src.replace('/small/', '/').replace('/thumbnail/', '/');
    } else {
        // Sidste fallback - placeholder
        img.src = '/placeholder-image.png';
        img.alt = 'Billede kunne ikke indlæses';
    }
};

/**
 * Global funktion til at skifte slide (kaldes fra inline onclick)
 */
window.changeSlide = function(direction) {
    // Find det aktive slideshow
    var activeSlideshow = document.querySelector('.slideshow');
    if (!activeSlideshow) return;
    
    var slides = activeSlideshow.querySelectorAll('.slide');
    var currentIndex = 0;
    
    // Find current active slide
    slides.forEach(function(slide, index) {
        if (slide.classList.contains('active')) {
            currentIndex = index;
        }
    });
    
    // Beregn næste index
    var nextIndex = currentIndex + direction;
    if (nextIndex < 0) nextIndex = slides.length - 1;
    if (nextIndex >= slides.length) nextIndex = 0;
    
    // Trigger slide change
    var event = new CustomEvent('changeSlide', { detail: { index: nextIndex } });
    activeSlideshow.dispatchEvent(event);
};

/**
 * Global funktion til at gå til specifikt slide
 */
window.goToSlide = function(index) {
    // Find det aktive slideshow
    var activeSlideshow = document.querySelector('.slideshow');
    if (!activeSlideshow) return;
    
    // Trigger slide change
    var event = new CustomEvent('changeSlide', { detail: { index: index } });
    activeSlideshow.dispatchEvent(event);
};

/**
 * Smooth scroll til ankre
 */
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        var target = document.querySelector(this.getAttribute('href'));
        
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

/**
 * Debug hjælper
 */
window.debugImages = function() {
    var images = document.querySelectorAll('img');
    console.log('Total images on page:', images.length);
    
    images.forEach(function(img, index) {
        console.log('Image ' + index + ':', {
            src: img.src,
            alt: img.alt,
            loading: img.loading,
            complete: img.complete,
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
            displayWidth: img.width,
            displayHeight: img.height
        });
    });
};

console.log('LATL.dk main.js loaded successfully');
