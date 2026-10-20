<?php
/**
 * Retro USSR Store — WooCommerce setup script for WordPress Playground.
 * Creates: store settings, 12 categories, 12 demo products, static pages,
 * shipping zone (Russia) and demo payment methods.
 */
require_once '/wordpress/wp-load.php';

// 1. Store settings: currency, locale, catalog.
update_option( 'blogname', 'Ретро СССР' );
update_option( 'blogdescription', 'Винтаж и антиквариат из СССР' );
update_option( 'woocommerce_currency', 'RUB' );
update_option( 'woocommerce_currency_pos', 'right_space' );
update_option( 'woocommerce_price_thousand_sep', ' ' );
update_option( 'woocommerce_price_decimal_sep', ',' );
update_option( 'woocommerce_price_num_decimals', 0 );
update_option( 'woocommerce_default_country', 'RU' );
update_option( 'woocommerce_allowed_countries', 'specific' );
update_option( 'woocommerce_specific_allowed_countries', array( 'RU' ) );
update_option( 'woocommerce_weight_unit', 'kg' );
update_option( 'woocommerce_dimension_unit', 'cm' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_store_pages_only', 'no' );
update_option( 'woocommerce_checkout_phone_field', 'required' );
update_option( 'woocommerce_checkout_company_field', 'hidden' );
update_option( 'woocommerce_catalog_columns', 3 );
update_option( 'woocommerce_catalog_rows', 4 );
update_option( 'woocommerce_allow_tracking', 'no' );
update_option( 'woocommerce_show_marketplace_suggestions', 'no' );

// 2. Payment methods (demo).
$bacs                        = get_option( 'woocommerce_bacs_settings', array() );
$bacs['enabled']             = 'yes';
$bacs['title']               = 'Оплата картой по счёту (демо)';
$bacs['description']         = 'Учебный магазин. Оплата по выставленному счёту. Реальные списания не производятся.';
update_option( 'woocommerce_bacs_settings', $bacs );
$cod                = get_option( 'woocommerce_cod_settings', array() );
$cod['enabled']     = 'yes';
$cod['title']       = 'Оплата при получении';
$cod['description'] = 'Оплата наличными или картой курьеру при получении заказа.';
update_option( 'woocommerce_cod_settings', $cod );

// 3. Shipping zone: Russia with 4 methods.
$zone_id = 0;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'Россия' === $z['zone_name'] ) {
		$zone_id = $z['zone_id'];
	}
}
if ( ! $zone_id ) {
	$zone = new WC_Shipping_Zone( 0 );
	$zone->set_zone_name( 'Россия' );
	$zone->add_location( 'RU', 'country' );
	$zone->save();
	$zone_id = $zone->get_id();
}
$zone = new WC_Shipping_Zone( $zone_id );
$shipping_map = array(
	array( 'flat_rate', 'WC_Shipping_Flat_Rate', 'Курьер по России', array( 'cost' => '500' ) ),
	array( 'flat_rate', 'WC_Shipping_Flat_Rate', 'СДЭК / Почта России', array( 'cost' => '350' ) ),
	array( 'local_pickup', 'WC_Shipping_Local_Pickup', 'Самовывоз (бесплатно)', array( 'cost' => '0' ) ),
	array( 'free_shipping', 'WC_Shipping_Free_Shipping', 'Бесплатная доставка от 5 000 ₽', array( 'requires' => 'min_amount', 'min_amount' => '5000' ) ),
);
foreach ( $shipping_map as $m ) {
	list( $type, , $title, $settings ) = $m;
	$exists = false;
	foreach ( $zone->get_shipping_methods() as $existing ) {
		if ( $existing->get_title() === $title ) {
			$exists = true;
		}
	}
	if ( $exists ) {
		continue;
	}
	$iid  = $zone->add_shipping_method( $type );
	$opts = array_merge( array( 'enabled' => 'yes', 'title' => $title, 'tax_status' => 'none' ), $settings );
	update_option( "woocommerce_{$type}_{$iid}_settings", $opts );
}

// 4. Product attributes: Состояние, Период.
foreach ( array( 'sostoyanie' => 'Состояние', 'period' => 'Период' ) as $slug => $label ) {
	if ( ! wc_attribute_taxonomy_id_by_name( 'pa_' . $slug ) ) {
		wc_create_attribute( array( 'name' => $label, 'slug' => $slug, 'type' => 'select', 'has_archives' => false ) );
	}
}


