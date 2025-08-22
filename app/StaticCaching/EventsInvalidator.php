<?php

namespace App\StaticCaching;

use Statamic\Entries\Entry;
use Statamic\StaticCaching\DefaultInvalidator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class EventsInvalidator extends DefaultInvalidator
{
    public function invalidate($item)
    {
        if ($item instanceof Entry && $item->collection()->handle() === 'veranstaltungen') {
            // Clear all event-related caches by key pattern
            $this->clearEventCaches();
            
            // Clear static cache for affected URLs
            $this->cacher->invalidateUrls([
                '/',                    // Homepage
                '/spielplan',           // Schedule
                '/premiere',            // Premieres
                '/monsun_digital',      // Digital events
                '/veranstaltungen',     // Events index
                '/pastperformances'     // Past performances
            ]);
            
            // Log that invalidation was triggered
            Log::info('Events cache invalidated for: ' . $item->id());
        }
        
        parent::invalidate($item);
    }

    /**
     * Clear all event-related caches by key pattern
     */
    private function clearEventCaches(): void
    {
        $cacheDriver = config('cache.default');
        
        if ($cacheDriver === 'redis') {
            // Redis-specific approach
            $this->clearRedisEventCaches();
        } else {
            // For file, database, etc.
            $store = cache()->getStore();
            
            if (method_exists($store, 'flush')) {
                cache()->flush();
            } else {
                // Fallback to forgetting specific keys
                $this->forgetSpecificCacheKeys();
            }
        }
    }
    
    /**
     * Clear Redis cache keys using scan and delete
     */
    private function clearRedisEventCaches(): void
    {
        try {
            // Get Redis connection
            $redis = Redis::connection();
            
            // Patterns to match
            $patterns = [
                'monsun_events_events.future.*',
                'monsun_events_events.pastPerformances.*',
                'monsun_events_events.today.*',
                'monsun_events_events.digital.*',
                'monsun_events_events.premieres.*',
                'monsun_events_events.grouped.*',
                'monsun_events_laravel_cache:*'
            ];
            
            foreach ($patterns as $pattern) {
                $cursor = '0';
                do {
                    // Scan for keys matching pattern
                    [$cursor, $keys] = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 1000);
                    
                    // Delete found keys
                    if (!empty($keys)) {
                        $redis->del($keys);
                        Log::info('Deleted Redis keys: ' . implode(', ', $keys));
                    }
                } while ($cursor != '0');
            }
            
            // Also clear application cache
            cache()->flush();
            
        } catch (\Exception $e) {
            Log::error('Redis cache clearing failed: ' . $e->getMessage());
            // Fallback to forgetting specific keys
            $this->forgetSpecificCacheKeys();
        }
    }
    
    /**
     * Forget specific cache keys as fallback
     */
    private function forgetSpecificCacheKeys(): void
    {
        $types = ['future', 'pastPerformances', 'today', 'digital', 'premieres'];
        foreach ($types as $type) {
            cache()->forget("events.{$type}.veranstaltungen");
            cache()->forget("events.{$type}.all");
        }
        
        // Also clear any grouped results
        cache()->forget('events.grouped.M-Y');
        cache()->forget('events.grouped.MMMM YYYY');
    }
}
