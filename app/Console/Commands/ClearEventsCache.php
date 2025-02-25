<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ClearEventsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all events-related caches from Redis';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Clearing events cache...');
        
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
            
            $totalDeleted = 0;
            
            foreach ($patterns as $pattern) {
                $this->info("Scanning for keys matching: {$pattern}");
                $cursor = '0';
                $patternDeleted = 0;
                
                do {
                    // Scan for keys matching pattern
                    [$cursor, $keys] = $redis->scan($cursor, 'MATCH', $pattern, 'COUNT', 1000);
                    
                    // Delete found keys
                    if (!empty($keys)) {
                        $redis->del($keys);
                        $patternDeleted += count($keys);
                        $totalDeleted += count($keys);
                        
                        // Log the first few keys for debugging
                        $sampleKeys = array_slice($keys, 0, 3);
                        $this->info("Deleted keys (sample): " . implode(', ', $sampleKeys));
                        
                        if (count($keys) > 3) {
                            $this->info("... and " . (count($keys) - 3) . " more");
                        }
                    }
                } while ($cursor != '0');
                
                $this->info("Deleted {$patternDeleted} keys matching {$pattern}");
            }
            
            // Also clear application cache
            $this->call('cache:clear');
            
            // Clear static cache
            $this->call('please:static-clear');
            
            $this->info("Total Redis keys deleted: {$totalDeleted}");
            $this->info('Events cache cleared successfully!');
            
            Log::info("Events cache cleared: {$totalDeleted} Redis keys deleted");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error clearing Redis cache: ' . $e->getMessage());
            Log::error('Redis cache clearing failed: ' . $e->getMessage());
            return 1;
        }
    }
}
