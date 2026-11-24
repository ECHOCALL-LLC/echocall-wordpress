#!/bin/sh
# Syntax check of every committed PHP file with the PHP version on the path.
set -u

status=0
for file in $(git ls-files '*.php'); do
  if ! out=$(php -l "$file" 2>&1); then
    echo "$out"
    status=1
  fi
done

if [ "$status" -eq 0 ]; then
  echo "$(git ls-files '*.php' | wc -l | tr -d ' ') files without syntax errors on PHP $(php -r 'echo PHP_VERSION;')."
fi
exit "$status"
