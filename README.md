# CiviEvent Block

CiviEvent Block is a standalone, dynamic Gutenberg block for upcoming public [CiviCRM](https://civicrm.org/) events. It recreates the core list and single-event workflows of the legacy CiviEvent Widget in the block editor without storing event markup in post content.

## Requirements

- WordPress 6.6 or newer
- PHP 7.4 or newer
- An active CiviCRM plugin

## Development

The block intentionally ships build-free JavaScript and uses WordPress-provided packages. No `node_modules` directory or compilation step is required.

Useful checks:

```sh
php -l civievent-block.php
find includes blocks -name '*.php' -exec php -l {} \;
node --check blocks/events/index.js
php tests/render-smoke.php
```

## Extension hooks

- `civievent_block_query_args` filters the Event.get API v3 parameters.
- `civievent_block_events` filters event records before rendering.
- `civievent_block_registration_label` filters registration link text.
