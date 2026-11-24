#!/bin/sh
# The version lives in four places. They have to agree, and on a release they
# have to agree with the tag as well: sh check-versions.sh v2.0.0
set -eu

header=$(sed -n 's/^ \* Version: *\([0-9][0-9.]*\)$/\1/p' echocall-chat-widget.php)
constant=$(sed -n "s/^define( 'ECHOCALL_CHAT_VERSION', '\([0-9.]*\)' );$/\1/p" echocall-chat-widget.php)
stable=$(sed -n 's/^Stable tag: *\([0-9][0-9.]*\)$/\1/p' readme.txt)
changelog=$(sed -n 's/^## \[\([0-9][0-9.]*\)\].*/\1/p' CHANGELOG.md | head -n 1)

echo "plugin header: $header"
echo "ECHOCALL_CHAT_VERSION: $constant"
echo "readme.txt stable tag: $stable"
echo "newest CHANGELOG.md section: $changelog"

if [ -z "$header" ] || [ "$header" != "$constant" ] || [ "$header" != "$stable" ] || [ "$header" != "$changelog" ]; then
  echo "The versions do not agree."
  exit 1
fi

if [ $# -gt 0 ] && [ "v$header" != "$1" ]; then
  echo "The tag $1 does not match version $header."
  exit 1
fi

echo "All versions are $header."
