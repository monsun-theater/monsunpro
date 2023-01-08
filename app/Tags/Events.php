<?php

namespace App\Tags;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;
use Statamic\Support\Arr;
use Statamic\Tags\Collection\Collection as CollectionTag;

class Events extends CollectionTag
{
    public function afterToday()
    {
        return $this->outputDates($this
            ->getDates()
            ->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) > now()->endOfDay())
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

    public function count()
    {
        return $this->getDates()->count();
    }

    private function getDates(string $type = 'future'): Collection
    {
    $this->params->put('from', 'veranstaltungen');
    $limit = $this->params->pull('limit');

    $events = parent::index();

    if ($as = $this->params->get('as')) {
        $events = $events[$as];
    }

    $dates = $events
        ->flatMap(fn (Entry $event) => collect($event->get('performance_dates'))
            ->map(fn (array $date) => array_merge($event->toAugmentedArray(), $date))
        )
        ->sortBy(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')));

    if ($type === 'pastPerformances') {
        $dates = $dates->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) <= now());
    } elseif ($type === 'future') {
        $dates = $dates->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) > now());
    }

    return $dates->take($limit);
    }


    private function outputDates(Collection $dates): array
    {
        if ($groupByFormat = $this->params->get('group_by_format')) {
            if ($dates->isEmpty()) {
                return ['no_results' => true];
            }

            return collect([
                'date_groups' => $dates
                ->groupBy(fn ($event) => Carbon::parse(Arr::get($event, 'perf_date'))->format($groupByFormat))
                ->map(fn ($events, $date_group) => ['date_group' => $date_group, 'entries' => $events])
                ->values()
                ->all(),
            ])->all();
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
