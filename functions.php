<?php
/**
 * CSD Darmstadt theme functions.
 * All the custom blocks (hero, quicklinks etc) are renderd server-side here.
 * No shortcodes, no raw HTML in templates.
 *
 * @package csd-darmstadt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* load styles and the nav script */
function csd_enqueue_styles() {
	wp_enqueue_style(
		'twentytwentyfive-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);
	wp_enqueue_style(
		'csd-style',
		get_stylesheet_uri(),
		array( 'twentytwentyfive-style' ),
		wp_get_theme()->get( 'Version' )
	);
	wp_enqueue_script(
		'csd-nav',
		get_stylesheet_directory_uri() . '/assets/nav.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'csd_enqueue_styles' );

function csd_editor_styles() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'csd_editor_styles' );

/* PT Sans from Google Fonts, loaded for both frontend and the editor iframe */
function csd_enqueue_fonts() {
	$url = 'https://fonts.googleapis.com/css2?family=PT+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap';
	wp_register_style( 'csd-pt-sans', $url );
	wp_enqueue_style(  'csd-pt-sans' );
}
add_action( 'enqueue_block_assets', 'csd_enqueue_fonts' );

/* inline SVG icons used in the quick access tiles */
function csd_icon( $name ) {
	$o = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
	$c = '</svg>';
	$paths = array(
		'community' => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><circle cx="17.5" cy="7.5" r="2"/><path d="M21 21v-1a3 3 0 0 0-3-3"/>',
		'flag'      => '<path d="M5 21V4"/><path d="M5 4h13l-2.5 4L18 12H5"/>',
		'coffee'    => '<path d="M4 9h13v4a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z"/><path d="M17 10h2a2 2 0 0 1 0 4h-2"/><path d="M7 3v2.5M11 3v2.5"/>',
		'run'       => '<circle cx="15" cy="5" r="2"/><path d="M9.5 8.5 14 11l1 4 3.5 2.5"/><path d="M8 21l2.5-5L8 13l-3 2"/>',
		'heart'     => '<path d="M12 20s-6.5-4-8.5-8.2A4.6 4.6 0 0 1 12 6.5a4.6 4.6 0 0 1 8.5 5.3C18.5 16 12 20 12 20z"/>',
		'smile'     => '<circle cx="12" cy="12" r="9"/><path d="M9 10h.01M15 10h.01M8.5 14.5a4 4 0 0 0 7 0"/>',
		'chat'      => '<path d="M4 5h13a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H9l-4 3v-3a2 2 0 0 1-1-2V7a2 2 0 0 1 2-2z"/>',
		'hand'      => '<path d="M8 13V6.5a1.5 1.5 0 0 1 3 0V11M11 11V5a1.5 1.5 0 0 1 3 0v6M14 11V6.5a1.5 1.5 0 0 1 3 0V14a6 6 0 0 1-6 6 6 6 0 0 1-5.2-3l-2-3.4a1.5 1.5 0 0 1 2.5-1.6L8 13"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'video'     => '<polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>',
		'camera'    => '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>',
	);
	$p = isset( $paths[ $name ] ) ? $paths[ $name ] : '';
	return $o . $p . $c;
}

/* small helper functions used by the blocks below */

/* fallback tile colour, cycles through the brand palette */
function csd_card_color( $index ) {
	$colors = array( 'purple', 'blue', 'green', 'orange', 'purple', 'ink' );
	return $colors[ $index % count( $colors ) ];
}

/* hex values for tile backgrounds, mirrors theme.json */
function csd_hex() {
	return array(
		'pink'   => '#6546B4',
		'purple' => '#6546B4',
		'green'  => '#41B73D',
		'yellow' => '#FFCB03',
		'blue'   => '#13A3DC',
		'orange' => '#F59C00',
		'ink'    => '#363738',
	);
}

/* grab the featured image, or fall back to the first img tag in the post content */
function csd_post_image( $post ) {
	$thumb = get_the_post_thumbnail_url( $post, 'large' );
	if ( $thumb ) {
		return $thumb;
	}
	if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $m ) ) {
		return $m[1];
	}
	return '';
}

