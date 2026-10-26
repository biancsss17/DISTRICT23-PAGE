# UECFI website and CMS

## Local website
Project: C:/Users/User/Documents/ChatGPT/fys d23
Website: http://127.0.0.1:8005
Administrator: http://127.0.0.1:8005/admin/login

The local installation uses SQLite so it works without a separate database server.
The migrations support MySQL/MariaDB. The example environment retains MySQL placeholders.
The original /generate POST endpoint remains available.

Start from PowerShell:
```powershell
cd "C:\Users\User\Documents\ChatGPT\fys d23"
php artisan serve --host=127.0.0.1 --port=8005
```

## Fresh installation
Use PHP 8.4.1 or newer for the supplied dependency lock file, Composer 2, PDO, PDO SQLite (local), or PDO MySQL (hosting), fileinfo, mbstring, openssl, DOM and XML.
```
composer install
```
Copy .env.example to .env. Configure the database, then:
```
php artisan key:generate
php artisan migrate
php artisan db:seed --class=CmsSeeder
php artisan cms:admin your-email@example.com
php artisan serve --host=127.0.0.1 --port=8005
```
The administrator command securely prompts for a password (12+ characters).
There is no public registration, default production password or pretend password-reset endpoint.

## Visual editor
- `/admin` is the only management interface. The former dashboard, separate content forms, settings pages and account page are removed.
- Content tab edits the header, organization, homepage, navigation and footer. Design controls fonts, sizes, colors and the continuous background. Sections controls homepage headings, order and visibility.
- Media tab manages homepage cover slides and the shared media library. Uploaded files can be permanently deleted there or from their content records. Delete clears references across the site.
- Records tab shows published counts, recent edits and management for News, Achievements and Officers. Create, edit, private preview and delete are available there. Activities remain removed from the public site and editor.
- News and Achievements retain draft/publish, scheduled publication, featured flag, unique slugs and numeric ordering. A published item appears publicly only after its publication time.
- Content and officers allow up to 3 photos and 1 video per item, counting the cover or portrait; new gallery uploads replace the current gallery. Maximum file size is 50 MB.
- Page settings are saved as a private draft and become public only after Publish changes. Record changes and media deletion apply immediately. Versions & administration contains previous-publication restore, draft discard and logout. Passwords are managed outside the editor by an authorized site administrator.
- Existing content tables and media records are shared by the public site and editor. Do not drop them when removing legacy admin code.
- Text content is escaped; arbitrary HTML/JavaScript is never executed.

## Shared hosting
1. Select PHP 8.5 and verify requirements with composer check-platform-reqs.
2. Create a MySQL database and database user in the hosting dashboard.
3. Upload the application OUTSIDE the public document root. Configure the domain document root to the application's public directory.
4. Configure .env with APP_ENV=production, APP_DEBUG=false, your HTTPS APP_URL, SESSION_SECURE_COOKIE=true, and real DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD.
5. Preserve APP_KEY across deployments. Generate it only for a new installation.
6. Install locked dependencies: composer install --no-dev --optimize-autoloader.
7. Run php artisan migrate --force. Run CmsSeeder once if initial sample content/settings are desired; it does not overwrite existing records.
8. Create a real admin using php artisan cms:admin.
9. Ensure storage and bootstrap/cache are writable by the PHP process; never use world-writable permissions.
10. Run php artisan config:cache, php artisan route:cache and php artisan view:cache.
11. Back up the database and storage/app/public before upgrades.

Uploaded media is served through the /media/{id} route; symlinks are not required. Existing files keep their recorded storage disk.
No Node.js process, queue worker or build server is required on hosting.
The implementation uses Blade, CSS, vanilla JavaScript and local GSAP / ScrollTrigger 3.13.0.
The starter Tailwind CDN form is retained but the CMS design uses its own stylesheet, avoiding a browser-side CSS compiler.
Google Fonts are optional external resources with system-font fallbacks.
The mountain hero image is decorative stock imagery from Unsplash (photo-1464822759023-fed622ff2c3b), not an image of a UECFI activity.
All seeded stories and officer profiles are explicitly sample content; statistics are labeled illustrative. Replace them with approved information before launch.

## Verification
```
php artisan test
php artisan view:cache
php artisan route:cache
php artisan route:clear
```
Feature tests exercise public routes, private drafts, authorization, record CRUD, uploads, media deletion and editor settings.
Screenshots are stored privately under storage/app/private.

## Architecture
- contents: common news/achievement/activity fields, indexed type/status/date, creator and cover references.
- activity_images: ordered content gallery relation retained in the database; Activity remains hidden/removed in the UI.
- officers and officer_media: hierarchy, term, biography, portrait and ordered photo/video gallery.
- media: storage disk, stored path, original display name and alt text.
- site_settings: validated content and branding fields.
- page_sections: editable headings, description, order and visibility.
- users: standard hashed passwords with explicit is_admin flag.
- app/Http/Controllers: public, authentication, media, visual page editor and record editor controllers.
- resources/views/layouts, public, admin, partials: reusable Blade templates.
- public/css/site.css, page-editor.css and admin-login.css: responsive interfaces.
- public/js/site.js: splash, motion, navigation, dialogs, counters and gallery.
- public/js/page-editor.js and editor-records.js: page preview, drafts, content editing and media management.

