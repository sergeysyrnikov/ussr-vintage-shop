<?php
// Adapt Astra theme to retro-modern palette (burgundy / mustard / cream).
require 'C:/Users/Serhio/Studio/ussr-vintage-shop/wp-load.php';
$log = '';

$defaults = get_option( 'astra-settings', array() );
if ( ! is_array( $defaults ) ) {
	$defaults = array();
}
$astra = array_merge( $defaults, array(
	// Palette.
	'base-color'                   => '#4A3728',
	'link-color'                   => '#7B2D26',
	'theme-color'                  => '#7B2D26',
	'accent-color'                 => '#E8B84B',
	'button-color'                 => '#7B2D26',
	'button-bg-color'              => '#7B2D26',
	'button-border-radius'         => 8,
	// Typography: serif headings, clean body.
	'body-font-family'             => 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
	'body-font-size'               => '16',
	'headings-font-family'         => 'Georgia, Times New Roman, serif',
	'h1-font-size'                 => '52',
	'h2-font-size'                 => '36',
	'h3-font-size'                 => '26',
	'headings-font-weight'         => '700',
	// Layout.
	'site-content-layout'          => 'page-builder',
	'site-content-width'           => 1200,
	'header-display'               => 'stack',
	// Header colors.
	'header-main-rt-section-text-color' => '#F7F1E3',
	'header-main-rt-section-link-color' => '#E8B84B',
	// Footer.
	'footer-bg-color'              => '#4A3728',
	'footer-color'                 => '#F7F1E3',
	'footer-link-color'            => '#E8B84B',
	// Blog / shop cards.
	'blog-single-post-structure'   => array( 'single-image', 'single-title', 'single-meta', 'single-content' ),
	// Misc.
	'display-site-title-site-tagline' => 1,
	'site-title-font-size'         => '26',
	'site-title-color'             => '#F7F1E3',
	'site-tagline-color'           => '#E8D9C0',
	'header-main-sep'              => 0,
	'footer-adv'                   => 0,
) );
update_option( 'astra-settings', $astra );
$log .= "astra settings updated (" . count( $astra ) . " keys)\n";

// Global custom CSS for consistent retro-modern look with Astra.
$existing_css = wp_get_custom_css();
$css          = '
:root { --retro-burgundy:#7B2D26; --retro-mustard:#E8B84B; --retro-cream:#F7F1E3; --retro-brown:#4A3728; }
body { background: var(--retro-cream); }
h1,h2,h3,h4,h5 { font-family: Georgia, "Times New Roman", serif !important; }
.site-header { background: var(--retro-burgundy) !important; padding: 18px 0; }
.site-header .site-title a { color: var(--retro-cream) !important; letter-spacing: 1px; }
.site-header .ast-masthead-custom-menu a, .main-header-menu a { color: var(--retro-cream) !important; font-size: 15px; }
.main-header-menu a:hover { color: var(--retro-mustard) !important; }
.ast-button, .button, .wc-block-components-button { background: var(--retro-burgundy) !important; color: var(--retro-cream) !important; border-radius: 8px !important; padding: 12px 28px !important; font-weight: 600; border: none !important; transition: all .2s ease; }
.ast-button:hover, .button:hover, .wc-block-components-button:hover { background: var(--retro-brown) !important; transform: translateY(-2px); }
.site-footer { background: var(--retro-brown) !important; color: var(--retro-cream) !important; padding: 48px 0 32px; }
.site-footer a { color: var(--retro-mustard) !important; }
.woocommerce ul.products li.product { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(74,55,40,.10); transition: transform .25s ease, box-shadow .25s ease; padding-bottom: 16px; }
.woocommerce ul.products li.product:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(123,45,38,.18); }
.woocommerce ul.products li.product img { width: 100%; aspect-ratio: 1/1; object-fit: cover; margin-bottom: 12px; }
.woocommerce ul.products li.product .woocommerce-loop-product__title { font-family: Georgia, serif; color: var(--retro-brown); padding: 0 16px; font-size: 17px; }
.woocommerce ul.products li.product .price { color: var(--retro-burgundy); font-size: 18px; font-weight: 700; padding: 0 16px; }
.woocommerce ul.products li.product .woocommerce-loop-product__link { display: block; }
button[name="add-to-cart"], .single_add_to_cart_button { background: var(--retro-burgundy) !important; border-radius: 8px !important; }
.woocommerce-message, .woocommerce-info { border-color: var(--retro-mustard); background: #fff8e7; }
@media (max-width: 781px) {
  .site-header .main-header-menu { background: var(--retro-burgundy); }
  h1 { font-size: 32px !important; }
}
';
wp_update_custom_css_post( $existing_css . $css );
$log .= "custom css appended\n";

file_put_contents( 'C:/Users/Serhio/AppData/Local/Temp/astra-adapt.txt', $log );
echo "done\n";
