// Service Worker for Video Shorts Reels
// Optimized for 5k+ concurrent users

const CACHE_NAME = 'video-shorts-v1';
const STATIC_CACHE = 'static-v1';
const VIDEO_CACHE = 'video-v1';
const THUMBNAIL_CACHE = 'thumbnail-v1';

// Cache sizes (in MB)
const MAX_VIDEO_CACHE_SIZE = 50;
const MAX_THUMBNAIL_CACHE_SIZE = 10;
const MAX_STATIC_CACHE_SIZE = 5;

// Files to cache immediately
const STATIC_FILES = [
    '/gp-admin/admin/vendors/styles/core.css',
    '/gp-admin/admin/vendors/styles/style.css',
    '/gp-admin/admin/vendors/scripts/core.js',
    '/gp-admin/admin/vendors/scripts/script.min.js',
    '/gp-admin/admin/php/defaultavatar/video-thumbnail.png'
];

// Install event - cache static files
self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('Caching static files');
                return cache.addAll(STATIC_FILES);
            })
            .then(() => {
                console.log('Static files cached successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Failed to cache static files:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME && 
                            cacheName !== STATIC_CACHE && 
                            cacheName !== VIDEO_CACHE && 
                            cacheName !== THUMBNAIL_CACHE) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - handle different types of requests
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Handle video files
    if (isVideoRequest(event.request)) {
        event.respondWith(handleVideoRequest(event.request));
        return;
    }
    
    // Handle thumbnail images
    if (isThumbnailRequest(event.request)) {
        event.respondWith(handleThumbnailRequest(event.request));
        return;
    }
    
    // Handle static files
    if (isStaticRequest(event.request)) {
        event.respondWith(handleStaticRequest(event.request));
        return;
    }
    
    // Handle API requests
    if (isAPIRequest(event.request)) {
        event.respondWith(handleAPIRequest(event.request));
        return;
    }
    
    // Default: network first, fallback to cache
    event.respondWith(
        fetch(event.request)
            .catch(() => {
                return caches.match(event.request);
            })
    );
});

// Check if request is for a video file
function isVideoRequest(request) {
    const url = new URL(request.url);
    return url.pathname.includes('/uploads/videos/') || 
           url.pathname.includes('/videos/') ||
           request.headers.get('accept')?.includes('video/');
}

// Check if request is for a thumbnail
function isThumbnailRequest(request) {
    const url = new URL(request.url);
    return url.pathname.includes('/images/video_thumbnails/') ||
           url.pathname.includes('/thumbnails/');
}

// Check if request is for static files
function isStaticRequest(request) {
    const url = new URL(request.url);
    return url.pathname.includes('/vendors/') ||
           url.pathname.includes('/css/') ||
           url.pathname.includes('/js/') ||
           url.pathname.includes('/images/');
}

// Check if request is for API
function isAPIRequest(request) {
    const url = new URL(request.url);
    return url.pathname.includes('/api/');
}

// Handle video requests with intelligent caching
async function handleVideoRequest(request) {
    try {
        // Try network first
        const response = await fetch(request);
        
        if (response.ok) {
            // Cache the video
            const cache = await caches.open(VIDEO_CACHE);
            cache.put(request, response.clone());
            
            // Clean up old videos if cache is too large
            await cleanupVideoCache();
            
            return response;
        }
    } catch (error) {
        console.log('Video fetch failed, trying cache:', error);
    }
    
    // Fallback to cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    // Return a placeholder or error response
    return new Response('Video not available', { status: 404 });
}

// Handle thumbnail requests
async function handleThumbnailRequest(request) {
    try {
        // Try cache first for thumbnails (they don't change often)
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // If not in cache, fetch and cache
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(THUMBNAIL_CACHE);
            cache.put(request, response.clone());
            
            // Clean up old thumbnails
            await cleanupThumbnailCache();
            
            return response;
        }
    } catch (error) {
        console.log('Thumbnail fetch failed:', error);
    }
    
    // Return default thumbnail
    return caches.match('/gp-admin/admin/php/defaultavatar/video-thumbnail.png');
}

// Handle static file requests
async function handleStaticRequest(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.log('Static file fetch failed:', error);
        return new Response('File not available', { status: 404 });
    }
}

// Handle API requests with caching strategy
async function handleAPIRequest(request) {
    // For GET requests, try cache first, then network
    if (request.method === 'GET') {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Return cached response but update in background
            updateCacheInBackground(request);
            return cachedResponse;
        }
    }
    
    // For other methods or if no cache, go to network
    try {
        const response = await fetch(request);
        
        // Cache successful GET responses
        if (request.method === 'GET' && response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        console.log('API request failed:', error);
        
        // Return cached response if available
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        return new Response('Service unavailable', { status: 503 });
    }
}

// Update cache in background
async function updateCacheInBackground(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response);
        }
    } catch (error) {
        console.log('Background cache update failed:', error);
    }
}

// Clean up video cache to prevent it from growing too large
async function cleanupVideoCache() {
    const cache = await caches.open(VIDEO_CACHE);
    const keys = await cache.keys();
    
    if (keys.length > 20) { // Keep only 20 videos in cache
        const keysToDelete = keys.slice(0, keys.length - 20);
        await Promise.all(keysToDelete.map(key => cache.delete(key)));
        console.log('Cleaned up video cache');
    }
}

// Clean up thumbnail cache
async function cleanupThumbnailCache() {
    const cache = await caches.open(THUMBNAIL_CACHE);
    const keys = await cache.keys();
    
    if (keys.length > 50) { // Keep only 50 thumbnails in cache
        const keysToDelete = keys.slice(0, keys.length - 50);
        await Promise.all(keysToDelete.map(key => cache.delete(key)));
        console.log('Cleaned up thumbnail cache');
    }
}

// Handle background sync for offline actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

// Background sync implementation
async function doBackgroundSync() {
    try {
        // Sync any pending actions (likes, comments, etc.)
        console.log('Performing background sync...');
        
        // This would sync any offline actions when connection is restored
        // Implementation depends on your specific offline functionality
        
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Handle push notifications
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        
        const options = {
            body: data.body || 'New content available!',
            icon: '/gp-admin/admin/images/logo.png',
            badge: '/gp-admin/admin/images/badge.png',
            data: data.data || {},
            actions: [
                {
                    action: 'view',
                    title: 'View Now'
                },
                {
                    action: 'dismiss',
                    title: 'Dismiss'
                }
            ]
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title || 'Video Shorts', options)
        );
    }
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/gp-admin/admin/video_shorts_reels.php')
        );
    }
});

// Handle message events from main thread
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_CLEAR') {
        event.waitUntil(clearAllCaches());
    }
});

// Clear all caches
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(cacheNames.map(name => caches.delete(name)));
    console.log('All caches cleared');
}

// Performance monitoring
let performanceMetrics = {
    cacheHits: 0,
    cacheMisses: 0,
    networkRequests: 0,
    errors: 0
};

// Report metrics periodically
setInterval(() => {
    if (performanceMetrics.cacheHits > 0 || performanceMetrics.cacheMisses > 0) {
        const hitRate = performanceMetrics.cacheHits / (performanceMetrics.cacheHits + performanceMetrics.cacheMisses) * 100;
        console.log(`Cache hit rate: ${hitRate.toFixed(2)}%`);
        console.log(`Network requests: ${performanceMetrics.networkRequests}`);
        console.log(`Errors: ${performanceMetrics.errors}`);
        
        // Reset metrics
        performanceMetrics = {
            cacheHits: 0,
            cacheMisses: 0,
            networkRequests: 0,
            errors: 0
        };
    }
}, 60000); // Report every minute
