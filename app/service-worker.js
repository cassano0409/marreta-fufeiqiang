/**
 * Service Worker for Marreta App
 * 
 * The service worker acts as a network proxy and share target handler,
 * enabling the PWA to receive shared URLs from other applications.
 * 
 * O service worker atua como um proxy de rede e manipulador de compartilhamento,
 * permitindo que o PWA receba URLs compartilhadas de outros aplicativos.
 */

// Handles all network requests
// Gerencia todas as requisições de rede
self.addEventListener('fetch', (event) => {
    event.respondWith(fetch(event.request));
});

/**
 * Share target event handler - processes URLs shared from other applications
 * Manipulador do evento share_target - processa URLs compartilhadas de outros aplicativos
 */
self.addEventListener('share_target', (event) => {
    event.respondWith((async () => {
        const formData = await event.request.formData();
        const url = formData.get('url') || '';
        const redirectUrl = `/p/${encodeURIComponent(url)}`;
        return Response.redirect(redirectUrl, 303);
    })());
});
