<?php
/**
 * Fetch real product photos from Wikimedia Commons, process (square crop,
 * warm retro tone) and attach to WooCommerce products by title match.
 */
foreach ( array( '/wordpress/wp-load.php', 'C:/Users/Serhio/Studio/ussr-vintage-shop/wp-load.php' ) as $c ) {
	if ( file_exists( $c ) ) {
		require_once $c;
		break;
	}
}
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
$log = '';

$photos = array(
	'radio'     => 'VEF 206 portable transistor radio (Soviet Union, 1971-1986).jpg',
	'vase'      => 'A cut glass vase on white.jpg',
	'watch'     => 'Vostok Komandirskie Watch.jpg',
	'camera'    => 'Zenit-E mit Helios-44-2 -- 2023 -- 0024-31.jpg',
	'vinyl'     => '12in-Vinyl-LP-Record-Angle.jpg',
	'book'      => 'Vintage Tennyson book (Unsplash).jpg',
	'coins'     => 'Soviet Coins 1961 1962 01 977.PNG',
	'badges'    => 'My homeland is the USSR badges - p01.jpg',
	'coat'      => 'Overcoat MET 1973.65.1 F.jpg',
	'sideboard' => 'Clevelandart 1985.72.jpg',
	'guitar'    => 'Classical Guitar two views.jpg',
	'toys'      => '20030514 160101-Meccano set-rt1.jpg',
);
// Product title keyword -> photo key.
$match = array(
	'ВЭФ'        => 'radio',
	'ваза'       => 'vase',
	'Восток'     => 'watch',
	'Зенит'      => 'camera',
	'Пластинка'  => 'vinyl',
	'Книга'      => 'book',
	'монет'      => 'coins',
	'значк'      => 'badges',
	'Пальто'     => 'coat',
	'Сервант'    => 'sideboard',
	'Гитара'     => 'guitar',
	'Конструктор' => 'toys',
);

$tmpdir = sys_get_temp_dir() . '/store-photos';
if ( ! is_dir( $tmpdir ) ) {
	mkdir( $tmpdir, 0777, true );
}

// 1. Download originals from Commons.
foreach ( $photos as $key => $file ) {
	$dest = "$tmpdir/$key.jpg";
	if ( file_exists( $dest ) ) {
		$log .= "cached: $key\n";
		continue;
	}
	$url = 'https://commons.wikimedia.org/wiki/Special:FilePath/' . rawurlencode( $file ) . '?width=1200';
	$ctx = stream_context_create( array( 'http' => array( 'timeout' => 60, 'header' => "User-Agent: StoreDemo/1.0 (portfolio demo)\r\n" ), 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false ) ) );
	$data = @file_get_contents( $url, false, $ctx );
	if ( $data && strlen( $data ) > 5000 ) {
		file_put_contents( $dest, $data );
		$log .= "downloaded: $key (" . strlen( $data ) . " bytes)\n";
	} else {
		$log .= "FAIL: $key\n";
	}
	sleep( 1 );
}

// 2. Process: square center-crop + warm retro tone.
foreach ( $photos as $key => $file ) {
	$src = "$tmpdir/$key.jpg";
	if ( ! file_exists( $src ) ) {
		continue;
	}
	$size = getimagesize( $src );
	if ( ! $size ) {
		continue;
	}
	$im = match ( $size[2] ) {
		IMAGETYPE_JPEG => imagecreatefromjpeg( $src ),
		IMAGETYPE_PNG  => imagecreatefrompng( $src ),
		IMAGETYPE_GIF  => imagecreatefromgif( $src ),
		IMAGETYPE_WEBP => function_exists( 'imagecreatefromwebp' ) ? imagecreatefromwebp( $src ) : false,
		default        => false,
	};
	if ( ! $im ) {
		$log .= "cannot decode: $key\n";
		continue;
	}
	$w = imagesx( $im );
	$h = imagesy( $im );
	$side = min( $w, $h );
	$sx   = (int) ( ( $w - $side ) / 2 );
	$sy   = (int) ( ( $h - $side ) / 2 );
	$sq   = imagecreatetruecolor( 800, 800 );
	imagecopyresampled( $sq, $im, 0, 0, $sx, $sy, 800, 800, $side, $side );
	// Warm retro tone: subtle mustard overlay + slight contrast.
	$tint = imagecreatetruecolor( 800, 800 );
	imagefill( $tint, 0, 0, imagecolorallocate( $tint, 232, 184, 75 ) );
	imagecopymerge( $sq, $tint, 0, 0, 0, 0, 800, 800, 10 );
	imagedestroy( $tint );
	imagefilter( $sq, IMG_FILTER_CONTRAST, -6 );
	imagefilter( $sq, IMG_FILTER_BRIGHTNESS, 4 );
	// Thin cream frame for a print feel.
	$cream = imagecolorallocate( $sq, 247, 241, 227 );
	imagerectangle( $sq, 0, 0, 799, 799, $cream );
	imagejpeg( $sq, "$tmpdir/{$key}-final.jpg", 88 );
	imagedestroy( $sq );
	imagedestroy( $im );
	$log .= "processed: $key\n";
}

// 3. Attach to products.
$uploads = wp_upload_dir();
$q = new WP_Query( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
foreach ( $q->posts as $post ) {
	$photo = '';
	foreach ( $match as $kw => $key ) {
		if ( mb_stripos( $post->post_title, $kw ) !== false ) {
			$photo = $key;
			break;
		}
	}
	$src = "$tmpdir/{$photo}-final.jpg";
	if ( ! $photo || ! file_exists( $src ) ) {
		$log .= 'no photo for: ' . $post->post_title . "\n";
		continue;
	}
	$dest = $uploads['path'] . '/' . $post->post_name . '-photo.jpg';
	copy( $src, $dest );
	$filetype  = wp_check_filetype( basename( $dest ), null );
	$attach_id = wp_insert_attachment( array(
		'post_mime_type' => $filetype['type'],
		'post_title'     => $post->post_title,
		'post_status'    => 'inherit',
		'guid'           => $uploads['url'] . '/' . basename( $dest ),
	), $dest, $post->ID );
	if ( $attach_id && ! is_wp_error( $attach_id ) ) {
		wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $dest ) );
		set_post_thumbnail( $post->ID, $attach_id );
		$log .= 'attached [' . $photo . '] -> ' . $post->post_title . "\n";
	}
}

file_put_contents( 'C:/Users/Serhio/AppData/Local/Temp/fetch-photos.txt', $log );
echo "done\n";
