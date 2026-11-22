#!/usr/bin/env bash
# Roundcube 1.6.x conflicts with PHP 8.5 native array_first() — wrap helpers.
set -euo pipefail
BOOT=/usr/share/roundcube/program/lib/Roundcube/bootstrap.php
python3 - <<'PY'
from pathlib import Path
p = Path("/usr/share/roundcube/program/lib/Roundcube/bootstrap.php")
text = p.read_text()
if "!function_exists('array_first')" in text:
    print("already patched")
    raise SystemExit(0)
replacements = [
    (
        """function array_first($array)
{
    if (is_array($array)) {
        reset($array);
        foreach ($array as $element) {
            return $element;
        }
    }
}""",
        """if (!function_exists('array_first')) {
    function array_first($array)
    {
        if (is_array($array)) {
            reset($array);
            foreach ($array as $element) {
                return $element;
            }
        }
    }
}""",
    ),
    (
        """function array_last($array)
{
    if (is_array($array)) {
        return end($array);
    }
}""",
        """if (!function_exists('array_last')) {
    function array_last($array)
    {
        if (is_array($array)) {
            return end($array);
        }
    }
}""",
    ),
]
for old, new in replacements:
    if old in text:
        text = text.replace(old, new, 1)
p.write_text(text)
print("patched")
PY
php -l "$BOOT" >/dev/null