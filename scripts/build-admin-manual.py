"""Build the illustrated guide for the current D23 visual editor.

Usage: python scripts/build-admin-manual.py FRAMES_DIRECTORY OUTPUT_PDF
The input screenshots must come from scripts/build-admin-guide.cjs on a disposable DB.
"""
from pathlib import Path
from io import BytesIO
import sys

from PIL import Image
from reportlab.lib import colors
from reportlab.pdfgen import canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.utils import ImageReader


PAGES = [
    ("01-login", "Sign in", [
        "Open /admin/login. Use the administrator account assigned to you; public visitors cannot reach the editor.",
        "The demonstration account in the recording is disposable. Never share a real password in a video, PDF or support chat.",
        "After signing in, the new visual editor opens at /admin. If access fails, ask an authorized administrator to check the account rather than repeatedly guessing.",
    ]),
    ("02-overview", "One visual workspace", [
        "The right canvas renders the actual homepage, including its responsive layout and media. It is not a screenshot replacement.",
        "Use Content for words and site settings; Design for typography/background; Media for slides/files; Sections for homepage ordering; Records for stories and people.",
        "The preview is private. Public visitors see only published page settings and published records.",
    ]),
    ("03-home-text", "Homepage content", [
        "Open Content > Home. Change the hero introduction, headline, description, button labels and destinations, heritage copy, statistics and section links.",
        "Some text updates appear in the preview as you type. Save draft to rebuild the complete page after wider changes.",
        "Check links and wording at desktop and phone sizes before you publish. A draft alone does not update the public page.",
    ]),
    ("04-identity", "Header and contact details", [
        "In Content > Header & contact details, edit the title, subtitle, organization name, district, email, phone, address and social links.",
        "Use valid email addresses and full HTTPS URLs where the form requests them. Confirm that links reach the intended destination.",
        "These are page settings: save a draft, inspect the preview, then publish when ready.",
    ]),
    ("04b-brand", "Logo and palette", [
        "Open Content > Brand to select a stored logo or favicon and adjust the approved navy, gold and accent colors.",
        "Upload a replacement image in Media first if it is not yet in the library. The original emblem remains available as the default.",
        "Check contrast on both website themes and at phone width. Changes remain private until page publication.",
    ]),
    ("05-navigation", "Navigation wording", [
        "Open Content > Navigation to change the labels used by the public menu. Keep labels short enough to fit smaller screens.",
        "The menu targets the corresponding homepage sections. Verify Home, News, Achievements and Officers after publishing.",
        "For a destination change, use the validated destination field if exposed; do not insert arbitrary script or HTML.",
    ]),
    ("06-footer", "Footer content", [
        "Content > Footer controls its headline, explanatory line and copyright text. The footer keeps a separate background from the continuous page design.",
        "Review the actual footer in the preview after saving a draft. Check spelling, organization name and year before publishing.",
        "The footer is part of the page draft, not a News or Officer record.",
    ]),
    ("07-type", "Typography", [
        "In Design, first choose Style this part. Then set a font family, size, weight, alignment and text color for that target.",
        "Other font choices and page targets use the same controls. A change to one target does not automatically change every element; check navigation, cards and footer separately.",
        "Reset this part to original removes its override and returns it to the site's existing CSS.",
    ]),
    ("08-background", "Continuous background", [
        "Choose Original design, Solid color or Gradient. For a gradient, select both colors and an angle.",
        "The main homepage background is continuous across sections. The cover media and footer retain their own backgrounds.",
        "Try the preview in both themes and at all viewport sizes before publishing a new palette.",
    ]),
    ("09-phone", "Responsive previews", [
        "Desktop, Tablet and Phone change the preview width; they do not create separate copies of the content.",
        "A text or media edit affects the same underlying page on all screen sizes, while responsive CSS adjusts the layout.",
        "After a typography or spacing change, inspect navigation wrapping, line breaks, cards and image crops at each width.",
    ]),
    ("10-media", "Homepage cover slides", [
        "The Media tab holds the cover selection and shared library. Add a stored file with + Add slide, then use arrows to adjust playback order.",
        "Remove takes a slide out of the rotation only. Delete photo or Delete video permanently removes the file everywhere it is referenced.",
        "Cover selection and order are page-draft changes; saving a draft previews them, publishing makes them public.",
    ]),
    ("11-photo-upload", "Upload a photo", [
        "Choose a JPG, PNG, WebP or GIF file. The application allows up to 50 MB per file, subject to the server's PHP upload limits.",
        "Upload adds the file to the library immediately; selecting it as a cover slide and publishing are separate actions.",
        "Give the preview time to refresh and check the crop before publication. Avoid uploading duplicates unnecessarily.",
    ]),
    ("12-video-upload", "Upload a video", [
        "The media library accepts MP4, WebM and OGG. Check format and file size before uploading.",
        "A video may be used in the homepage cover or in a content item's gallery, depending on where it is uploaded and selected.",
        "Playback and loading can vary by device. Review the public-style preview before publishing.",
    ]),
    ("13-sections", "Homepage sections", [
        "Sections controls each homepage section's heading, description, numeric order and visibility.",
        "A lower Order number appears earlier. Save draft to rebuild the full preview after changing sequence or visibility.",
        "The Records tab controls the stories and people inside sections; editing a section heading does not edit those records.",
    ]),
    ("13b-visibility", "Visibility without deletion", [
        "Turn Visible off to hide a section from the homepage while keeping its underlying News, Achievement or Officer records.",
        "Turn it back on if the section should return. Change Order to move it; do not delete content merely to rearrange the page.",
        "Save draft, inspect the preview, then publish the final arrangement.",
    ]),
    ("14-draft", "Save a private draft", [
        "Save draft stores your page settings privately and refreshes the real homepage preview.",
        "It does not change the public website. Record saves and permanent media deletes are exceptions: they apply immediately.",
        "There is one shared page draft. If another administrator changes it, reload and reconcile before continuing.",
    ]),
    ("15-published", "Publish page changes", [
        "Publish changes applies the current page draft to this installation and keeps the previous page configuration for one-step restore.",
        "Confirm that sections, text, logo, colors and cover are correct before publishing. Check the public homepage afterward.",
        "Publishing is not a code deployment; local and Railway installations have separate databases and settings.",
    ]),
    ("16-versions", "Restore a publication", [
        "Versions & administration contains Restore previous publication and Discard draft.",
        "Restore returns the prior page settings, section configuration and cover selection. It does not undo record edits or recreate deleted files.",
        "For broader recovery, retain independent database and media backups.",
    ]),
    ("16b-discard", "Discard an unpublished draft", [
        "Discard draft removes unpublished page changes and returns the editor to the current public page settings.",
        "The confirmation explains that uploaded media stays in the library. Delete an unwanted file separately if appropriate.",
        "Discard cannot reverse an immediately saved News/Officer edit or a permanent media deletion.",
    ]),
    ("17-records", "Records overview", [
        "The Records tab replaces the old dashboard and separate content-management pages. It shows published counts and recent updates.",
        "Choose Manage News, Manage Achievements or Manage Officers. Their changes save immediately, independently of page drafts.",
        "Activities remain removed from the public site and editor.",
    ]),
    ("18-news-form", "Create a News story", [
        "Add a title, unique URL slug, summary and plain-text body. Category, featured flag and sort order help organize the story.",
        "Draft keeps it private. Published still requires a publication time at or before now before it appears publicly.",
        "A story supports up to 3 photos and 1 video total, counting its cover; each file may be up to 50 MB.",
    ]),
    ("19-validation", "Read validation errors", [
        "An invalid slug or missing field returns a clear message. Email, URL and other field errors follow the same correct-and-save-again workflow; nothing is saved until validation passes.",
        "Use letters, numbers, dashes or underscores for the slug; it must be unique. Check upload type, size and per-item media counts.",
        "Correct the marked input and save again. Do not refresh before reviewing the message.",
    ]),
    ("20-news-saved", "Verify a saved News item", [
        "After Save story, confirm the success message and reopen the item to verify that its title, summary, body and media persisted.",
        "Use Preview for an administrator-only view. For a published item whose date has arrived, also check the public News section.",
        "The record save is immediate; page Publish changes is not required for the story itself.",
    ]),
    ("21-photo-deleted", "Delete an individual photo", [
        "The saved cover and gallery each show a separate Delete photo or Delete video control. Confirm the exact file before deleting.",
        "Deletion is permanent across all references and is not reversed by Restore previous publication.",
        "Use Remove on a cover slide only when you want the file kept in the library.",
    ]),
    ("22-news-list", "Manage saved stories", [
        "The News list provides Edit, Preview and Delete for every saved item. Reopen Edit to change fields and save again.",
        "Preview is private and can show a draft. Delete removes the record after confirmation; its uploaded files may remain until separately deleted.",
        "Take a backup before removing real organizational content.",
    ]),
    ("22b-private-preview", "Private article preview", [
        "The article preview uses the same public detail design but is reachable only through the administrator route.",
        "It lets you proofread text, inspect the cover and gallery, and check layout before public publication.",
        "A preview does not publish. Return to Records to revise or change the item status.",
    ]),
    ("23-achievement", "Achievements", [
        "Manage Achievements uses the story fields and adds an achievement date. Enter verified details only.",
        "Save, reopen and privately preview the record, then confirm the public section when its status/date make it visible.",
        "Delete an item only after confirming that it is the intended record. Media files have separate delete controls.",
    ]),
    ("24-officer", "Officer profiles", [
        "Add name, position, term, biography, optional contact/social link, portrait, gallery, sort order and status.",
        "A portrait counts toward the 3-photo limit. A profile may also have at most 1 video, and each upload may be up to 50 MB.",
        "Use Draft until the name, role, term and approved image are verified.",
    ]),
    ("25-officer-saved", "Verify and edit an officer", [
        "After saving, reopen the profile to verify the term, biography, status and portrait. The public Officers section shows published profiles.",
        "Use the portrait's Delete photo control only when you intend to remove the stored file.",
        "Deleting the profile itself is a separate confirmed action.",
    ]),
    ("26-video-delete", "Delete a video", [
        "In Media, find the exact video in the shared library and choose Delete video. Read the confirmation carefully.",
        "This deletes the stored file and clears its references across the site. It is not the same as Remove from cover rotation.",
        "The example uses a disposable upload, never a production video.",
    ]),
    ("27-video-gone", "Verify media removal", [
        "After confirmation the deleted upload disappears from the library. Refresh if the list appears stale.",
        "Review affected stories, officers and cover slides for empty media positions. Replace content if needed.",
        "Permanent deletion is not included in page-version restore; backups are the recovery path.",
    ]),
    ("28-logout", "End the admin session", [
        "Open Versions & administration and choose Log out when finished, especially on a shared computer.",
        "The password-change form from the prior admin has been removed. An authorized administrator handles account resets outside this editor.",
        "The editor retains no separate old-admin database; the public site and new editor share the same content records.",
    ]),
    ("29-finish", "Final publication checklist", [
        "Proofread content, inspect every media file, check Desktop/Tablet/Phone, then save draft and preview.",
        "Publish page changes only when approved. Verify public News, Achievements, Officers and navigation afterward.",
        "Remember: record edits and permanent media deletes happen immediately. Back up the database and media before major revisions.",
    ]),
]

