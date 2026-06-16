# CSD Darmstadt WordPress Theme

This is our custom WordPress theme for csd-darmstadt.de. It's built on top of Twenty Twenty-Five and maintained by the vielbunt e.V. web team.

## Setup

1. Make sure Twenty Twenty-Five is installed (it doesn't have to be active, just present).
2. Upload this theme via Design > Themes > Theme hinzufügen > Theme hochladen and activate it.
3. The font (PT Sans) loads automaticaly from Google Fonts, nothing to do there.
4. Go to Design > Editor and assign the correct navigation to the header nav block. It should pick up "Hauptnavigation" on its own but if it dosn't, just select it manually in the sidebar.
5. Under Einstellungen > Lesen, set a static front page so the front-page template kicks in.

## Our custom blocks

We built these as server-side rendered blocks, so they show up correctly in the editor without needing a build step.

| Block | What it does |
|---|---|
| `csd/hero` | the big hero section at the top of the front page. texts, buttons and background image are all editable in the site editor |
| `csd/quicklinks` | the 8 coloured quick access tiles. title and URL are editable per tile in the site editor |
| `csd/events` | the announcements grid, shows the 8 latest posts as tiles |
| `csd/feed` | the "Weitere Ankündigungen" section, shows full post content starting from post 9 |
| `csd/logo` | logo block, use `variant="csd"` for the CSD logo or `variant="vielbunt"` for the vielbunt logo |
| `csd/footerlinks` | the footer nav links |
| `csd/post-hero` | the purple hero banner on single posts and pages |

## Editing the Schnellzugriff and Hero in WordPress

Just open Design > Editor, click on the block you want to edit and look at the right sidebar. The CSD Hero block has panels for "Texte", "Buttons" and "Hintergrundbild". The Schnellzugriff block has one collapsable panel per tile where you can change the title and URL. Colors and icons are fixed in the PHP and woud need a code change.

## Spendenkampagne (Donorbox goal meter + button)

The campaign band under the hero is driven by the Customizer, not the block editor. Open **Design → Customizer → "Spendenkampagne"**. There you can:

- toggle the whole band on/off,
- set a heading and an optional intro text,
- paste the **goal meter** embed code (Donorbox: campaign → "Ziel-Messer" → Code einbetten),
- paste the **donate button** embed code (Donorbox: "Spenden-Button" → Code einbetten).

It ships **enabled** and pre-filled with the `csd-darmstadt-2026` campaign codes, so it works out of the box. Leave a field empty to hide just that part; untick the toggle (or empty both code fields) once the campaign is over and the band disappears with no layout gap. The embed fields accept the raw Donorbox HTML including its `<script>` — only users allowed to post unfiltered HTML (admins) keep it verbatim, everyone else gets `wp_kses_post`.

**How it's placed:** the band is *not* a block you drop into a template. It's appended right after the front-page hero via a `render_block` filter (`csd_render_campaign_after_hero`). This is deliberate — once `front-page` has been edited in the Site Editor it lives in the database and edits to `templates/front-page.html` are ignored, so anchoring on the hero block is the only reliable placement.

## The yearly flag graphic

We keep the annual CSD graphic at `assets/flag-pic-2026.svg`. All the text in that file has been converted to paths already so it renders corectly without needing any fonts installed. When we make a new version for 2027, just replace that file and the hero will pick it up automaticaly.

## Colors

The main brand color is CSD purple `#6546B4`. In the theme it's registered under two slugs, `purple` and `pink`, both pointing to the same value. The `pink` slug exists becuase a lot of the base CSS uses it by name and we didnt want to rename everything.

## Font

We load PT Sans from Google Fonts. Two weights, 400 and 700, both with italic variants. If the site ever needs to work fully offline or without Google, we'd have to self-host the font files.

## Cera Pro note

The logos and the flag graphic all use proper vector paths now, no font files needed. We converted the text in those SVGs to outlines using the Cera Pro font from our local font library, so everything renders identically to before.

## License

The CSD Darmstadt branding and vielbunt logos belong to vielbunt e.V.
