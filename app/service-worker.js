/**
 * Service Worker for Marreta App
 * 
 * The service worker acts as a network proxy and share target handler,
 * enabling the PWA to receive shared URLs from other applications.
 */

// Handles all network requests
self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});

/**
 * Share target event handler - processes URLs shared from other applications
 */
self.addEventListener('share_target', (event) => {
    event.respondWith((async () => {
        const formData = await event.request.formData();
        const url = formData.get('url') || '';
        const redirectUrl = `/p/${encodeURIComponent(url)}`;
        return Response.redirect(redirectUrl, 303);
    })());
});
