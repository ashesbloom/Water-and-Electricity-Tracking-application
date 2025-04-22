document.addEventListener('DOMContentLoaded', () => {

    function setupScrollAnimations() {
        const animatedElements = document.querySelectorAll('.scroll-animate');

        if ("IntersectionObserver" in window) {
            const observerOptions = {
                root: null, 
                // Trigger when element is 80px from bottom edge
                rootMargin: '0px 0px -80px 0px', 
                // Trigger as soon as any part is visible within the adjusted margin
                threshold: 0.01 
            };

            const animationObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('scroll-animate-active');
                        entry.target.classList.remove('scroll-animate-init');
                    } else {
                        if (entry.target.classList.contains('scroll-animate-active')) {
                            entry.target.classList.remove('scroll-animate-active');
                            entry.target.classList.add('scroll-animate-init');
                        }
                    }
                });
            }, observerOptions);

            animatedElements.forEach(el => {
                 if (!el.classList.contains('scroll-animate-init')) {
                     el.classList.add('scroll-animate-init');
                 }
                 if (el) {
                    animationObserver.observe(el); 
                 }
            });

        } else {
            // Fallback for older browsers
            console.warn("Intersection Observer not supported, activating animations directly.");
            animatedElements.forEach(el => {
                if (el) { 
                   el.classList.remove('scroll-animate-init'); 
                   el.classList.add('scroll-animate-active'); 
                }
            });
        }
    }

    setupScrollAnimations();

});