/* the actual block render callbacks */

/* hero block */
function csd_block_hero( $attributes = array() ) {
	/* check persistent settings first so a template reset dosnt wipe out the texts */
	$saved = get_option( 'csd_hero_settings', array() );

	$kicker  = ( isset( $attributes['kicker'] )  && '' !== $attributes['kicker'] )
		? $attributes['kicker']
		: ( ( isset( $saved['kicker'] )    && '' !== $saved['kicker'] )    ? $saved['kicker']    : apply_filters( 'csd_hero_kicker', 'CHRISTOPHER STREET DAY DARMSTADT' ) );
	$title   = ( isset( $attributes['title'] )   && '' !== $attributes['title'] )
		? $attributes['title']
		: ( ( isset( $saved['title'] )     && '' !== $saved['title'] )     ? $saved['title']     : apply_filters( 'csd_hero_title', 'Seid dabei.' ) );
	$lead    = ( isset( $attributes['lead'] )    && '' !== $attributes['lead'] )
		? $attributes['lead']
		: ( ( isset( $saved['lead'] )      && '' !== $saved['lead'] )      ? $saved['lead']      : apply_filters( 'csd_hero_lead', 'Der CSD Darmstadt feiert queeres Leben in Darmstadt und Umgebung. Am 15. August 2026 gehen wir gemeinsam auf die Straße.' ) );
	$btn1_label = ( isset( $attributes['btn1Label'] ) && '' !== $attributes['btn1Label'] ) ? $attributes['btn1Label'] : ( $saved['btn1Label'] ?? 'Mitmachen'   );
	$btn1_url   = ( isset( $attributes['btn1Url'] )   && '' !== $attributes['btn1Url'] )   ? $attributes['btn1Url']   : ( $saved['btn1Url']   ?? home_url( '/mitmachen/' ) );
	$btn2_label = ( isset( $attributes['btn2Label'] ) && '' !== $attributes['btn2Label'] ) ? $attributes['btn2Label'] : ( $saved['btn2Label'] ?? 'Zur Anreise' );
	$btn2_url   = ( isset( $attributes['btn2Url'] )   && '' !== $attributes['btn2Url'] )   ? $attributes['btn2Url']   : ( $saved['btn2Url']   ?? home_url( '/anreise/' ) );

	if ( ! empty( $attributes['bgUrl'] ) ) {
		$media = 'url(' . esc_url( $attributes['bgUrl'] ) . ')';
	} elseif ( ! empty( $saved['bgUrl'] ) ) {
		$media = 'url(' . esc_url( $saved['bgUrl'] ) . ')';
	} else {
		$media = apply_filters( 'csd_hero_media', 'linear-gradient(135deg,#2a1878,#6546b4)' );
	}

	/* inline the flag SVG so we can style it with CSS. swap this file for a transparent version when ready */
	$flag_path = get_stylesheet_directory() . '/assets/flag-pic-2026.svg';
	$flag_svg  = is_readable( $flag_path ) ? file_get_contents( $flag_path ) : '';
	$flag_svg  = preg_replace( '/<\?xml.*?\?>/is', '', $flag_svg );
	$flag_svg  = preg_replace( '/<!DOCTYPE.*?>/is', '', $flag_svg );
	$flag_svg  = trim( $flag_svg );

	ob_start();
	?>
	<section class="vb-hero csd-hero">
		<div class="vb-hero__media" aria-hidden="true" style="background-image:<?php echo esc_attr( $media ); ?>"></div>
		<div class="vb-bars-anim" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
		<div class="vb-hero__text">
			<?php if ( $flag_svg ) : ?>
			<div class="csd-hero__flag" aria-hidden="true"><?php echo $flag_svg; ?></div>
			<?php endif; ?>
			<div class="csd-hero__content">
				<p class="vb-kicker"><?php echo esc_html( $kicker ); ?></p>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p class="vb-lead"><?php echo esc_html( $lead ); ?></p>
				<div class="vb-hero__btns">
					<a class="vb-btn-solid" href="<?php echo esc_url( $btn1_url ); ?>"><?php echo esc_html( $btn1_label ); ?> <?php echo csd_icon( 'arrow' ); ?></a>
					<a class="vb-btn-ghost" href="<?php echo esc_url( $btn2_url ); ?>"><?php echo esc_html( $btn2_label ); ?></a>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* quick access tiles. colours and icons are hardcoded here,
   but title and URL can be overriden in the site editor per tile */
function csd_default_tiles() {
	return array(
		array( 'label' => 'After Show Party', 'url' => 'https://www.csd-darmstadt.de/after-show-party-centralstation/', 'color' => 'orange', 'icon' => 'smile'    ),
		array( 'label' => 'Motto 2025',       'url' => 'https://www.csd-darmstadt.de/motto-2025/',                      'color' => 'purple', 'icon' => 'flag'     ),
		array( 'label' => 'Pride Week 2025',  'url' => 'https://www.csd-darmstadt.de/csd-pride-week-2025/',             'color' => 'green',  'icon' => 'community'),
		array( 'label' => 'Kontakt',          'url' => 'https://www.csd-darmstadt.de/kontakt/',                         'color' => 'blue',   'icon' => 'chat'     ),
		array( 'label' => 'Fotos CSD 2025',   'url' => 'https://www.csd-darmstadt.de/2025/08/fotos-vom-csd-darmstadt-2025-in-arbeit/', 'color' => 'orange', 'icon' => 'camera'),
		array( 'label' => 'Videos',           'url' => 'https://www.csd-darmstadt.de/videos/',                          'color' => 'ink',    'icon' => 'video'    ),
		array( 'label' => 'Anreise',          'url' => 'https://www.csd-darmstadt.de/anreise/',                         'color' => 'green',  'icon' => 'arrow'    ),
		array( 'label' => 'Mitmachen!',       'url' => 'https://www.csd-darmstadt.de/mitmachen/',                       'color' => 'yellow', 'icon' => 'hand'     ),
	);
}

function csd_block_quicklinks( $attributes = array() ) {
	/* load persistent settings so tile labels survive a template reset */
	$saved    = get_option( 'csd_quicklinks_settings', array() );
	$defaults = csd_default_tiles();
	$hex      = csd_hex();

	/* prefer block attributes → saved options → hardcoded defaults. color/icon always from PHP */
	$has_attr_tiles = isset( $attributes['tiles'] ) && is_array( $attributes['tiles'] ) && count( $attributes['tiles'] ) > 0;
	$has_saved_tiles = isset( $saved['tiles'] ) && is_array( $saved['tiles'] ) && count( $saved['tiles'] ) > 0;
	$attr_tiles = $has_attr_tiles ? $attributes['tiles'] : ( $has_saved_tiles ? $saved['tiles'] : array() );

	$tiles = array();
	foreach ( $defaults as $i => $default ) {
		$override = isset( $attr_tiles[ $i ] ) ? (array) $attr_tiles[ $i ] : array();
		$tiles[]  = array(
			'label' => ( isset( $override['label'] ) && '' !== $override['label'] ) ? $override['label'] : $default['label'],
			'url'   => ( isset( $override['url'] )   && '' !== $override['url'] )   ? $override['url']   : $default['url'],
			'color' => $default['color'],
			'icon'  => $default['icon'],
		);
	}

	$heading = ( isset( $attributes['heading'] ) && '' !== $attributes['heading'] )
		? $attributes['heading']
		: ( ( isset( $saved['heading'] ) && '' !== $saved['heading'] ) ? $saved['heading'] : 'Schnellzugriff' );

	$has_attr_images  = isset( $attributes['images'] ) && is_array( $attributes['images'] ) && count( $attributes['images'] ) > 0;
	$has_saved_images = isset( $saved['images'] )      && is_array( $saved['images'] )      && count( $saved['images'] )      > 0;
	$images = $has_attr_images ? $attributes['images'] : ( $has_saved_images ? $saved['images'] : array() );

	$grid = '<div class="vb-grid vb-grid--quick">';
	foreach ( $tiles as $i => $t ) {
		$label = $t['label'];
		$url   = $t['url'];
		$color = $t['color'];
		$icon  = $t['icon'];
		$hexc  = isset( $hex[ $color ] ) ? $hex[ $color ] : '#6546B4';

		$img_url = '';
		if ( isset( $images[ $i ]['url'] ) && '' !== $images[ $i ]['url'] ) {
			$img_url = $images[ $i ]['url'];
		}
		$layers = '';
		if ( $img_url ) {
			$layers = sprintf(
				'<span class="vb-tile__bg" style="background-image:url(%1$s)"></span><span class="vb-tile__shade" style="background:%2$s"></span>',
				esc_url( $img_url ),
				esc_attr( $hexc )
			);
		}

		$grid .= sprintf(
			'<a class="vb-tile is-%1$s%2$s" href="%3$s" style="background:%4$s">%5$s<span class="vb-tile__icon">%6$s</span><span class="vb-tile__label">%7$s</span></a>',
			esc_attr( $color ),
			$img_url ? ' has-img' : '',
			esc_url( $url ),
			esc_attr( $hexc ),
			$layers,
			csd_icon( $icon ),
			esc_html( $label )
		);
	}
	$grid .= '</div>';

	return sprintf(
		'<section class="vb-quick"><div class="vb-quick__inner"><h2 class="vb-quick__title">%1$s</h2>%2$s</div></section>',
		esc_html( $heading ),
		$grid
	);
}

/* announcements grid, shows the 8 most recent posts */
function csd_block_events( $attributes = array() ) {
	$limit = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 8;

	$query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );

	if ( ! $query->have_posts() ) {
		return '<p class="vb-empty">' . esc_html__( 'Aktuell sind keine Ankündigungen vorhanden.', 'csd-darmstadt' ) . '</p>';
	}

	$out = '<div class="vb-grid vb-grid--events">';
	$i   = 0;
	foreach ( $query->posts as $post ) {
		$url  = get_permalink( $post );
		$img  = csd_post_image( $post );
		$date = get_the_date( 'd.m.Y', $post );

		if ( $img ) {
			$alt  = esc_attr( get_the_title( $post ) );
			$out .= sprintf(
				'<a class="vb-card vb-card--img" href="%1$s"><img src="%2$s" alt="%3$s" loading="lazy" /></a>',
				esc_url( $url ),
				esc_url( $img ),
				$alt
			);
		} else {
			$color = csd_card_color( $i );
			$out  .= sprintf(
				'<a class="vb-card is-%1$s" href="%2$s" style="background:var(--wp--preset--color--%1$s)"><span class="vb-card__date" style="color:var(--wp--preset--color--%1$s)">%3$s</span><span class="vb-card__title">%4$s</span></a>',
				esc_attr( $color ),
				esc_url( $url ),
				esc_html( $date ),
				esc_html( get_the_title( $post ) )
			);
		}
		$i++;
	}
	$out .= '</div>';
	return $out;
}

