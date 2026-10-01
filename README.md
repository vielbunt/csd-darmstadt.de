# CSD Darmstadt WordPress Theme

This is our custom WordPress theme for csd-darmstadt.de. It's built on top of Twenty Twenty-Five and maintained by the vielbunt e.V. web team.

## Setup

1. Make sure Twenty Twenty-Five is installed (it doesn't have to be active, just present).
2. The theme lives on the server in the folder `csddarmstadtzweinull`. Only the very first install is a manual upload (Design > Themes > Theme hinzufügen > Theme hochladen, the ZIP has to contain that folder name). After that, updates come in automatically, see below.
3. The font (PT Sans) loads automaticaly from Google Fonts, nothing to do there.
4. Go to Design > Editor and assign the correct navigation to the header nav block. It should pick up "Hauptnavigation" on its own but if it dosn't, just select it manually in the sidebar.
5. Under Einstellungen > Lesen, set a static front page so the front-page template kicks in.

## Updates and deployment

Every push to `main` goes live on its own, usually within two or three minutes:

1. The GitHub Action (`.github/workflows/deploy.yml`) checks the PHP syntax, `theme.json` and the JS, then boots a throwaway WordPress with the theme ([WordPress Playground](https://github.com/WordPress/wordpress-playground)) and loads a few pages. Any PHP error or warning stops everything right there, nothing reaches the server.
2. It builds `theme.zip` (folder `csddarmstadtzweinull`, version = the one in `style.css` plus the run number, e.g. `2.2.0.17`) and publishes it as a GitHub release together with a small `release.json`.
3. It calls `POST https://www.csd-darmstadt.de/wp-json/csd/v1/deploy` with the secret `DEPLOY_TOKEN`. WordPress downloads the release and installs it through its normal theme updater, into the existing folder. Content, menus and front page settings are not touched.
4. Finally it loads the live front page and checks that hero and tiles are there.

If the webhook ever fails, WordPress still sees the update under Dashboard > Aktualisierungen and installs it with its automatic background update (switched on for this theme).

Status, last runs, an "update now" button and the token are under **Design > Theme-Updates** in wp-admin.

**One-time setup for the webhook:** in wp-admin open Design > Theme-Updates, click "Token erzeugen", copy it into GitHub under Settings > Secrets and variables > Actions as `DEPLOY_TOKEN`. Alternatively define `CSD_DEPLOY_TOKEN` in `wp-config.php`.

To bump the version for a bigger change just edit `Version:` in `style.css`, the run number is added automatically.

## Our custom blocks

We built these as server-side rendered blocks, so they show up correctly in the editor without needing a build step.

| Block | What it does |
|---|---|
| `csd/hero` | the big hero section at the top of the front page. texts, buttons and background image are all editable in the site editor |
| `csd/quicklinks` | the 8 coloured quick access tiles. title and URL are editable per tile in the site editor |
| `csd/events` | the announcements grid, shows the 8 latest posts as tiles |
| `csd/feed` | the "Weitere Ankündigungen" section, a compact list (picture, title, short text, date) starting from post 9 |
| `csd/logo` | logo block, use `variant="csd"` for the CSD logo or `variant="vielbunt"` for the vielbunt logo |
| `csd/footerlinks` | the footer nav links |
| `csd/post-hero` | the purple hero banner on single posts and pages |

## Editing the Schnellzugriff and Hero in WordPress

Open Design > Editor, click on the block you want to edit and look at the right sidebar. The CSD Hero block has panels for "Texte", "Buttons" and "Hintergrundbild". The Schnellzugriff block has one collapsable panel per tile where you can change the title, URL and background image (with a small preview of the chosen image). The canvas updates right away, the changes are stored with the normal **Speichern** button. Empty fields show the grey default text. Colors and icons are fixed in the PHP and woud need a code change.

### Why the tile images dont disappear anymore

Up to 2.1.x the hero texts and tile images lived as block attributes inside the front-page template. WordPress re-serialises templates in PHP on every save and turns the image map `{"0":…,"1":…}` into a list `[…]`. The editor then rejects that list on the next load and every tile image is gone. On top of that, a theme re-upload or a template reset threw the attributes away.

Since 2.2 all of this lives in one option, `csd_frontpage` (see `inc/frontpage.php`):

- registered with a strict REST schema and edited through WordPress' own "site" entity, so it is saved with "Speichern" like everything else,
- independent of the theme folder, the template and theme updates,
- images are stored with their attachment ID (plus the URL as fallback).

On the first page load after the update, the content is copied over once from the customised template and from the old options `csd_hero_settings` / `csd_quicklinks_settings` (those stay as a backup). The same migration also removes a few old HTML comments from the front-page template that made the editor flag the `<main>` group as "invalid content".

## Spendenkampagne (Donorbox goal meter + button)

The campaign band under the hero is driven by the Customizer, not the block editor. Open **Design → Customizer → "Spendenkampagne"**. There you can:

- toggle the whole band on/off,
- set a heading and an optional intro text,
- paste the **goal meter** embed code (Donorbox: campaign → "Ziel-Messer" → Code einbetten),
- paste the **donate button** embed code (Donorbox: "Spenden-Button" → Code einbetten).

It ships **disabled** (no campaign for 2027 yet) but pre-filled with the `csd-darmstadt-2026` campaign codes, replace them for a new campaign. Leave a field empty to hide just that part; untick the toggle (or empty both code fields) once the campaign is over and the band disappears with no layout gap. The embed fields accept the raw Donorbox HTML including its `<script>` — only users allowed to post unfiltered HTML (admins) keep it verbatim, everyone else gets `wp_kses_post`.

**How it's placed:** the band is *not* a block you drop into a template. It's appended right after the front-page hero via a `render_block` filter (`csd_render_campaign_after_hero`). This is deliberate — once `front-page` has been edited in the Site Editor it lives in the database and edits to `templates/front-page.html` are ignored, so anchoring on the hero block is the only reliable placement.

## The CSD graphic and its date

The graphic next to the hero text lives in `assets/flag.svg`. It has no fixed date anymore: the date comes from the hero block, panel **Grafik**, field "Datum in der Grafik" (format `21.08.2027`). The theme sets it in Cera Pro Bold with exactly the size, tracking and centring of the original artwork, so it looks hand-made in the design tool. No font file is loaded, the theme only ships the outlines of the digits 0 to 9 and the dot (`inc/flag-glyphs.php`). Leave the field empty and the graphic has no date. On phones the graphic is hidden on purpose.

If the artwork itself changes one day, export it with the date as outlines (like the 2026 version) and run `python3 tools/flag-glyphs.py <Cera-Pro-Bold.otf> <new.svg>`, the script swaps the date for the placeholder and rebuilds the digit table.

## Lightbox, favicons, one-time steps

- **Lightbox:** `assets/lightbox.js` (no jQuery) opens every link to an image file, with arrows, keys and swipe through the gallery or post. Image blocks without a link use WordPress' own lightbox (enabled in `theme.json`). The old FancyBox plugin is no longer needed.
- **Icons:** favicon and app icons live in `assets/icons` and replace the site icon from the Customizer.
- **One-time steps:** `inc/once.php` holds things that should happen exactly once on the server after an update (e.g. switching off the FancyBox plugin, Autoptimize not touching Google Fonts). Results are listed under Design > Theme-Updates.
- **Spenden buttons:** while the campaign is off, buttons that point to a CSD Donorbox campaign go to the general donation link from the Customizer (Spendenkampagne > "Spenden-Link ohne Kampagne", default vielbunt.org/spenden/).

## Post overviews

"Alle Beiträge" (`/beitraege/`, set as posts page), categories, tags and search use `inc/archive.php`: tiles in sharepic format 4:5 (`contain`, nothing gets cut off), filter buttons for the most used categories, 12 posts per page and a proper pagination. The front page links "Alle Beiträge →" and "Ältere Beiträge →" lead there.

## Categories and featured images

- Every post has "News" plus one topic: Programm, Fotos & Rückblick, Aktionswoche, Motto, Mitmachen & Unterstützen, Andere CSDs, Verein, else CSD Allgemein. New posts get that automatically on publish if no topic was picked (`inc/categorize.php`). Cleanup of all posts in October 2026, backup in the option `csd_kategorien_backup`.
- Missing featured images are taken from the first media library image in the text (`inc/thumbnails.php`).

## Search engines

- `inc/seo.php`: category, tag, author and date archives, search results and `/page/2/` get `noindex` and are left out of the sitemap. Every page and post has a switch **"Nicht in Suchmaschinen anzeigen"** (sidebar, panel "Suchmaschinen") for old forms, past campaigns and so on, without deleting them. Pages have an excerpt field now, that text becomes the description Google shows.
- `inc/schema.php`: structured data on the front page (WebSite, Organization) plus an **Event** for the next CSD. Its date is the "Datum in der Grafik" field, the place is set at the top of the file (Karolinenplatz). After the day the event disappears until a new date is entered.

## Colors

The main brand color is CSD purple `#6546B4`. In the theme it's registered under two slugs, `purple` and `pink`, both pointing to the same value. The `pink` slug exists becuase a lot of the base CSS uses it by name and we didnt want to rename everything.

## Font

PT Sans (400 and 700, both with italics) ships with the theme in `assets/fonts` and is declared as `fontFace` in `theme.json`, so WordPress loads it in the frontend and the editor without asking Google. That keeps visitor IPs away from Google Fonts (GDPR). License: SIL Open Font License, see `assets/fonts/PT-Sans-OFL.txt`.

## Cera Pro note

The logos and the flag graphic all use proper vector paths now, no font files needed. We converted the text in those SVGs to outlines using the Cera Pro font from our local font library, so everything renders identically to before.

## License

The CSD Darmstadt branding and vielbunt logos belong to vielbunt e.V.
