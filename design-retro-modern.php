<?php
/**
 * Retro-modern design upgrade for «Ретро СССР» store.
 * Applies: Russian language, clean menu, hero homepage, retro-modern CSS,
 * styled product images. Works on WordPress Playground and local dev sites.
 */
$candidates = array( '/wordpress/wp-load.php', 'C:/Users/Serhio/Studio/ussr-vintage-shop/wp-load.php' );
foreach ( $candidates as $c ) {
	if ( file_exists( $c ) ) {
		require_once $c;
		break;
	}
}
if ( ! defined( 'ABSPATH' ) ) {
	echo "wp-load.php not found\n";
	exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/translation-install.php';
$log = array();

// --- 1. Russian language pack (core + WooCommerce) ---
update_option( 'WPLANG', 'ru_RU' );
function retro_import_lang( $type, $slug = '' ) {
	$args = array( 'locale' => 'ru_RU' );
	if ( $slug ) {
		$args['slug']    = $slug;
		$args['version'] = defined( 'WC_VERSION' ) ? WC_VERSION : 'latest';
	} else {
		$args['version'] = $GLOBALS['wp_version'];
	}
	$api = translations_api( $type, $args );
	$package = '';
	if ( ! is_wp_error( $api ) ) {
		if ( isset( $api['package'] ) && $api['package'] ) {
			$package = $api['package'];
		} elseif ( isset( $api['translations'] ) ) {
			foreach ( $api['translations'] as $t ) {
				if ( isset( $t['language'] ) && 'ru_RU' === $t['language'] && ! empty( $t['package'] ) ) {
					$package = $t['package'];
				}
			}
		}
	}
	if ( ! $package ) {
		return 'skip';
	}
	$tmp = download_url( $package );
	if ( is_wp_error( $tmp ) ) {
		return 'download fail';
	}
	$dest = $slug ? WP_LANG_DIR . '/plugins' : WP_LANG_DIR;
	if ( ! is_dir( $dest ) ) {
		wp_mkdir_p( $dest );
	}
	$out  = 'ok';
	$zip  = new ZipArchive();
	if ( true === $zip->open( $tmp ) ) {
		$zip->extractTo( $dest );
		$zip->close();
	} else {
		$out = 'unzip fail';
	}
	@unlink( $tmp );
	return $out;
}
$log[] = 'lang core: ' . retro_import_lang( 'core' );
$log[] = 'lang woo: ' . retro_import_lang( 'plugins', 'woocommerce' );

// --- 2. Theme: Astra (keep if active, otherwise fall back to Twenty Twenty-Five) ---
$active = wp_get_theme()->get( 'Template' );
if ( 'astra' !== $active ) {
	$theme_dir = get_theme_root() . '/astra';
	switch_theme( is_dir( $theme_dir ) ? 'astra' : 'twentytwentyfive' );
}

// --- 3. Pages: Russian titles, cleanup ---
function retro_page_title( $slug, $title ) {
	$p = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $p ) {
		wp_update_post( array( 'ID' => $p->ID, 'post_title' => $title ) );
		return $p->ID;
	}
	return 0;
}
retro_page_title( 'shop', 'Каталог' );
retro_page_title( 'cart', 'Корзина' );
retro_page_title( 'checkout', 'Оформление заказа' );
retro_page_title( 'my-account', 'Личный кабинет' );
// Remove empty custom block templates that shadow theme templates (e.g. front-page).
global $wpdb;
$empty_tpls = $wpdb->get_results( "SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type IN ('wp_template','wp_navigation') AND post_status != 'trash'" );
foreach ( $empty_tpls as $t ) {
	$len = strlen( (string) get_post_field( 'post_content', $t->ID ) );
	if ( $len < 40 ) {
		wp_delete_post( $t->ID, true );
		$log[] = 'removed empty tpl: ' . $t->post_name;
	}
}
$sample = get_page_by_path( 'sample-page', OBJECT, 'page' );
if ( $sample ) {
	wp_delete_post( $sample->ID, true );
}
$home = get_page_by_path( 'glavnaya', OBJECT, 'page' );
if ( $home ) {
	$home_id = $home->ID;
} else {
	$home_id = wp_insert_post( array(
		'post_type'   => 'page',
		'post_title'  => 'Главная',
		'post_name'   => 'glavnaya',
		'post_status' => 'publish',
	) );
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );
// --- 4. Homepage content (block markup) ---
$hero_html = '
<!-- wp:cover {"url":"","dimRatio":0,"overlayColor":"burgundy","minHeight":420,"contentPosition":"center center","align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:420px"><span aria-hidden="true" class="wp-block-cover__background has-burgundy-background-color has-background-dim-0"></span><div class="wp-block-cover__inner-container">
<!-- wp:group {"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group">
<!-- wp:heading {"textAlign":"center","level":1,"textColor":"cream","style":{"typography":{"fontSize":"52px","lineHeight":"1.1"}}} -->
<h1 class="wp-block-heading has-text-align-center has-cream-color has-text-color" style="font-size:52px;line-height:1.1">Вещи с историей из СССР</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"cream","style":{"typography":{"fontSize":"20px"}}} -->
<p class="has-text-align-center has-cream-color has-text-color" style="font-size:20px">Винтаж и антиквариат советской эпохи — радиотехника, часы, винил, мебель. Каждая вещь единственная в своём роде.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"mustard","textColor":"burgundy","style":{"border":{"radius":"8px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-mustard-background-color has-burgundy-color has-text-color has-background" href="/shop/" style="border-radius:8px">Смотреть каталог</a></div>
<!-- /wp:button -->
<!-- wp:button {"backgroundColor":"transparent","textColor":"cream","style":{"border":{"radius":"8px","color":"#F7F1E3","width":"2px"}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-cream-color has-text-color has-transparent-background has-border-color" href="/dostavka-i-oplata/" style="border-width:2px;border-radius:8px">Доставка и оплата</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->';

$advantages_html = '
<!-- wp:columns {"align":"wide","style":{"spacing":{"padding":{"top":"48px","bottom":"24px"}}}} -->
<div class="wp-block-columns alignwide" style="padding-top:48px;padding-bottom:24px">
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3,"textColor":"burgundy"} -->
<h3 class="wp-block-heading has-text-align-center has-burgundy-color has-text-color">Уникальные вещи</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Всё, что в каталоге — оригиналы советских лет. Повторов не будет.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3,"textColor":"burgundy"} -->
<h3 class="wp-block-heading has-text-align-center has-burgundy-color has-text-color">Честное описание</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Указываем состояние, следы времени и все нюансы — без сюрпризов.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"textAlign":"center","level":3,"textColor":"burgundy"} -->
<h3 class="wp-block-heading has-text-align-center has-burgundy-color has-text-color">Доставка по России</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Курьер, СДЭК, Почта или самовывоз. Бесплатно при заказе от 5 000 ₽.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->';

$shop_html = '
<!-- wp:heading {"textAlign":"center","level":2,"textColor":"burgundy","style":{"spacing":{"margin":{"top":"24px"}}}} -->
<h2 class="wp-block-heading has-text-align-center has-burgundy-color has-text-color" style="margin-top:24px">Популярные товары</h2>
<!-- /wp:heading -->
<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"perPage":8,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true},"tagName":"div"} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"isDescendentOfQueryLoop":true} /-->
<!-- wp:post-title {"isLink":true,"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true} /-->
<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template --></div>
<!-- /wp:woocommerce/product-collection -->';

$page_content = $hero_html . $advantages_html . $shop_html;
wp_update_post( array( 'ID' => $home_id, 'post_content' => $page_content ) );
$log[] = 'homepage blocks written';

// --- 5. Color palette + styles via Custom CSS (theme-independent) ---
$css = '
:root {
  --retro-burgundy: #7B2D26;
  --retro-mustard: #E8B84B;
  --retro-cream: #F7F1E3;
  --retro-brown: #4A3728;
}
body { background: var(--retro-cream); }
.has-burgundy-color { color: #7B2D26 !important; }
.has-burgundy-background-color { background-color: #7B2D26 !important; }
.has-cream-color { color: #F7F1E3 !important; }
.has-mustard-background-color { background-color: #E8B84B !important; }
.wp-block-cover .wp-block-cover__background { opacity: 1 !important; }
h1, h2, h3, h4 { font-family: Georgia, "Times New Roman", serif; }
.wp-site-blocks { max-width: 1200px; margin: 0 auto; }
a { color: var(--retro-burgundy); }
a:hover { color: var(--retro-brown); }
.wp-element-button, .wp-block-button__link {
  background: var(--retro-burgundy) !important; color: var(--retro-cream) !important;
  border-radius: 8px !important; padding: 12px 28px !important;
  font-weight: 600; transition: all .2s ease;
}
.wp-element-button:hover, .wp-block-button__link:hover { background: var(--retro-brown) !important; transform: translateY(-2px); }
.wp-block-woocommerce-product-collection .wc-block-product-template { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 24px; }
.wc-block-product { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(74,55,40,.10); transition: transform .25s ease, box-shadow .25s ease; }
.wc-block-product:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(123,45,38,.18); }
.wc-block-components-product-price { color: var(--retro-burgundy); font-size: 18px; font-weight: 700; }
.woocommerce-loop-product__title { font-family: Georgia, serif; color: var(--retro-brown); }
header.wp-block-template-part { background: var(--retro-burgundy); }
header.wp-block-template-part .wp-block-site-title a { color: var(--retro-cream) !important; font-family: Georgia, serif; font-size: 26px; letter-spacing: 1px; }
header.wp-block-template-part nav a { color: var(--retro-cream) !important; font-size: 15px; }
footer.wp-block-template-part { background: var(--retro-brown); color: var(--retro-cream); margin-top: 48px; }
footer.wp-block-template-part a { color: var(--retro-mustard) !important; }
/* Mobile: hamburger nav, single column grids, bigger tap targets */
@media (max-width: 781px) {
  .wp-block-woocommerce-product-collection .wc-block-product-template { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  h1.wp-block-heading { font-size: 32px !important; }
  .wp-element-button, .wp-block-button__link { width: 100%; text-align: center; }
}
@media (max-width: 480px) {
  .wp-block-woocommerce-product-collection .wc-block-product-template { grid-template-columns: 1fr; }
}
.woocommerce-message, .woocommerce-info { border-color: var(--retro-mustard); background: #fff8e7; }
';
$custom_css_id = wp_get_custom_css_post();
if ( $custom_css_id ) {
	wp_update_custom_css_post( $css );
} else {
	wp_update_custom_css_post( $css );
}
$log[] = 'custom css applied';

// --- 6. Clean menu: Главная, Каталог, Доставка и оплата, О магазине, Контакты ---
$locations = get_theme_mod( 'nav_menu_locations' );
$menu_name = 'Основное меню';
$menu      = wp_get_nav_menu_object( $menu_name );
if ( ! $menu ) {
	$menu_id = wp_create_nav_menu( $menu_name );
} else {
	$menu_id = $menu->term_id;
}
$items = array(
	array( 'title' => 'Главная', 'url' => '/' ),
	array( 'title' => 'Каталог', 'url' => '/shop/' ),
	array( 'title' => 'Доставка и оплата', 'url' => '/dostavka-i-oplata/' ),
	array( 'title' => 'О магазине', 'url' => '/o-magazine/' ),
	array( 'title' => 'Контакты', 'url' => '/kontakty/' ),
);
foreach ( wp_get_nav_menu_items( $menu_id ) as $old ) {
	wp_delete_post( $old->ID, true );
}
foreach ( $items as $i => $item ) {
	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'  => $item['title'],
		'menu-item-url'    => $item['url'],
		'menu-item-status' => 'publish',
		'menu-item-type'   => 'custom',
		'menu-item-position' => $i + 1,
	) );
}
$locations['primary'] = $menu_id;
$locations['footer']  = $menu_id;
set_theme_mod( 'nav_menu_locations', $locations );
$log[] = 'menu rebuilt: ' . count( $items ) . ' items';

// Block themes use wp_navigation posts — create the real menu for the header.
$nav_html = '<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"left"}} -->';
foreach ( $items as $item ) {
	$nav_html .= '<!-- wp:navigation-link {"label":"' . $item['title'] . '","url":"' . $item['url'] . '"} /-->';
}
$nav_html .= '<!-- /wp:navigation -->';
foreach ( get_posts( array( 'post_type' => 'wp_navigation', 'numberposts' => -1, 'post_status' => 'any' ) ) as $old_nav ) {
	wp_delete_post( $old_nav->ID, true );
}
$nav_id = wp_insert_post( array(
	'post_type'    => 'wp_navigation',
	'post_title'   => 'Основное меню',
	'post_name'    => 'primary-navigation',
	'post_content' => $nav_html,
	'post_status'  => 'publish',
) );
$log[] = 'block navigation created: ' . $nav_id;

// --- 7. Retro-styled placeholder images (redrawn) ---
if ( extension_loaded( 'gd' ) ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$uploads  = wp_upload_dir();
	$labels   = array(
		'ussr-radiopriemniki-i-magnitofony' => 'VEF-10', 'ussr-posuda-i-hrustal' => 'CRYSTAL',
		'ussr-chasy' => 'VOSTOK', 'ussr-foto-i-kinotehnika' => 'ZENIT-E',
		'ussr-vinilovye-plastinki' => 'VINYL', 'ussr-knigi-zhurnaly-otkrytki' => 'BOOK',
		'ussr-monety-banknoty-marki' => 'COINS', 'ussr-znachki-i-suveniry-sssr' => 'BADGES',
		'ussr-vintazhnaya-odezhda' => 'COAT', 'ussr-mebel-i-interer' => 'FURNITURE',
		'ussr-muzykalnye-instrumenty' => 'GUITAR', 'ussr-igrushki-i-detskie-veshchi' => 'TOYS',
	);
	$q = new WP_Query( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
	foreach ( $q->posts as $post ) {
		$img_id = get_post_thumbnail_id( $post->ID );
		$label  = isset( $labels[ $post->post_name ] ) ? $labels[ $post->post_name ] : strtoupper( substr( $post->post_name, 0, 8 ) );
		$im  = imagecreatetruecolor( 800, 800 );
		$cream = imagecolorallocate( $im, 247, 241, 227 );
		$burgundy = imagecolorallocate( $im, 123, 45, 38 );
		$mustard  = imagecolorallocate( $im, 232, 184, 75 );
		$brown    = imagecolorallocate( $im, 74, 55, 40 );
		imagefilledrectangle( $im, 0, 0, 800, 800, $cream );
		// Decorative frame and shapes.
		imagerectangle( $im, 40, 40, 760, 760, $burgundy );
		imagerectangle( $im, 56, 56, 744, 744, $burgundy );
		imagefilledellipse( $im, 400, 330, 300, 300, $mustard );
		imagefilledellipse( $im, 400, 330, 220, 220, $burgundy );
		imagefilledrectangle( $im, 120, 560, 680, 570, $brown );
		imagestring( $im, 5, 330, 315, $label, $cream );
		imagestring( $im, 4, 300, 600, 'RETRO USSR VINTAGE', $brown );
		imagestring( $im, 3, 315, 640, 'placeholder - replace with real photo', $brown );
		$imgpath = $uploads['path'] . '/' . $post->post_name . '-v2.png';
		imagepng( $im, $imgpath );
		imagedestroy( $im );
		$filetype  = wp_check_filetype( basename( $imgpath ), null );
		$attach_id = wp_insert_attachment( array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $post->post_title,
			'post_status'    => 'inherit',
			'guid'           => $uploads['url'] . '/' . basename( $imgpath ),
		), $imgpath, $post->ID );
		if ( $attach_id && ! is_wp_error( $attach_id ) ) {
			wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $imgpath ) );
			set_post_thumbnail( $post->ID, $attach_id );
		}
	}
	$log[] = 'images redrawn: ' . count( $q->posts );
}

// --- 8. Summary ---
echo "=== RETRO-MODERN DESIGN APPLIED ===\n";
foreach ( $log as $line ) {
	echo ' - ' . $line . "\n";
}
