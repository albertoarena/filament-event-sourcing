@php
    /**
     * Reusable, build-free JSON payload renderer with server-side syntax highlighting.
     *
     * Shared by the event-history slide-over and the Stored Events resource so both
     * present identical, theme-aware (light/dark) highlighted JSON. Accepts $payload
     * as an array, a JSON string, or an arrayable object.
     */
    $fesPayload = $payload ?? [];

    if (is_object($fesPayload) && method_exists($fesPayload, 'toArray')) {
        $fesPayload = $fesPayload->toArray();
    } elseif (is_string($fesPayload)) {
        $fesPayload = json_decode($fesPayload, true);
    }

    $fesPayload = is_array($fesPayload) ? $fesPayload : (array) $fesPayload;

    $fesJson = json_encode($fesPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($fesJson === false || $fesJson === '[]') {
        $fesHighlighted = '<span class="fes-json-empty">{ }</span>';
    } else {
        // Escape markup but keep quotes intact so the tokenizer can see strings.
        $fesEscaped = htmlspecialchars($fesJson, ENT_NOQUOTES);

        $fesHighlighted = preg_replace_callback(
            '/(?<str>"(?:\\\\.|[^"\\\\])*")(?<colon>\s*:)?|(?<bool>\btrue\b|\bfalse\b|\bnull\b)|(?<num>-?\d+(?:\.\d+)?(?:[eE][+\-]?\d+)?)/',
            static function (array $m): string {
                if (($m['str'] ?? '') !== '') {
                    return ($m['colon'] ?? '') !== ''
                        ? '<span class="fes-json-key">'.$m['str'].'</span>'.$m['colon']
                        : '<span class="fes-json-string">'.$m['str'].'</span>';
                }

                if (($m['bool'] ?? '') !== '') {
                    return '<span class="fes-json-bool">'.$m['bool'].'</span>';
                }

                return '<span class="fes-json-number">'.$m['num'].'</span>';
            },
            $fesEscaped
        );
    }
@endphp

@once
    <style>
        .fes-code {
            --fes-code-bg: #f9fafb;
            --fes-code-border: #e5e7eb;
            --fes-json-key: #2563eb;
            --fes-json-string: #15803d;
            --fes-json-number: #b45309;
            --fes-json-bool: #7c3aed;
            --fes-json-punct: #9ca3af;
            margin: 0;
            padding: 0.75rem 0.875rem;
            border: 1px solid var(--fes-code-border);
            background: var(--fes-code-bg);
            border-radius: 0.625rem;
            overflow-x: auto;
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
            font-size: 0.75rem;
            line-height: 1.5;
            tab-size: 2;
        }

        .dark .fes-code {
            --fes-code-bg: rgba(255, 255, 255, 0.03);
            --fes-code-border: rgba(255, 255, 255, 0.1);
            --fes-json-key: #60a5fa;
            --fes-json-string: #4ade80;
            --fes-json-number: #fbbf24;
            --fes-json-bool: #c4b5fd;
            --fes-json-punct: #6b7280;
        }

        .fes-code code { font: inherit; color: var(--fes-json-punct); white-space: pre; }
        .fes-json-key { color: var(--fes-json-key); }
        .fes-json-string { color: var(--fes-json-string); }
        .fes-json-number { color: var(--fes-json-number); }
        .fes-json-bool { color: var(--fes-json-bool); font-style: italic; }
        .fes-json-empty { color: var(--fes-json-punct); font-style: italic; }
    </style>
@endonce

<pre class="fes-code"><code>{!! $fesHighlighted !!}</code></pre>
