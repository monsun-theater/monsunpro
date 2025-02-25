<?php

namespace App\Tags;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;
use Statamic\Support\Arr;
use Statamic\Tags\Collection\Collection as CollectionTag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Events extends CollectionTag
{
    /**
     * Debug mode - set to true to enable detailed logging
     */
    protected $debug = true;
    
    public function afterToday()
    {
        return $this->outputDates($this
            ->getDates()
            ->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) > $this->getNow()->endOfDay())
        );
    }

    /**
     * @return string|array
     */
    public function pastPerformances()
    {
        return $this->outputDates($this->getDates('pastPerformances'));
    }

    public function future()
    {
        if ($this->debug) {
            Log::info('Events::future called with params: ' . json_encode($this->params->all()));
        }
        
        $result = $this->outputDates($this->getDates('future'));
        
        if ($this->debug) {
            Log::info('Events::future result has ' . (isset($result['no_results']) ? 'no results' : 
                (isset($result['date_groups']) ? count($result['date_groups']) . ' date groups' : 'direct entries')));
        }
        
        return $result;
    }

    public function premieres()
    {
        return $this->outputDates(
            $this
                ->getDates()
                ->filter(fn (array $event) => Arr::get($event, 'premiere_toggle', false))
        );
    }

    public function digital()
    {
        return $this->outputDates(
            $this
                ->getDates()
                ->filter(fn (array $event) => Arr::get($event, 'monsun_digital', false))
        );
    }

    public function today()
    {
        return $this->outputDates($this
            ->getDates()
            ->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date'))->isToday())
        );
    }

    public function count(): int
    {
        return $this->getDates()->count();
    }

    /**
     * Get current time with consistent timezone
     */
    private function getNow(): Carbon
    {
        return now()->setTimezone(config('app.timezone', 'UTC'));
    }

    private function getDates(string $type = 'future'): Collection
    {
        // Generate a more specific cache key
        $cacheKey = sprintf(
            'events.%s.%s.%s',
            $type,
            $this->params->get('from', 'all'),
            md5(serialize(array_diff_key($this->params->all(), ['from' => true])))
        );
        
        if ($this->debug) {
            Log::info("Events::getDates using cache key: {$cacheKey}");
        }
        
        // Skip cache in debug mode to ensure we're getting fresh data
        $useCache = !$this->debug;
        
        $callback = function () use ($type) {
            $this->params->put('from', 'veranstaltungen');
            $limit = $this->params->pull('limit');

            if ($this->debug) {
                Log::info("Events::getDates fetching events from collection");
            }
            
            $events = parent::index();
            
            if ($this->debug) {
                Log::info("Events::getDates got " . (is_array($events) ? count($events) : 'unknown') . " events from collection");
            }

            if ($as = $this->params->get('as')) {
                $events = $events[$as];
                
                if ($this->debug) {
                    Log::info("Events::getDates using 'as' parameter: {$as}");
                }
            }

            // Eager load performance dates
            if (method_exists($events, 'loadMissing')) {
                $events = $events->loadMissing('performance_dates');
            }

            $now = $this->getNow();
            
            if ($this->debug) {
                Log::info("Events::getDates current time: {$now}");
            }

            $dates = $events
                ->flatMap(function (Entry $event) {
                    $performanceDates = $event->get('performance_dates');
                    
                    if ($this->debug && empty($performanceDates)) {
                        Log::info("Event {$event->id()} has no performance dates");
                    }
                    
                    return collect($performanceDates)
                        ->map(fn (array $date) => array_merge(
                            $event->toAugmentedArray(),
                            $date
                        ));
                })
                ->pipe(function ($collection) use ($type, $now) {
                    return match($type) {
                        'pastPerformances' => $collection->filter(
                            fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) <= $now
                        ),
                        'future' => $collection->filter(
                            fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) > $now
                        ),
                        default => $collection
                    };
                })
                ->sortBy(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')));

            if ($this->debug) {
                Log::info("Events::getDates filtered to " . $dates->count() . " dates for type: {$type}");
                
                // Log the first few dates for debugging
                $dates->take(3)->each(function ($event) {
                    Log::info("Sample event date: " . Arr::get($event, 'perf_date') . " - " . Arr::get($event, 'title'));
                });
            }

            return $limit ? $dates->take($limit) : $dates;
        };
        
        return $useCache 
            ? cache()->remember($cacheKey, now()->addMinutes(5), $callback)
            : $callback();
    }

    private function outputDates(Collection $dates): array
    {
        if ($this->debug) {
            Log::info("Events::outputDates received " . $dates->count() . " dates");
        }
        
        if ($groupByFormat = $this->params->get('group_by_format')) {
            if ($dates->isEmpty()) {
                if ($this->debug) {
                    Log::info("Events::outputDates returning no_results=true");
                }
                return ['no_results' => true];
            }

            // Generate a more specific cache key for grouped results
            $cacheKey = sprintf(
                'events.grouped.%s.%s',
                $groupByFormat,
                md5(serialize($dates->map(fn($event) => [
                    'id' => $event['id'] ?? null,
                    'perf_date' => Arr::get($event, 'perf_date')
                ])->toArray()))
            );
            
            if ($this->debug) {
                Log::info("Events::outputDates using grouped cache key: {$cacheKey}");
            }
            
            // Skip cache in debug mode
            $useCache = !$this->debug;
            
            $callback = function () use ($dates, $groupByFormat) {
                $result = collect([
                    'date_groups' => $dates
                        ->groupBy(fn ($event) => Carbon::parse(Arr::get($event, 'perf_date'))->format($groupByFormat))
                        ->map(fn ($events, $date_group) => ['date_group' => $date_group, 'entries' => $events])
                        ->values()
                        ->all(),
                ])->all();
                
                if ($this->debug) {
                    Log::info("Events::outputDates grouped into " . count($result['date_groups']) . " date groups");
                }
                
                return $result;
            };
            
            return $useCache
                ? cache()->remember($cacheKey, now()->addMinutes(5), $callback)
                : $callback();
        }

        if ($as = $this->params->get('as')) {
            $result = [
                'no_results' => $dates->isEmpty(),
                $as => $dates->all(),
            ];
            
            if ($this->debug) {
                Log::info("Events::outputDates returning with 'as' parameter: {$as}, no_results: " . ($dates->isEmpty() ? 'true' : 'false'));
            }
            
            return $result;
        }

        if ($this->debug) {
            Log::info("Events::outputDates returning direct dates array");
        }
        
        return $dates->all();
    }
}
