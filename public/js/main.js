/**
 * LATL.dk - Frontend JavaScript
 * Simpel slideshow-funktionalitet
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser slideshows
    initSlideshows();
});

/**
 * Initialiser alle slideshow elementer på siden
 */
function initSlideshows() {
    var slideshows = document.querySelectorAll('.slideshow');
    
    for (var i = 0; i < slideshows.length; i++) {
        setupSlideshow(slideshows[i]);
    }
}

/**
 * Opsæt et enkelt slideshow
 */
function setupSlideshow(slideshow) {
    var slides = slideshow.querySelectorAll('.slide');
    var slidesContainer = slideshow.querySelector('.slides');
    var indicators = slideshow.querySelectorAll('.indicator');
    var prevButton = slideshow.querySelector('.prev');
    var nextButton = slideshow.querySelector('.next');
    
    if (!slides.length) return;
    
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
            } else {
                slides[j].classList.remove('active');
            }
        }
        
        // Opdater indicators
        for (var k = 0; k < indicators.length; k++) {
            if (k === currentIndex) {
                indicators[k].classList.add('active');
            } else {
                indicators[k].classList.remove('active');
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
    for (var m = 0; m < indicators.length; m++) {
        (function(index) {
            indicators[index].addEventListener('click', function() {
                goToSlide(index);
            });
        })(m);
    }
    
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
}
