<div class="fi-fes-event-history space-y-3">
    @if ($capped)
        <p class="fi-fes-event-history-notice text-sm text-gray-500 dark:text-gray-400">
            Showing the latest {{ $cap }} events. Older events are not shown.
        </p>
    @endif

    @forelse ($events as $event)
        <details class="fi-fes-event-history-item rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <summary class="cursor-pointer">
                <span class="font-medium">{{ class_basename($event->event_class) }}</span>
                <span class="text-gray-500 dark:text-gray-400">v{{ $event->aggregate_version }}</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $event->created_at }}</span>
            </summary>

            @php($payload = $event->event_properties)
            @php($payload = is_object($payload) && method_exists($payload, 'toArray') ? $payload->toArray() : (is_string($payload) ? json_decode($payload, true) : (array) $payload))
            <pre class="fi-fes-event-history-payload mt-2 overflow-x-auto text-xs">{{ json_encode($payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No events recorded.</p>
    @endforelse
</div>
