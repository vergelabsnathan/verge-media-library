#!/bin/bash
#
#  Compatibility matrix.
#
#  Run before every release. Each companion plugin is activated on its own,
#  tested, then deactivated, so a failure names exactly one plugin rather than
#  leaving a stack to bisect. Run it again with several active together -- that
#  is the configuration real sites are in.
#
#  Usage, from the plugin root:
#      npx @wordpress/env run cli -- wp eval-file \
#          /var/www/html/wp-content/plugins/vergelabs-media-library/tests/compat/seed.php
#      bash tests/compat/matrix.sh elementor woocommerce advanced-custom-fields
#
#  Note: the smoke test talks to http://localhost:8888 because that is the
#  site's home URL. Using 127.0.0.1 sets the auth cookie on a host the
#  post-login redirect never returns to, and every check then fails at login.
#
set -u
export MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL='*'

HERE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT="$( cd "$HERE/../.." && pwd )"
OUT="$HERE/results.txt"

wpcli() { ( cd "$ROOT" && npx --yes @wordpress/env@latest run cli -- wp "$@" 2>&1 | grep -viE "^ℹ|^✔" ); }

: > "$OUT"

for slug in "$@"; do
    echo "### $slug" >&2
    act=$(wpcli plugin activate "$slug")
    echo "$act" >&2

    if ! echo "$act" | grep -qi "Plugin .* activated\|already active"; then
        echo "$slug | ACTIVATION FAILED | $(echo "$act" | tr '\n' ' ' | cut -c1-120)" >> "$OUT"
        wpcli plugin deactivate "$slug" >/dev/null
        continue
    fi

    res=$( cd "$HERE" && node smoke.js "$slug" 2>&1 )
    echo "$res" >&2
    verdict=$(echo "$res" | grep '^VERDICT' | head -1)
    fails=$(echo "$res" | grep '^  FAIL' | sed 's/^  FAIL //' | tr '\n' ';')
    echo "$slug | ${verdict:-NO VERDICT} | $fails" >> "$OUT"

    wpcli plugin deactivate "$slug" >/dev/null
done

echo "=== RESULTS ===" >&2
cat "$OUT" >&2
