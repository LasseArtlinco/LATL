/**
 * LATL.dk Frontend Scripts
 * Håndterer frontend-funktionalitet for LATL.dk
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser komponenter
    initNavigation();
    initSlideshows();
    initLazyLoading();
    initProductBands();
    initAccessibility();
});

/**
 * Mobile navigation
 */
function initNavigation() {
    const mobileNavToggle = document.querySelector('.mobile-nav-toggle');
    const nav = document.querySelector('nav');
    
    if (mobileNavToggle && nav) {
        mobileNavToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
            
            // Opdater aria-expanded
            const expanded = nav.classList.contains('active');
            mobileNavToggle.setAttribute('aria-expanded', expanded);
            
            // Tilføj eller fjern scroll lock på body
            document.body.classList.toggle('nav-open', expanded);
        });
        
        // Luk nav når der klikkes udenfor
        document.addEventListener('click', function(event) {
            if (nav.classList.contains('active') && 
                !nav.contains(event.target) && 
                event.target !== mobileNavToggle) {
                nav.classList.remove('active');
                mobileNavToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('nav-open');
            }
        });
        
        // Luk nav med Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && nav.classList.contains('active')) {
                nav.classList.remove('active');
                mobileNavToggle.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('nav-open');
            }
        });
    }
}

/**
 * Slideshow funktionalitet
 */
function initSlideshows() {
    const slideshows = document.querySelectorAll('.slideshow');
    
    slideshows.forEach(function(slideshow, slideshowIndex) {
        const slides = slideshow.querySelector('.slides');
        const slideElements = slideshow.querySelectorAll('.slide');
        const indicators = slideshow.querySelectorAll('.indicator');
        const prevBtn = slideshow.querySelector('.prev');
        const nextBtn = slideshow.querySelector('.next');
        
        // Hvis der ikke er noget indhold, afbryd
        if (!slides || !slideElements.length) return;
        
        // Giv slideshow et unikt ID hvis det ikke har et
        if (!slideshow.id) {
            slideshow.id = `slideshow-${slideshowIndex}`;
        }
        
        let currentSlide = 0;
        let isAnimating = false;
        const slideCount = slideElements.length;
        let touchStartX = 0;
        let touchEndX = 0;
        let autoplayTimer;
        
        // Hvis der kun er ét slide, gør vi ingenting
        if (slideCount <= 1) return;
        
        /**
         * Går til det angivne slide
         */
        function goToSlide(index, direction) {
            if (isAnimating) return;
            isAnimating = true;
            
            // Sikre at index er indenfor gyldigt område
            index = (index + slideCount) % slideCount;
            
            // Hvis en retning er angivet, tilføj CSS-klasse for animation
            if (direction) {
                slides.classList.add(`sliding-${direction}`);
            }
            
            slides.style.transform = `translateX(-${index * 100}%)`;
            
            // Opdater active class
            slideElements.forEach(function(slide, i) {
                slide.classList.toggle('active', i === index);
                slide.setAttribute('aria-hidden', i !== index);
            });
            
            indicators.forEach(function(indicator, i) {
                indicator.classList.toggle('active', i === index);
                indicator.setAttribute('aria-selected', i === index);
            });
            
            currentSlide = index;
            
            // Nulstil animation flag efter transition
            setTimeout(function() {
                isAnimating = false;
                if (direction) {
                    slides.classList.remove(`sliding-${direction}`);
                }
            }, 500);
            
            // Præload næste billede
            const nextIndex = (index + 1) % slideCount;
            const nextImage = slideElements[nextIndex].querySelector('img');
            if (nextImage && nextImage.dataset.src) {
                nextImage.src = nextImage.dataset.src;
                nextImage.removeAttribute('data-src');
            }
        }
        
        /**
         * Går til næste slide
         */
        function nextSlide() {
            goToSlide(currentSlide + 1, 'right');
        }
        
        /**
         * Går til forrige slide
         */
        function prevSlide() {
            goToSlide(currentSlide - 1, 'left');
        }
        
        // Tilføj accessibility attributter
        slideshow.setAttribute('role', 'region');
        slideshow.setAttribute('aria-label', 'Slideshow');
        
        slideElements.forEach(function(slide, i) {
            slide.setAttribute('role', 'tabpanel');
            slide.setAttribute('aria-hidden', i !== 0);
            slide.id = `slide-${slideshow.id}-${i}`;
        });
        
        // Event listeners
        if (prevBtn) {
            prevBtn.addEventListener('click', prevSlide);
            prevBtn.setAttribute('aria-label', 'Forrige slide');
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', nextSlide);
            nextBtn.setAttribute('aria-label', 'Næste slide');
        }
        
        indicators.forEach(function(indicator, i) {
            indicator.addEventListener('click', function() {
                goToSlide(i);
            });
            indicator.setAttribute('role', 'tab');
            indicator.setAttribute('aria-selected', i === 0);
            indicator.setAttribute('aria-label', `Gå til slide ${i+1}`);
            indicator.setAttribute('aria-controls', `slide-${slideshow.id}-${i}`);
        });
        
        // Touch-support til mobile enheder
        slideshow.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        slideshow.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const swipeThreshold = 50; // Minimum swipe distance
            
            if (touchEndX < touchStartX - swipeThreshold) {
                // Swipe venstre - gå til næste slide
                nextSlide();
            } else if (touchEndX > touchStartX + swipeThreshold) {
                // Swipe højre - gå til forrige slide
                prevSlide();
            }
        }
        
        // Keyboard navigation
        slideshow.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
            }
        });
        
        // Autoplay
        const autoplay = slideshow.dataset.autoplay === 'true';
        const interval = parseInt(slideshow.dataset.interval) || 5000;
        
        function startAutoplay() {
            if (autoplayTimer) return;
            autoplayTimer = setInterval(nextSlide, interval);
        }
        
        function stopAutoplay() {
            clearInterval(autoplayTimer);
            autoplayTimer = null;
        }
        
        if (autoplay && slideCount > 1) {
            startAutoplay();
            
            // Pause autoplay on hover or focus
            slideshow.addEventListener('mouseenter', stopAutoplay);
            slideshow.addEventListener('focusin', stopAutoplay);
            
            slideshow.addEventListener('mouseleave', startAutoplay);
            slideshow.addEventListener('focusout', startAutoplay);
            
            // Pause when page is not visible
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopAutoplay();
                } else {
                    startAutoplay();
                }
            });
        }
    });
}

