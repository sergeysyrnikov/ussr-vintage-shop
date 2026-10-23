---
name: wordpress-toolbox
description: "The WordPress ToolBox - build, rebuild, audit or fix WordPress + WooCommerce pages against a design screenshot using Elementor with the Solace or Blocksy theme, working server-side through the Novamira connector."
---

# WordPress ToolBox (Elementor + Solace/Blocksy + WooCommerce + Novamira)

One skill, whole pipeline: read a design screenshot, build it as real Elementor
data directly on the WordPress server, connect every product section to live
WooCommerce data, make it hold up on desktop, tablet, AND mobile, then verify
the render in a browser and self-correct. Everything below is verified against
Elementor 4.2.x + Solace + Solace Extra (and Blocksy 2.1.x) on live Hostinger sites.

## Workflow

1. **Inventory the site first** (one execute-php call): active THEME (Solace vs
   Blocksy decides how the header/footer are built - see the Blocksy section),
   media library images, products (names, prices, sale prices, URLs, image ids),
   product categories (real archive URLs and counts), menus, Elementor kit
   colors/fonts, existing page structure. Never invent products, prices, or
   links - map every card and button in the design to something real. If the
   design shows a promo with no matching product/coupon, create the real
   product/coupon first.
2. **Plan sections** from the screenshot: each horizontal band = one top-level
   container. Inside bands, side-by-side items = a row container with column
   containers. Map content to native widgets (table below).
3. **Build server-side** in 2-3 execute-php calls that stash partial section
   arrays in wp options, then one final call that merges, validates, backs up,
   and saves (recipe below, autosave cleanup included). Never pass huge JSON as
   a single tool parameter.
4. **Verify desktop AND mobile**: re-read the saved meta, fetch the rendered
   page, then open the live site in a browser (Claude Chrome extension),
   screenshot section by section against the design, and fix every mismatch.
   Then run the Mobile & Tablet checklist below. Do not report done before
   both passes.
5. **Refine** on request with surgical by-reference edits - never rebuild what
   the user didn't ask to change.

## Element JSON hard rules (violations render broken or not at all)

- Every element: unique `id` (7-char lowercase hex), `elType`
  (`container`/`widget`), `settings` object, `elements` array (empty for
  widgets), `isInner` (false for top-level, true nested). Widgets also need
  `widgetType`.
- **Unique IDs are non-negotiable.** Generate with
  `substr(md5(uniqid('', true)), 0, 7)` - NEVER with string patterns like
  `'prefix'.$i.'0'` (at i=0 that recreates the parent id; the child's width
  CSS then hits the parent and whole sections collapse to ~300px). Before
  every save, walk the tree and abort on any duplicate.
- **Activation keys**: style groups do nothing without their switch.
  `typography_typography: "custom"` alongside any `typography_*` value;
  `background_background: "classic"` (or `"gradient"`) alongside color/image;
  `box_shadow_box_shadow_type: "yes"` for shadows.
- **Value shapes**: dimensions (padding/margin/radius) =
  `{"unit":"px","top":"20","right":"20","bottom":"20","left":"20","isLinked":true}`
  (sides as STRINGS). Sliders (font size, width, gap) =
  `{"unit":"px","size":20}` (size as NUMBER). Flex gap =
  `{"column":"24","row":"24","isLinked":true,"unit":"px","size":24}`.
- **Repeater items** (icon lists, product element order, tabs) each need a
  unique 7-char `_id`.
- **Escapes**: never write `\uXXXX` in PHP - it prints literally. Hex byte
  escapes (`"\xC2\xB7"` middot, `"\xC2\xA9"` (c), `"\xE2\x80\x9C"` curly
  quote) work ONLY in double-quoted strings; in single quotes they also print
  literally. Concatenate: `'text '."\xC2\xB7".' more'`.
- No `custom_css` (Pro-only, silently ignored) and no html widgets unless the
  user explicitly allows custom HTML.

## Layout patterns (verified)

- **Section**: top-level container, `content_width:'boxed'` - element is
  centered at the kit content width and backgrounds fill that boxed area.
  For edge-to-edge bands use `content_width:'full'` + padding 0.
