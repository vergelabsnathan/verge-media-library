# How this plugin touches WordPress core

## The rule

**Wrap or add. Never replace.**

The original rule was "do not override core media UI". That cannot be satisfied.
WordPress core's media JavaScript exposes no hook API at all — `wp-includes/js/media-views.js`
contains zero `wp.hooks`, zero `applyFilters`, and one `doAction`. There is no supported way
to add a filter dropdown to the media grid. Patching core views is the only route, so the
question is not *whether* to patch but *how*.

Three ways to patch, in descending order of safety:

| Pattern | Verdict |
|---|---|
| `media.view.X = media.view.Y.extend({...})` — register a new view | Safe. Adds, changes nothing. |
| `core.X.method.apply(this, arguments)` then adjust — wrap | Safe. Core keeps running; we react to it. |
| `_.extend(media.view.X.prototype, {...})` without calling through — replace | **Liability.** Core's implementation is discarded and we silently fall behind every time WordPress changes it. |

## Why this is not theoretical

Two live bugs, both caused by replacement:

1. **The WordPress 7.0 layout break.** `AttachmentsBrowser.createToolbar` was replaced with a
   fork of core's version. WordPress 7.0 changed the toolbar to a CSS grid that places elements
   by id; our fork knew nothing about it and the toolbar stacked into a 300px block with every
   label above the wrong control.

2. **A live accessibility regression.** Core's `createToolbar` sets a `filters-heading` item — a
   screen-reader `<h2>` labelling the filter group. Our replacement never recreates it, so the
   heading is simply missing. Nobody noticed because replacement fails silently: core adds
   something, and our copy just doesn't have it.

A wrap would have inherited both changes for free.

## Current state

Measured against WordPress 7.0.4:

- **8 replacements** — the liability
- **4 wraps** — fine
- **9 added methods** — fine
- **3 new views** — fine

### The eight to convert

| Target | Why it was replaced | Route to a wrap |
|---|---|---|
| `AttachmentsBrowser.createToolbar` | to add taxonomy/author filters and to hide core's when disabled | Call core, then `toolbar.set()` ours and `toolbar.unset()` the disabled ones. Core's `Toolbar` exposes `get`/`set`/`unset`. Also restores `filters-heading`. |
| `AttachmentFilters.change` | reset-button state across several filters | Call core, then update the reset button |
| `AttachmentFilters.select` | match our extra props | Call core, then correct the selection |
| `AttachmentsBrowser.updateContent` | no-results messaging in our grid mode | Call core, then adjust; or gate to `eml-grid` only |
| `AttachmentsBrowser.createUploader` | same | as above |
| `AttachmentsBrowser.createAttachments` | same | as above |
| `AttachmentCompat.render` | taxonomy field classes and term counts | Call core, then post-process the DOM it produced |
| `controller.Library.uploading` | autoSelect behaviour | Call core, then adjust selection |

`createToolbar` is the one to do first: it is the largest, it caused the WP 7 break, and it is
the one dropping `filters-heading`.

## Guard

Converting is not enough on its own — a wrap can still drift if core renames a toolbar key.
The test ground should assert, per WordPress version in the matrix:

- every key core's `createToolbar` sets is still present after ours runs
  (catches a repeat of `filters-heading`)
- the toolbar occupies exactly two grid rows with each label sharing an x with its control
- no duplicate element ids in the toolbar

Those three checks would have caught all of: the WP 7.0 layout break, the duplicate author
filter id, and the missing heading.
