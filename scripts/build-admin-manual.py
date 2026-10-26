"""Build the process-focused D23 administrator manual.

Usage: python scripts/build-admin-manual.py FRAMES_DIRECTORY OUTPUT_PDF
Screenshots come from scripts/build-admin-guide.cjs on a disposable database.
"""

from io import BytesIO
from pathlib import Path
import sys

from PIL import Image
from reportlab.lib import colors
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas


# One workflow per page. Controls that work alike share one process.
WORKFLOWS = [
    ("02a-guide-menu", "Open the editor", [
        "Go to /admin/login and sign in.",
        "Use Content, Design, Media, Sections or Records in the left panel. The right side is the live page preview.",
        "Click Edit panel to expand the preview. Click it again to show the controls.",
        "Click text, a section heading or a content card in the preview to jump to its editor.",
        "Open Admin guide for the video or this manual.",
    ]),
    ("03-home-text", "Change homepage text", [
        "Open Content > Home.",
        "Edit the hero heading, introduction, description and button labels.",
        "Set button destinations; edit statistics, the heritage text and section links in the same group.",
        "Click Save draft and inspect the refreshed preview.",
        "Click Publish changes when the page is ready.",
    ]),
    ("04a-contact", "Change site identity and links", [
        "Open Content > Header & contact details.",
        "Edit the site name, subtitle, organization, district, email, phone, address and social URLs.",
        "Open Content > Navigation for menu labels and Content > Footer for footer wording.",
        "Save draft, check the links and wording in the preview, then publish.",
    ]),
    ("11a-brand-selection", "Change logo and colors", [
        "Upload a logo image in Media if it is not already in the library.",
        "Open Content > Brand. Select the image for Official logo and Favicon.",
        "Choose the brand colors in the same group.",
        "Check desktop and phone previews in light and dark mode.",
        "Save draft, then publish. Select Default emblem to return to the original logo.",
    ]),
    ("07-type", "Change fonts and text style", [
        "Open Design and choose Style this part.",
        "Select the target, font, size, weight, alignment and text color.",
        "Repeat for another page part only if it needs a different style.",
        "Use Reset this part to original to remove the selected override.",
        "Check the preview, save draft and publish.",
    ]),
    ("08-background", "Change background and spacing", [
        "Open Design > Continuous page background.",
        "Choose Original design, Solid color or Gradient.",
        "For Gradient, set Start, End and Gradient angle. Set Section spacing if needed.",
        "Use Desktop, Tablet and Phone and the page theme switch to inspect the result.",
        "Save draft, then publish. The cover and footer have separate backgrounds.",
    ]),
    ("11-photo-upload", "Upload a photo or video", [
        "Open Media and click + Upload photos or videos.",
        "Select JPG, PNG, WebP or GIF photos, or MP4, WebM or OGG videos.",
        "Keep each file at or below 50 MB. If an upload fails, use a supported smaller file.",
        "After upload, find the file in Media library and click + Add slide to use it on the homepage cover.",
        "Save draft and publish to show the new cover slide publicly.",
    ]),
    ("12b-slide-order", "Arrange homepage slides", [
        "Open Media > Selected slides.",
        "Use the up and down arrows to set playback order. The first slide plays first.",
        "Click Remove to take a slide out of rotation without deleting its file.",
        "Use + Add slide in Media library to put a stored file back. The cover holds up to 20 slides.",
        "Save draft, check the preview and publish.",
    ]),
    ("26-video-delete", "Delete a stored media file", [
        "Open Media library and find the exact photo or video.",
        "Click Delete photo or Delete video on that file.",
        "Confirm permanent deletion. The file disappears from all places that use it.",
        "Check affected cover slides and records, then replace missing media if needed.",
    ]),
    ("13c-section-order", "Edit homepage sections", [
        "Open Sections, or click a section heading in the page preview.",
        "Change Heading and Description.",
        "Set Order; lower numbers appear earlier.",
        "Turn Visible off to hide the section without deleting its records. Turn it on to show it again.",
        "Save draft, inspect the page and publish.",
    ]),
    ("15-published", "Save and publish page changes", [
        "Edit page settings in Content, Design, Media or Sections.",
        "Click Save draft or press Ctrl+S. This refreshes the private page preview.",
        "Review desktop, tablet and phone layouts.",
        "Click Publish changes to update the public page.",
        "Open the public site and check the published result.",
    ]),
    ("16b-discard", "Undo page changes", [
        "Open Versions & administration.",
        "Click Discard draft to remove unpublished page changes, or Restore previous publication to return to the prior published page settings.",
        "Confirm the action and inspect the refreshed preview.",
        "These controls do not undo saved News, Achievement or Officer records or restore deleted files.",
    ]),
    ("17-records", "Open and manage records", [
        "Open Records and choose Manage News, Manage Achievements or Manage Officers.",
        "Click + Add to create a record, Edit to change one, Preview to inspect an article, or Delete to remove a record.",
        "Recent updates and page cards open the same editor directly.",
        "Save a record in its form. Record saves take effect immediately; page Publish changes is separate.",
        "If you leave an unsaved form, choose Cancel to keep editing or confirm to discard those edits.",
    ]),
    ("18-news-form", "Create a News story", [
        "Open Records > Manage News > + Add story.",
        "Enter Title, Slug, Summary and Content. Set Category, Featured, Sort order and Published at as needed.",
        "Set Status to Draft while preparing; Published makes it eligible to appear when its date arrives.",
        "Choose a cover and optional gallery, then click Save story.",
        "Reopen the story and use Preview to check the saved text and media.",
    ]),
    ("19b-photo-limit", "Fix upload and form errors", [
        "Read the message beside the form or upload control.",
        "Keep each record within 3 photos and 1 video total; the cover or portrait counts toward the limit.",
        "Keep each file at or below 50 MB. Remove an extra file or choose a smaller supported file.",
        "Correct invalid slugs, email addresses or URLs in their fields.",
        "Click Save again and confirm the success message.",
    ]),
    ("22-news-list", "Edit, preview or delete News", [
        "Open Records > Manage News and find the story.",
        "Click Edit, change its fields and Save story. Reopen it to verify the change.",
        "Click Preview to see the saved article, including a draft, privately.",
        "Click Delete on the record and confirm only when you intend to remove the whole story.",
        "To remove only a photo or video, open Edit and use that file's separate delete button.",
    ]),
    ("23b-achievement-published", "Manage an Achievement", [
        "Open Records > Manage Achievements > + Add achievement.",
        "Enter the story fields and Achievement date. Add cover or gallery media within the same limits as News.",
        "Save as Draft, reopen and use Preview to check it.",
        "Set Published and a valid Published at time, then save to make it eligible for the public section.",
        "Use Edit for changes or Delete to remove the entire Achievement.",
    ]),
    ("25-officer-saved", "Manage an Officer", [
        "Open Records > Manage Officers > + Add officer.",
        "Enter name, position, term, biography, order and status. Add a portrait and optional gallery.",
        "Save, reopen and verify the term, portrait and other details.",
        "Edit and save again to update the profile. Use its media buttons to delete an individual file.",
        "Use Delete on the officer list to remove the entire profile.",
    ]),
    ("21a-gallery-video-deleted", "Delete media inside a record", [
        "Open an existing News, Achievement or Officer record with Edit.",
        "Find the saved cover, portrait or gallery item.",
        "Click that item's Delete photo or Delete video button and confirm.",
        "Check the refreshed form. The record remains; only that stored file is removed.",
        "Use the record list's Delete button only when you want to remove the entire record.",
    ]),
    ("28-logout", "Finish the admin session", [
        "Save any open record form and publish any page draft that should go live.",
        "Check the public homepage and relevant News, Achievements or Officers section.",
        "Open Versions & administration and click Log out.",
    ]),
]


