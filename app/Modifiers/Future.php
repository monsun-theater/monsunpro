<?php

namespace App\Modifiers;

use Illuminate\Support\Carbon;
use Statamic\Modifiers\Modifier;
use Statamic\Support\Arr;

class Future extends Modifier
{
    /**
     * Modify a value.
     *
     * @param mixed  $value    The value to be modified
     * @param array  $params   Any parameters used in the modifier
     * @param array  $context  Contextual values
     * @return mixed
     */
    public function index($dates, $params, $context)
    {
        $dates = collect($dates)->filter(function ($date) {
            return Carbon::parse(Arr::get($date, 'perf_date')) > Carbon::now();
        })->values();

        if ($dates->isEmpty()) {
            return ['no_results' => true];
        }

        return $dates;
    }
}
