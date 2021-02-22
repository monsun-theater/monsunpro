<?php

namespace App\Tags;

use Carbon\Carbon;
use Statamic\Entries\Entry;
use Statamic\Support\Arr;
use Statamic\Tags\Collection\Collection as CollectionTag;

class FutureEvents extends CollectionTag
{
    /**
     * @return string|array
     */
    public function index()
    {
        return $this->getEvents();
    }

    public function count()
    {
        return $this->getEvents()->count();
    }

    private function getEvents()
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
            return [
                'date_groups' => $entries
                    ->groupBy(fn ($entry) => Carbon::parse($this->getNextDate($entry->get('performance_dates')))->format($groupByFormat))
                    ->map(fn ($entries, $date_group) => ['date_group' => $date_group, 'entries' => $entries])
                    ->values(),
            ];
        }

        return $entries;
    }

    private function hasFutureDate($dates): bool
    {
        return $this->getNextDate($dates) != null;
    }

    private function getNextDate(array $dates = [])
    {
        $data = collect($dates)->first(fn ($event) => Carbon::parse($event['perf_date']) >= now()->startOfDay());

        return Arr::get($data, 'perf_date');
    }
}
