# WordPress Playground link

Opens a throwaway WordPress 7.x on PHP 8.3 in the browser, installs and activates this
plugin, seeds four media categories, a second author and eight images, and lands on the
media grid so the repaired toolbar is the first thing you see.

```
https://playground.wordpress.net/?blueprint-url=https%3A%2F%2Fraw.githubusercontent.com%2Fvergelabsnathan%2Fverge-media-library%2Fmain%2Fplayground%2Fblueprint.json
```

Verified end to end: 8 attachments, 4 filters, toolbar 66px across two grid rows, no
duplicate element ids.

## Why the zip is mirrored in this repo

GitHub release assets send no `Access-Control-Allow-Origin` header, so a browser-based
Playground cannot fetch them. `raw.githubusercontent.com` sends `ACAO: *`, so
`playground/verge-media-library.zip` is served from the repo instead. The community
`github-proxy.com` relay was unreachable when tested and is deliberately not relied on.

`playground/` is `export-ignore`d, so the mirrored zip never lands inside the release zip.

## Keeping it current

The blueprint points at `main`, so the mirrored zip must be refreshed on every release:

```
git archive --format=zip --prefix=verge-media-library/ -o dist/verge-media-library-<v>.zip HEAD
cp dist/verge-media-library-<v>.zip playground/verge-media-library.zip
git add -f playground/verge-media-library.zip
```

Worth folding into the release script in Phase 1 so it cannot be forgotten.
