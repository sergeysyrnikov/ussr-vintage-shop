<?php
// Build the complete Playground blueprint: WooCommerce + Astra + store + design + photos.
$setup  = file_get_contents( 'C:/Programing/wp/ussr-vintage-shop/setup-store.php' );
$design = file_get_contents( 'C:/Programing/wp/ussr-vintage-shop/design-retro-modern.php' );
$photos = file_get_contents( 'C:/Programing/wp/ussr-vintage-shop/fetch-photos.php' );
$astra  = file_get_contents( 'C:/Programing/wp/ussr-vintage-shop/adapt-astra.php' );

$bp = array(
	'$schema'           => 'https://playground.wordpress.net/blueprint-schema.json',
	'meta'              => array(
		'title'       => 'Retro USSR Vintage Store',
		'description' => 'Современный магазин винтажа из СССР: Astra, ретро-модерн, реальные фото, 12 разделов.',
		'author'      => 'Sergey',
		'categories'  => array( 'woocommerce', 'demo', 'portfolio' ),
	),
	'landingPage'       => '/',
	'preferredVersions' => array( 'php' => '8.3', 'wp' => 'latest' ),
	'steps'             => array(
		array( 'step' => 'installPlugin', 'pluginData' => array( 'resource' => 'wordpress.org/plugins', 'slug' => 'woocommerce' ) ),
		array( 'step' => 'installTheme', 'themeData' => array( 'resource' => 'wordpress.org/themes', 'slug' => 'astra' ) ),
		array( 'step' => 'activatePlugin', 'pluginPath' => 'woocommerce/woocommerce.php' ),
		array( 'step' => 'activateTheme', 'themeFolderName' => 'astra' ),
		array( 'step' => 'runPHP', 'code' => $setup ),
		array( 'step' => 'runPHP', 'code' => $design ),
		array( 'step' => 'runPHP', 'code' => $astra ),
		array( 'step' => 'runPHP', 'code' => $photos ),
		array( 'step' => 'login', 'username' => 'admin', 'password' => 'password' ),
	),
);
file_put_contents( 'C:/Programing/wp/ussr-vintage-shop/blueprint-standalone.json', json_encode( $bp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
$compact = json_encode( $bp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
$url     = 'https://playground.wordpress.net/#' . rawurlencode( $compact );
file_put_contents( 'C:/Programing/wp/ussr-vintage-shop/playground-link.txt', $url );

$html = '<!DOCTYPE html>
<html lang="ru">
<head><meta charset="utf-8"><title>Ретро СССР — запуск магазина</title>
<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#7B2D26;color:#F7F1E3}div{text-align:center;padding:40px;max-width:640px}a.btn{display:inline-block;padding:18px 36px;background:#E8B84B;color:#4A3728;text-decoration:none;border-radius:12px;font-size:20px;font-weight:700}a.btn:hover{background:#F7F1E3}p{color:#E8D9C0;margin-top:24px;font-size:14px}</style>
</head>
<body><div><h1>Ретро СССР</h1><h2>Интернет-магазин винтажа — Astra + реальные фото</h2>
<a class="btn" href="' . htmlspecialchars( $url, ENT_QUOTES ) . '">Запустить магазин в Playground</a>
<p>Сборка 2–3 минуты: WooCommerce + Astra + русский + ретро-палитра + 12 товаров с реальными фото.<br>Если кнопка не сработает — скопируйте ссылку из playground-link.txt в адресную строку.</p></div></body></html>';
file_put_contents( 'C:/Programing/wp/ussr-vintage-shop/playground-store.html', $html );

echo 'bp=' . filesize( 'C:/Programing/wp/ussr-vintage-shop/blueprint-standalone.json' ) . ' link=' . strlen( $url ) . "\n";
