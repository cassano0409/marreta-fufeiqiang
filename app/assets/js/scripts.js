/**
 * Service Worker registration for PWA functionality
 * Registers a service worker to enable offline capabilities and PWA features
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(() => {
                // Service Worker registered successfully
            })
            .catch(() => {
                // Service Worker registration failed
            });
    });
}

/**
 * Header toggle menus
 */
document.addEventListener('DOMContentLoaded', function () {
    const integration = document.querySelector('.integration');
    const integrationToggle = document.querySelector('.integration__toggle');
    const extension = document.querySelector('.extension');
    const extensionToggle = document.querySelector('.extension__toggle');

    // Function to close all menus
    const closeAllMenus = () => {
        integration.classList.remove('open');
        extension.classList.remove('open');
    };

    // Function to close other menus except the one passed
    const closeOtherMenus = (exceptMenu) => {
        if (exceptMenu !== integration) {
            integration.classList.remove('open');
        }
        if (exceptMenu !== extension) {
            extension.classList.remove('open');
        }
    };

    integrationToggle.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent click from bubbling to document
        closeOtherMenus(integration);
        integration.classList.toggle('open');
    });

    extensionToggle.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent click from bubbling to document
        closeOtherMenus(extension);
        extension.classList.toggle('open');
    });

    // Prevent clicks inside menus from closing them
    integration.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    extension.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // Close menus when clicking outside
    document.addEventListener('click', () => {
        closeAllMenus();
    });
});