@php
    /**
     * Self-contained styling.
     *
     * Filament's bundled stylesheet only ships the utility classes Filament itself
     * uses, so arbitrary Tailwind utilities in a distributed plugin view are not
     * guaranteed to resolve in a host panel. To render reliably in any panel
     * (light or dark) with no build step, this view scopes its own styles under
     * `.fi-fes-event-history` and highlights the JSON payload server-side.
     */

    $categoryFor = static function (string $class): string {
        $name = class_basename($class);

        return match (true) {
            str_contains($name, 'Created') => 'created',
            str_contains($name, 'Deleted') => 'deleted',
            str_contains($name, 'Failed') => 'failed',
            str_contains($name, 'Changed'), str_contains($name, 'Updated') => 'changed',
            default => 'default',
        };
    };
@endphp

<style>
    .fi-fes-event-history {
        --fes-text: #111827;
        --fes-muted: #6b7280;
        --fes-border: #e5e7eb;
        --fes-card: #ffffff;
        --fes-code-bg: #f9fafb;
        --fes-rail: #e5e7eb;
        --fes-json-key: #2563eb;
        --fes-json-string: #15803d;
        --fes-json-number: #b45309;
        --fes-json-bool: #7c3aed;
        --fes-json-punct: #9ca3af;
    }

    .dark .fi-fes-event-history {
        --fes-text: #f4f4f5;
        --fes-muted: #9ca3af;
        --fes-border: rgba(255, 255, 255, 0.1);
        --fes-card: rgba(255, 255, 255, 0.02);
        --fes-code-bg: rgba(255, 255, 255, 0.03);
        --fes-rail: rgba(255, 255, 255, 0.12);
        --fes-json-key: #60a5fa;
        --fes-json-string: #4ade80;
        --fes-json-number: #fbbf24;
        --fes-json-bool: #c4b5fd;
        --fes-json-punct: #6b7280;
    }

    .fi-fes-event-history {
        color: var(--fes-text);
    }

    .fi-fes-event-history-notice {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.625rem 0.875rem;
        border: 1px solid rgba(245, 158, 11, 0.3);
        background: rgba(245, 158, 11, 0.1);
        border-radius: 0.625rem;
        font-size: 0.8125rem;
        line-height: 1.25rem;
        color: #b45309;
    }

    .dark .fi-fes-event-history-notice {
        color: #fbbf24;
    }

    .fi-fes-event-history-notice svg {
        flex: none;
        width: 1rem;
        height: 1rem;
    }

    /* Timeline */
    .fes-timeline {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .fes-event {
        position: relative;
        padding-left: 1.75rem;
    }

    /* Connecting rail */
    .fes-event::before {
        content: '';
        position: absolute;
        left: 0.3125rem;
        top: 1.25rem;
        bottom: -1rem;
        width: 2px;
        background: var(--fes-rail);
    }

    .fes-event:last-child::before {
        display: none;
    }

    /* Node dot */
    .fes-event::after {
        content: '';
        position: absolute;
        left: 0;
        top: 0.4375rem;
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 9999px;
        background: var(--fes-card);
        box-shadow: 0 0 0 2px var(--fes-dot, #9ca3af);
    }

    .fes-event.is-created { --fes-dot: #16a34a; --fes-accent: #15803d; --fes-accent-bg: rgba(34, 197, 94, 0.12); }
    .fes-event.is-deleted { --fes-dot: #dc2626; --fes-accent: #b91c1c; --fes-accent-bg: rgba(239, 68, 68, 0.12); }
    .fes-event.is-failed  { --fes-dot: #ea580c; --fes-accent: #c2410c; --fes-accent-bg: rgba(249, 115, 22, 0.13); }
    .fes-event.is-changed { --fes-dot: #2563eb; --fes-accent: #1d4ed8; --fes-accent-bg: rgba(59, 130, 246, 0.12); }
    .fes-event.is-default { --fes-dot: #6b7280; --fes-accent: #374151; --fes-accent-bg: rgba(107, 114, 128, 0.12); }

    .dark .fes-event.is-created { --fes-accent: #4ade80; }
    .dark .fes-event.is-deleted { --fes-accent: #f87171; }
    .dark .fes-event.is-failed  { --fes-accent: #fb923c; }
    .dark .fes-event.is-changed { --fes-accent: #60a5fa; }
    .dark .fes-event.is-default { --fes-accent: #d1d5db; }

    .fes-card {
        border: 1px solid var(--fes-border);
        background: var(--fes-card);
        border-radius: 0.75rem;
        overflow: hidden;
    }

    .fes-card-header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 0.875rem;
    }

    .fes-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.125rem;
        color: var(--fes-accent);
        background: var(--fes-accent-bg);
        white-space: nowrap;
    }

    .fes-version {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.4375rem;
        border-radius: 0.375rem;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        color: var(--fes-muted);
        background: var(--fes-code-bg);
        border: 1px solid var(--fes-border);
    }

    .fes-time {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 0.3125rem;
        font-size: 0.75rem;
        color: var(--fes-muted);
        white-space: nowrap;
    }

    .fes-time svg {
        width: 0.875rem;
        height: 0.875rem;
        opacity: 0.7;
    }

    /* The JSON block comes from the shared partial; attach it to the card header
       by dropping its standalone frame (rounded corners + full border). */
    .fi-fes-event-history .fes-card .fes-code {
        border-width: 1px 0 0 0;
        border-radius: 0;
    }

    .fes-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 2.5rem 1rem;
        text-align: center;
        color: var(--fes-muted);
        border: 1px dashed var(--fes-border);
        border-radius: 0.75rem;
    }

    .fes-empty svg {
        width: 2rem;
        height: 2rem;
        opacity: 0.6;
    }
</style>

<div class="fi-fes-event-history">
    @if ($capped)
        <p class="fi-fes-event-history-notice">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" />
            </svg>
            Showing the latest {{ $cap }} events. Older events are not shown.
        </p>
    @endif

    @forelse ($events as $event)
        @php
            $category = $categoryFor($event->event_class);
            $payload = $event->event_properties;
            $payload = is_object($payload) && method_exists($payload, 'toArray')
                ? $payload->toArray()
                : (is_string($payload) ? json_decode($payload, true) : (array) $payload);
        @endphp

        @if ($loop->first)
            <ol class="fes-timeline">
        @endif

            <li class="fi-fes-event-history-item fes-event is-{{ $category }}">
                <div class="fes-card">
                    <div class="fes-card-header">
                        <span class="fes-badge">{{ class_basename($event->event_class) }}</span>
                        <span class="fes-version">v{{ $event->aggregate_version }}</span>
                        <span class="fes-time">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                            </svg>
                            {{ $event->created_at }}
                        </span>
                    </div>
                    <div class="fi-fes-event-history-payload">
                        @include('filament-event-sourcing::partials.json-payload', ['payload' => $payload ?? []])
                    </div>
                </div>
            </li>

        @if ($loop->last)
            </ol>
        @endif
    @empty
        <div class="fes-empty">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            No events recorded.
        </div>
    @endforelse
</div>
