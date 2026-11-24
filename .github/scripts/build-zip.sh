#!/bin/sh
# Builds build/echocall-chat-widget.zip from the committed state. WordPress takes
# the folder inside the ZIP as the plugin slug, so it is always echocall-chat-widget,
# whatever the repository is called. .gitattributes keeps the GitHub files out.
set -eu

slug=echocall-chat-widget
rm -rf build
mkdir -p build
git archive --format=zip --prefix="$slug/" -o "build/$slug.zip" HEAD
echo "Built build/$slug.zip"
