#!/bin/sh
export REPOSITORY="GEWIS/gewisdb"
export BRANCH="main"
cd /tmp
wget --no-cache "https://github.com/${REPOSITORY}/archive/refs/heads/${BRANCH}.zip"
unzip "${BRANCH}.zip"
rm "${BRANCH}.zip"
cp -R -u gewisdb-${BRANCH}/public/* /code/public/
rm -R /tmp/gewisdb-${BRANCH}
