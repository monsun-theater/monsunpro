<?php

namespace App\StaticCaching;

use Statamic\Entries\Entry;
use Statamic\StaticCaching\DefaultInvalidator;
use Illuminate\Support\Facades\Cache;

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
        }
        
        parent::invalidate($item);
    }

    /**
     * Clear all event-related caches by key pattern
     */
    private function clearEventCaches(): void
    {
        $store = cache()->getStore();
        
        if (method_exists($store, 'flush')) {
            // If it's a store like file or database that can scan keys
            cache()->flush();
        } else {
            // Otherwise clear specific cache keys we know about
            $types = ['future', 'pastPerformances', 'today', 'digital', 'premieres'];
            foreach ($types as $type) {
                cache()->forget("events.{$type}.*");
            }
            cache()->forget('events.grouped.*');
        }
    }
}
