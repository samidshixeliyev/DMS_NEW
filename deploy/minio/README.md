# MinIO migration tooling

Migrates the DMS execution-attachment storage from local disk to MinIO.

## Background

- New uploads already go to MinIO (controller writes to the `minio` disk and
  stamps `execution_attachments.disk = 'minio'`).
- Old rows have `disk` = `local` / `NULL` and are read from local storage.
- The object **key** on MinIO is identical to the stored `file_path`
  (`execution-attachments/{legal_act_id}/{hash}.ext`), so migrating a file is a
  straight copy — no path rewriting needed, only flipping the `disk` marker.

## Files

| File | Purpose |
|---|---|
| `migrate_files_to_minio.sh` | Interactive uploader. Prompts for endpoint, bucket, access key, secret key, CA/PEM path, paths, etc. |
| `upload_to_minio.php` | Worker invoked by the shell script (uses the project's AWS SDK in `vendor/`). |
| `update_disk_to_minio.sql` | **Generated** by a real run — flips `disk='minio'` for the rows whose file was confirmed uploaded. |

## Order of operations

1. **Deploy the code** (the MinIO disk + AWS SDK) so `vendor/autoload.php` and
   the `minio` disk config exist on the server.
2. **Add the `disk` column** (if not already):
   ```sql
   ALTER TABLE execution_attachments ADD disk VARCHAR(32) NULL;
   ```
3. **Run the migration on the server** (where the files live):
   ```bash
   cd deploy/minio
   chmod +x migrate_files_to_minio.sh
   ./migrate_files_to_minio.sh
   ```
   - Do a **dry run** first (default) to see what would upload.
   - Then re-run and answer `n` to "Dry run first?" to upload for real.
   - It is **resumable**: "skip existing" skips files already on MinIO.
4. **Apply the generated SQL** in SQL Server (SSMS):
   ```
   deploy/minio/update_disk_to_minio.sql
   ```
   This sets `disk='minio'` only for attachments whose file was confirmed on
   MinIO. After this, those attachments are served from MinIO.

## Notes

- Secrets are read silently and passed to PHP via the environment, never on the
  command line.
- The script never modifies the database directly and never touches `.env`.
- HTTPS: provide the CA/PEM path when prompted (self-signed / internal CA).
- After everything is migrated and verified, you may keep the `disk` column as
  the source of truth, or set the default app disk and treat all rows as MinIO.
