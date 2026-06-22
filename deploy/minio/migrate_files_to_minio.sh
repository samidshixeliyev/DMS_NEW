#!/usr/bin/env bash
#
# migrate_files_to_minio.sh
# -------------------------
# One-off migration of EXISTING local execution-attachment files into MinIO.
#
# Run this ON THE SERVER that holds the files (it reuses the project's already
# deployed AWS SDK in vendor/, so no extra tools or internet are needed).
#
# It does NOT change the database directly. Instead it writes a reviewed SQL
# file (update_disk_to_minio.sql) that flips disk='minio' ONLY for the rows
# whose file was confirmed uploaded — you run that in SQL Server yourself.
#
# Safe to re-run: with "skip existing" on, already-uploaded files are skipped,
# so an interrupted run can simply be started again.
#
set -euo pipefail
umask 077

red(){ printf '\033[31m%s\033[0m\n' "$*"; }
grn(){ printf '\033[32m%s\033[0m\n' "$*"; }
yel(){ printf '\033[33m%s\033[0m\n' "$*"; }
cyn(){ printf '\033[36m%s\033[0m\n' "$*"; }
die(){ red "ERROR: $*"; exit 1; }

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
UPLOADER="$SCRIPT_DIR/upload_to_minio.php"
[ -f "$UPLOADER" ] || die "upload_to_minio.php not found next to this script."
command -v php >/dev/null 2>&1 || die "php is not installed / not in PATH."

echo
cyn "=================================================="
cyn "   Migrate local attachments -> MinIO"
cyn "=================================================="
echo "Uploads existing files; generates a SQL script to flip disk='minio'."
echo "Does NOT modify the database directly. Does NOT touch .env."
echo

# ---------------------------------------------------------------------------
# Prompts (each clearly named, with a sensible default in [brackets])
# ---------------------------------------------------------------------------
read -r -p "Project root path                 [/var/www/dms_new]: " PROJECT
PROJECT="${PROJECT:-/var/www/dms_new}"; PROJECT="${PROJECT%/}"
[ -d "$PROJECT" ] || die "project path not found: $PROJECT"

AUTOLOAD="$PROJECT/vendor/autoload.php"
[ -f "$AUTOLOAD" ] || die "vendor/autoload.php not found under $PROJECT (deploy the code first)."

read -r -p "Local storage base dir            [$PROJECT/storage/app/private]: " BASE_DIR
BASE_DIR="${BASE_DIR:-$PROJECT/storage/app/private}"; BASE_DIR="${BASE_DIR%/}"
[ -d "$BASE_DIR" ] || die "storage base dir not found: $BASE_DIR"

read -r -p "Subfolder to migrate              [execution-attachments]: " SUBDIR
SUBDIR="${SUBDIR:-execution-attachments}"
[ -d "$BASE_DIR/$SUBDIR" ] || die "nothing to migrate: $BASE_DIR/$SUBDIR does not exist."

read -r -p "MinIO endpoint URL                [https://localhost:9000]: " MINIO_ENDPOINT
MINIO_ENDPOINT="${MINIO_ENDPOINT:-https://localhost:9000}"

read -r -p "MinIO bucket                      [dms]: " MINIO_BUCKET
MINIO_BUCKET="${MINIO_BUCKET:-dms}"

read -r -p "MinIO access key                  : " MINIO_KEY
[ -n "${MINIO_KEY:-}" ] || die "access key is required."

# Secret is read silently (not echoed, not stored in shell history).
read -r -s -p "MinIO secret key                  : " MINIO_SECRET; echo
[ -n "${MINIO_SECRET:-}" ] || die "secret key is required."

read -r -p "MinIO region                      [us-east-1]: " MINIO_REGION
MINIO_REGION="${MINIO_REGION:-us-east-1}"

read -r -p "TLS CA / PEM cert path (HTTPS)    [none]: " MINIO_CA
MINIO_CA="${MINIO_CA:-}"
if [ -n "$MINIO_CA" ]; then
  [ -f "$MINIO_CA" ] || die "CA/PEM file not found: $MINIO_CA"
fi