// 5. Categories: 12 sections.
$categories = array(
	array( 'Радиоприёмники и магнитофоны', 'radiopriemniki-i-magnitofony', 'Ламповые и транзисторные приёмники, магнитофоны «Вега», «Маяк», «Электроника».' ),
	array( 'Посуда и хрусталь', 'posuda-i-hrustal', 'Сервизы, хрусталь Гусь-Хрустального, фарфор и эмалированная посуда.' ),
	array( 'Часы', 'chasy', 'Наручные и настенные часы «Слава», «Восток», «Ракета», «Полёт».' ),
	array( 'Фото- и кинотехника', 'foto-i-kinotehnika', '«Зенит», «Смена», «Киев», проекторы «Луч» — плёночная эпоха.' ),
	array( 'Виниловые пластинки', 'vinilovye-plastinki', 'Грампластинки «Мелодии» — рок, джаз, классика, эстрада.' ),
	array( 'Книги, журналы, открытки', 'knigi-zhurnaly-otkrytki', 'Книги, журналы, «Роман-газета», открытки и агитплакаты.' ),
	array( 'Монеты, банкноты, марки', 'monety-banknoty-marki', 'Монеты СССР, купюры, конверты первого дня, марки.' ),
	array( 'Значки и сувениры СССР', 'znachki-i-suveniry-sssr', 'Значки, гербы, шкатулки и сувениры из городов СССР.' ),
	array( 'Винтажная одежда', 'vintazhnaya-odezhda', 'Одежда и аксессуары советских времён.' ),
	array( 'Мебель и интерьер', 'mebel-i-interer', 'Стенки «Мрия», серванты, торшеры и лампы.' ),
	array( 'Музыкальные инструменты', 'muzykalnye-instrumenty', 'Гитары «Тоника», баяны, гармони, синтезаторы.' ),
	array( 'Игрушки и детские вещи', 'igrushki-i-detskie-veshchi', 'Игрушки, конструкторы, машинки и детские книжки.' ),
);
$cat_ids = array();
foreach ( $categories as $c ) {
	$existing = get_term_by( 'slug', $c[1], 'product_cat' );
	if ( $existing ) {
		$cat_ids[ $c[1] ] = $existing->term_id;
		continue;
	}
	$r = wp_insert_term( $c[0], 'product_cat', array( 'slug' => $c[1], 'description' => $c[2] ) );
	if ( ! is_wp_error( $r ) ) {
		$cat_ids[ $c[1] ] = $r['term_id'];
	}
}



