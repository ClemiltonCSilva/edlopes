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

        const closestIndex = () => {
            const trackLeft = track.getBoundingClientRect().left;
            let index = 0;
            let closestDistance = Infinity;
            items.forEach((item, i) => {
                const distance = Math.abs(item.getBoundingClientRect().left - trackLeft);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    index = i;
                }
            });
            return index;
        };

        const updateUI = () => {
            const index = closestIndex();
            if (currentEl) currentEl.textContent = String(index + 1).padStart(2, '0');
            if (prevBtn) prevBtn.disabled = track.scrollLeft <= 2;
            if (nextBtn) {
                const maxScroll = track.scrollWidth - track.clientWidth;
                nextBtn.disabled = track.scrollLeft >= maxScroll - 2;
            }
        };

        const goTo = (delta) => {
            const targetIndex = Math.max(0, Math.min(items.length - 1, closestIndex() + delta));
            track.scrollTo({ left: items[targetIndex].offsetLeft, behavior: 'smooth' });
        };

        prevBtn?.addEventListener('click', () => goTo(-1));
        nextBtn?.addEventListener('click', () => goTo(1));

        let ticking = false;
        track.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(() => {
                updateUI();
                ticking = false;
            });
        }, { passive: true });

        updateUI();
    });
});
