# Adoption request

Two messages. Send the author one first and give it a few days, then send the
plugins team one and say you tried. The plugins team asks that you contact the
developer before asking them to step in.

---

## 1. To the author

To: wpUXsolutions@gmail.com
Subject: Enhanced Media Library — WordPress 7 patch, and an offer to help maintain

Hello,

I use Enhanced Media Library and I'd rather it stayed alive than quietly broke on people.

WordPress 7.0 changed `.media-toolbar-secondary` to a fixed CSS grid that places only its own two filters, by id. Everything EML adds to that container has no grid area and stacks below the toolbar. There's a second issue behind it: `AttachmentFilters.Authors` inherits the base id `media-attachment-filters`, so under id-based placement the author filter and the type filter land in the same cell and overlap. On WP 7.0.4 the toolbar goes from 300px over six rows to 66px over two once both are fixed.

I've also cleared the PHP 8 warnings. The four settings handlers read their nonce field before checking it exists, and with WP_DEBUG on that echoed warning breaks headers for the rest of the request. Four `get_terms()` calls still use the signature deprecated in WP 4.5.

One thing you'll want regardless of what you decide: `wpuxss_eml_apply_settings_to_network` checks a nonce but no capability, and it writes options into every site on a network. It isn't cleanly exploitable, since the nonce is only printed on a super-admin screen, but it should require `manage_network_options`.

The patches are here, GPLv2, attributed to you, and yours to take with no conditions:
https://github.com/vergelabsnathan/verge-media-library

If you'd like to keep ownership and just want the fixes, say the word and I'll send them however suits you — a diff, a PR, or SVN patches. If you'd rather hand the plugin on, I'm willing to take over maintenance and would keep it free and GPL.

Either is fine by me. I'd just like the 70,000 sites running it not to be stuck.

Nathan
nathan@vergelabs.nl

---

## 2. To the plugins team

Send only after the author has had a reasonable window, and only if there's no reply.

To: plugins@wordpress.org
Subject: Adoption request — enhanced-media-library

Hello,

I'd like to be considered to adopt **enhanced-media-library**.

- Last release: 2.9.4, 15 July 2024.
- Author: wpUXsolutions (@webbistro). No forum replies since May 2025; recent threads are unanswered.
- Roughly 70,000 active installs, currently broken on WordPress 7.0.

I emailed the author at wpUXsolutions@gmail.com on <DATE> offering the patches or to take over maintenance, and posted the same offer in the support forum. No reply as of <DATE>.

I have already fixed the outstanding issues and published the work under GPLv2 with attribution to the original author:

- Repository: https://github.com/vergelabsnathan/verge-media-library
- Try it in the browser: <PLAYGROUND_URL>

Fixed: the WordPress 7.0 media toolbar layout, a duplicate element id that made the author filter overlap the type filter, the PHP 8 undefined-array-key warnings in the four settings handlers, and four deprecated `get_terms()` calls. I also added a missing `manage_network_options` capability check to the AJAX handler that writes options across a multisite network — I'd flag that one for your attention whatever the outcome of this request.

If adoption isn't appropriate, I'm equally happy to submit the patches for someone else to commit. My interest is that the plugin keeps working.

WordPress.org username: <WPORG_USERNAME>
Two-factor authentication is enabled on the account.

Thanks,
Nathan Verge
nathan@vergelabs.nl

---

## Before sending

- `<DATE>` twice in the second email.
- `<WPORG_USERNAME>` — the readme `Contributors:` field currently says `vergelabsnathan`,
  which is a GitHub username. Confirm the real WordPress.org one and correct the readme
  to match, or the first submission bounces.
- `<PLAYGROUND_URL>` — see docs/outreach/playground.md.
- Adoption is not guaranteed and the team can take a long time. Nothing here depends on
  it; the fork stands on its own either way.