// 6. Products: one per category (12 total).
$products = array(
	array( 'radiopriemniki-i-magnitofony', 'Радиоприёмник ВЭФ-10, 1970-е', 12500, '1970-е', 'Хорошее', 'Легендарный латвийский радиоприёмник с фонарной подсветкой шкалы.', 'Транзисторный радиоприёмник ВЭФ-10 выпуска Рижского радиозавода. Рабочий, настроен на все диапазоны. Корпус без сколов, шкала подсвечивается.' ),
	array( 'posuda-i-hrustal', 'Хрустальная ваза «Гусь-Хрустальный», 1970-е', 6500, '1970-е', 'Отличное', 'Гранёная ваза ручной работы с классическим алмазным резом.', 'Ваза из свинцового хрусталя Гусь-Хрустального завода. Глубокая алмазная грань, звонкий звук. Сколов и трещин нет.' ),
	array( 'chasy', 'Часы наручные «Восток Командирские», 1980-е', 9200, '1980-е', 'Хорошее', 'Механические часы Чистопольского завода с автоподзаводом.', 'Наручные часы «Восток» с 17-камерным механизмом. Ход исправный. Корпус — сталь, ремешок новый, кожаный.' ),
	array( 'foto-i-kinotehnika', 'Фотоаппарат Зенит-Е, 1965 год', 7400, '1960-е', 'Хорошее', 'Зеркальная камера Красногорского механического завода.', 'Классический «Зенит-Е» с объективом Гелиос-44. Затвор отрабатывает все выдержки. На корпусе следы времени.' ),
	array( 'vinilovye-plastinki', 'Пластинка «Кино — Звезда по имени Солнце», 1989', 3200, '1980-е', 'Хорошее', 'Оригинальный альбом Виктора Цоя, «Мелодия».', 'Виниловая грампластинка, оригинальное издание 1989 года. Конверт с потёртостями, пластинка в отличном состоянии.' ),
	array( 'knigi-zhurnaly-otkrytki', 'Книга «Мастер и Маргарита», изд. 1973', 5600, '1970-е', 'Хорошее', 'Издание «Художественной литературы», оформление 1970-х.', 'Книга М.А. Булгакова, 1973 год. Твёрдый переплёт, суперобложка сохранена. Бумага пожелтела по краям.' ),
	array( 'monety-banknoty-marki', 'Набор монет СССР 1961–1991, 25 шт.', 1100, '1960-е', 'Отличное', 'Разменные монеты Госбанка в капсулах.', 'Коллекционный набор разменных монет СССР: от 1 копейки до 1 рубля. Все монеты в защитных капсулах.' ),
	array( 'znachki-i-suveniry-sssr', 'Набор значков «Спорт СССР», 30 шт., 1970-е', 2400, '1970-е', 'Отличное', 'Спортивная фалеристика: Олимпиада-80, Динамо, Спартакиады.', 'Коллекция из 30 эмалированных значков спортивной тематики. Эмаль целая, замочки на месте.' ),
	array( 'vintazhnaya-odezhda', 'Пальто шерстяное, 1970-е, размер 48', 6800, '1970-е', 'Хорошее', 'Классическое демисезонное пальто из стопроцентной шерсти.', 'Мужское пальто прямого кроя, ткань — плотная шерсть. Подкладка родная, без разрывов.' ),
	array( 'mebel-i-interer', 'Сервант «Мрия», 1970-е', 18500, '1970-е', 'Хорошее', 'Легендарная стенка-сервант с застеклённой витриной.', 'Мебельная стенка «Мрия» Львовской фабрики «Прогресс». Шпон дуба, застеклённая витрина, выдвижные ящики.' ),
	array( 'muzykalnye-instrumenty', 'Гитара акустическая «Тоника», 1980-е', 9700, '1980-е', 'Хорошее', 'Советская шестиструнная гитара с тёплым клубным звуком.', 'Акустическая гитара «Тоника» Ленинградского комбината. Гриф без коробления, лады выровнены.' ),
	array( 'igrushki-i-detskie-veshchi', 'Конструктор «Механик» №5, 1980-е', 3900, '1980-е', 'Хорошее', 'Металлический конструктор с ключами и инструкцией.', 'Настоящий металлический конструктор «Механик»: детали, винты, гайки, два ключа и книжка с чертежами.' ),
);
foreach ( $products as $p ) {
	list( $cat_slug, $title, $price, $period, $cond, $short, $desc ) = $p;
	$slug = 'ussr-' . $cat_slug;
	if ( get_page_by_path( $slug, OBJECT, 'product' ) ) {
		continue;
	}
	$prod = new WC_Product_Simple();
	$prod->set_name( $title );
	$prod->set_slug( $slug );
	$prod->set_status( 'publish' );
	$prod->set_catalog_visibility( 'visible' );
	$prod->set_description( $desc );
	$prod->set_short_description( $short );
	$prod->set_regular_price( (string) $price );
	$prod->set_category_ids( array( $cat_ids[ $cat_slug ] ) );
	$prod->set_stock_status( 'instock' );
	$prod->set_manage_stock( false );
	$prod->set_sku( 'USSR-' . strtoupper( substr( md5( $slug ), 0, 6 ) ) );
	$attrs = array();
	foreach ( array( 'pa_sostoyanie' => $cond, 'pa_period' => $period ) as $tax => $val ) {
		if ( ! term_exists( $val, $tax ) ) {
			wp_insert_term( $val, $tax );
		}
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
		$a->set_name( $tax );
		$a->set_options( array( $val ) );
		$a->set_visible( true );
		$a->set_variation( false );
		$attrs[] = $a;
	}
	$prod->set_attributes( $attrs );
	$prod->save();
}

