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
    public function future()
    {
        return $this->outputDates($this->getDates());
    }

    public function premieres()
    {
        return $this->outputDates(
            $this
                ->getDates()
                ->filter(fn (array $event) => Arr::get($event, 'premiere_toggle', false))
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

    private function getDates(): Collection
    {
        $this->params->put('from', 'veranstaltungen');
        $limit = $this->params->pull('limit');

        $events = parent::index();

        if ($as = $this->params->get('as')) {
            $events = $events[$as];
        }

        return $events
            ->flatMap(fn (Entry $event) => collect($event->get('performance_dates'))
                ->map(fn (array $date) => array_merge($event->toAugmentedArray(), $date))
            )->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) > now())
            ->sortBy(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')))
            ->take($limit);
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
