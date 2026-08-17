document.addEventListener('DOMContentLoaded', function () {
    const startNowBtn = document.getElementById('start-now');
    const featuresWrapper = document.getElementById('featuresWrapper');
    const chevronIcon = document.getElementById('chevron-icon');
    const bgImg = document.getElementById('bgImg');

    if (bgImg) {
        const img = new Image();
        img.src = 'styles/images/background.jpg';
        if (img.complete) {
            bgImg.classList.add('loaded');
        } else {
            img.onload = function () {
                bgImg.classList.add('loaded');
            };
        }
    }

    if (startNowBtn && featuresWrapper) {
        startNowBtn.addEventListener('click', function () {
            const isExpanded = featuresWrapper.classList.toggle('expanded');

            if (chevronIcon) {
                chevronIcon.style.transform = isExpanded
                    ? 'rotate(180deg)'
                    : 'rotate(0deg)';
            }
        });
    }
});