/* further announcements feed. starts at post 9 becuase the first 8 are already shown above */
function csd_block_feed( $attributes = array() ) {
	$limit = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 6;

	/* skip the first 8 posts, those are already shown in the grid above */
	$query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'offset'              => 8,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	) );

	if ( ! $query->have_posts() ) {
		return '<p class="vb-empty">' . esc_html__( 'Noch keine weiteren Beiträge.', 'csd-darmstadt' ) . '</p>';
	}

	/* save and restore the global $post so block rendering context stays intact */
	global $post;
	$saved_post = $post;

	$out = '<div class="vb-feed vb-feed--full">';
	foreach ( $query->posts as $wp_post ) {
		$url  = get_permalink( $wp_post );
		$cats = get_the_category( $wp_post->ID );
		$cat  = ! empty( $cats ) ? $cats[0]->name : '';

		/* set post context so galleries and embeds render correctly */
		$post = $wp_post;
		setup_postdata( $post );
		$rendered_content = apply_filters( 'the_content', $wp_post->post_content );

		/* show the featured image as a full-width banner if one is set */
		$banner_url = get_the_post_thumbnail_url( $wp_post, 'large' );
		$banner_html = '';
		if ( $banner_url ) {
			$banner_html = sprintf(
				'<a class="vb-feed__banner" href="%1$s"><img src="%2$s" alt="%3$s" loading="lazy" /></a>',
				esc_url( $url ),
				esc_url( $banner_url ),
				esc_attr( get_the_title( $wp_post ) )
			);
		}

		$out .= '<article class="vb-feed__row vb-feed__row--full">';
		$out .= $banner_html;
		$out .= '<div class="vb-feed__body">';
		if ( $cat ) {
			$out .= '<span class="vb-feed__cat">' . esc_html( $cat ) . '</span>';
		}
		$out .= '<h3 class="vb-feed__title"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $wp_post ) ) . '</a></h3>';
		$out .= '<p class="vb-feed__meta">' . esc_html( get_the_date( '', $wp_post ) ) . '</p>';
		$out .= '<div class="vb-feed__content entry-content">' . $rendered_content . '</div>';
		$out .= '</div>';
		$out .= '</article>';
	}
	$out .= '</div>';
	$out .= '<p class="vb-feed__next-wrap"><a class="vb-feed__next" href="https://www.csd-darmstadt.de/page/2/">Nächste Seite →</a></p>';

	/* restore the global post context */
	$post = $saved_post;
	wp_reset_postdata();

	return $out;
}
/* logo block, supports csd and vielbunt variants */
function csd_block_logo( $attributes = array() ) {
	$variant = isset( $attributes['variant'] ) ? $attributes['variant'] : 'csd';

	switch ( $variant ) {
		case 'vielbunt':
			$file     = 'logo-vielbunt-footer.svg';
			$href     = 'https://www.vielbunt.org';
			$label    = 'vielbunt e.V. – zur Website';
			$css_class = 'vb-logo--vielbunt';
			$external  = true;
			break;
		default: /* csd */
			$file     = 'logo-csd.svg';
			$href     = home_url( '/' );
			$label    = 'CSD Darmstadt – Startseite';
			$css_class = 'vb-logo--csd';
			$external  = false;
			break;
	}

	$path = get_stylesheet_directory() . '/assets/logo/' . $file;
	$svg  = is_readable( $path ) ? file_get_contents( $path ) : '';
	$svg  = preg_replace( '/<\?xml.*?\?>/is', '', $svg );
	$svg  = preg_replace( '/<!DOCTYPE.*?>/is', '', $svg );
	$svg  = trim( $svg );

	$target = $external ? ' target="_blank" rel="noopener noreferrer"' : '';

	return sprintf(
		'<a class="vb-logo-link %1$s" href="%2$s" aria-label="%3$s"%4$s>%5$s</a>',
		esc_attr( $css_class ),
		esc_url( $href ),
		esc_attr( $label ),
		$target,
		$svg
	);
}