def setup_fonts():
    font_dir = Path("C:/Windows/Fonts")
    pdfmetrics.registerFont(TTFont("Guide", str(font_dir / "arial.ttf")))
    pdfmetrics.registerFont(TTFont("GuideBold", str(font_dir / "arialbd.ttf")))


def image_reader(path):
    with Image.open(path) as image:
        buffer = BytesIO()
        image.convert("RGB").save(buffer, format="JPEG", quality=86, optimize=True)
        buffer.seek(0)
        return ImageReader(buffer)


def wrap(text, font, size, width):
    lines, line = [], ""
    for word in text.split():
        trial = (line + " " + word).strip()
        if pdfmetrics.stringWidth(trial, font, size) <= width:
            line = trial
        else:
            if line:
                lines.append(line)
            line = word
    if line:
        lines.append(line)
    return lines


def draw_steps(pdf, steps, x, top, width):
    y = top
    for number, step in enumerate(steps, 1):
        pdf.setFillColor(colors.HexColor("#b78023"))
        pdf.setFont("GuideBold", 11)
        pdf.drawString(x, y, f"{number:02d}")
        lines = wrap(step, "Guide", 10.8, width - 32)
        for line in lines:
            pdf.setFillColor(colors.HexColor("#183047"))
            pdf.setFont("Guide", 10.8)
            pdf.drawString(x + 31, y, line)
            y -= 14.5
        y -= 15
    if y < 82:
        raise RuntimeError("Steps overflow the page")


