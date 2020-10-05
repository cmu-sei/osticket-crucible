#!/bin/sh
echo running $0
set -e

BASE_FILE=/tmp/osticket-plugin.phar

echo "Copying built-in Crucible phar to plugins folder..."
cp $BASE_FILE  /data/upload/include/plugins/crucible.phar
echo "Done."

# Disabling auto-update for now, time comparison doesn't work well
# FILE=/plugin/osticket-plugin.phar
# echo "Copying built-in Crucible phar to mount location..."
# cp $BASE_FILE /plugin/osticket-plugin.phar.base-image
# echo "Done."

# if test -f "$FILE"; then
#     echo "Crucible phar exists at $FILE"
#     if [ $FILE -nt $BASE_FILE ]; then
#         echo "$FILE is newer than $BASE_FILE, replacing it..."
#         cp $FILE  /data/upload/include/plugins/crucible.phar
#         echo "Done."
#     else
#         echo "$FILE is NOT newer than $BASE_FILE, no changes to be made."
#     fi
# else
#     echo "Crucible phar does not exist at $FILE, no updates to be applied."
# fi