/* footer nav links */
function csd_block_footerlinks( $attributes = array() ) {
	$links = array(
		array( 'CSD auf Facebook',       'http://www.facebook.com/csd-darmstadt' ),
		array( 'vielbunt auf Instagram',  'https://instagram.com/vielbunt' ),
		array( 'Datenschutzerklärung',    'https://www.csd-darmstadt.de/datenschutzerklaerung/' ),
		array( 'Impressum',               'https://www.csd-darmstadt.de/impressum/' ),
		array( 'Kontakt',                 'https://www.csd-darmstadt.de/kontakt/' ),
		array( 'Login',                   'http://www.csd-darmstadt.de/wp-admin' ),
	);
	$out = '<nav class="vb-footerlinks" aria-label="' . esc_attr__( 'Links und Rechtliches', 'csd-darmstadt' ) . '">';
	foreach ( $links as $l ) {
		$out .= sprintf( '<a href="%1$s">%2$s</a>', esc_url( $l[1] ), esc_html( $l[0] ) );
	}
	$out .= '</nav>';
	return $out;
}

/* post/page hero with the same purple overlay as the front page hero */
function csd_block_post_hero( $attributes = array() ) {
	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return '';
	}

	$img = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( $img ) {
		$media = 'url(' . esc_url( $img ) . ')';
	} else {
		$media = 'linear-gradient(135deg,#2a1878,#6546b4)';
	}

	$title  = get_the_title( $post_id );
	$kicker = '';
	if ( is_singular( 'post' ) ) {
		$cats   = get_the_category( $post_id );
		$kicker = ! empty( $cats ) ? $cats[0]->name : 'Ankündigung';
	}

	ob_start();
	?>
	<section class="vb-hero vb-hero--post">
		<div class="vb-hero__media" aria-hidden="true" style="background-image:<?php echo esc_attr( $media ); ?>"></div>
		<div class="vb-bars-anim" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
		<div class="vb-hero__text">
			<div class="csd-hero__content">
				<?php if ( $kicker ) : ?>
				<p class="vb-kicker"><?php echo esc_html( $kicker ); ?></p>
				<?php endif; ?>
				<h1><?php echo esc_html( $title ); ?></h1>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* register all our custom blocks with WordPress */
