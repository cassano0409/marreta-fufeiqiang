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

    // Remove toasty elements when clicked
    document.addEventListener('click', (e) => {
        const toastyElement = e.target.closest('.toasty');
        if (toastyElement) {
            toastyElement.remove();
        }
    });

    // Toggle header open class when open-nav is clicked
    document.addEventListener('click', (e) => {
        const openNavElement = e.target.closest('.open-nav');
        if (openNavElement) {
            const header = document.querySelector('header');
            if (header.classList.contains('open')) {
                header.classList.remove('open');
            } else {
                header.classList.add('open');
            }
        }
    });

    // Paste button functionality
    const pasteButton = document.getElementById('paste');
    const urlInput = document.getElementById('url');

    if (pasteButton && urlInput) {
        pasteButton.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const clipboardText = await navigator.clipboard.readText();
                urlInput.value = clipboardText.trim();
            } catch (err) {
                console.error('Failed to read clipboard contents', err);
            }
        });
    }
});