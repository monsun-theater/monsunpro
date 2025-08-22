<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\Entry;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class CheckFutureEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:check-future';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if there are future events in the database';

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
        $this->info('Checking for future events...');
        
        // Get all events from the veranstaltungen collection
        $events = Entry::query()
            ->where('collection', 'veranstaltungen')
            ->get();
        
        $this->info("Found {$events->count()} total events in the veranstaltungen collection");
        
        // Get current time with timezone
        $now = now()->setTimezone(config('app.timezone', 'UTC'));
        $this->info("Current time: {$now}");
        
        // Extract all performance dates
        $allDates = [];
        $futureEvents = [];
        
        foreach ($events as $event) {
            $performanceDates = $event->get('performance_dates');
            
            if (empty($performanceDates)) {
                $this->warn("Event {$event->id()} ({$event->get('title')}) has no performance dates");
                continue;
            }
            
            foreach ($performanceDates as $date) {
                $perfDate = Arr::get($date, 'perf_date');
                $carbonDate = Carbon::parse($perfDate);
                $allDates[] = [
                    'id' => $event->id(),
                    'title' => $event->get('title'),
                    'date' => $perfDate,
                    'carbon_date' => $carbonDate,
                    'is_future' => $carbonDate > $now
                ];
                
                if ($carbonDate > $now) {
                    $futureEvents[] = [
                        'id' => $event->id(),
                        'title' => $event->get('title'),
                        'date' => $perfDate,
                        'formatted_date' => $carbonDate->format('Y-m-d H:i:s')
                    ];
                }
            }
        }
        
        $this->info("Found " . count($allDates) . " total performance dates");
        $this->info("Found " . count($futureEvents) . " future performance dates");
        
        if (count($futureEvents) > 0) {
            $this->info("\nFuture events:");
            $this->table(
                ['ID', 'Title', 'Date', 'Formatted Date'],
                $futureEvents
            );
        } else {
            $this->warn("No future events found!");
            
            // Show some past events for reference
            $pastEvents = array_filter($allDates, function($date) {
                return !$date['is_future'];
            });
            
            // Sort by date descending
            usort($pastEvents, function($a, $b) {
                return $b['carbon_date']->timestamp <=> $a['carbon_date']->timestamp;
            });
            
            // Take the 5 most recent past events
            $recentPastEvents = array_slice($pastEvents, 0, 5);
            
            if (count($recentPastEvents) > 0) {
                $this->info("\nMost recent past events for reference:");
                $tableData = array_map(function($event) {
                    return [
                        'id' => $event['id'],
                        'title' => $event['title'],
                        'date' => $event['date'],
                        'formatted_date' => $event['carbon_date']->format('Y-m-d H:i:s')
                    ];
                }, $recentPastEvents);
                
                $this->table(
                    ['ID', 'Title', 'Date', 'Formatted Date'],
                    $tableData
                );
            }
        }
        
        return 0;
    }
}