// 7. Placeholder images generated via GD.
if ( extension_loaded( 'gd' ) ) {
	$colors = array(
		'radiopriemniki-i-magnitofony' => array( 178, 34, 34 ), 'posuda-i-hrustal' => array( 70, 130, 180 ),
		'chasy' => array( 210, 105, 30 ), 'foto-i-kinotehnika' => array( 47, 79, 79 ),
		'vinilovye-plastinki' => array( 147, 51, 234 ), 'knigi-zhurnaly-otkrytki' => array( 139, 69, 19 ),
		'monety-banknoty-marki' => array( 218, 165, 32 ), 'znachki-i-suveniry-sssr' => array( 220, 20, 60 ),
		'vintazhnaya-odezhda' => array( 85, 107, 47 ), 'mebel-i-interer' => array( 160, 82, 45 ),
		'muzykalnye-instrumenty' => array( 128, 0, 32 ), 'igrushki-i-detskie-veshchi' => array( 255, 140, 0 ),
	);
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$uploads = wp_upload_dir();
	foreach ( $products as $p ) {
		list( $cat_slug, $title, , , $cond, , ) = $p;
		$the_product = wc_get_product( get_page_by_path( 'ussr-' . $cat_slug, OBJECT, 'product' ) ? get_page_by_path( 'ussr-' . $cat_slug, OBJECT, 'product' )->ID : 0 );
		if ( ! $the_product || $the_product->get_image_id() ) {
			continue;
		}
		$rgb = isset( $colors[ $cat_slug ] ) ? $colors[ $cat_slug ] : array( 100, 100, 100 );
		$im  = imagecreatetruecolor( 800, 800 );
		$bg  = imagecolorallocate( $im, $rgb[0], $rgb[1], $rgb[2] );
		imagefilledrectangle( $im, 0, 0, 800, 800, $bg );
		$dark = imagecolorallocate( $im, max( 0, $rgb[0] - 40 ), max( 0, $rgb[1] - 40 ), max( 0, $rgb[2] - 40 ) );
		imagefilledrectangle( $im, 0, 560, 800, 800, $dark );
		$white = imagecolorallocate( $im, 255, 252, 245 );
		imagerectangle( $im, 24, 24, 776, 776, $white );
		imagestring( $im, 5, 56, 90, 'VINTAGE  *  SSSR', $white );
		imagefilledrectangle( $im, 60, 200, 740, 210, $white );
		imagefilledrectangle( $im, 60, 480, 740, 490, $white );
		imagefilledellipse( $im, 400, 350, 180, 180, $dark );
		imagerectangle( $im, 340, 290, 460, 410, $white );
		imagestring( $im, 4, 60, 630, 'CONDITION: ' . ( 'Отличное' === $cond ? 'EXCELLENT' : 'GOOD' ) . '  *  USSR', $white );
		imagestring( $im, 3, 60, 690, 'placeholder - replace with real photo', $white );
		$imgpath = $uploads['path'] . '/' . 'ussr-' . $cat_slug . '.png';
		imagepng( $im, $imgpath );
		imagedestroy( $im );
		$filetype = wp_check_filetype( basename( $imgpath ), null );
		$attach_id = wp_insert_attachment( array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_status'    => 'inherit',
			'guid'           => $uploads['url'] . '/' . basename( $imgpath ),
		), $imgpath, $the_product->get_id() );
		if ( $attach_id && ! is_wp_error( $attach_id ) ) {
			wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $imgpath ) );
			$the_product->set_image_id( $attach_id );
			$the_product->save();
		}
	}
}

// 8. Static pages.
$pages = array(
	'o-magazine' => array( 'О магазине', '<p>«Ретро СССР» — интернет-магазин винтажных и антикварных вещей советской эпохи: радиотехника, посуда, часы, винил, книги, мебель и многое другое.</p><p>Каждая вещь в каталоге — единственная в своём роде. Мы честно описываем состояние: что сохранилось идеально, а где время оставило свои следы.</p>' ),
	'dostavka-i-oplata' => array( 'Доставка и оплата', '<h2>Доставка</h2><ul><li><strong>Курьер по России</strong> — 500 ₽</li><li><strong>СДЭК / Почта России</strong> — 350 ₽</li><li><strong>Самовывоз</strong> — бесплатно</li><li><strong>Бесплатная доставка</strong> при заказе от 5 000 ₽</li></ul><h2>Оплата</h2><ul><li>Оплата картой по счёту</li><li>Оплата при получении</li></ul><p><em>Сайт является учебным демонстрационным проектом. Реальные списания не производятся.</em></p>' ),
	'kontakty' => array( 'Контакты', '<p>Телефон: +7 (900) 000-00-00</p><p>Email: info@retro-ussr.example</p><p>Адрес: г. Москва, ул. Советская, д. 1</p><p>Часы работы: ежедневно с 10:00 до 20:00</p>' ),
	'politika-konfidencialnosti' => array( 'Политика конфиденциальности', '<p>Мы собираем только те данные, которые необходимы для оформления и доставки заказа: имя, телефон, адрес электронной почты и адрес доставки.</p><p>Персональные данные не передаются третьим лицам и используются исключительно для обработки заказов.</p>' ),
	'oferta' => array( 'Публичная оферта', '<p>Настоящий сайт является учебным демонстрационным проектом и не предназначен для реальной торговли.</p><p>Информация о товарах, ценах и условиях доставки приведена в демонстрационных целях.</p>' ),
);
foreach ( $pages as $slug => $data ) {
	if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
		continue;
	}
	wp_insert_post( array(
		'post_title'   => $data[0],
		'post_name'    => $slug,
		'post_content' => $data[1],
		'post_status'  => 'publish',
		'post_type'    => 'page',
	) );
}

// 9. Permalinks.
global $wp_rewrite;
$wp_rewrite->set_permalink_structure( '/%postname%/' );
flush_rewrite_rules();

