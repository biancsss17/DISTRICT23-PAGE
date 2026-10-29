# D23 technology and media guide

This document describes what the UECFI District 23 website uses today and why the current media rules exist.

## Application stack

| Area | What D23 uses | Purpose |
|---|---|---|
| Backend | Laravel 13 | Routing, authentication, validation, database access, sessions and server-rendered pages. |
| Runtime | PHP 8.4.1 or newer | Runs Laravel locally and on Railway. Railway currently uses PHP 8.5. |
| Templates | Blade | Builds public pages, login, admin editor and reusable content partials. |
| Styling | Project CSS in `public/css/` | Controls the public website, splash screen, responsive layout and visual editor. |
| Browser code | Vanilla JavaScript ES modules in `public/js/` | Handles splash, navigation, animations, preview, drafts, records and media controls. |
| Motion | Local GSAP and ScrollTrigger 3.13.0 files | Drives page motion without a Node process in production. |
| Local database | SQLite | Runs locally without a separate database server. |
| Production database | The database configured by Railway | Stores users, settings, sections, records and media references. |
| Media storage | Neon Object Storage through its S3-compatible API | Stores new files when `CMS_MEDIA_DISK=neon`. |
| Existing media | Its recorded disk, including the Railway volume | Preserves files when the upload disk changes. |
| Hosting | Railway | Runs Laravel, migrations and the health check. |
| Source control | GitHub | Stores the deployable D23 code. |
| Admin access | Laravel sessions plus the `is_admin` flag | Restricts the editor and record management. |

The application does not need a frontend build server, queue worker or Node process on Railway. CSS and JavaScript are served directly from `public/`.

## What the admin editor manages

The `/admin` page is the single management interface for homepage text, header, navigation, footer, organization details, fonts, sizes, weights, alignment, colors, gradients, spacing, cover media, News, Achievements, Officers, private drafts, publishing, restoration, previews and individual media deletion. Activities were intentionally removed from the public site and editor. Historical migrations remain because a fresh installation still needs the complete schema history.

## Media flow

1. The administrator selects a file in the editor.
2. Browser JavaScript checks its type and size for an immediate message.
3. Laravel validates the same file on the server. This is the authoritative check.
4. `MediaStorage` writes the file to the configured disk and saves its name, path, disk and type in the database.
5. The public `/media/{id}` route reads it through its recorded disk.
6. Deleting a file clears homepage, cover, logo, favicon and draft references before removing the database record and stored object.

The two validation layers improve the user experience while protecting the application and storage.

## Current media policy

| Rule | Value |
|---|---:|
| Photos per News, Achievement or Officer item | 3 |
| Videos per News, Achievement or Officer item | 1 |
| Maximum size for each photo | 10 MB |
| Maximum size for each video | 50 MB |
| Homepage cover selection | Same per-file limits; up to 20 selected slides |
| Photo types | JPG, JPEG, PNG, WebP and GIF |
| Video types | MP4, WebM and OGG |

The policy is defined in `config/media.php` and shared by `MediaRules`, `MediaUploadSize`, the record controller and the browser editor. PHP’s upload configuration in `php-conf/50-media-upload-limits.ini` is sized to accept the same policy.

## Why photos are limited to 10 MB

Ten megabytes is enough for a clear website photo from a phone or camera after ordinary export, while discouraging uncompressed camera originals and oversized screenshots. Photos are commonly downloaded as part of a page or gallery, so very large files slow mobile connections and use storage quickly.

The limit is per photo. Three photos can therefore use up to about 30 MB before storage and image optimization are considered. The application does not silently resize or compress uploads, so web-ready images are recommended.

## Why videos are limited to 50 MB

Video files need more space than still images, especially for the 25-second homepage slideshow clips used by this site. Fifty megabytes allows a useful short MP4, WebM or OGG clip while preventing an accidental large camera recording from filling storage or taking too long to upload.

The limit is per video and each record accepts one video. Longer or higher-quality videos should be trimmed or compressed before upload. These are application limits, not a promise of a storage provider’s free allowance. Neon plan quotas and pricing can change, so usage must be monitored in Neon. The app rejects an oversized file but cannot prevent a project from reaching a provider quota if many permitted files are uploaded.

## Storage configuration

New uploads use the disk named by `CMS_MEDIA_DISK`:

```text
CMS_MEDIA_DISK=neon
```

Neon credentials belong in Railway Variables or a local `.env` file and must never be committed to GitHub or placed in chat. The setup and safe connection test are documented in [NEON_STORAGE_SETUP.md](../NEON_STORAGE_SETUP.md).

When the Neon disk is active, new files go to the private Neon bucket while old media remains available from the disk recorded on each old media row. This avoids moving or deleting existing content during the storage migration.

## Development and deployment

Run locally:

```powershell
cd "C:\Users\User\Documents\ChatGPT\fys d23"
php artisan serve --host=127.0.0.1 --port=8005
```

Verify the application:

```powershell
php artisan test
php vendor/bin/pint app config routes tests
```

The feature tests cover public pages, admin authorization, drafts, publishing, restoration, News, Achievements, Officers, upload validation and media deletion. Railway runs the migration command from `railway.json` before starting and checks `/up` as its health endpoint.

## Operational rules

- Keep `APP_KEY` stable after deployment and keep `APP_DEBUG=false` in production.
- Keep credentials in environment variables, never in source control.
- Back up the database and any existing Railway media volume before infrastructure changes.
- Do not run the guide recorder against production; it creates and deletes demonstration records.
- Changing `CMS_MEDIA_DISK` affects new uploads only; it does not move existing media automatically.
