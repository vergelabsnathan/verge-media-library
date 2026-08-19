# Submitting to WordPress.org

State of the gate as of 2.9.8. Everything here has been run, not assumed.

## Done

| Requirement | State |
|---|---|
| `wp plugin check` errors | **0** |
| `wp plugin check` warnings | **0** |
| `php -l` on every file | clean |
| Runs on current WordPress | verified on 7.0.4 / PHP 8.3.33 |
| `debug.log` clean after exercising every screen | yes |
| GPLv2 or later, attribution to wpUXsolutions | header, readme, admin footer |
| Unique prefix on functions, classes, options, handles, AJAX actions | `vergeml_` / `vergeml-` |
| No minified code without source | both files recovered to readable source |
| No external service calls | the upstream notice poller is removed |
| No locked features or upsell | the three "/ Premium Feature" blocks are gone |
| Dev files excluded from the zip | `.gitattributes` export-ignore, verified against the built archive |
| Version consistency | header, `VERGEML_VERSION` and `Stable tag` asserted equal at build |
| Screenshots | six, captured from a real install, in `assets/` |

## Not done — needs you

**`Contributors:` in readme.txt.** It currently reads `vergelabsnathan`, which is a
GitHub handle. It must be a real WordPress.org username, and that account needs
two-factor enabled before it can submit anything.

Confirm the username, then:

```
readme.txt  ->  Contributors: <your-wordpress-org-username>
```

That is the only thing between this and a submission.

## When you submit

1. One submission at a time. If the review team replies, answer that email — do not
   open a second submission.
2. Upload the artifact built by `git archive`, not a zip of the working directory. The
   working directory carries `.wp-env.json`, `dist/` and `playground/`, which Plugin
   Check flags as hidden and compressed files. The built archive contains none of them.

```
git archive --format=zip --prefix=vergelabs-media-library/ -o dist/vergelabs-media-library-<version>.zip HEAD
```

3. Screenshots live in the SVN `assets/` directory, not inside the plugin zip. The six
   in `assets/` here are export-ignored for exactly that reason.

## Not blocking, worth knowing

- The strings that used to borrow WordPress's own translations now carry our text
  domain, because wordpress.org does not allow borrowing core's. They will show in
  English until translated at translate.wordpress.org. This was a deliberate trade.
- `eml-save-changes-message` is used twice as an id. Cosmetic, logged in
  `architecture.md`.
- Seven of the eight core-view replacements are still replacements rather than wraps.
  `createToolbar` is converted; the rest are listed in `architecture.md` with the route
  for each. Not a submission concern, a maintenance one.
