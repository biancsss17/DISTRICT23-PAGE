# D23 website

Laravel application for the UECFI District 23 public website and visual admin editor.
See SETUP.md for installation, hosting, storage configuration and account management.

## Code map

- `routes/web.php`: public pages and authenticated editor endpoints.
- `PageEditorController`: editor and private preview; delegates draft operations.
- `SavePageDraftRequest`: validation of page settings, section layout and design controls.
- `PageDrafts`: saving, publishing, discarding and restoring page versions.
- `EditorRecordsController`: News, Achievement and Officer records.
- `EditorMediaController`: shared media upload and deletion endpoints.
- `MediaStorage`: storage operations using each media record's disk.
- `MediaRules` and `MediaUploadSize`: shared file types and size validation.
- `resources/views/admin/editor/`: individual editor panels and record dialog.
- `public/js/editor-api.js`: shared authenticated requests and upload prechecks.
- `public/js/editor-styles.js`: preview style generation.
- `public/js/page-editor.js`: page editor interactions.
- `public/js/editor-records.js`: record forms and media controls.

PHP classes are under `app/`; upload policy is in `config/media.php`.
Current policy: 3 photos and 1 video per content item, 10 MB per photo and 50 MB per video.
New uploads use the configured media disk; existing media keeps its recorded disk.
Activities are intentionally removed. Historical migrations remain necessary for fresh installations.

## Development

Install the locked Composer dependencies, configure a local `.env`, and follow SETUP.md.
Serve with `php artisan serve --host=127.0.0.1 --port=8005`.
The application serves CSS and JavaScript directly from `public/`; there is no Node build step.
Editor JavaScript uses browser ES modules, so include new modules in deployments.
Never commit `.env`, credentials, database copies or uploaded user files.

## Verification and formatting

```sh
php artisan test
php vendor/bin/pint app config routes tests
```

JavaScript and CSS use Prettier 3.6.2 with `.prettierrc.json`.
Blade templates use blade-formatter 1.44.6, four-space indentation and a 120-character wrap length.
Formatting tools are development-only and do not need to be installed on the server.

`scripts/build-admin-guide.cjs` exercises the editor while recording its walkthrough.
Run it only against an isolated local instance with a disposable database and local media disk;
it intentionally creates, publishes and deletes demonstration records and files.
It requires Playwright and Chrome; its runtime paths may need adjustment on another machine.
`D23_GUIDE_BASE` sets the local URL and `D23_GUIDE_FAST=1` shortens demonstration pauses.
Do not replace the published guide with a fast test recording.

The published guide assets live in `public/tutorials/`. The manual generator is
`scripts/build-admin-manual.py`. Keep both guides aligned with user-visible workflow changes.
