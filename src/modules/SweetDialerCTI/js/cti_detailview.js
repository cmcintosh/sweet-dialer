/**
 * SweetDialerCTI DetailView JavaScript
 */

function initDetailView() {
    highlightValidationStatus();
}

function highlightValidationStatus() {
    var banners = document.querySelectorAll('.validation-banner');
    for (var i = 0; i < banners.length; i++) {
        var banner = banners[i];
        if (banner.classList.contains('validation-passed')) {
            banner.style.backgroundColor = '#e6f7e6';
            banner.style.border = '1px solid #2ecc71';
            banner.style.padding = '10px 15px';
            banner.style.borderRadius = '4px';
        } else if (banner.classList.contains('validation-failed')) {
            banner.style.backgroundColor = '#ffe6e6';
            banner.style.border = '1px solid #e74c3c';
            banner.style.padding = '10px 15px';
            banner.style.borderRadius = '4px';
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDetailView);
} else {
    initDetailView();
}
