# Local visual editor

Open `/admin` after signing in. The homepage is rendered by the same templates,
CSS, animations and media pipeline as the public site, inside an authenticated
preview. No separate screenshot representation is used.

## Editing

- Click supported page text to select its field in Content.
- Use Design for system-font families, responsive sizes, weight, alignment,
  colors, a continuous solid/gradient background and section spacing.
- Use Media to upload a photo/video (50 MB per file), select up to 20 cover
  slides, remove a slide from the rotation and reorder slides.
- Use Sections to edit headings, descriptions, visibility and order.
- Save draft refreshes the complete preview, including links, media and sections.
- Desktop, Tablet and Phone change the preview viewport, not the public layout.
- Story and officer cards open their existing full editors in an overlay.

## Publication and recovery

The site has one shared page draft. Save draft does not publish it. Publish
changes applies the page settings, section configuration, cover selection and
styles on the current installation. It does not deploy code to Railway.

The previous page configuration is saved before each publication. Restore previous
publication restores those settings; it does not restore story/profile edits,
uploaded/deleted files or all database history. Keep independent database and
media backups. Discard draft leaves uploaded library files in place.

Record forms and permanent media deletion are separate from page drafts and save
immediately, as labeled in the overlay. Publishing is blocked if page settings
changed outside the editor after the draft was started. Reset the draft to the
current page in that case. Reopen the editor after other administrators save.

## Compatibility and limits

Original styling is unchanged until a style override is published. Reset a style
group to return to the original CSS. Gradients exclude the cover and footer.
Test both light/dark themes and mobile before publishing custom colors.

The editor configures existing functionality; it is not an arbitrary code editor.
Splash choreography, new content types, integrations and entirely new behaviors
still require development. Existing content/media validation remains in force.

Routes use the existing administrator middleware and CSRF protection. Drafts and
previous versions use private database setting records. Public styling is emitted
only from whitelisted selectors, font stacks and validated bounded values.

## Tests

Run `php -n vendor/phpunit/phpunit/phpunit` if the local Herd CLI stalls with its
default INI. Tests use the in-memory testing database, not the live/local content.
The `-n` workaround assumes the bundled runtime has the required extensions built in.

Do not deploy this change until the owner has reviewed the local editor.
