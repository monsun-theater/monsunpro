<?php

namespace App\Tags;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;
use Statamic\Support\Arr;
use Statamic\Tags\Collection\Collection as CollectionTag;
use Illuminate\Support\Facades\Cache;

class Events extends CollectionTag
{
    public function afterToday()
    {
        return $this->outputDates($this
            ->getDates()
            ->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) > now()->endOfDay())
        );
    }

    /**updated
     * @return string|array
     */
    public function pastPerformances()
    {
        return $this->outputDates($this->getDates('pastPerformances'));
    }

    public function future()
    {
        return $this->outputDates($this->getDates('future'));
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

    private function getDates(string $type = 'future'): Collection
    {
        // Generate a more specific cache key
        $cacheKey = sprintf(
            'events.%s.%s.%s',
            $type,
            $this->params->get('from', 'all'),
            md5(serialize(array_diff_key($this->params->all(), ['from' => true])))
        );
        
        return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($type) {
            $this->params->put('from', 'veranstaltungen');
            $limit = $this->params->pull('limit');

            $events = parent::index();

            if ($as = $this->params->get('as')) {
                $events = $events[$as];
            }

            // Eager load performance dates
            if (method_exists($events, 'loadMissing')) {
                $events = $events->loadMissing('performance_dates');
            }

            $dates = $events
                ->flatMap(function (Entry $event) {
                    return collect($event->get('performance_dates'))
                        ->map(fn (array $date) => array_merge(
                            $event->toAugmentedArray(),
                            $date
                        ));
                })
                ->pipe(function ($collection) use ($type) {
                    $now = now();
                    
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

            return $limit ? $dates->take($limit) : $dates;
        });
    }

    private function outputDates(Collection $dates): array
    {
        if ($groupByFormat = $this->params->get('group_by_format')) {
            if ($dates->isEmpty()) {
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
            
            return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($dates, $groupByFormat) {
                return collect([
                    'date_groups' => $dates
                        ->groupBy(fn ($event) => Carbon::parse(Arr::get($event, 'perf_date'))->format($groupByFormat))
                        ->map(fn ($events, $date_group) => ['date_group' => $date_group, 'entries' => $events])
                        ->values()
                        ->all(),
                ])->all();
            });
        }

        if ($as = $this->params->get('as')) {
            return [
                'no_results' => $dates->isEmpty(),
                $as => $dates->all(),
            ];
        }

        return $dates->all();
    }
}