/**
 * Lazy loading af billeder
 */
function initLazyLoading() {
    // Fallback for ældre browsere
    if (!('IntersectionObserver' in window)) {
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            img.setAttribute('src', img.getAttribute('data-src'));
            img.onload = function() {
                img.removeAttribute('data-src');
            };
        });
        return;
    }
    
    // Brug IntersectionObserver til lazy loading af billeder
    const imageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const img = entry.target;
                const src = img.getAttribute('data-src');
                
                if (src) {
                    img.setAttribute('src', src);
                    img.onload = function() {
                        img.removeAttribute('data-src');
                        img.classList.add('loaded');
                    };
                }
                
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });
    
    document.querySelectorAll('img[data-src]').forEach(function(img) {
        imageObserver.observe(img);
    });
    
    // Også håndter baggrundsbilleder med data-bg
    const bgObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const element = entry.target;
                const bg = element.getAttribute('data-bg');
                
                if (bg) {
                    element.style.backgroundImage = `url(${bg})`;
                    element.classList.add('bg-loaded');
                    element.removeAttribute('data-bg');
                }
                
                observer.unobserve(element);
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });
    
    document.querySelectorAll('[data-bg]').forEach(function(el) {
        bgObserver.observe(el);
    });
}

/**
 * Produkt bånd interaktioner
 */
function initProductBands() {
    const productBands = document.querySelectorAll('.product-band');
    
    productBands.forEach(function(band) {
        // Animationer og hover-effekter
        band.addEventListener('mouseenter', function() {
            this.classList.add('hover');
        });
        
        band.addEventListener('mouseleave', function() {
            this.classList.remove('hover');
        });
        
        // Fokus-håndtering for tilgængelighed
        const link = band.querySelector('a');
        if (link) {
            link.addEventListener('focus', function() {
                band.classList.add('hover');
            });
            
            link.addEventListener('blur', function() {
                band.classList.remove('hover');
            });
        }
    });
}

/**
 * Tilgængelighed-forbedringer
 */
function initAccessibility() {
    // Tilføj fokusindikator
    const style = document.createElement('style');
    style.textContent = `
        :focus {
            outline: 2px solid var(--accent-color, #9FC131);
            outline-offset: 2px;
        }
        
        :focus:not(:focus-visible) {
            outline: none;
        }
        
        :focus-visible {
            outline: 2px solid var(--accent-color, #9FC131);
            outline-offset: 2px;
        }
    `;
    document.head.appendChild(style);
    
    // Tilføj "skip to content" link for tastatur-navigation
    const skipLink = document.createElement('a');
    skipLink.href = '#main-content';
    skipLink.className = 'skip-link';
    skipLink.textContent = 'Gå til indhold';
    document.body.insertBefore(skipLink, document.body.firstChild);
    
    // Tilføj ID til main content
    const main = document.querySelector('main');
    if (main && !main.id) {
        main.id = 'main-content';
        main.setAttribute('tabindex', '-1');
    }
    
    // Sørg for, at alle billeder har alt-tekst
    document.querySelectorAll('img:not([alt])').forEach(function(img) {
        img.setAttribute('alt', '');
    });
}

/**
 * Performanceoptimeringer
 */
window.addEventListener('load', function() {
    // Udskyd ikke-kritiske scripts
    setTimeout(function() {
        // Her kan tilføjes scripts der ikke er nødvendige for initial loading
    }, 2000);
    
    // Prefetch links ved hover
    document.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('mouseenter', function() {
            const href = this.getAttribute('href');
            if (href && href.startsWith('/') && !link.prefetched) {
                const prefetch = document.createElement('link');
                prefetch.rel = 'prefetch';
                prefetch.href = href;
                document.head.appendChild(prefetch);
                link.prefetched = true;
            }
        });
    });
});
