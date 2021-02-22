<?php

namespace App\Tags;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Entries\Entry;
use Statamic\Support\Arr;
use Statamic\Tags\Collection\Collection as CollectionTag;

class Events extends CollectionTag
{
    /**
     * @return string|array
     */
    public function future()
    {
        return $this->getEvents();
    }

    public function today()
    {
        return $this
            ->getEvents()
            ->filter(fn (Entry $entry) => Carbon::parse(Arr::get($event, 'perf_date'))->isToday());
    }

    public function count()
    {
        return $this->getEvents()->count();
    }

    private function getEvents(): Collection
    {
        $this->params->put('from', 'veranstaltungen');

        $events = parent::index();

        if ($as = $this->params->get('as')) {
            $events = $events[$as];
        }

        $dates = $events
            ->flatMap(fn (Entry $event) => collect($event->get('performance_dates'))
                ->map(fn (array $date) => array_merge($event->toAugmentedArray(), $date))
            )->filter(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')) > now())
            ->sortBy(fn (array $event) => Carbon::parse(Arr::get($event, 'perf_date')));

        // if group by
        if ($groupByFormat = $this->params->get('group_by_format')) {
            return collect([
                'date_groups' => $dates
                    ->groupBy(fn ($event) => Carbon::parse(Arr::get($event, 'perf_date'))->format($groupByFormat))
                    ->map(fn ($events, $date_group) => ['date_group' => $date_group, 'entries' => $events])
                    ->values(),
            ]);
        }

        return $dates;
    }

    private function hasFutureDate($dates): bool
    {
        return $this->getNextDate($dates) != null;
    }

    private function getNextDate(array $dates = []): ?Carbon
    {
        $data = collect($dates)->first(fn ($event) => Carbon::parse($event['perf_date']) >= now()->startOfDay());

        if (! $date = Arr::get($data, 'perf_date')) {
            return null;
        }

        return Carbon::parse($date);
    }
}