# Keep the manual in the same order as the newly recorded cursor-led video.
PAGES.extend([
    ("02a-guide-menu", "Open the guide menu", [
        "Select Admin guide in the editor toolbar. The floating card offers Video and Manual without leaving the editor first.",
        "The video demonstrates actual mouse clicks and typing in a disposable installation. The manual explains each screen in more detail.",
        "Close the card by selecting its heading again, clicking outside it, or pressing Escape.",
    ]),
    ("02b-panel-hidden", "Expand the page canvas", [
        "Select Edit panel to hide the left controls temporarily and give the live page more room.",
        "Select Edit panel again to restore the controls. This changes only your editor workspace, not the public website.",
        "Use the Desktop, Tablet and Phone buttons when checking the layout at different widths.",
    ]),
    ("02c-click-preview", "Select text on the page", [
        "Click editable text in the live homepage preview. The corresponding field opens in Content and receives focus.",
        "Type a replacement in that field; supported text changes appear in the preview as you type. Save draft to refresh the full page.",
        "Clicking a section heading opens Sections; clicking a story or officer opens the corresponding record editor.",
    ]),
    ("02d-click-card", "Open a record from the page", [
        "Click a News or Achievement card in the live preview to open that saved record directly in the editor.",
        "Officer cards use the same shortcut. The record form is identical to the one reached from the Records tab.",
        "Inspect or edit the record, then save it separately from the page draft. Close the dialog to return to the page preview.",
    ]),
    ("03a-buttons", "Buttons and destinations", [
        "In Home, edit the primary and secondary button text separately from their destination fields.",
        "An internal destination starts with a slash, such as /achievements. For external destinations, enter an approved full URL when the field permits it.",
        "After publishing, click each button on the public website to confirm that it reaches the intended page.",
    ]),
    ("03b-statistics", "Statistics and section links", [
        "Home contains four statistic values and labels, a statistics note, heritage details and link text for News, Achievements and Officers.",
        "Click and type into the desired field, then save a draft. Use values that the organization can substantiate.",
        "Check the resulting wording and number layout in the Desktop and Phone previews before publication.",
    ]),
    ("04a-contact", "Contact and social fields", [
        "Header & contact details includes the displayed identity, organization name, acronym, district, email, phone and address.",
        "Facebook and Instagram accept full web addresses. Incorrect URL or email formatting is rejected when you save.",
        "The example uses disposable addresses; enter only approved real contact information on the live site.",
    ]),
    ("08a-solid", "Solid background option", [
        "Design offers Original, Solid color and Gradient for the continuous main-page background.",
        "Choose Solid color and set the start color to apply one color; select Original design to return to the existing style.",
        "The homepage cover and footer keep their own backgrounds. Review both light and dark themes.",
    ]),
    ("09a-dark-mode", "Check the other theme", [
        "Use the Dark mode switch in the page preview to inspect contrast and legibility after changing the palette or typography.",
        "Check buttons, navigation, section headings and cards in both themes. Return the switch to the desired viewing mode afterward.",
        "Theme inspection is a preview task; it does not create separate mobile or dark-mode content copies.",
    ]),
    ("11a-brand-selection", "Choose a logo and favicon", [
        "Upload an approved image in Media, then open Content > Brand and select it for Official logo or Favicon.",
        "The original emblem remains the default choice. The three brand colors can be adjusted in the same group.",
        "Logo, favicon and palette are page-draft changes. Check both themes and screen sizes, then publish deliberately.",
    ]),
    ("12a-file-limit", "50 MB upload validation", [
        "Each photo or video must be at most 50 MB. The editor rejects a larger file before upload and shows a clear status message.",
        "Choose a smaller or compressed file in a supported format and retry. Server PHP settings may impose a lower practical limit.",
        "An unsuccessful upload does not create a usable library item or a cover slide.",
    ]),
    ("12b-slide-order", "Change slideshow order", [
        "In Selected slides, use the up and down arrows to change the rotation order. The first item appears first.",
        "Remove takes a file out of the cover rotation but keeps the stored photo or video in the media library.",
        "Save draft to refresh the preview. Publish changes to update the public homepage cover.",
    ]),
    ("12c-add-slide", "Reuse a stored slide", [
        "In the Media library, choose + Add slide beside a stored file to include it in the draft cover rotation again.",
        "You can select up to 20 cover slides. The limit warning follows the same clear-error pattern demonstrated for media counts; a selected item cannot be added twice.",
        "This is different from a new upload and different from permanent Delete photo or Delete video.",
    ]),
    ("13c-section-order", "Section text and order", [
        "Each section has editable Heading, Description, Order and Visible controls. Type into the first two to change its introduction.",
        "Lower order numbers display earlier. Turn Visible off to hide a section without deleting its records.",
        "Save draft and inspect the refreshed preview; publish only after checking every homepage section.",
    ]),
    ("13d-click-section", "Jump to section controls", [
        "Click a section heading in the live page preview to open the Sections tab and locate its controls.",
        "This is a navigation shortcut inside the editor. It does not change the section until you edit a field.",
        "Save a draft after changing section content, visibility or order to see the complete layout.",
    ]),
    ("14a-shortcut", "Save a page draft with Ctrl+S", [
        "While editing page settings, press Ctrl+S. The editor intercepts this shortcut and saves the same private draft as the Save draft button.",
        "Wait for the Draft saved message and refreshed preview. This shortcut does not publish the page.",
        "An open News, Achievement or Officer form has its own Save control; Ctrl+S is not a replacement for that record save.",
    ]),
    ("17a-recent-update", "Open a recent update", [
        "The Records tab lists recent changes. Click one entry to open that existing record directly.",
        "This is a shortcut to the same News, Achievement or Officer form reached through its Manage button and Edit action.",
        "Check the record type and title before changing or deleting anything. Record saves apply immediately.",
    ]),
    ("18a-unsaved-warning", "Unsaved record warning", [
        "After typing into a record form, choosing All News, Close or another record type asks whether to discard unsaved changes.",
        "Choose Cancel to keep editing, or confirm to leave without saving the temporary changes.",
        "The page editor also warns before leaving with unsaved page settings. This is the same protect-your-work pattern.",
    ]),
    ("18b-discard-confirmed", "Return without saving", [
        "After confirming the warning, the record list returns and the temporary unsaved title is not stored.",
        "This does not delete any previously saved record. Add a new item or reopen an existing one when ready.",
        "For page settings, Discard draft is different: it removes an already saved but unpublished page draft.",
    ]),
    ("19a-news-options", "Story organization and timing", [
        "Set Category, Featured, Published at, Status and Sort order alongside the News title, slug, summary and body.",
        "Draft stays private. Published items appear publicly only when their scheduled publication time has arrived.",
        "Review these settings before saving; record changes apply immediately, independently of the page draft.",
    ]),
    ("19b-photo-limit", "Three-photo validation", [
        "A story or officer profile may have at most three photos total, including its cover or portrait.",
        "The example attempts four photos and receives a validation message. Remove one extra gallery upload, then save again.",
        "Existing gallery media also counts unless the new gallery upload replaces it. Review all selected files before saving.",
    ]),
    ("19c-video-limit", "One-video validation", [
        "A content item may contain at most one video across its cover and gallery. The example attempts two and is rejected.",
        "Remove the extra video and keep the other media within the three-photo limit.",
        "Each individual file also has a 50 MB maximum and must use a supported format.",
    ]),
    ("19d-news-gallery", "Save a cover and gallery", [
        "The valid News example includes one cover photo, one gallery photo and one gallery video.",
        "Select Cover photo or video for the lead media; use Additional photos and video for the gallery. New gallery uploads replace the current gallery.",
        "Save and reopen the item to confirm that each file appears before relying on the public page.",
    ]),
    ("21a-gallery-video-deleted", "Delete a gallery video", [
        "Open the saved item. Each gallery video has its own Delete video button and confirmation.",
        "Confirming removes that stored file everywhere it is referenced, but leaves the record itself in place.",
        "Check the refreshed gallery afterward. Page-version restore does not recreate a permanently deleted file.",
    ]),
    ("21b-gallery-photo-deleted", "Delete a gallery photo", [
        "Saved gallery photos have separate Delete photo buttons. Choose the exact image before confirming.",
        "Deleting a file and deleting its parent story are different operations. A story may continue without that image.",
        "For recoverability, keep independent media backups before removing real organizational photos.",
    ]),
    ("23a-achievement-draft", "Save an Achievement draft", [
        "Set Status to Draft and save. The record persists in the editor but is not eligible for the public section.",
        "Administrators can use its private Preview to check wording, date and media before publication.",
        "Saving a record does not require Publish changes for the page draft; the two workflows are separate.",
    ]),
    ("23b-achievement-published", "Publish an Achievement", [
        "Change the saved Achievement to Published, confirm Published at, and save it again.",
        "It becomes eligible for the public Achievements section when the publication time has arrived and the section is visible.",
        "Reopen the record and check the live site afterward. Delete only a disposable or explicitly approved item.",
    ]),
    ("25a-officer-video-deleted", "Officer gallery controls", [
        "An officer profile can have a portrait and additional gallery photos or video within the per-item limits.",
        "Its saved gallery video has a separate Delete video control; removing it does not delete the officer profile.",
        "The portrait, gallery photos and profile record each have their own deletion path and confirmation.",
    ]),
])
PAGES.sort(key=lambda page: page[0])