- **Card grid**: row container (`content_width:'full'`, `flex_direction:'row'`,
  `flex_wrap:'wrap'`, explicit flex_gap) - card containers with
  `content_width:'full'` + `width {unit:'%',size:23}` (+ `width_tablet` 47,
  `width_mobile` 100). 4 x 23% + 24px gaps fits a 1280 row.
- **DEEP-NESTING WIDTH TRAP**: percentage `width` on inner containers nested
  3+ levels deep sets the `--width` var but the element IGNORES it (collapses
  to min-content; text wraps per-syllable). Section-child card containers are
  fine; go deeper and it breaks. Fix: avoid deep inner columns - use single
  widgets instead (icon-box replaces icon+heading+text stacks; image widgets
  take px `width` reliably at any depth).
- **Full-bleed image halves** (hero photo, banner art): image widgets cannot
  bleed to section edges. Use an EMPTY child container with
  `background_image` + `background_size:'cover'` + `min_height` per
  breakpoint. Text half gets the gradient/color background and its own padding.
- **Never give a container `width {unit:'auto'}`** - it absorbs hundreds of
  px and starves siblings (a header nav wrapped to two lines from this).
  Pin trailing groups (cart + button) to a px width instead.
- Widget custom width: `_element_width:'initial'` +
  `_element_custom_width {unit:'%',size:N}` - but theme widgets (e.g. the
  Solace nav) may override it; fix sizing on the CONTAINERS around them.

## Mobile & tablet optimization (MANDATORY, not optional polish)

A build is not done until it passes this checklist. Apply DURING the build,
verify after:

1. Every multi-column row: `flex_direction_mobile:'column'` (and usually
   `flex_direction_tablet:'column'` for 2-col hero rows) PLUS
   `width_mobile {unit:'%',size:100}` on each column - width alone does not
   stack a row. 4-col grids: `width_tablet` 47 for 2-up.
2. Every heading >= 28px gets `typography_font_size_mobile` at ~70-75% of
   desktop (floor 24px); >= 34px also gets a `_tablet` size at ~85%.
3. Product grids that must stack: the Solace Shop widget's `shop_columns` is
   NOT responsive (no tablet/mobile variants exist) - 4 columns stay 4 tiny
   columns on phones. Use the WooCommerce `[products]` shortcode instead
   (`[products limit="4" columns="4" visibility="featured"]`,
   `on_sale="true"`, category filters, etc.): its `products columns-4` markup
   gets automatic 2-column mobile stacking from WooCommerce's smallscreen
   stylesheet. Use the Solace widget only when fixed columns are acceptable
   or you need its per-element styling controls.
4. Overlapping/absolute-feeling elements (badges with negative margins):
   provide `width_mobile`, `min_height_mobile`, and `margin_mobile` overrides.
5. Generous desktop paddings (64px+, %-unit paddings) need `padding_mobile`
   overrides (~24-40px).
6. Buttons that crowd a mobile header: `hide_mobile:'hidden-mobile'`.
7. Verify: `resize_window` to 390x844 in a NEW browser window (create a fresh
   tab group first - a fullscreen macOS window refuses to resize; if
   `window.innerWidth` doesn't change, say so and ask the user for a phone
   screenshot instead of guessing). See "More browser-audit traps" for the
   new-tab workaround that actually resized.

## Widget cheat sheet (free tier)