function csd_register_blocks() {
	$common = array( 'api_version' => 3 );

	register_block_type( 'csd/hero', array_merge( $common, array(
		'attributes'      => array(
			'bgUrl'      => array( 'type' => 'string', 'default' => '' ),
			'bgId'       => array( 'type' => 'number', 'default' => 0 ),
			'kicker'     => array( 'type' => 'string', 'default' => '' ),
			'title'      => array( 'type' => 'string', 'default' => '' ),
			'lead'       => array( 'type' => 'string', 'default' => '' ),
			'btn1Label'  => array( 'type' => 'string', 'default' => '' ),
			'btn1Url'    => array( 'type' => 'string', 'default' => '' ),
			'btn2Label'  => array( 'type' => 'string', 'default' => '' ),
			'btn2Url'    => array( 'type' => 'string', 'default' => '' ),
		),
		'render_callback' => 'csd_block_hero',
	) ) );
	register_block_type( 'csd/quicklinks', array_merge( $common, array(
		'attributes'      => array(
			'heading' => array( 'type' => 'string', 'default' => 'Schnellzugriff' ),
			'tiles'   => array( 'type' => 'array',  'default' => array(),
				'items' => array( 'type' => 'object' ) ),
			'images'  => array( 'type' => 'object', 'default' => array() ),
		),
		'render_callback' => 'csd_block_quicklinks',
	) ) );
	register_block_type( 'csd/events', array_merge( $common, array(
		'attributes'      => array( 'limit' => array( 'type' => 'number', 'default' => 8 ) ),
		'render_callback' => 'csd_block_events',
	) ) );
	register_block_type( 'csd/feed', array_merge( $common, array(
		'attributes'      => array( 'limit' => array( 'type' => 'number', 'default' => 6 ) ),
		'render_callback' => 'csd_block_feed',
	) ) );
	register_block_type( 'csd/logo', array_merge( $common, array(
		'attributes'      => array( 'variant' => array( 'type' => 'string', 'default' => 'csd' ) ),
		'render_callback' => 'csd_block_logo',
	) ) );
	register_block_type( 'csd/footerlinks', array_merge( $common, array(
		'render_callback' => 'csd_block_footerlinks',
	) ) );
	register_block_type( 'csd/post-hero', array_merge( $common, array(
		'render_callback' => 'csd_block_post_hero',
		'uses_context'    => array( 'postId', 'postType' ),
	) ) );
}
add_action( 'init', 'csd_register_blocks' );

