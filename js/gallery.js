document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.gallery').forEach((gallery) => {
        const track = gallery.querySelector('.gallery-track');
        const items = Array.from(gallery.querySelectorAll('.gallery-item'));
        const prevBtn = gallery.querySelector('.gallery-prev');
        const nextBtn = gallery.querySelector('.gallery-next');
        const currentEl = gallery.querySelector('.gallery-current');
        const totalEl = gallery.querySelector('.gallery-total');

        if (!track || !items.length) return;

        if (totalEl) totalEl.textContent = String(items.length).padStart(2, '0');

        const updateCounter = () => {
            const trackLeft = track.getBoundingClientRect().left;
            let closestIndex = 0;
            let closestDistance = Infinity;

            items.forEach((item, index) => {
                const distance = Math.abs(item.getBoundingClientRect().left - trackLeft);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closestIndex = index;
                }
            });

            if (currentEl) currentEl.textContent = String(closestIndex + 1).padStart(2, '0');
            if (prevBtn) prevBtn.disabled = closestIndex === 0;
            if (nextBtn) nextBtn.disabled = closestIndex === items.length - 1;
        };

        const scrollByItem = (direction) => {
            const gap = parseFloat(getComputedStyle(track).gap) || 0;
            const step = items[0].getBoundingClientRect().width + gap;
            track.scrollBy({ left: step * direction, behavior: 'smooth' });
        };

        prevBtn?.addEventListener('click', () => scrollByItem(-1));
        nextBtn?.addEventListener('click', () => scrollByItem(1));

        let ticking = false;
        track.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(() => {
                updateCounter();
                ticking = false;
            });
        }, { passive: true });

        updateCounter();
    });
});