heading (inline `<span style="color:...">` in the title is fine for highlight
words), text-editor, button (pill = border_radius 100 all corners; icon via
`selected_icon` + `icon_align`), image (`image_border_radius`; `link_to:
'custom'`; px `width` works at any depth), icon-box (`position` left/top,
`view:'stacked'` + `shape:'circle'` for chip icons), icon-list
(`view:'inline'` for check rows), star-rating (static stars), image-box,
toggle (FAQ; `title_background`, `selected_icon`/`selected_active_icon`
fa-plus/fa-minus, `icon_align:'right'`), social-icons, divider, counter,
shortcode, wpforms (when WPForms is active - edit field placeholders in the
wpforms post's JSON `post_content`). There is NO countdown widget and NO form
widget in free Elementor - say so instead of faking them.

## Solace WooCommerce Shop widget - `solace-extra-woocommerce-shop`

Verified controls (use only when fixed columns are OK - see Mobile #3):

- `query_source`: `latest` | `sale` | `featured` (nothing else). 
  `query_orderby`: date/title/menu_order/rand. `posts_per_page` (number),
  `shop_columns` ('1'-'5' strings, NOT responsive), `show_pagination`
  ('' hides).
- `product_elements_order` is a repeater; each item needs `_id` + `element`.
  Valid `element` values ONLY: `Title`, `Description`, `Category`, `Tags`,
  `Price`, `Add To Cart`. **No Rating element.** Unknown values render
  nothing, silently. Trim to Title/Price/Add To Cart to avoid category-chip
  soup.
- Styling: `product_name_color`, `product_name_typography_*`, `price_color`,
  `sol_product_border_*`.
- Differentiate sections with real Woo data: `$p->set_featured(true)` for a
  bestsellers grid vs `on_sale` for a flash-sale grid.
- Star ratings: seed `_wc_average_rating` + `_wc_review_count` post meta
  (shows on product pages); use Elementor `star-rating` for static display.

## Server-side build & save recipe (Novamira execute-php)

```php
function nid(){ return substr(md5(uniqid('', true)), 0, 7); }
function &find(&$els,$id){ $n=null; foreach($els as &$e){
  if(($e['id']??'')===$id) return $e;
  if(!empty($e['elements'])){ $r=&find($e['elements'],$id); if($r!==null) return $r; }
} return $n; }
// find MUST be declared `function &find` and called `$e = &find(...)` -
// without both ampersands every edit mutates a copy and the save is a no-op
// while logging success. Always re-read the meta afterward and strpos for a
// changed string.
```

Save sequence (order matters):

```php
update_option('backup_'.date('Ymd_His'), get_post_meta($id,'_elementor_data',true), false);
// duplicate-id walk here; abort if any
update_post_meta($id,'_elementor_data', wp_slash(wp_json_encode($els))); // wp_slash mandatory
// Autosave cleanup - mandatory, see "Autosave shadowing" below. A leftover
// autosave ALWAYS wins in the editor, no matter how old it is.
foreach (get_posts(['post_parent'=>$id,'post_type'=>'revision','numberposts'=>-1,'fields'=>'ids']) as $rev) {
  if (false !== strpos((string) get_post_field('post_name', $rev), 'autosave')) { wp_delete_post($rev, true); }
}
\Elementor\Plugin::instance()->files_manager->clear_cache();
do_action('litespeed_purge_all');
```

### Autosave shadowing (editor shows the OLD text, frontend the new one)

`Documents_Manager::ajax_get_document_config()` resolves the document through
`get_doc_or_auto_save()`, which returns the autosave WHENEVER one exists - there
is no newer-than-the-post check. A direct
`update_post_meta('_elementor_data', ...)` never touches `post_modified`, so an
autosave left by an editor tab that was open during the write keeps winning: the
editor loads the OLD content, and pressing Update there writes that old content
back over your work. Always run the autosave cleanup in the save sequence above,
and tell the user to close or reload the editor tab before touching the page.
Verify before reporting done:
`Plugin::instance()->documents->get_doc_or_auto_save($id)->get_post()->ID` must
equal `$id` - any other ID means an autosave is still shadowing the document.

Large pages: build sections into `update_option('build_a', $arr, false)`
across 2-3 calls, merge in the final call, delete the temp options.

Page settings: merge into existing `_elementor_page_settings`
(`background_background`, `background_color`, `hide_title`) - don't replace
the array blind.

## Solace Site Builder header/footer (free)

Custom headers/footers are Elementor documents in the `solace-sitebuilder`
post type; when active they completely replace the Customizer header/footer.

```php
$pid = wp_insert_post(['post_title'=>'Header','post_type'=>'solace-sitebuilder','post_status'=>'publish']);
update_post_meta($pid,'_solace_header_status','1');          // '1' string = active
update_post_meta($pid,'_solace_template','header');           // 'header'|'footer'
update_post_meta($pid,'_solace_header_conditions', [['type'=>'include','value'=>'basic-global']]);
update_post_meta($pid,'_elementor_data', wp_slash(wp_json_encode($els)));
update_post_meta($pid,'_elementor_edit_mode','builder');
update_post_meta($pid,'_elementor_template_type','wp-post');
update_post_meta($pid,'_elementor_version', ELEMENTOR_VERSION);
```

Traps: one active part per type (zero others' status first); a DRAFT with
status '1' hijacks pages and blanks them - always publish; edit parts via
Solace -> Site Builder, not guessed URLs.

Native Solace widgets for parts: `solace-extra-site-logo`
(`solace_extra_site_logo` media + `solace_extra_width`),
`solace-extra-nav-menu` (`menu` = menu SLUG; `layout` horizontal/vertical -
vertical works for footer link columns; `pointer` underline; item/dropdown
color controls; built-in mobile hamburger), `solace_extra_minicart`
(`cart_icon_color`). Mega menus: nested WP menu items give a styled dropdown;
a true multi-column mega panel needs Elementor Pro or a mega-menu plugin -
tell the user, don't fake it.

Logo on the wrong background: generate a recolored copy server-side with GD
(preserve alpha, repaint opaque pixels), upload as a new attachment.

## Blocksy theme sites (header/footer live in the Customizer, not Elementor)

When the active theme is Blocksy (check `wp_get_theme()`), the header and
footer are NOT Elementor documents. Do not build a Solace Site Builder part -
edit Blocksy's builder data directly:

- `get_theme_mod('header_placements')` - `sections[0].items[]` is a LIST of
  `{id, values}`; `sections[0].desktop` / `.mobile` are the row -> placement
  -> item-id layouts. `get_theme_mod('footer_placements')` -
  `sections[0].rows[].columns` (arrays of item ids per column) and
  `sections[0].items` keyed BY id. Back both up to an option first.
- **CSS TRAP**: `set_theme_mod()` saves the values but the page keeps the OLD
  look. Blocksy writes its dynamic CSS to
  `uploads/blocksy/css/global.css` and only regenerates it on a Customizer
  save. After EVERY theme_mod write run
  `do_action('blocksy:dynamic-css:refresh-caches');` then
  `do_action('litespeed_purge_all');`, and prove it by reading that file
  and strpos'ing your new value. (Layout changes show immediately, which
  makes the stale CSS look like a wrong key - it isn't.)
- **KEY-NAME TRAP**: option keys are not guessable
  (`mobileMenuColor` not `mobileMenuFontColor`; `menuFontColor` /
  `headerMenuFont` on menus; `headerRowBackground.backgroundColor.default
  .color` + `headerRowHeight` on rows; `rowFontColor`, `4_columns_layout`,
  `rowTopBottomSpacing` on footer rows; `custom_logo`/`logoMaxHeight` as
  `{desktop,tablet,mobile,__changed:[]}`). Grep the item's
  `inc/panel-builder/header/<item>/options.php` (or
  `blocksy-companion/framework/features/header/items/<item>/`) for
  `'key' => [` before writing. Color values are
  `{default:{color}, hover:{color}, active:{color}}`; fonts are
  `{family, variation:'n5', size:'14px', line-height, letter-spacing,
  text-transform, text-decoration}`.
- Free header items: logo, menu, menu-secondary (second WP menu - use it
  for a top-bar "Track Order | Help" link pair), text (ONE per header -
  good for a promo top bar; remove it from the offcanvas if you use it
  there), search, cart (`has_cart_badge:'yes'`), account (companion),
  socials, button, trigger, mobile-menu. Footer: copyright, socials, menu,
  widget-area-1..6. Blocksy free search is an ICON that opens a modal -
  `search_label_visibility` + `search_label` gives icon + text, but there
  is no inline input box; say so instead of faking one.
- Offcanvas drawer is near-black by default: the theme's green palette
  `active`/`hover` link color is unreadable there. Set `mobileMenuColor`
  and `mobileMenuDropdownColor` default/hover/active to light values and
  verify by opening the drawer (use `find` + `ref` on the "Menu" button).
- Footer columns = block widgets: write `widget_block[N] = ['content' =>
  <block markup>]` and set `sidebars_widgets['ct-footer-sidebar-N']` =
  `['block-N']` (move the old ids to `wp_inactive_widgets`, don't delete).
  Column headings: an `h4` inherits the big serif heading font - use a
  styled paragraph (`font-size:14px;font-weight:700`) to match a small
  bold sans heading. A `socials` item can sit in the same column as a
  widget area (`[['widget-area-1','socials'], ...]`).
- Socials URLs come from theme mods; when empty every icon is `href="#"`
  and fails the dead-link audit. Ask the user for the URLs - never invent.
- WooCommerce "Store coming soon" in the admin bar = coming-soon mode is
  on; logged-out visitors see a placeholder. Flag it, don't change it.
- Menu swaps: create a NEW menu (`wp_create_nav_menu`) and point the item's
  `menu` value + `nav_menu_locations` at it; don't gut the old one.

## Script/wordmark logo generation (verified)

The cloud container can't reach Google Fonts, but the WP server can.
Render server-side with GD: `wp_remote_get('https://fonts.googleapis.com/
css?family=Allura', ['user-agent' => 'curl/7.0'])` - the OLD user-agent
returns a `.ttf` url (a modern UA returns woff2, which GD can't load) -
save the TTF under uploads, `imagettfbbox` + `imagettftext` on a
transparent truecolor canvas (`imagesavealpha`), `imagepng`,
`wp_insert_attachment` + `wp_generate_attachment_metadata`. Make a dark and
a white copy in one go (transparent-header variant). Preview it by
navigating the browser to the attachment URL.

## Verification checklist (do not skip, in order)

1. Re-read saved meta; strpos a changed string ("persisted" proof).
2. `wp_remote_get(home_url('/?v='.time()))`, strip `<style>`/`<script>`,
   check key strings AND assert zero literal `\u00` / `\xC2` sequences.
   (Cache-buster note: `?m=1` 404s WordPress - `m` is the month query var.)
3. Browser pass with the Chrome extension: screenshot every section against
   the design. Measure suspects with `getBoundingClientRect().width` -
   section roots should be viewport/boxed width; a root with a % max-width it
   never set = id collision. Pages with smooth-scroll ignore
   `window.scrollTo` mid-animation and reset scroll on load: wait after
   navigate, then use `document.documentElement.scrollTop` or wheel scrolls.
4. LiteSpeed lazy-load images may not paint after programmatic scroll jumps -
   fire one real wheel-scroll tick before screenshotting, or you will chase
   phantom missing-image bugs (images with naturalWidth > 0 and opacity 1
   are fine; the screenshot raced the fade-in).
5. Mobile pass per the Mobile & Tablet section.
6. Fix every mismatch found, purge caches, re-verify the fixed spots.

## Judgment calls

- Editability over pixel-fidelity: real widgets a human can keep editing in
  Elementor beat frozen markup. ~90% faithful and editable wins.
- Real data over decoration: a design's fake product card becomes the user's
  real product, price, image, and link - or gets replaced.
- When the design shows something impossible natively (live countdown, inline
  email capture, mega panel, card star-ratings, header search field), say so
  plainly and offer the plugin/Pro path. Never ship a fake that looks
  functional.

## Dark-theme product grids: dual-widget responsive pattern

The `[products]` shortcode inherits theme text colors - on a DARK page its
titles/prices render near-invisible. For dark designs use the Solace Shop
widget (it has `product_name_color` / `price_color`) and solve its
non-responsive columns with TWO widgets: a desktop/tablet copy
(`shop_columns:'4'`, `hide_mobile:'hidden-mobile'`) plus a mobile copy
(`shop_columns:'2'`, `hide_desktop` + `hide_tablet`). Fully native, stacks
correctly, keeps styled text. Known gap: the widget's `price_color` only
styles SALE prices; regular-only prices inherit the kit body color and can
look muted on dark - flag it (fix needs one CSS line with user permission,
or make the product a sale item).

## Programmatic browser audit (run after every build)

Screenshots miss things. Run these via the extension's JS console and fix
what they surface:

1. **Horizontal overflow**: `document.documentElement.scrollWidth >
   window.innerWidth` must be false. If true, find culprits: visible
   elements with `getBoundingClientRect().right > innerWidth`. KNOWN CAUSE:
   side margins on a BOXED top-level container (100% width + margins =
   overflow). Never put horizontal margins on section roots - wrap card
   styling in an inner container and give the root side PADDING instead.
2. **Dead links**: scan `a[href="#"]` and href-less `<a>`, excluding core
   false positives (#wpadminbar, .woocommerce-store-notice Dismiss, Elementor
   toggle titles, minicart badge). Point footer/menu dummies at real
   destinations (/my-account/, /my-account/orders/, /contact/, /shop/,
   category archives); report any that need pages that don't exist yet.
3. **Contrast**: for visible text compare luminance of computed color vs
   effective background (walk ancestors for first non-transparent bg);
   flag pairs under ~3:1. This caught kit-inherited dark prices on a dark
   page and a Blocksy starter footer's #8e98a0 grey text.
4. **Broken images**: `[...document.images].filter(i=>i.complete &&
   i.naturalWidth===0)`.
5. **Console**: read error/warn messages via the extension.
6. **Dual-widget grids**: exactly one shop widget visible per section per
   breakpoint, with the hide classes present.
7. **Theme overflow menus**: Blocksy shows a "More" dropdown when header
   items don't fit - `header .menu-item-more` count must be 0 at desktop.

## Flex wrap doctrine - when to wrap, when not (verified)

- Use `flex_wrap:'wrap'` ONLY on rows meant to reflow: card grids, icon-box
  strips, chip/badge groups, button pairs that may stack.
- Keep NOWRAP (the default) on single-line UI rows: header rows, heading +
  action-button rows, two-column splits. Stacking on small screens comes
  from `flex_direction_mobile/_tablet:'column'` + `width_mobile:100%` -
  NEVER from accidental wrapping.
- Wrap-row width math must fit the row's CONTENT box: sum(child widths) +
  gaps + the row's own padding. **Elementor containers ship with DEFAULT
  padding (~10-24px per side from the kit)** - a layout row you gave no
  explicit padding still loses ~48px of width, and fixed-width children
  then wrap one lonely item onto a second line (a 4-box countdown became
  3+1 this way). Give pure layout rows explicit `padding: 0`.
- 4-up % grids: use 23% per card (never 25%) + an explicit gap so rounding
  can't overflow into a wrap.
- Detecting TRUE wraps in the browser audit: a child whose LEFT edge
  returns to the row start while its TOP sits below the first child has
  wrapped. Do NOT use top-difference alone - vertically-centered rows with
  different child heights have different tops without wrapping (false
  positives).

## More browser-audit traps

- `resize_window` can report success while `window.innerWidth` doesn't
  change (the extension's own tab in a fullscreen window). Fix that worked:
  `tabs_create_mcp` a NEW tab, navigate it, then resize THAT tab's window;
  re-check innerWidth (390 came out as ~406 with the side panel - still
  inside the mobile breakpoint). Resize back to 1440x900 and close the tab
  when done.
- Scaled screenshots (`scale:0.5`) return coordinates in the FULL frame -
  clicking with the scaled pixel positions hits the wrong thing (a mis-
  click opened wp-admin "New post"). Use `find` + `ref` for clicks on
  mobile.
- `scrollIntoView` / `window.scrollTo` are ignored on smooth-scroll pages;
  use repeated wheel scrolls (10 ticks each) to reach the footer.
- Logged-in at a narrow desktop viewport the WP admin bar wraps to two
  lines and overlaps the top bar ("Howdy ..." over the header). Measure
  `#wpadminbar` height - it's an admin-only artifact, not a site bug.
- Boxed Elementor rows wrap their children in `.e-con-inner`; measure the
  inner's width, not the row's, when checking grid math.
- Icon-box strips: a per-box `_padding.left` (used as a divider gutter)
  comes out of the title's content box. Compare `canvas.measureText` of the
  title at its font vs `.elementor-icon-box-content` width; if text >
  content, cut the row's side padding to 0 and the box gutter to 16 before
  touching font sizes.
- Contrast audit: skip `.star-rating` / `.screen-reader-text` nodes and
  elements under a `background-image` (walk ancestors and bail on an image
  before checking colors) or you'll chase phantom fails.
- Wrap detection on mobile lists intentional stacks (wrap rows at 100%
  width); only flag wraps at desktop width.