/* load the editor JS so our blocks have a proper sidebar UI */
function csd_block_editor_assets() {
	wp_enqueue_script(
		'csd-blocks',
		get_stylesheet_directory_uri() . '/assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-api-fetch' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'csd_block_editor_assets' );

/* REST endpoint so the editor can persist block settings independent of the template markup.
   without this, uploading a new theme ZIP resets hero texts and tile labels to empty defaults */
add_action( 'rest_api_init', 'csd_register_settings_api' );
function csd_register_settings_api() {
	register_rest_route( 'csd/v1', '/settings', array(
		array(
			'methods'             => 'GET',
			'callback'            => 'csd_api_get_settings',
			'permission_callback' => '__return_true',
		),
		array(
			'methods'             => 'POST',
			'callback'            => 'csd_api_save_settings',
			'permission_callback' => function () {
				return current_user_can( 'edit_theme_options' );
			},
		),
	) );
}

function csd_api_get_settings() {
	return rest_ensure_response( array(
		'hero'       => get_option( 'csd_hero_settings',       array() ),
		'quicklinks' => get_option( 'csd_quicklinks_settings', array() ),
	) );
}

function csd_api_save_settings( WP_REST_Request $request ) {
	$body = $request->get_json_params();
	if ( isset( $body['hero'] ) && is_array( $body['hero'] ) ) {
		update_option( 'csd_hero_settings', $body['hero'] );
	}
	if ( isset( $body['quicklinks'] ) && is_array( $body['quicklinks'] ) ) {
		update_option( 'csd_quicklinks_settings', $body['quicklinks'] );
	}
	return rest_ensure_response( array( 'ok' => true ) );
}

/* embedded posts sometimes miss a charset declaration, which causes garbled text */
add_action( 'embed_head', function () {
	echo '<meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '" />' . "
";
}, -100 );

/* WP sometimes outputs \u0026 as literal text in nav blocks, this fixes that */
function csd_fix_nav_entities( $content, $block ) {
	if ( isset( $block['blockName'] ) && 'core/navigation' === $block['blockName'] ) {
		$content = str_replace( '\u0026', '&', $content );
	}
	return $content;
}
add_filter( 'render_block', 'csd_fix_nav_entities', 10, 2 );

/* auto-select "Hauptnavigation" for the nav block so the meta nav dosnt
   sneak in. also overrides an existing ref if it points to meta */
function csd_pin_hauptnavigation( $parsed_block ) {
	if ( 'core/navigation' !== $parsed_block['blockName'] ) {
		return $parsed_block;
	}

	static $haupt_id = null;
	static $meta_id  = null;

	if ( null === $haupt_id ) {
		$all_navs = get_posts( array(
			'post_type'      => 'wp_navigation',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );

		$haupt_id = false;
		$meta_id  = false;

		foreach ( $all_navs as $nav ) {
			$lower = strtolower( trim( $nav->post_title ) );
			if ( in_array( $lower, array( 'hauptnavigation', 'main navigation', 'header navigation', 'navigation' ), true ) ) {
				$haupt_id = $nav->ID;
			}
			if ( in_array( $lower, array( 'meta', 'meta navigation', 'footer', 'footer navigation' ), true ) ) {
				$meta_id = $nav->ID;
			}
		}

		/* Fallback: wenn kein expliziter Match, nimm jede Navigation die nicht "meta" ist */
		if ( false === $haupt_id && false !== $meta_id ) {
			foreach ( $all_navs as $nav ) {
				if ( $nav->ID !== $meta_id ) {
					$haupt_id = $nav->ID;
					break;
				}
			}
		}
	}

	$current_ref = isset( $parsed_block['attrs']['ref'] ) ? (int) $parsed_block['attrs']['ref'] : 0;

	/* Override wenn: kein ref gesetzt ODER ref zeigt auf die meta-Navigation */
	if ( $haupt_id && ( ! $current_ref || $current_ref === $meta_id ) ) {
		$parsed_block['attrs']['ref'] = $haupt_id;
	}

	return $parsed_block;
}
add_filter( 'render_block_data', 'csd_pin_hauptnavigation' );
