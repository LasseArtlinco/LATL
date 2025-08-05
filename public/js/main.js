/**
 * LATL.dk - Frontend JavaScript
 * Håndterer slideshow, responsivt design, accessibility og andet frontend funktionalitet
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser slideshows
    initSlideshows();
    
    // Tilføj lazy-loading til billeder
    initLazyLoading();
    
    // Forbedret tilgængelighed
    initAccessibility();
    
    // Responsive menu
    initResponsiveMenu();
    
    // Animation ved scroll
    initScrollAnimations();
});

/**
 * Initialiser alle slideshow elementer på siden
 */
function initSlideshows() {
    const slideshows = document.querySelectorAll('.slideshow');
    
    slideshows.forEach(slideshow => {
        const slides = slideshow.querySelectorAll('.slide');
        const slidesContainer = slideshow.querySelector('.slides');
        const indicators = slideshow.querySelectorAll('.indicator');
        const prevButton = slideshow.querySelector('.prev');
        const nextButton = slideshow.querySelector('.next');
        
        if (!slides.length) return;
        
        let currentIndex = 0;
        let autoplayInterval = null;
        const autoplay = slideshow.dataset.autoplay === 'true';
        const interval = parseInt(slideshow.dataset.interval) || 5000;
        
        // Funktion til at skifte slide
        function goToSlide(index) {
            // Håndter cirkulært loop
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;
            
            // Opdater currentIndex
            currentIndex = index;
            
            // Flyt slidesContainer
            slidesContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
            
            // Opdater active-klasse på slides
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === currentIndex);
                
                // Opdater ARIA attributter for tilgængelighed
                slide.setAttribute('aria-hidden', i !== currentIndex);
            });
            
            // Opdater indicators
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === currentIndex);
                indicator.setAttribute('aria-selected', i === currentIndex);
            });
            
            // Nulstil autoplay hvis aktiveret
            if (autoplay) {
                clearInterval(autoplayInterval);
                startAutoplay();
            }
        }
        
        // Funktion til at starte autoplay
        function startAutoplay() {
            if (autoplay) {
                autoplayInterval = setInterval(function() {
                    goToSlide(currentIndex + 1);
                }, interval);
            }
        }
        
        // Tilføj event listeners til navigationsknapper
        if (prevButton) {
            prevButton.addEventListener('click', function() {
                goToSlide(currentIndex - 1);
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', function() {
                goToSlide(currentIndex + 1);
            });
        }
        
        // Tilføj event listeners til indicators
        indicators.forEach(function(indicator, i) {
            indicator.addEventListener('click', function() {
                goToSlide(i);
            });
        });
        
        // Tilføj touch-support til mobilenheder
        let touchStartX = 0;
        let touchEndX = 0;
        
        slideshow.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        slideshow.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            
            // Beregn swipe-retning
            const diff = touchStartX - touchEndX;
            
            // Hvis swipe er signifikant (mere end 50px)
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    // Swipe til venstre - næste slide
                    goToSlide(currentIndex + 1);
                } else {
                    // Swipe til højre - forrige slide
                    goToSlide(currentIndex - 1);
                }
            }
        }, { passive: true });
        
        // Håndter keyboard navigation
        slideshow.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                goToSlide(currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                goToSlide(currentIndex + 1);
            }
        });
        
        // Håndter pause ved hover
        if (autoplay) {
            slideshow.addEventListener('mouseenter', function() {
                clearInterval(autoplayInterval);
            });
            
            slideshow.addEventListener('mouseleave', function() {
                startAutoplay();
            });
            
            // Start autoplay
            startAutoplay();
        }
        
        // Initialiser første slide
        goToSlide(0);
    });
}

/**
 * Initialiser lazy-loading af billeder
 */
function initLazyLoading() {
    // Tjek om browseren understøtter IntersectionObserver
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    // Hvis der er et data-src, brug det som src
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    
                    // Hvis der er et data-srcset, brug det som srcset
                    if (img.dataset.srcset) {
                        img.srcset = img.dataset.srcset;
                        img.removeAttribute('data-srcset');
                    }
                    
                    // Fjern loading="lazy" da billedet nu er indlæst
                    img.removeAttribute('loading');
                    
                    // Stop med at observere billedet
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for browsere uden IntersectionObserver support
        // De vil bruge standard loading="lazy" attribut
    }
}

/**
 * Forbedret tilgængelighed
 */
function initAccessibility() {
    // "Skip to content" link
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Spring til indhold';
    
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Tilføj main-content id til hovedindholdet hvis det ikke findes
    if (!document.getElementById('main-content')) {
        const main = document.querySelector('main') || document.querySelector('.main-content');
        if (main) {
            main.id = 'main-content';
        }
    }
    
    // Keyboard-navigation for bånd
    const bands = document.querySelectorAll('.band');
    bands.forEach(band => {
        if (!band.hasAttribute('tabindex')) {
            band.setAttribute('tabindex', '0');
        }
    });
}

/**
 * Initialiser responsive menu til mobilvisning
 */
function initResponsiveMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            
            menuToggle.setAttribute('aria-expanded', !expanded);
            mobileMenu.classList.toggle('active');
            
            if (!expanded) {
                // Åbn menu
                document.body.style.overflow = 'hidden'; // Forhindre baggrunds-scroll
            } else {
                // Luk menu
                document.body.style.overflow = '';
            }
        });
        
        // Luk menu når der klikkes udenfor
        document.addEventListener('click', function(e) {
            if (mobileMenu.classList.contains('active') && 
                !mobileMenu.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                
                menuToggle.setAttribute('aria-expanded', 'false');
                mobileMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
}

/**
 * Initialiser animationer ved scroll
 */
function initScrollAnimations() {
    // Tjek om browseren understøtter IntersectionObserver
    if ('IntersectionObserver' in window) {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        const options = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    
                    // Stop med at observere elementet efter animation
                    observer.unobserve(entry.target);
                }
            });
        }, options);
        
        elements.forEach(function(element) {
            observer.observe(element);
        });
    } else {
        // Fallback for browsere uden IntersectionObserver support
        document.querySelectorAll('.animate-on-scroll').forEach(element => {
            element.classList.add('animated');
        });
    }
}

/**
 * Hjælpefunktion til at detektere device-type
 */
function isMobile() {
    return window.innerWidth <= 768;
}

/**
 * Hjælpefunktion til at debounce funktionskald
 */
function debounce(func, wait) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait || 100);
    };
}

// Tilføj resize event listener med debounce
window.addEventListener('resize', debounce(function() {
    // Funktioner der skal køres ved vinduesændring
    // ...
}, 250));