def screenshot(path):
    """Embed an RGB JPEG to keep the illustrated guide practical to download."""
    with Image.open(path) as image:
        data = BytesIO()
        image.convert('RGB').save(data, format='JPEG', quality=86, optimize=True)
        data.seek(0)
        return ImageReader(data)


def font_setup():
    base = Path('C:/Windows/Fonts')
    pdfmetrics.registerFont(TTFont('ArialGuide', str(base / 'arial.ttf')))
    pdfmetrics.registerFont(TTFont('ArialGuideBold', str(base / 'arialbd.ttf')))


def wrap(text, font, size, max_width):
    words = text.split()
    lines, line = [], ''
    for word in words:
        trial = f'{line} {word}'.strip()
        if pdfmetrics.stringWidth(trial, font, size) <= max_width:
            line = trial
        else:
            if line:
                lines.append(line)
            line = word
    if line:
        lines.append(line)
    return lines


def main():
    if len(sys.argv) != 3:
        raise SystemExit(__doc__)
    frames, output = Path(sys.argv[1]), Path(sys.argv[2])
    missing = [name for name, _, _ in PAGES if not (frames / f'{name}.png').is_file()]
    if missing:
        raise SystemExit('Missing screenshots: ' + ', '.join(missing))
    font_setup()
    W, H = 1008, 612
    navy = colors.HexColor('#071936')
    ink = colors.HexColor('#183047')
    gold = colors.HexColor('#d5a444')
    muted = colors.HexColor('#637487')
    pale = colors.HexColor('#f4f6f8')
    c = canvas.Canvas(str(output), pagesize=(W, H), pageCompression=1)
    c.setTitle('D23 Visual Editor - Complete Illustrated Administrator Manual')
    c.setAuthor('UECFI District 23 FYS')

    # Cover.
    c.setFillColor(navy); c.rect(0, 0, W, H, fill=1, stroke=0)
    c.setFillColor(gold); c.roundRect(46, H-86, 300, 3, 1, fill=1, stroke=0)
    c.setFont('ArialGuideBold', 13); c.drawString(46, H-70, 'UECFI / DISTRICT 23 FYS')
    c.setFillColor(colors.white); c.setFont('ArialGuideBold', 39)
    c.drawString(46, H-150, 'The visual editor')
    c.drawString(46, H-198, 'administrator manual')
    c.setFillColor(colors.HexColor('#c8d5df')); c.setFont('ArialGuide', 14)
    c.drawString(46, H-233, 'Current interface | Illustrated, step-by-step | 2026 edition')
    c.drawImage(screenshot(frames/'02-overview.png'), 46, 55, width=580, height=362, preserveAspectRatio=True, anchor='c', mask='auto')
    c.setFillColor(colors.HexColor('#11294b')); c.roundRect(650, 65, 312, 350, 13, fill=1, stroke=0)
    c.setFillColor(gold); c.setFont('ArialGuideBold', 15); c.drawString(675, 376, 'Start here')
    cover_lines = [
        'Sign in at /admin/login.',
        'Edit page settings in one visual workspace.',
        'Use Records for News, Achievements and Officers.',
        'Page drafts need publishing; record edits do not.',
        'Permanent media deletion cannot be restored by page versions.',
        'The former password-change form is removed.',
    ]
    y = 342
    for item in cover_lines:
        for line in wrap('- ' + item, 'ArialGuide', 12, 262):
            c.setFillColor(colors.white); c.setFont('ArialGuide', 12); c.drawString(675, y, line); y -= 18
        y -= 13
    c.setFillColor(colors.HexColor('#a9bbcd')); c.setFont('ArialGuide', 9)
    c.drawString(46, 24, 'Screens captured from a disposable local demo. No production records or credentials are shown.')
    c.showPage()

    for index, (name, title, paragraphs) in enumerate(PAGES, 1):
        c.setFillColor(pale); c.rect(0, 0, W, H, fill=1, stroke=0)
        c.setFillColor(navy); c.rect(0, H-80, W, 80, fill=1, stroke=0)
        c.setFillColor(gold); c.setFont('ArialGuideBold', 10)
        c.drawString(30, H-24, f'ADMIN GUIDE  /  {index:02d} OF {len(PAGES):02d}')
        c.setFillColor(colors.white); c.setFont('ArialGuideBold', 25); c.drawString(30, H-59, title)
        c.setFillColor(colors.white); c.roundRect(26, 66, 690, 442, 10, fill=1, stroke=0)
        c.drawImage(screenshot(frames / f'{name}.png'), 34, 76, width=674, height=421.25, preserveAspectRatio=True, anchor='c', mask='auto')
        c.setFillColor(colors.HexColor('#e8edf2')); c.roundRect(730, 66, 252, 442, 10, fill=1, stroke=0)
        c.setFillColor(gold); c.setFont('ArialGuideBold', 11); c.drawString(747, 478, 'HOW TO USE THIS SCREEN')
        y = 451
        for num, paragraph in enumerate(paragraphs, 1):
            c.setFillColor(ink); c.setFont('ArialGuideBold', 10)
            c.drawString(747, y, f'{num:02d}')
            y -= 17
            for line in wrap(paragraph, 'ArialGuide', 10.3, 218):
                c.setFillColor(ink); c.setFont('ArialGuide', 10.3); c.drawString(747, y, line)
                y -= 14
            y -= 16
        if y < 77:
            raise RuntimeError(f'Text overflow on page {index}: {title}')
        c.setStrokeColor(colors.HexColor('#d8e0e7')); c.line(30, 45, W-30, 45)
        c.setFillColor(muted); c.setFont('ArialGuide', 9)
        c.drawString(30, 27, 'UECFI District 23 FYS  |  Visual editor administrator manual')
        c.drawRightString(W-30, 27, f'{index + 1:02d}')
        c.showPage()
    c.save()
    print(f'Created {output} with {len(PAGES) + 1} pages')


if __name__ == '__main__':
    main()
