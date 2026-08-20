# Compatibility

Tested 2026-08-20 against WordPress (wp-env latest), PHP 8.3, `WP_DEBUG` and
`WP_DEBUG_LOG` on. Reproduce with `tests/compat/matrix.sh`.

## What is actually checked

Nineteen assertions per plugin, aimed at the surfaces this plugin touches,
because that is where a conflict would show:

- media grid toolbar renders, with its filters, on two rows and no duplicate ids
- core's `filters-heading` screen-reader element survives
- attachments load through the real `query-attachments` endpoint
- search by taxonomy term, by title, and a nonsense term returning nothing
- list view, the bulk term picker, the bulk action, the taxonomy column
- all three settings screens
- the media modal inside the editor
- no JavaScript error **attributed to this plugin's files**
- no PHP notice, warning or fatal naming this plugin in `debug.log`

JavaScript errors are attributed by source URL. A companion shouting about its
own missing API key is not this plugin's failure, and lumping the two together
would either hide a real conflict or invent one.

## Results

Each plugin was activated alone, tested, and deactivated.

| Plugin | Result |
| --- | --- |
| Elementor | pass |
| WooCommerce | pass |
| Advanced Custom Fields | pass |
| Beaver Builder (Lite) | pass |
| Classic Editor | pass |
| FooGallery | pass |
| MetaSlider | pass |
| NextGEN Gallery | pass |
| Polylang | pass |
| Yoast SEO | pass |
| Rank Math | pass |
| LiteSpeed Cache | pass |
| Smush | pass |
| ShortPixel | pass |
| Jetpack | pass (alone; see below) |

Then **fourteen of them activated together**, which is the configuration real
sites are in: all nineteen checks pass. `debug.log` for the whole campaign
contains six lines, none from this plugin — Smush exhausting memory in its own
helper, a WooCommerce textdomain notice, and cron-schedule errors left by
activating and deactivating plugins in a loop.

Errors observed from companions, present with this plugin switched off and so
not ours: NextGEN's `photocrati_ajax is not defined` on non-NextGEN admin
screens, Yoast's React `defaultProps` deprecation warning, and ShortPixel's
"No API Key set for this site".

## Not covered

- **Jetpack in the full stack.** Jetpack makes blocking outbound calls to
  wordpress.com. In an offline container those never resolve and the whole
  bootstrap hangs — with this plugin switched off as well, so it is the sandbox
  and not a conflict. Jetpack passes when tested on its own. A stack test with
  Jetpack needs an environment with outbound network.
- **Divi, WPML, WP Rocket.** Commercial; no licence available. WPML is the one
  worth doing first: its media translation module duplicates attachments and
  filters attachment queries, which is where this plugin's `posts_search` /
  `posts_join` filters live. Divi ships its own media frame. WP Rocket
  concatenates admin JavaScript.
- **Multisite.** Single-site only so far.

## Why this matters here

This plugin filters `posts_search`, `posts_join` and `posts_distinct` on
attachment queries and *replaces* core's search clause — necessary so search
columns can be switched off, but it means every attachment query in the admin
passes through it. It also wraps core media views rather than replacing them
(see [architecture.md](architecture.md)); seven replacements remain and are the
most likely source of a future conflict with a plugin that ships its own media
frame.
