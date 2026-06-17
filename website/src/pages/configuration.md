---
layout: ../layouts/Layout.astro
title: Configuration
description: The full configuration reference.
---

# Configuration

The config file is published to `config/filament-event-sourcing.php`.

```php
return [
    // Column on projection models holding the aggregate uuid. Independent of the primary key.
    'aggregate_uuid_column' => 'uuid',

    'stored_events_resource' => [
        'navigation_group' => null,
        'navigation_sort' => null,
        'per_page' => 25,
    ],

    'replay' => [
        // Master switch. The plugin option alone is not enough; this must also be true.
        'enabled' => false,
        // Gate ability checked before showing or running a replay. Null means panel access only.
        'authorize' => null,
    ],
];
```

## Options

- `aggregate_uuid_column` is the column the audit tooling and write bridge use to link a
  projection to its aggregate. It is independent of the model's primary key. A model may also
  override it with a `getAggregateUuidColumn()` method.
- `stored_events_resource.navigation_group` and `navigation_sort` place the Stored Events
  resource in the panel navigation.
- `stored_events_resource.per_page` sets the default pagination size for the Stored Events table.
- `replay.enabled` is the master switch for the replay page. The plugin's `replayPage()` option
  is required as well.
- `replay.authorize` is an optional gate ability checked before the replay page is shown or a
  replay runs. Null means any user with panel access may replay.
