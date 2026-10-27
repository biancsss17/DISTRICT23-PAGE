# Neon Object Storage for D23 media

New photo and video uploads use the disk selected by `CMS_MEDIA_DISK`. It stays `public` until the Neon bucket is ready. Every existing media row records its own disk, so changing the upload disk does not move or delete existing files. Keep the Railway volume attached for those files.

## 1. Create the Neon storage bucket

1. Create a Neon project on the Free plan. The existing D23 PostgreSQL database can remain on Railway; a Neon database migration is not required for media storage.
2. In the project's main branch, create a **private** Object Storage bucket for D23 media. The Laravel `/media/{id}` route serves files through the site; public bucket access is unnecessary.
3. Obtain the S3-compatible access key, secret key, region and **branch endpoint** from Neon. Neon may present these as `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_REGION`, and `AWS_ENDPOINT_URL_S3`. Keep the credentials out of Git and chat.

## 2. Add Railway variables without changing uploads yet

In the D23 web service's production Variables, add the following server-side values from Neon:

```text
NEON_STORAGE_ACCESS_KEY_ID=<Neon S3 access key>
NEON_STORAGE_SECRET_ACCESS_KEY=<Neon S3 secret key>
NEON_STORAGE_REGION=<Neon S3 region>
NEON_STORAGE_BUCKET=<private bucket name>
NEON_STORAGE_ENDPOINT=<Neon branch S3 endpoint>
```

Deploy those variables while leaving `CMS_MEDIA_DISK=public` (or unset). From a checkout linked to the same Railway service, run `railway run php artisan media:check-neon`. The check writes, reads and deletes one small temporary object and does not print credentials. If it passes, set `CMS_MEDIA_DISK=neon` in Railway and deploy that variable change. Upload a sample photo and video through the admin editor, check both public media URLs, and delete the samples.

Neon uses path-style bucket URLs. The application sets `use_path_style_endpoint=true` for the `neon` disk. Existing `public` media remains on the Railway volume.

## Upload limits

The app accepts up to 3 photos and 1 video per News, Achievement or Officer item, counting its cover or portrait. Each photo may be up to 10 MB and each video up to 50 MB. The same size limits apply to homepage media uploads. The PHP upload settings in `php-conf/50-media-upload-limits.ini` support those uploads on Railway. Activities remain removed.

Neon's Free plan has a storage allowance; monitor usage in Neon so uploads do not hit the limit. Keep the project on the Free plan if you do not want paid usage.
