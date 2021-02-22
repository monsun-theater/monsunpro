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
            ->filter(function (Entry $entry) {
                return $this->getNextDate($entry->get('performance_dates'))->isToday();
            });
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

        /** @var \Illuminate\Support\Collection */
        $output = $events
            // filter to include events that have a date in the future
            ->filter(fn (Entry $event) => $this->hasFutureDate($event->get('performance_dates')));

        $entries = $output
            ->sortBy(fn (Entry $entry) => $this->getNextDate($entry->get('performance_dates')));

        // if group by
        if ($groupByFormat = $this->params->get('group_by_format')) {
            return collect([
                'date_groups' => $entries
                    ->groupBy(fn ($entry) => $this->getNextDate($entry->get('performance_dates'))->format($groupByFormat))
                    ->map(fn ($entries, $date_group) => ['date_group' => $date_group, 'entries' => $entries])
                    ->values(),
            ]);
        }

        return $entries;
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
