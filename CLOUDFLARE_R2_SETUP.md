# Cloudflare R2 and Railway setup

New uploads use `CMS_MEDIA_DISK`. It defaults to `public` so current local and live media remain available on their existing disk. Existing records keep their own disk value; switching new uploads to R2 does not move or delete old files.

## 1. Create an R2 bucket and credentials

1. In Cloudflare, open **R2 Object Storage** and create a bucket for the site.
2. In **Manage R2 API Tokens**, create an S3 API token scoped to that bucket with **Object Read & Write** permissions. Keep the Access Key ID and Secret Access Key private.
3. Find the Cloudflare account ID. The S3 endpoint is `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`; the app uses region `auto`.
4. Do not put credentials in source control, browser JavaScript, or a public bucket URL. The app streams objects through its authenticated-origin `/media/{id}` route by default.

## 2. Add Railway variables

In the Railway service's **Variables**, add:

```text
CMS_MEDIA_DISK=r2
R2_ACCESS_KEY_ID=<Cloudflare Access Key ID>
R2_SECRET_ACCESS_KEY=<Cloudflare Secret Access Key>
R2_BUCKET=<bucket name>
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
```

`R2_PUBLIC_URL` is optional and normally should be left unset; media is proxied through the application. Railway redeploys after variables change. The included deploy migration adds the media disk field and officer gallery table. Keep the Railway volume attached for existing files and backups.

## 3. Local setup (optional)

Add the same values to `.env` (never commit it), set `CMS_MEDIA_DISK=r2`, then run:

```powershell
php artisan config:clear
php artisan migrate
```

To keep local uploads on disk, leave `CMS_MEDIA_DISK=public`.

## Upload limits

Railway's `PHP_INI_SCAN_DIR=:/app/php-conf` loads the included `php-conf/50-media-upload-limits.ini`, which sets PHP's 50 MB per-file limit and a request body cap sufficient for the 10-file homepage slideshow. News, Achievements and Officer validation enforce 3 photos and 1 video per content/profile; an item cover counts toward those totals. Activities remain removed. The web form gives a specific validation error when a file is too large or an item exceeds its media allowance.

Cloudflare credentials are deliberately not supplied by the application. Add them in Railway before switching `CMS_MEDIA_DISK` to `r2`; until then new live uploads continue to use the existing Railway volume.
