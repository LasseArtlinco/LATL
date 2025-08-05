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
    var slideshows = document.querySelectorAll('.slideshow');
    
    for (var i = 0; i < slideshows.length; i++) {
        var slideshow = slideshows[i];
        var slides = slideshow.querySelectorAll('.slide');
        var slidesContainer = slideshow.querySelector('.slides');
        var indicators = slideshow.querySelectorAll('.indicator');
        var prevButton = slideshow.querySelector('.prev');
        var nextButton = slideshow.querySelector('.next');
        
        if (!slides.length) continue;
        
        var currentIndex = 0;
        var autoplayInterval = null;
        var autoplay = slideshow.dataset.autoplay === 'true';
        var interval = parseInt(slideshow.dataset.interval) || 5000;
        
        // Funktion til at skifte slide
        function goToSlide(index) {
            // Håndter cirkulært loop
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;
            
            // Opdater currentIndex
            currentIndex = index;
            
            // Flyt slidesContainer
            slidesContainer.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
            
            // Opdater active-klasse på slides
            for (var j = 0; j < slides.length; j++) {
                if (j === currentIndex) {
                    slides[j].classList.add('active');
                    slides[j].setAttribute('aria-hidden', 'false');
                } else {
                    slides[j].classList.remove('active');
                    slides[j].setAttribute('aria-hidden', 'true');
                }
            }
            
            // Opdater indicators
            for (var k = 0; k < indicators.length; k++) {
                if (k === currentIndex) {
                    indicators[k].classList.add('active');
                    indicators[k].setAttribute('aria-selected', 'true');
                } else {
                    indicators[k].classList.remove('active');
                    indicators[k].setAttribute('aria-selected', 'false');
                }
            }
            
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
        
        // Luk over variabler for hver slideshow (IIFE)
        (function(slideshow, slides, slidesContainer, indicators, prevButton, nextButton, goToSlide, startAutoplay) {
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
            for (var i = 0; i < indicators.length; i++) {
                (function(index) {
                    indicators[index].addEventListener('click', function() {
                        goToSlide(index);
                    });
                })(i);
            }
            
            // Tilføj touch-support til mobilenheder
            var touchStartX = 0;
            var touchEndX = 0;
            
            slideshow.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });
            
            slideshow.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                
                // Beregn swipe-retning
                var diff = touchStartX - touchEndX;
                
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
            
        })(slideshow, slides, slidesContainer, indicators, prevButton, nextButton, goToSlide, startAutoplay);
    }
}

/**
 * Initialiser lazy-loading af billeder
 */
function initLazyLoading() {
    // Tjek om browseren understøtter IntersectionObserver
    if ('IntersectionObserver' in window) {
        var lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            for (var i = 0; i < entries.length; i++) {
                var entry = entries[i];
                if (entry.isIntersecting) {
                    var img = entry.target;
                    
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
            }
        });
        
        for (var i = 0; i < lazyImages.length; i++) {
            imageObserver.observe(lazyImages[i]);
        }
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
    var skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Spring til indhold';
    
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Tilføj main-content id til hovedindholdet hvis det ikke findes
    if (!document.getElementById('main-content')) {
        var main = document.querySelector('main') || document.querySelector('.main-content');
        if (main) {
            main.id = 'main-content';
        }
    }
    
    // Keyboard-navigation for bånd
    var bands = document.querySelectorAll('.band');
    for (var i = 0; i < bands.length; i++) {
        if (!bands[i].hasAttribute('tabindex')) {
            bands[i].setAttribute('tabindex', '0');
        }
    }
}

/**
 * Initialiser responsive menu til mobilvisning
 */
function initResponsiveMenu() {
    var menuToggle = document.querySelector('.menu-toggle');
    var mobileMenu = document.querySelector('.mobile-menu');
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', function() {
            var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            
            menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
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
        var elements = document.querySelectorAll('.animate-on-scroll');
        
        var options = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        
        var observer = new IntersectionObserver(function(entries, observer) {
            for (var i = 0; i < entries.length; i++) {
                var entry = entries[i];
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    
                    // Stop med at observere elementet efter animation
                    observer.unobserve(entry.target);
                }
            }
        }, options);
        
        for (var i = 0; i < elements.length; i++) {
            observer.observe(elements[i]);
        }
    } else {
        // Fallback for browsere uden IntersectionObserver support
        var elements = document.querySelectorAll('.animate-on-scroll');
        for (var i = 0; i < elements.length; i++) {
            elements[i].classList.add('animated');
        }
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
