<?php

declare(strict_types=1);

return [
    // Column on projection models holding the aggregate uuid. This is independent of the
    // model's primary key: the projection may use an `id` PK with a separate uuid column,
    // or a `uuid` PK that doubles as this column. The package only reads/writes this column.
    'aggregate_uuid_column' => 'uuid',

    'stored_events_resource' => [
        'navigation_group' => null,
        'navigation_sort' => null,
        'per_page' => 25,
    ],

    'replay' => [
        // Master switch. The plugin option alone is NOT enough; this must also be true.
        // Defence in depth for a destructive-ish operation.
        'enabled' => false,
        // Gate/ability checked before showing or running replay. Null = panel access only.
        'authorize' => null,
    ],
];
