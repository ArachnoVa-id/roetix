<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\EventVariables;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoadEventProps
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $client = $request->route('client');

        // Get the event and associated venue
        $event = Event::where('slug', $client)
            ->first();
        if (!$event) {
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Event not found.',
                'props' => EventVariables::getDefaultValue()
            ]);
        }

        $props = $event->eventVariables;

        if (!$props) {
            return Inertia::render('User/Landing', [
                'client' => $client,
                'error' => 'Event variables not found for ' . $event->name . '.',
                'props' => EventVariables::getDefaultValue()
            ]);
        }

        $props->reconstructImgLinks();

        $request->merge([
            'event' => $event,
            'props' => $props,
        ]);

        return $next($request);
    }
}
