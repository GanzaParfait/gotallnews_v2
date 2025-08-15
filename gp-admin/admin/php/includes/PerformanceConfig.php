<?php
/**
 * Performance Configuration for Video Shorts Reels
 * Optimized for 5k+ concurrent users
 */

class PerformanceConfig {
    
    // Video preloading strategies
    const PRELOAD_STRATEGIES = [
        'low_bandwidth' => [
            'preload' => 'none',
            'quality' => 'low',
            'max_concurrent' => 1,
            'buffer_size' => 1024 * 1024 // 1MB
        ],
        'medium_bandwidth' => [
            'preload' => 'metadata',
            'quality' => 'medium',
            'max_concurrent' => 2,
            'buffer_size' => 5 * 1024 * 1024 // 5MB
        ],
        'high_bandwidth' => [
            'preload' => 'auto',
            'quality' => 'high',
            'max_concurrent' => 3,
            'buffer_size' => 10 * 1024 * 1024 // 10MB
        ]
    ];
    
    // Memory management
    const MEMORY_LIMITS = [
        'max_heap_size' => 100 * 1024 * 1024, // 100MB
        'cleanup_threshold' => 80 * 1024 * 1024, // 80MB
        'gc_interval' => 30000, // 30 seconds
        'max_video_cache' => 10
    ];
    
    // Network optimization
    const NETWORK_CONFIG = [
        'connection_timeout' => 10000, // 10 seconds
        'retry_attempts' => 3,
        'backoff_multiplier' => 2,
        'max_concurrent_requests' => 5,
        'request_queue_size' => 100
    ];
    
    // Rendering optimization
    const RENDERING_CONFIG = [
        'max_fps' => 60,
        'throttle_threshold' => 30,
        'debounce_delay' => 100,
        'throttle_delay' => 16, // ~60fps
        'use_request_animation_frame' => true
    ];
    
    // Cache configuration
    const CACHE_CONFIG = [
        'video_cache_size' => 50 * 1024 * 1024, // 50MB
        'thumbnail_cache_size' => 10 * 1024 * 1024, // 10MB
        'comment_cache_size' => 1000,
        'cache_ttl' => 3600, // 1 hour
        'enable_service_worker' => true
    ];
    
    // Database optimization
    const DB_CONFIG = [
        'max_connections' => 100,
        'query_timeout' => 5000,
        'connection_pool_size' => 20,
        'enable_query_cache' => true,
        'cache_queries' => [
            'get_comments',
            'get_video_stats',
            'get_user_profile'
        ]
    ];
    
    /**
     * Get performance configuration based on user's connection
     */
    public static function getConfig($connectionType = 'medium') {
        $config = [
            'preload' => self::PRELOAD_STRATEGIES[$connectionType] ?? self::PRELOAD_STRATEGIES['medium_bandwidth'],
            'memory' => self::MEMORY_LIMITS,
            'network' => self::NETWORK_CONFIG,
            'rendering' => self::RENDERING_CONFIG,
            'cache' => self::CACHE_CONFIG,
            'database' => self::DB_CONFIG
        ];
        
        return $config;
    }
    
    /**
     * Get optimized video settings
     */
    public static function getVideoSettings($connectionType = 'medium') {
        $preloadConfig = self::PRELOAD_STRATEGIES[$connectionType] ?? self::PRELOAD_STRATEGIES['medium_bandwidth'];
        
        return [
            'preload' => $preloadConfig['preload'],
            'quality' => $preloadConfig['quality'],
            'maxConcurrent' => $preloadConfig['max_concurrent'],
            'bufferSize' => $preloadConfig['buffer_size'],
            'enableAdaptiveBitrate' => true,
            'enableProgressiveLoading' => true
        ];
    }
    
    /**
     * Get memory management settings
     */
    public static function getMemorySettings() {
        return [
            'maxHeapSize' => self::MEMORY_LIMITS['max_heap_size'],
            'cleanupThreshold' => self::MEMORY_LIMITS['cleanup_threshold'],
            'gcInterval' => self::MEMORY_LIMITS['gc_interval'],
            'maxVideoCache' => self::MEMORY_LIMITS['max_video_cache']
        ];
    }
    
    /**
     * Get network optimization settings
     */
    public static function getNetworkSettings() {
        return [
            'connectionTimeout' => self::NETWORK_CONFIG['connection_timeout'],
            'retryAttempts' => self::NETWORK_CONFIG['retry_attempts'],
            'backoffMultiplier' => self::NETWORK_CONFIG['backoff_multiplier'],
            'maxConcurrentRequests' => self::NETWORK_CONFIG['max_concurrent_requests'],
            'requestQueueSize' => self::NETWORK_CONFIG['request_queue_size']
        ];
    }
    
    /**
     * Get rendering optimization settings
     */
    public static function getRenderingSettings() {
        return [
            'maxFps' => self::RENDERING_CONFIG['max_fps'],
            'throttleThreshold' => self::RENDERING_CONFIG['throttle_threshold'],
            'debounceDelay' => self::RENDERING_CONFIG['debounce_delay'],
            'throttleDelay' => self::RENDERING_CONFIG['throttle_delay'],
            'useRequestAnimationFrame' => self::RENDERING_CONFIG['use_request_animation_frame']
        ];
    }
    
    /**
     * Get cache settings
     */
    public static function getCacheSettings() {
        return [
            'videoCacheSize' => self::CACHE_CONFIG['video_cache_size'],
            'thumbnailCacheSize' => self::CACHE_CONFIG['thumbnail_cache_size'],
            'commentCacheSize' => self::CACHE_CONFIG['comment_cache_size'],
            'cacheTtl' => self::CACHE_CONFIG['cache_ttl'],
            'enableServiceWorker' => self::CACHE_CONFIG['enable_service_worker']
        ];
    }
    
    /**
     * Get database optimization settings
     */
    public static function getDatabaseSettings() {
        return [
            'maxConnections' => self::DB_CONFIG['max_connections'],
            'queryTimeout' => self::DB_CONFIG['query_timeout'],
            'connectionPoolSize' => self::DB_CONFIG['connection_pool_size'],
            'enableQueryCache' => self::DB_CONFIG['enable_query_cache'],
            'cacheQueries' => self::DB_CONFIG['cache_queries']
        ];
    }
    
    /**
     * Detect user's connection type
     */
    public static function detectConnectionType() {
        // This would be implemented based on actual connection detection
        // For now, return medium as default
        return 'medium_bandwidth';
    }
    
    /**
     * Get performance recommendations
     */
    public static function getRecommendations($currentMetrics) {
        $recommendations = [];
        
        if ($currentMetrics['memory_usage'] > self::MEMORY_LIMITS['cleanup_threshold']) {
            $recommendations[] = 'High memory usage detected. Consider reducing video cache size.';
        }
        
        if ($currentMetrics['network_latency'] > 1000) {
            $recommendations[] = 'High network latency detected. Consider reducing video quality.';
        }
        
        if ($currentMetrics['fps'] < self::RENDERING_CONFIG['throttle_threshold']) {
            $recommendations[] = 'Low FPS detected. Consider reducing animation complexity.';
        }
        
        return $recommendations;
    }
}
?>
