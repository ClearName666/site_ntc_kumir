document.addEventListener("DOMContentLoaded", function () {
    const viewport = document.getElementById("mediaViewport");
    const track = document.getElementById("mediaTrack");
    const prevBtn = document.getElementById("mediaPrev");
    const nextBtn = document.getElementById("mediaNext");

    if (!viewport || !track) return;

    let originalCards = Array.from(track.children);
    if (originalCards.length === 0) return;

    // Если карточек слишком мало для прокрутки, скрываем стрелки управления
    if (originalCards.length <= 1) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        return;
    }

    // Клонируем элементы для бесконечного цикла
    originalCards.forEach(card => {
        let cloneAfter = card.cloneNode(true);
        let cloneBefore = card.cloneNode(true);
        track.appendChild(cloneAfter);
        track.insertBefore(cloneBefore, track.firstChild);
    });

    let allCards = Array.from(track.children);
    let totalOriginals = originalCards.length;
    let currentIndex = totalOriginals; // Старт с оригинального первого элемента
    let isTransitioning = false;

    function getGapAndWidth() {
        let style = window.getComputedStyle(track);
        let gap = parseFloat(style.gap) || 0;
        let cardWidth = allCards[0].getBoundingClientRect().width;
        return { gap, cardWidth };
    }

    function updatePosition(smooth = true) {
        const { gap, cardWidth } = getGapAndWidth();
        const offset = -currentIndex * (cardWidth + gap);
        
        if (smooth) {
            track.style.transition = "transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)";
        } else {
            track.style.transition = "none";
        }
        track.style.transform = `translateX(${offset}px)`;
    }

    // Инициализация стартовой позиции
    setTimeout(() => updatePosition(false), 50);

    function moveNext() {
        if (isTransitioning) return;
        isTransitioning = true;
        currentIndex++;
        updatePosition(true);
    }

    function movePrev() {
        if (isTransitioning) return;
        isTransitioning = true;
        currentIndex--;
        updatePosition(true);
    }

    nextBtn.addEventListener("click", moveNext);
    prevBtn.addEventListener("click", movePrev);

    // Контроль бесконечного прыжка без анимации на концах трека
    track.addEventListener("transitionend", function () {
        isTransitioning = false;
        
        if (currentIndex >= totalOriginals * 2) {
            currentIndex = totalOriginals;
            updatePosition(false);
        } else if (currentIndex < totalOriginals) {
            currentIndex = totalOriginals * 2 - 1;
            updatePosition(false);
        }
    });

    // Обработка ресайза окна для корректного пересчета ширин
    let resizeTimeout;
    window.addEventListener("resize", function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            updatePosition(false);
        }, 100);
    });
});