/**
 * LATL Bånd Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser alle slideshows
    initSlideshows();
    
    // Initialiser lazy loading
    initLazyLoading();
});

/**
 * Initialiser alle slideshow bånd på siden
 */
function initSlideshows() {
    const slideshows = document.querySelectorAll('.band-slideshow');
    
    slideshows.forEach(function(slideshow) {
        const slides = slideshow.querySelectorAll('.slide');
        const dots = slideshow.querySelectorAll('.slideshow-dot');
        const prevBtn = slideshow.querySelector('.prev-slide');
        const nextBtn = slideshow.querySelector('.next-slide');
        
        // Spring over hvis der ikke er slides
        if (slides.length === 0) return;
        
        let currentSlide = 0;
        let slideInterval;
        
        // Funktion til at vise et bestemt slide
        function showSlide(n) {
            // Reset interval ved manuel navigation
            clearInterval(slideInterval);
            
            // Wrap-around hvis n er udenfor rækkevidde
            if (n >= slides.length) {
                currentSlide = 0;
            } else if (n < 0) {
                currentSlide = slides.length - 1;
            } else {
                currentSlide = n;
            }
            
            // Fjern active klasse fra alle slides
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            // Tilføj active klasse til det aktuelle slide
            slides[currentSlide].classList.add('active');
            
            // Opdater dots hvis de findes
            if (dots.length > 0) {
                dots.forEach(dot => {
                    dot.classList.remove('active');
                });
                dots[currentSlide].classList.add('active');
            }
            
            // Genstart automatisk skift
            startAutoSlide();
        }
        
        // Funktion til at skifte til næste slide
        function nextSlide() {
            showSlide(currentSlide + 1);
        }
        
        // Funktion til at skifte til forrige slide
        function prevSlide() {
            showSlide(currentSlide - 1);
        }
        
        // Funktion til automatisk slideshow
        function startAutoSlide() {
            // Stop eksisterende interval
            if (slideInterval) {
                clearInterval(slideInterval);
            }
            
            // Skift slide hvert 5. sekund
            slideInterval = setInterval(nextSlide, 5000);
        }
        
        // Tilføj event listeners til knapper
        if (prevBtn) {
            prevBtn.addEventListener('click', prevSlide);
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', nextSlide);
        }
        
        // Tilføj event listeners til dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
            });
        });
        
        // Tilføj swipe navigation på mobil
        let touchStartX = 0;
        let touchEndX = 0;
        
        slideshow.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        slideshow.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
        
        function handleSwipe() {
            // Hvis der er swiped mere end 50px
            if (touchEndX < touchStartX - 50) {
                // Swipe venstre, gå til næste slide
                nextSlide();
            } else if (touchEndX > touchStartX + 50) {
                // Swipe højre, gå til forrige slide
                prevSlide();
            }
        }
        
        // Start automatisk skift hvis der er mere end ét slide
        if (slides.length > 1) {
            startAutoSlide();
            
            // Pause automatisk skift ved hover
            slideshow.addEventListener('mouseenter', () => {
                clearInterval(slideInterval);
            });
            
            slideshow.addEventListener('mouseleave', () => {
                startAutoSlide();
            });
        }
    });
}

/**
 * Lazy loading af billeder i bånd
 */
function initLazyLoading() {
    // Tjek om IntersectionObserver er understøttet
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const lazyImage = entry.target;
                    
                    // Indlæs billedet
                    if (lazyImage.dataset.src) {
                        lazyImage.src = lazyImage.dataset.src;
                        lazyImage.removeAttribute('data-src');
                    }
                    
                    // Indlæs srcset
                    if (lazyImage.dataset.srcset) {
                        lazyImage.srcset = lazyImage.dataset.srcset;
                        lazyImage.removeAttribute('data-srcset');
                    }
                    
                    // Stop med at observere dette billede
                    imageObserver.unobserve(lazyImage);
                }
            });
        });
        
        lazyImages.forEach(function(lazyImage) {
            imageObserver.observe(lazyImage);
        });
    } else {
        // Fallback for browsere, der ikke understøtter IntersectionObserver
        // Indlæs alle billeder med det samme
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        lazyImages.forEach(function(lazyImage) {
            if (lazyImage.dataset.src) {
                lazyImage.src = lazyImage.dataset.src;
            }
            if (lazyImage.dataset.srcset) {
                lazyImage.srcset = lazyImage.dataset.srcset;
            }
        });
    }
}
