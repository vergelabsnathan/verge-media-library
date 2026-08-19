# Reply for the "Layout Issues in WP 7" thread

Thread: https://wordpress.org/support/topic/layout-issues-in-wp-7/

Post as a reply. Plain, one link, no pitch.

---

Confirming this and adding the detail, since I went and traced it.

WordPress 7.0 changed `.media-toolbar-secondary` in `wp-includes/css/media-views.css` from inline-block flow to a fixed grid:

```css
.media-toolbar-secondary {
    display: grid;
    grid-template-columns: repeat( 2, 1fr );
    grid-template-rows: repeat( 2, 1fr );
}
```

It then places only its own two filters, by id:

```css
label[for="media-attachment-filters"] { grid-area: 1 / 1 / 2 / 2; }
select#media-attachment-filters       { grid-area: 2 / 1 / 3 / 2; }
```

Everything the plugin adds to that same container — the author filter, the taxonomy filters, Reset All Filters, the search box — has no `grid-area`, so it falls into implicit auto-placement and stacks onto extra rows.

There is a second problem underneath it. The plugin's author filter renders with the **same id as the type filter** (`media-attachment-filters`), because `AttachmentFilters.Authors` doesn't declare its own `id` the way `AttachmentFilters.Taxonomy` does. That was harmless when the layout was inline-block, but now that WordPress places elements by id, both get assigned the same cell and draw on top of each other. That is why the type filter looks missing and why the remaining labels sit above the wrong controls.

On WP 7.0.4 the toolbar goes from 300px tall across six grid rows down to 66px across two once both are fixed.

If you want to patch a site yourself, the layout half is CSS only. Two rows of column flow instead of the fixed 2x2, and the two core selects released by name, because an id selector outranks any number of classes:

```css
body.eml-media-css .attachments-browser .media-toolbar-secondary {
    display: grid;
    grid-auto-flow: column;
    grid-template-columns: none;
    grid-template-rows: auto auto;
    grid-auto-columns: minmax(0, max-content);
    justify-content: start;
    align-items: end;
    column-gap: 12px;
}
body.eml-media-css .attachments-browser .media-toolbar-secondary > label,
body.eml-media-css .attachments-browser .media-toolbar-secondary > select#media-attachment-filters,
body.eml-media-css .attachments-browser .media-toolbar-secondary > select#media-attachment-date-filters {
    grid-area: auto;
}
body.eml-media-css .attachments-browser .media-toolbar-secondary > .view-switch,
body.eml-media-css .attachments-browser .media-toolbar-secondary > .media-button {
    grid-row: 1 / -1;
}
```

Add that at 900px and below as `display: block`, which is what core does at that breakpoint, or the desktop layout survives onto mobile.

The duplicate id needs a JS change, so CSS alone won't fully fix the author filter.

I've put a patched build together with these fixes and the PHP 8 warnings cleared. It's GPLv2 like the original and credits wpUXsolutions: https://github.com/vergelabsnathan/vergelabs-media-library/releases

---

## Note before posting

WordPress.org support forums discourage promoting a different plugin inside another
plugin's support threads. The text above leads with the diagnosis and a fix people can
apply to their existing install, and mentions the build once at the end — which is the
defensible shape. If a moderator objects, the fallback is to drop the final paragraph
entirely and leave the diagnosis, which is useful on its own.
