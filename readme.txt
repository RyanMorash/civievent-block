=== CiviEvent Block ===
Contributors: ryanmorash
Tags: civicrm, events, block, gutenberg, calendar
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Display upcoming public CiviCRM events in a dynamic Gutenberg block.

== Description ==

CiviEvent Block adds a server-rendered CiviCRM Events block. Event information is loaded from CiviCRM whenever the page is viewed, so published content stays current without resaving the post.

The block supports:

* Lists of up to 20 upcoming events or one upcoming event.
* Event type filtering and single-event offsets.
* Optional summaries, times, registration links, and a view-all link.
* Optional city, state/province, and country output.
* Card, divided, and striped styles plus standard WordPress color, spacing, typography, alignment, and anchor controls.
* A live server-rendered editor preview.

This plugin requires CiviCRM with the CiviEvent component enabled. CiviCRM is not hosted in the WordPress.org Plugin Directory and must be installed separately.

If CiviCRM is not active, CiviEvent Block remains active without causing a fatal error. Administrators see guidance on the Plugins screen, editors see a configuration message in the block preview, and the block produces no public output until CiviCRM is available.

== Installation ==

1. [Install and activate CiviCRM](https://docs.civicrm.org/installation/en/latest/wordpress/), then confirm that the CiviEvent component is enabled.
2. Upload the `civievent-block` directory to `/wp-content/plugins/`.
3. Activate CiviEvent Block.
4. Insert the CiviCRM Events block in the block editor and choose its settings in the block sidebar.

== Frequently Asked Questions ==

= Which events are displayed? =

The block displays active, public, non-template events whose start date is today or later, ordered by start date. Developers can adjust the CiviCRM API parameters with the `civievent_block_query_args` filter.

= Can I customize the markup or query? =

The `civievent_block_query_args`, `civievent_block_events`, and `civievent_block_registration_label` filters allow theme or plugin code to adjust the query, event records, and registration label.

= What happens if CiviCRM is not active? =

The plugin can still be activated safely. It shows a warning to administrators on the Plugins screen and a configuration message to editors. Visitors see no broken markup or error message; the block begins displaying events after CiviCRM is installed and active.

== Trademark ==

CiviCRM is a registered trademark of CIVICRM LLC.

== Changelog ==

= 1.0.0 =

* Change block namespace to match plugin slug
* Remove the unsupported WordPress.org dependency header for CiviCRM.
* Handle a missing CiviCRM installation with contextual administrator and editor guidance.
* Document the external CiviCRM installation requirement.

= 0.1.0 =

* Initial release with dynamic list and single-event modes.
