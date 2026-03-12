/**
 * Particles System - Microplastic particles animation
 * Partículas oceânicas bioluminescentes
 */

document.addEventListener('DOMContentLoaded', function() {
    const particlesContainer = document.getElementById('particles');

    if (!particlesContainer) return;

    // Number of particles
    const particleCount = 30;

    // Particle types
    const particleTypes = ['large', 'medium', 'small'];

    // Generate particles
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = `particle ${particleTypes[i % particleTypes.length]}`;

        // Random position
        const leftPosition = Math.random() * 100;
        const topPosition = Math.random() * 100;

        particle.style.left = `${leftPosition}%`;
        particle.style.top = `${topPosition}%`;

        // Random animation delay
        const delay = Math.random() * 10;
        particle.style.animationDelay = `${delay}s`;

        // Random animation duration (slower for more natural movement)
        const duration = 20 + Math.random() * 15;
        particle.style.animationDuration = `${duration}s`;

        particlesContainer.appendChild(particle);
    }
});
