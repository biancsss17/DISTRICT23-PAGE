/* Record the current visual editor against a disposable local database only.
   D23_GUIDE_BASE defaults to http://127.0.0.1:8017. */
const { chromium } = require('C:/Users/User/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');

const base = process.env.D23_GUIDE_BASE || 'http://127.0.0.1:8017';
const root = path.resolve(__dirname, '..');
const out = fs.mkdtempSync(path.join(os.tmpdir(), 'd23-admin-guide-'));
const frames = path.join(out, 'frames');
const videoFile = path.join(out, 'D23_Admin_Walkthrough_Complete.webm');
const transcriptFile = path.join(out, 'D23_Admin_Walkthrough_Complete_Transcript.txt');
fs.mkdirSync(frames, { recursive: true });
console.log('Guide output: ' + out);
const transcript = [];
let chapter = 0;

(async () => {
  const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, recordVideo: { dir: out, size: { width: 1440, height: 900 } }, reducedMotion: 'reduce' });
  await context.addInitScript(() => {
    const install = () => {
      if (document.querySelector('#guide-cursor')) return;
      const cursor = document.createElement('div');
      cursor.id = 'guide-cursor';
      cursor.style.cssText = 'position:fixed;left:0;top:0;z-index:2147483646;pointer-events:none;width:23px;height:23px;transform:translate(-2px,-2px);filter:drop-shadow(0 1px 3px #071936);opacity:0';
      cursor.innerHTML = '<svg width="23" height="23" viewBox="0 0 23 23"><path d="M2 1v18l5-5 3.7 7 3.8-2-3.8-7H19z" fill="#f7c85c" stroke="#071936" stroke-width="2"/></svg>';
      document.body.append(cursor);
      document.addEventListener('mousemove', e => {
        const host = document.querySelector('dialog[open]') || document.body;
        if (cursor.parentNode !== host) host.append(cursor);
        cursor.style.left = e.clientX + 'px'; cursor.style.top = e.clientY + 'px'; cursor.style.opacity = '1';
      }, true);
      document.addEventListener('mousedown', e => {
        const ring = document.createElement('span');
        ring.style.cssText = `position:fixed;z-index:2147483645;pointer-events:none;left:${e.clientX-22}px;top:${e.clientY-22}px;width:44px;height:44px;border:3px solid #f7c85c;border-radius:50%;box-shadow:0 0 0 2px #071936;transition:transform .48s,opacity .48s;`;
        (document.querySelector('dialog[open]') || document.body).append(ring);
        requestAnimationFrame(() => { ring.style.transform='scale(1.8)'; ring.style.opacity='0'; });
        setTimeout(() => ring.remove(), 600);
      }, true);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install, { once: true }); else install();
  });
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  page.on('dialog', dialog => dialog.accept());
  const wait = ms => page.waitForTimeout(process.env.D23_GUIDE_FAST ? Math.min(150, ms) : ms);
  async function caption(title, body, ms = 2400) {
    chapter++;
    const label = String(chapter).padStart(2, '0');
    transcript.push(`${label}. ${title}\n${body}\n`);
    await page.evaluate(({ title, body, label }) => {
      let box = document.querySelector('#d23-guide-caption');
      if (!box) {
        box = document.createElement('div');
        box.id = 'd23-guide-caption';
        box.style.cssText = 'position:fixed;z-index:2147483647;left:50%;bottom:20px;transform:translateX(-50%);width:min(920px,88vw);padding:13px 20px;border:1px solid #e8c269;border-radius:12px;background:#071936ed;color:#fff;box-shadow:0 10px 32px #07193677;pointer-events:none;font:16px/1.35 Arial,sans-serif;text-align:center';
        document.body.append(box);
      }
      const host = document.querySelector('dialog[open]') || document.body;
      if (box.parentNode !== host) host.append(box);
      box.innerHTML = `<strong style="color:#e8c269;font-size:13px;letter-spacing:1px">${label} · ${title}</strong><br><span>${body}</span>`;
    }, { title, body, label });
    await wait(ms);
  }
  async function shot(name) { await page.screenshot({ path: path.join(frames, name + '.png') }); }
  async function tab(name) { await page.locator(`[data-tab="${name}"]`).click(); await wait(300); }
  async function group(name) { const details = page.locator(`[data-group="${name}"]`); if (!(await details.evaluate(el => el.open))) await details.locator('summary').click(); await details.scrollIntoViewIfNeeded(); }
  async function openRecords(type) { await tab('records'); await page.locator('#records-panel').waitFor({ state: 'visible' }); await Promise.all([page.waitForResponse(r => r.url().endsWith('/records/' + type) && r.request().method() === 'GET'), page.locator(`#records-panel [data-record-type="${type}"]`).click()]); await page.locator('#record-list').waitFor({ state: 'visible' }); await wait(350); }
  async function switchRecordType(type) { await Promise.all([page.waitForResponse(r => r.url().endsWith('/records/' + type) && r.request().method() === 'GET'), page.locator(`.record-kinds [data-record-type="${type}"]`).click()]); await page.locator('#record-list .record-primary').waitFor({ state: 'visible' }); }
  async function previewReady() { await page.frameLocator('#page-preview').locator('.home-page').waitFor({ state: 'visible', timeout: 30000 }); }
  async function typeInto(locator, value) {
    await locator.click();
    await locator.press('ControlOrMeta+A');
    await locator.pressSequentially(value, { delay: process.env.D23_GUIDE_FAST ? 1 : 42 });
  }
  async function field(name, value) {
    const input = page.locator(`#record-form [name="${name}"]`);
    const kind = await input.getAttribute('type');
    if (kind === 'date' || kind === 'datetime-local') {
      await input.click();
      await input.fill(value);
    } else await typeInto(input, value);
  }
  async function setting(name, value) { await typeInto(page.locator(`#field-${name}`), value); }
  async function choose(name, value) { await page.locator(`#record-form [name="${name}"]`).selectOption(value); }
  async function saveRecord() {
    await Promise.all([
      page.waitForResponse(r => /\/admin\/editor\/records\/(news|achievements|officers)\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'GET', { timeout: 30000 }),
      page.locator('#record-form [type="submit"]').click(),
    ]);
    await page.waitForFunction(() => !document.querySelector('#record-form').hidden && document.querySelector('#record-message')?.textContent?.includes('saved.'), null, { timeout: 20000 });
    await page.waitForTimeout(500);
  }
  async function back() { await page.locator('#record-form .record-back').click(); await page.locator('#record-list').waitFor({ state: 'visible' }); await page.locator('#record-list .record-row').first().waitFor({ state: 'visible' }); }
  async function closeRecords() { await page.locator('#close-record').click(); await wait(350); }

  try {
    await page.goto(base + '/admin/login', { waitUntil: 'domcontentloaded' });
    await caption('Welcome to the current admin', 'Sign in with your administrator account. This recording uses a disposable demo account.');
    await shot('01-login');
    await page.locator('[name="email"]').fill('tutorial@example.test');
    await page.locator('[name="password"]').fill('GuideDemo2026!');
    await page.getByRole('button', { name: /Sign in/ }).click();
    await page.waitForURL(base + '/admin');
    await page.locator('#page-preview').waitFor();
    await previewReady();
    await caption('One visual editor', 'The real homepage appears on the right. Content, Design, Media, Sections and Records are managed on the left; repeated controls work alike.');
    await shot('02-overview');

    await page.locator('.admin-guide summary').click();
    await caption('Open the admin guide', 'The Admin guide menu offers this video and a detailed illustrated manual.');
    await shot('02a-guide-menu');
    await page.locator('.admin-guide summary').click();
    await page.locator('#toggle-panel').click();
    await caption('Expand the page preview', 'Edit panel hides the controls temporarily so you can inspect more of the real homepage. Click again to restore them.');
    await shot('02b-panel-hidden');
    await page.locator('#toggle-panel').click();
    await previewReady();
    await page.frameLocator('#page-preview').locator('.hero h1').click();
    await caption('Click the actual page to edit', 'Click editable text in the live preview. The matching Content field opens and receives focus.');
    await shot('02c-click-preview');
    await page.frameLocator('#page-preview').locator('#news [data-editor-record]').first().locator('h3 a').click();
    await page.locator('#record-form').waitFor({ state: 'visible' });
    await caption('Open a story from the live page', 'Click a News or Achievement card in the page preview to edit that saved record. Officer cards use the same shortcut.');
    await shot('02d-click-card');
    await closeRecords();
    await previewReady();

    await group('home');
    await caption('Edit homepage text', 'Home fields control the hero title, supporting copy, buttons and other homepage wording.');
    await setting('hero_heading', 'Lead. Serve. Inspire.');
    await shot('03-home-text');
    await setting('primary_text', 'Explore our achievements');
    await setting('primary_url', '/achievements');
    await caption('Buttons and destinations', 'Type a button label and its destination. Use an internal path or an approved URL, then check the public link.');
    await shot('03a-buttons');
    await setting('stat_1_value', '500');
    await setting('stat_1_label', 'Members');
    await caption('Statistics and section links', 'The four statistic values and labels, note, and News, Achievements and Officers link text are editable in Home.');
    await shot('03b-statistics');
    await group('organization');
    await caption('Header and contact', 'Header identity, organization and contact details are in Content. Type to preview supported text changes.');
    await shot('04-identity');
    await setting('header_title', 'UECFI DISTRICT 23 FYS');
    await setting('email', 'guide@example.test');
    await setting('facebook', 'https://example.test/community');
    await caption('Contact fields and links', 'Update the organization name, district, email, phone, address and social links here. Enter complete URLs for social links.');
    await shot('04a-contact');
    await group('brand');
    await caption('Logo and brand colors', 'Choose stored imagery for the logo and favicon, and edit the palette without leaving the visual editor.');
    await shot('04b-brand');
    await group('navigation');
    await caption('Navigation labels', 'Edit menu wording here. Check each destination after publishing.');
    await setting('nav_news', 'News');
    await shot('05-navigation');
    await group('footer');
    await caption('Footer content', 'The footer heading, supporting text and copyright share this Content panel.');
    await setting('footer_heading', 'Lead. Serve. Inspire.');
    await shot('06-footer');

    await tab('design');
    await caption('Typography controls', 'Choose a page part, then set its font, size, weight, alignment and color.');
    await page.locator('#style-target').selectOption('hero');
    await page.locator('[data-style="font"]').selectOption('Georgia');
    await typeInto(page.locator('[data-style="size"]'), '72');
    await page.locator('[data-style="weight"]').selectOption('700');
    await page.locator('[data-style="align"]').selectOption('center');
    await page.locator('[data-style="color"]').fill('#17345b');
    await shot('07-type');
    await caption('Repeat the same style steps elsewhere', 'Other font choices and page parts use this same target, font, size, weight, alignment and color workflow.');
    await page.locator('#reset-style').click();
    await caption('Reset a style', 'Reset this part to original removes the selected typography override.');
    await page.locator('[data-background="mode"]').selectOption('gradient');
    await page.locator('[data-background="start"]').fill('#f7f1df');
    await page.locator('[data-background="end"]').fill('#dce8f6');
    await page.locator('[data-background="angle"]').fill('120');
    await caption('Background gradients', 'Choose Original, Solid or Gradient. The cover and footer keep their own backgrounds.');
    await shot('08-background');
    await page.locator('[data-background="mode"]').selectOption('solid');
    await caption('Solid background option', 'A single color is another design option. Use Original design to return to the existing continuous background.');
    await shot('08a-solid');
    await page.locator('[data-background="mode"]').selectOption('original');
    await typeInto(page.locator('#section-spacing'), '88');
    await caption('Section spacing', 'Spacing changes can be tested in desktop, tablet and phone preview before publication.');
    await page.locator('[data-width="768"]').click();
    await caption('Tablet preview', 'The viewport buttons change only the editor preview size.');
    await page.locator('[data-width="390"]').click();
    await caption('Phone preview', 'Always review small screens; the same content adapts responsively.');
    await shot('09-phone');
    await page.locator('[data-width="1440"]').click();
    await page.frameLocator('#page-preview').locator('[data-theme-toggle]').click();
    await caption('Check dark mode', 'The website has light and dark appearances. Use the preview switch to inspect contrast before publishing colors or typography.');
    await shot('09a-dark-mode');
    await page.frameLocator('#page-preview').locator('[data-theme-toggle]').click();

    await tab('media');
    await caption('Cover slideshow', 'Choose up to 20 photos or videos. Move selected slides with arrows; Remove only takes a slide out of rotation.');
    await shot('10-media');
    const photo = path.join(root, 'public', 'images', 'landscape.jpg');
    await page.locator('#media-upload').setInputFiles(photo);
    await page.waitForFunction(() => document.querySelector('#editor-status')?.textContent?.includes('Uploaded.'), null, { timeout: 30000 });
    await caption('Upload a photo', 'Each file may be up to 50 MB. Upload adds it to the library immediately; publish controls homepage use.');
    await shot('11-photo-upload');
    await tab('content');
    await group('brand');
    const uploadedLogoId = await page.locator('#field-logo_id option').last().getAttribute('value');
    await page.locator('#field-logo_id').selectOption(uploadedLogoId);
    await page.locator('#field-favicon_id').selectOption(uploadedLogoId);
    await page.locator('#field-primary_color').fill('#102b50');
    await caption('Select logo, favicon and palette', 'A library image can be selected as a logo or favicon. Brand colors update the page theme; publish only after checking contrast.');
    await shot('11a-brand-selection');
    await page.locator('#field-logo_id').selectOption('');
    await page.locator('#field-favicon_id').selectOption('');
    await tab('media');
    const video = path.join(root, 'public', 'images', 'sample-uecfi-slideshow.webm');
    const galleryVideo = path.join(out, 'guide-gallery.webm');
    const officerVideo = path.join(out, 'officer-clip.webm');
    fs.copyFileSync(video, galleryVideo);
    fs.copyFileSync(video, officerVideo);
    await page.locator('#media-upload').setInputFiles(video);
    await page.waitForFunction(() => document.querySelector('#editor-status')?.textContent?.includes('Uploaded.'), null, { timeout: 30000 });
    await caption('Upload a video', 'Videos use the same media library. This disposable sample is used only for the guide.');
    await shot('12-video-upload');
    const oversized = path.join(out, 'too-large.png');
    const oversizedHandle = fs.openSync(oversized, 'w');
    fs.ftruncateSync(oversizedHandle, 50 * 1024 * 1024 + 1);
    fs.closeSync(oversizedHandle);
    await page.locator('#media-upload').setInputFiles(oversized);
    await page.waitForFunction(() => document.querySelector('#editor-status')?.textContent?.includes('50 MB'));
    await caption('50 MB upload validation', 'A file over 50 MB is rejected before upload. Choose a smaller photo or video and try again.');
    await shot('12a-file-limit');
    await page.locator('#selected-slides .media-tile').first().getByRole('button', { name: 'Move slide later' }).click();
    await caption('Order and remove', 'Use the arrows to reorder slides. Remove changes the draft selection without deleting the file.');
    await shot('12b-slide-order');
    await page.locator('#selected-slides .media-tile').last().getByRole('button', { name: 'Remove' }).click();
    await page.locator('#media-library .media-tile').filter({ hasText: 'sample-uecfi-slideshow.webm' }).first().getByRole('button', { name: '+ Add slide' }).click();
    await caption('Add a stored slide again', 'A removed slide stays in the media library. Add slide puts it back without another upload. The 20-slide cap uses the same limit-message pattern shown for other media counts.');
    await shot('12c-add-slide');

    await tab('sections');
    await caption('Manage sections', 'Change section headings, descriptions, display order and visibility without deleting their records.');
    await shot('13-sections');
    const firstSection = page.locator('.section-box').first();
    const originalSectionTitle = await firstSection.locator('input[type="text"]').inputValue();
    await typeInto(firstSection.locator('input[type="text"]'), 'Community stories');
    await typeInto(firstSection.locator('textarea'), 'Stories and people serving our district.');
    await typeInto(firstSection.locator('input[type="number"]'), '2');
    const visibility = firstSection.locator('input[type="checkbox"]');
    await visibility.uncheck();
    await caption('Visibility and order', 'Visible hides a section without deleting its stories. Numeric Order changes the section sequence.');
    await shot('13b-visibility');
    await visibility.check();
    await caption('Edit section heading and order', 'Type a heading and description, change its numeric order, and turn visibility back on before publishing.');
    await shot('13c-section-order');
    await page.frameLocator('#page-preview').locator('#news h2').click();
    await caption('Select a section from the page', 'Click a section heading in the live preview to jump to its controls in the Sections tab.');
    await shot('13d-click-section');
    await caption('Section preview', 'Save draft to refresh the actual page preview. The public site is still unchanged.');
    await page.locator('#save-draft').click();
    await page.waitForFunction(() => document.querySelector('#editor-status')?.textContent?.includes('Draft saved.'), null, { timeout: 20000 });
    await shot('14-draft');
    await tab('content');
    await group('home');
    await setting('hero_heading', 'Lead. Serve. Inspire. Together.');
    await page.keyboard.press('Control+s');
    await page.waitForFunction(() => document.querySelector('#editor-status')?.textContent?.includes('Draft saved.'), null, { timeout: 20000 });
    await caption('Save with Ctrl+S', 'Ctrl+S saves the same private page draft as Save draft. It does not publish or save an open story form.');
    await shot('14a-shortcut');
    await caption('Publish page changes', 'Publish applies the saved page draft on this installation. Record edits are separate and save immediately.');
    await Promise.all([page.waitForEvent('load'), page.locator('#publish').click()]);
    await previewReady();
    await shot('15-published');
    await page.locator('.versions summary').first().click();
    await caption('Restore and discard', 'Restore previous publication returns prior page settings. Discard draft removes only unpublished page changes.');
    await shot('16-versions');
    await Promise.all([page.waitForEvent('load'), page.locator('#restore').click()]);
    await previewReady();

    await page.locator('#field-hero_heading').fill('Unpublished guide draft');
    await page.locator('#save-draft').click();
    await page.waitForFunction(() => document.querySelector('#editor-status')?.textContent?.includes('Draft saved.'), null, { timeout: 20000 });
    await page.locator('.versions summary').first().click();
    await caption('Discard a draft', 'Discard removes unpublished page changes. Uploaded files stay in the media library.');
    await shot('16b-discard');
    await Promise.all([page.waitForEvent('load'), page.locator('#discard').click()]);
    await previewReady();

    await tab('records');
    await caption('Records overview', 'Published counts and recent updates are in Records. Open News, Achievements or Officers here.');
    await shot('17-records');
    await page.locator('#records-panel .record-recent').first().click();
    await page.locator('#record-form').waitFor({ state: 'visible' });
    await caption('Open a recent update', 'Click Recent updates to open an existing record directly. This is a shortcut to the same editor used by Manage News, Achievements and Officers.');
    await shot('17a-recent-update');
    await closeRecords();
    await openRecords('news');
    await page.locator('#record-list .record-primary').click();
    await field('title', 'Unsaved guide example');
    await caption('Unsaved record warning', 'Leaving a changed record asks whether to discard the unsaved form. Page-draft changes have a similar leave-page warning.');
    await shot('18a-unsaved-warning');
    const [unsavedDialog] = await Promise.all([page.waitForEvent('dialog'), page.locator('#record-form .record-back').click()]);
    if (!unsavedDialog.message().includes('Discard unsaved record changes')) throw new Error('Expected unsaved record warning');
    await page.locator('#record-list').waitFor({ state: 'visible' });
    await caption('Confirm discard and return', 'After accepting the warning, the temporary text is gone and the record list is unchanged. Cancel to keep editing.');
    await shot('18b-discard-confirmed');
    await page.locator('#record-list .record-primary').click();
    await caption('Create a News item', 'Enter title, slug, summary, body, publication status, schedule, cover and optional gallery.');
    await shot('18-news-form');
    await field('title', 'Guide demo: community update');
    await field('slug', 'bad slug');
    await field('content', 'A disposable story used to demonstrate the new Records editor.');
    await choose('status', 'published');
    await field('published_at', '2026-10-01T10:00');
    await page.locator('#record-form [type="submit"]').click();
    await page.waitForFunction(() => document.querySelector('#record-message')?.classList.contains('error'));
    await page.locator('.record-workspace').evaluate(el => el.scrollTop = 0);
    await caption('Clear validation', 'An invalid slug displays a clear error; nothing was saved. Email, URL and other field errors follow this same fix-and-save pattern.');
    await shot('19-validation');
    await field('slug', 'guide-demo-community-update');
    await field('excerpt', 'A short public summary.');
    await field('category', 'Community');
    await page.locator('#record-form [name="featured"]').check();
    await field('sort_order', '3');
    await caption('Category, featured flag and order', 'A story can be categorized, featured and sorted. Set Published at and Status to control when it appears publicly.');
    await shot('19a-news-options');
    await page.locator('#record-form [name="cover_upload"]').setInputFiles(photo);
    await page.locator('#record-form [name="gallery_uploads[]"]').setInputFiles([photo, photo, photo]);
    await page.locator('#record-form [type="submit"]').click();
    await page.waitForFunction(() => document.querySelector('#record-message')?.textContent?.includes('at most 3 photos'));
    await page.locator('.record-workspace').evaluate(el => el.scrollTop = 0);
    await caption('Three-photo limit', 'The cover counts toward the 3-photo limit. Four photos are rejected with a clear message; remove an extra gallery photo.');
    await shot('19b-photo-limit');
    await page.locator('#record-form [name="gallery_uploads[]"]').setInputFiles([galleryVideo, galleryVideo]);
    await page.locator('#record-form [type="submit"]').click();
    await page.waitForFunction(() => document.querySelector('#record-message')?.textContent?.includes('at most 1 video'));
    await page.locator('.record-workspace').evaluate(el => el.scrollTop = 0);
    await caption('One-video limit', 'A content item can contain only one video. Two gallery videos are rejected before the record is saved.');
    await shot('19c-video-limit');
    await page.locator('#record-form [name="gallery_uploads[]"]').setInputFiles([photo, galleryVideo]);
    await saveRecord();
    await caption('Save cover and gallery', 'This valid item has a cover photo, one gallery photo and one gallery video. Reopen it to verify all three files.');
    await shot('19d-news-gallery');
    await caption('Save and verify News', 'The save message confirms the record. Preview shows the saved article, including drafts, privately.');
    await shot('20-news-saved');
    await Promise.all([page.waitForResponse(r => /\/admin\/editor\/records\/news\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'GET'), page.locator('#record-form .record-media-item').first().getByRole('button', { name: /Delete photo/ }).click()]);
    await page.waitForTimeout(500);
    await caption('Delete a photo', 'Delete photo permanently removes this disposable file and its database references. It is different from removing a cover slide.');
    await shot('21-photo-deleted');
    await Promise.all([page.waitForResponse(r => /\/admin\/editor\/records\/news\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'GET'), page.locator('#record-form .record-media-item').filter({ hasText: 'guide-gallery.webm' }).getByRole('button', { name: 'Delete video' }).click()]);
    await caption('Delete a gallery video', 'Saved gallery videos have their own Delete video button. Confirming permanently removes this file from every reference.');
    await shot('21a-gallery-video-deleted');
    await Promise.all([page.waitForResponse(r => /\/admin\/editor\/records\/news\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'GET'), page.locator('#record-form .record-media-item').filter({ hasText: 'landscape.jpg' }).getByRole('button', { name: 'Delete photo' }).click()]);
    await caption('Delete a gallery photo', 'Gallery photos use the same permanent delete control. Removing a story record is a separate action.');
    await shot('21b-gallery-photo-deleted');
    await field('excerpt', 'Updated summary saved through the new editor.');
    await saveRecord();
    await caption('Edit an existing item', 'Reopen any saved record, change its fields, and save again. The public page uses the updated content.');
    await back();
    const newsRow = page.locator('.record-row').filter({ hasText: 'Guide demo: community update' });
    await caption('Private preview and delete', 'The list provides Edit, Preview and Delete. Delete asks for confirmation.');
    await shot('22-news-list');
    const previewUrl = await newsRow.getByRole('link', { name: 'Preview' }).getAttribute('href');
    await page.goto(base + previewUrl);
    await caption('Private article preview', 'This saved article is previewed through an administrator-only route. Drafts can be checked here too.');
    await shot('22b-private-preview');
    await page.goto(base + '/admin');
    await previewReady();
    await openRecords('news');
    await page.locator('.record-row').filter({ hasText: 'Guide demo: community update' }).getByRole('button', { name: 'Delete' }).click();
    await wait(450);

    await switchRecordType('achievements');
    await page.locator('#record-list .record-primary').click();
    await field('title', 'Guide demo: service milestone');
    await field('content', 'A disposable achievement used for the current admin guide.');
    await field('event_date', '2026-10-01');
    await choose('status', 'draft');
    await field('published_at', '2026-10-01T10:00');
    await caption('Achievements', 'Achievements use the same story editor and add an achievement date.');
    await shot('23-achievement');
    await saveRecord();
    await caption('Save an unpublished draft record', 'A draft Achievement saves immediately in Records but does not appear on the public site. Preview remains available to administrators.');
    await shot('23a-achievement-draft');
    await choose('status', 'published');
    await saveRecord();
    await caption('Publish an Achievement record', 'Changing this record to Published and saving makes it eligible for the public section when its publication time has arrived.');
    await shot('23b-achievement-published');
    await back();
    await page.locator('.record-row').filter({ hasText: 'Guide demo: service milestone' }).getByRole('button', { name: 'Delete' }).click();
    await caption('Achievement saved and removed', 'The disposable achievement was saved, verified in the list, then deleted after confirmation.');

    await switchRecordType('officers');
    await page.locator('#record-list .record-primary').click();
    await field('name', 'Guide Demo Officer');
    await field('position', 'Demonstration Coordinator');
    await field('term', '2026-2028');
    await field('biography', 'A disposable officer profile for the new admin guide.');
    await field('email', 'officer@example.test');
    await field('social_url', 'https://example.test/officer');
    await field('sort_order', '2');
    await choose('status', 'published');
    await page.locator('#record-form [name="photo_upload"]').setInputFiles(photo);
    await page.locator('#record-form [name="gallery_uploads[]"]').setInputFiles([photo, officerVideo]);
    await caption('Officer profiles', 'Add name, position, term, biography, portrait, gallery, order and publication status.');
    await shot('24-officer');
    await saveRecord();
    await caption('Officer media', 'The portrait has its own permanent delete control. Gallery photos and videos have separate controls too.');
    await shot('25-officer-saved');
    await Promise.all([page.waitForResponse(r => /\/admin\/editor\/records\/officers\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'GET'), page.locator('#record-form .record-media-item').first().getByRole('button', { name: /Delete photo/ }).click()]);
    await page.waitForTimeout(500);
    await Promise.all([page.waitForResponse(r => /\/admin\/editor\/records\/officers\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'GET'), page.locator('#record-form .record-media-item').filter({ hasText: 'officer-clip.webm' }).getByRole('button', { name: 'Delete video' }).click()]);
    await caption('Officer gallery video control', 'An officer can also have gallery media. This removes the saved video without deleting the profile.');
    await shot('25a-officer-video-deleted');
    await Promise.all([page.waitForResponse(r => /\/admin\/editor\/records\/officers\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'GET'), page.locator('#record-form .record-media-item').filter({ hasText: 'landscape.jpg' }).getByRole('button', { name: 'Delete photo' }).click()]);
    await field('term', '2027-2029');
    await saveRecord();
    await back();
    await page.locator('.record-row').filter({ hasText: 'Guide Demo Officer' }).getByRole('button', { name: 'Delete' }).click();
    await caption('Officer edit and delete', 'The term was updated and saved. The disposable profile and portrait were removed after confirmation.');
    await closeRecords();

    await tab('media');
    const videoTile = page.locator('#media-library .media-tile').filter({ hasText: 'sample-uecfi-slideshow.webm' }).first();
    await caption('Delete a video', 'Delete video permanently removes the disposable upload wherever it is referenced.');
    await shot('26-video-delete');
    const [deleteResponse] = await Promise.all([page.waitForResponse(r => /\/admin\/editor\/media\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'DELETE'), videoTile.getByRole('button', { name: /Permanently delete/ }).click()]);
    if (!deleteResponse.ok()) throw new Error('Demo video deletion failed: ' + (await deleteResponse.text()).slice(0, 300));
    await page.waitForTimeout(1500);
    if (!page.url().endsWith('/admin')) await page.goto(base + '/admin', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('domcontentloaded');
    await previewReady();
    await tab('media');
    await caption('Confirm deletion', 'The uploaded video is gone from the library. Always check the exact file before confirming.');
    await shot('27-video-gone');

    await page.locator('.versions summary').first().click();
    await page.locator('.versions form button').scrollIntoViewIfNeeded();
    await caption('Finish safely', 'The former password-change form is removed. Use Log out to end your session. Account resets are handled separately by an authorized administrator.');
    await shot('28-logout');
    await page.locator('.versions button').filter({ hasText: 'Log out' }).click();
    await page.waitForURL(base + '/');
    await caption('Guide complete', 'The new editor manages the page and content in one place. These examples changed only a disposable local guide database.');
    await shot('29-finish');

    fs.writeFileSync(transcriptFile, 'D23 VISUAL EDITOR - CURRENT ADMIN VIDEO GUIDE\n\nRecorded on a disposable local database. No production data or accounts were used.\n\n' + transcript.join('\n'), 'utf8');
    console.log(JSON.stringify({ chapters: chapter, frames: fs.readdirSync(frames).length, errors }));
  } catch (error) {
    console.error('Guide recording failed:', error);
    throw error;
  } finally {
    const video = page.video();
    await context.close();
    if (video) {
      try { await video.saveAs(videoFile); }
      catch (error) { console.error('Video export failed:', error.message); }
    }
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
