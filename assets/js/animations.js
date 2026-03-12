/**
 * Animations and Interactive Effects
 */

// Smooth scroll animations
document.addEventListener('DOMContentLoaded', function() {

    // Intersection Observer for fade-in effects
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeInUp');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements
    const elementsToAnimate = document.querySelectorAll('.academic-card, .timeline-item');
    elementsToAnimate.forEach(el => observer.observe(el));

    // Parallax effect for hero section
    const heroContent = document.querySelector('.hero-content, .container-lg h1');
    const particles = document.getElementById('particles');
    const causticLight = document.querySelector('.caustic-light');

    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;

        // Parallax no conteúdo do hero (mais lento)
        if (heroContent) {
            const parallaxContent = scrolled * 0.3;
            heroContent.style.transform = `translateY(${parallaxContent}px)`;
            heroContent.style.opacity = Math.max(1 - (scrolled / 500), 0);
        }

        // Parallax nas partículas (velocidade média)
        if (particles) {
            const parallaxParticles = scrolled * 0.2;
            particles.style.transform = `translateY(${parallaxParticles}px)`;
        }

        // Parallax na luz cáustica (mais rápido)
        if (causticLight) {
            const parallaxLight = scrolled * 0.4;
            causticLight.style.transform = `translateY(${parallaxLight}px)`;
        }
    });

    // Add hover glow effect to cards
    const cards = document.querySelectorAll('.academic-card, .timeline-content');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.4s ease';
        });
    });

    // Timeline marker pulse on scroll
    const timelineMarkers = document.querySelectorAll('.timeline-marker');
    const markerObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'pulse 2s infinite';
            }
        });
    }, { threshold: 0.5 });

    timelineMarkers.forEach(marker => markerObserver.observe(marker));

});

// Add dynamic particle brightness based on scroll
window.addEventListener('scroll', function() {
    const particles = document.querySelectorAll('.particle');
    const scrollPercentage = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight));

    particles.forEach(particle => {
        const opacity = 0.5 + (scrollPercentage * 0.5);
        particle.style.opacity = Math.min(opacity, 1);
    });
});