MINIO_VERIFY=1
case "$MINIO_ENDPOINT" in
  https://*)
    if [ -z "$MINIO_CA" ]; then
      yel "HTTPS endpoint with no CA file given."
      read -r -p "Disable TLS verification? (insecure) [y/N]: " NV
      case "${NV:-}" in y|Y|yes|YES) MINIO_VERIFY=0; yel "TLS verification DISABLED." ;; *) MINIO_VERIFY=1 ;; esac
    fi
    ;;
esac

read -r -p "Dry run first? (upload nothing)   [Y/n]: " DR
case "${DR:-Y}" in n|N|no|NO) DRY_RUN=0 ;; *) DRY_RUN=1 ;; esac

read -r -p "Skip files already in MinIO?      [Y/n]: " SK
case "${SK:-Y}" in n|N|no|NO) SKIP_EXISTING=0 ;; *) SKIP_EXISTING=1 ;; esac

TS="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="$SCRIPT_DIR/migrate_minio_$TS.log"
SQL_FILE="$SCRIPT_DIR/update_disk_to_minio.sql"

FILE_COUNT="$(find "$BASE_DIR/$SUBDIR" -type f 2>/dev/null | wc -l | tr -d ' ')"

echo
yel "About to migrate:"
echo "   from   : $BASE_DIR/$SUBDIR   ($FILE_COUNT files)"
echo "   to     : $MINIO_ENDPOINT  bucket '$MINIO_BUCKET'"
echo "   keys   : path relative to $BASE_DIR (matches DB file_path)"
echo "   verify : $( [ "$MINIO_VERIFY" = 1 ] && { [ -n "$MINIO_CA" ] && echo "CA: $MINIO_CA" || echo "system CA"; } || echo "DISABLED" )"
echo "   mode   : $( [ "$DRY_RUN" = 1 ] && echo "DRY RUN (no uploads)" || echo "REAL upload" ), skip-existing=$( [ "$SKIP_EXISTING" = 1 ] && echo yes || echo no )"
echo "   log    : $LOG_FILE"
echo "   sql    : $SQL_FILE   (generated; run manually after)"
echo
read -r -p "Proceed? [y/N]: " OK
case "${OK:-}" in y|Y|yes|YES) ;; *) die "aborted." ;; esac

# ---------------------------------------------------------------------------
# Run the PHP uploader. Secrets are passed via the environment (not argv) so
# they don't show up in the process list.
# ---------------------------------------------------------------------------
set +e
MINIO_AUTOLOAD="$AUTOLOAD" \
MINIO_ENDPOINT="$MINIO_ENDPOINT" \
MINIO_KEY="$MINIO_KEY" \
MINIO_SECRET="$MINIO_SECRET" \
MINIO_BUCKET="$MINIO_BUCKET" \
MINIO_REGION="$MINIO_REGION" \
MINIO_CA="$MINIO_CA" \
MINIO_VERIFY="$MINIO_VERIFY" \
BASE_DIR="$BASE_DIR" \
SUBDIR="$SUBDIR" \
DRY_RUN="$DRY_RUN" \
SKIP_EXISTING="$SKIP_EXISTING" \
LOG_FILE="$LOG_FILE" \
SQL_FILE="$SQL_FILE" \
php "$UPLOADER"
RC=$?
set -e
unset MINIO_SECRET

echo
if [ "$DRY_RUN" = 1 ]; then
  grn "Dry run finished. Re-run and answer 'n' to 'Dry run first?' to upload for real."
  echo "Log: $LOG_FILE"
  exit 0
fi

if [ "$RC" -eq 0 ]; then
  grn "Migration finished with no failures."
else
  yel "Migration finished but SOME files FAILED (exit $RC). Check the log:"
  echo "   $LOG_FILE"
  yel "Re-running with skip-existing will retry only the failed/missing ones."
fi

echo
cyn "NEXT STEP — update the database (manual):"
echo "  Review and run this generated file in SQL Server (SSMS):"
echo "     $SQL_FILE"
echo "  It sets disk='minio' ONLY for rows whose file was confirmed uploaded."
echo
echo "  (Blanket alternative, ONLY if every file migrated with zero failures:)"
echo "     UPDATE execution_attachments SET disk='minio' WHERE disk IS NULL OR disk='local';"
echo
