#!/usr/bin/env bash
#
# Build the DMS image and export it as a portable tar.gz for an offline server.
# RUN THIS ON A MACHINE WITH DOCKER + INTERNET (the build downloads the MS ODBC
# driver, Composer packages and npm packages).
#
#   cd <project root>
#   ./docker/build-image.sh
#
# Then copy the produced dms-app.tar.gz to the offline server and:
#   docker load -i dms-app.tar.gz
#
set -euo pipefail

cd "$(cd "$(dirname "$0")/.." && pwd)"

IMAGE="${IMAGE:-dms-app:latest}"
OUT="${OUT:-dms-app.tar.gz}"

command -v docker >/dev/null 2>&1 || { echo "ERROR: docker is not installed here."; exit 1; }

echo ">> Building image: $IMAGE"
docker build -t "$IMAGE" .

echo ">> Saving image -> $OUT"
docker save "$IMAGE" | gzip > "$OUT"

echo ">> Done."
echo "   Image : $IMAGE"
echo "   File  : $OUT  ($(du -h "$OUT" | cut -f1))"
echo
echo "Next: copy $OUT to the server, then:"
echo "   docker load -i $OUT"
echo "   docker compose --env-file .env.docker up -d"