def main():
    if len(sys.argv) != 3:
        raise SystemExit(__doc__)
    frames, output = Path(sys.argv[1]), Path(sys.argv[2])
    missing = [name for name, _, _ in WORKFLOWS if not (frames / f"{name}.png").is_file()]
    if missing:
        raise SystemExit("Missing screenshots: " + ", ".join(missing))
    output.parent.mkdir(parents=True, exist_ok=True)
    setup_fonts()
    width, height = 1008, 612
    navy = colors.HexColor("#071936")
    gold = colors.HexColor("#d5a444")
    pale = colors.HexColor("#f4f6f8")
    pdf = canvas.Canvas(str(output), pagesize=(width, height), pageCompression=1)
    pdf.setTitle("D23 Admin Manual - Complete Workflows")
    pdf.setAuthor("UECFI District 23 FYS")

    # Short start page: the three save rules are the only orientation needed.
    pdf.setFillColor(navy)
    pdf.rect(0, 0, width, height, fill=1, stroke=0)
    pdf.setFillColor(gold)
    pdf.setFont("GuideBold", 13)
    pdf.drawString(46, 548, "UECFI / DISTRICT 23 FYS")
    pdf.setFillColor(colors.white)
    pdf.setFont("GuideBold", 40)
    pdf.drawString(46, 471, "Admin workflows")
    pdf.setFont("Guide", 17)
    pdf.drawString(46, 430, "Click-by-click processes for the current visual editor")
    pdf.setFillColor(colors.HexColor("#122d50"))
    pdf.roundRect(46, 92, 910, 280, 15, fill=1, stroke=0)
    quick_rules = [
        ("PAGE", "Edit > Save draft > Preview > Publish changes"),
        ("RECORD", "Edit News, Achievement or Officer > Save form"),
        ("MEDIA", "Upload is immediate; Delete photo/video is permanent"),
    ]
    y = 326
    for label, text in quick_rules:
        pdf.setFillColor(gold)
        pdf.setFont("GuideBold", 13)
        pdf.drawString(74, y, label)
        pdf.setFillColor(colors.white)
        pdf.setFont("Guide", 15)
        pdf.drawString(185, y, text)
        y -= 78
    pdf.setFillColor(colors.HexColor("#bfcddc"))
    pdf.setFont("Guide", 10)
    pdf.drawString(46, 42, "Each following page shows one complete workflow. Similar controls use the same steps.")
    pdf.showPage()

    for index, (frame, title, steps) in enumerate(WORKFLOWS, 1):
        pdf.setFillColor(pale)
        pdf.rect(0, 0, width, height, fill=1, stroke=0)
        pdf.setFillColor(navy)
        pdf.rect(0, height - 78, width, 78, fill=1, stroke=0)
        pdf.setFillColor(gold)
        pdf.setFont("GuideBold", 10)
        pdf.drawString(30, height - 24, f"WORKFLOW {index:02d} / {len(WORKFLOWS):02d}")
        pdf.setFillColor(colors.white)
        pdf.setFont("GuideBold", 24)
        pdf.drawString(30, height - 58, title)

        pdf.setFillColor(colors.white)
        pdf.roundRect(25, 67, 632, 440, 10, fill=1, stroke=0)
        pdf.drawImage(image_reader(frames / f"{frame}.png"), 33, 78,
                      width=616, height=418, preserveAspectRatio=True,
                      anchor="c", mask="auto")
        pdf.setFillColor(colors.HexColor("#e8edf2"))
        pdf.roundRect(672, 67, 311, 440, 10, fill=1, stroke=0)
        pdf.setFillColor(colors.HexColor("#9a6612"))
        pdf.setFont("GuideBold", 11)
        pdf.drawString(691, 477, "DO THIS")
        draw_steps(pdf, steps, 691, 447, 274)
        pdf.setFillColor(colors.HexColor("#637487"))
        pdf.setFont("Guide", 9)
        pdf.drawString(30, 31, "D23 visual editor | Process manual")
        pdf.drawRightString(978, 31, str(index + 1))
        pdf.showPage()

    pdf.save()
    print(f"Created {output} with {len(WORKFLOWS) + 1} pages")


if __name__ == "__main__":
    main()
