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

require_once get_stylesheet_directory() . '/inc/frontpage.php';
require_once get_stylesheet_directory() . '/inc/flag-date.php';
require_once get_stylesheet_directory() . '/inc/meta.php';
require_once get_stylesheet_directory() . '/inc/icons.php';
require_once get_stylesheet_directory() . '/inc/seo.php';
require_once get_stylesheet_directory() . '/inc/schema.php';

/* search engines: archives out, per-page switch and excerpts for pages, see inc/seo.php */
vbseo_setup( array( 'toggle' => true, 'page_excerpt' => true ) );
require_once get_stylesheet_directory() . '/inc/deploy.php';
require_once get_stylesheet_directory() . '/inc/once.php';

/* updates straight from GitHub, see inc/deploy.php and Design > Theme-Updates */
new Vielbunt_Theme_Deploy(
	array(
		'repo'      => 'vielbunt/csd-darmstadt.de',
		'namespace' => 'csd/v1',
		'prefix'    => 'csd',
		'once'      => array(
			'2026-10-fancybox'     => array( 'FancyBox-Plugin abschalten (Theme hat jetzt eine eigene Lightbox)', 'vielbunt_once_disable_fancybox' ),
			'2026-10-autoptimize'  => array( 'Autoptimize: Google Fonts entfernen, kein Preconnect zu Google', 'vielbunt_once_autoptimize_no_gfonts' ),
			'2026-10-kampagne-aus' => array( 'Spendenkampagne 2026 ausschalten', 'csd_once_campaign_off' ),
			'2026-10-suche'        => array( 'Suche aufräumen: Altlasten auf noindex, Titel und Menü ohne Jahreszahl, Kategorie umbenannt', 'csd_once_search_cleanup' ),
		),
	)
);

/* one-time cleanup so Google gets sensible sub pages. everything is
   reversible: noindex via the switch "Nicht in Suchmaschinen anzeigen" in the
   page sidebar, the old titles and menu labels are in the result text under
   Design > Theme-Updates. straight via $wpdb for titles and menu so no content
   filter touches the page content (this can run on a normal page view) */
function csd_once_search_cleanup() {
	global $wpdb;
	$log = array();

	// Altlasten und alte Motto-/Aktionswochen-Seiten. Die Übersicht "Mottos vergangener Jahre" bleibt drin
	$hide = array(
		'qr', 'dein-weg-auf-den-festplatz/kontaktdatenformular', 'mit-vielbunt-zum-csd-hanau',
		'anmeldung-eines-infostands-auf-dem-riegerplatz',
		'csd-2011-wir-lieben-vielfalt', 'csd-2012-natuerlich-anders', 'csd-2013-mit-vollem-recht-queer',
		'csd-2014-ich-hab-nichts-gegen-die-aber', 'csd-2015-wir-koennen-auch-anders', 'csd-2016-liebe-sex-und-widerstand',
		'motto-2017', 'motto-2018', 'motto-2019', 'motto-2020-zusammenhalten', 'csd-motto-2021',
		'csd-2022-ich-hab-immer-noch-nix-gegen-die-aber-fuck-you', 'motto-2023-vielfalt-verpflichtet',
		'motto-2024', 'motto-2025', 'csd-pride-week-2024', 'csd-pride-week-2025',
	);
	$hidden = array();
	foreach ( $hide as $path ) {
		$page = get_page_by_path( $path );
		if ( $page ) {
			update_post_meta( $page->ID, '_vb_noindex', '1' );
			$hidden[] = $path;
		}
	}
	$log[] = count( $hidden ) . ' Seiten auf noindex (' . implode( ', ', $hidden ) . ')';

	// Seitentitel ohne Jahr
	$page = get_page_by_path( 'demo-parade' );
	if ( $page && 'Demo 2026' === $page->post_title ) {
		$wpdb->update( $wpdb->posts, array( 'post_title' => 'Demo & Route' ), array( 'ID' => $page->ID ) );
		clean_post_cache( $page->ID );
		$log[] = 'Seite /demo-parade/: "Demo 2026" -> "Demo & Route"';
	}

	// Menü: Jahreszahlen raus
	$labels = array( 'Der CSD 2026' => 'Der CSD', 'Demo 2026' => 'Demo & Route' );
	$navs   = get_posts( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'posts_per_page' => -1 ) );
	foreach ( $navs as $nav ) {
		$content = $nav->post_content;
		foreach ( $labels as $old => $new ) {
			$content = str_replace(
				array( '"label":"' . $old . '"', '"label":"' . str_replace( '&', '\u0026', $old ) . '"' ),
				'"label":"' . str_replace( '&', '\u0026', $new ) . '"',
				$content
			);
		}
		if ( $content !== $nav->post_content ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $nav->ID ) );
			clean_post_cache( $nav->ID );
			$log[] = 'Menü "' . $nav->post_title . '": ' . implode( ', ', array_map( function ( $o, $n ) { return '"' . $o . '" -> "' . $n . '"'; }, array_keys( $labels ), $labels ) );
		}
	}

	// Kategorie heißt nicht mehr wie die Seite "Bühnenprogramm"
	$cat = get_term_by( 'slug', 'programm', 'category' );
	if ( $cat && 'Bühnenprogramm' === $cat->name ) {
		wp_update_term( $cat->term_id, 'category', array( 'name' => 'Programm-News' ) );
		$log[] = 'Kategorie "Bühnenprogramm" -> "Programm-News"';
	}

	return implode( ' | ', $log );
}

/* no campaign for 2027 yet, so the 2026 one goes off once. switch it back on
   in the Customizer (Spendenkampagne) as soon as there is a new one */
function csd_once_campaign_off() {
	set_theme_mod( 'csd_campaign_enable', false );
	return 'Kampagne im Customizer ausgeschaltet';
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
	/* replaces the old FancyBox plugin, no jQuery needed */
	wp_enqueue_script(
		'csd-lightbox',
		get_stylesheet_directory_uri() . '/assets/lightbox.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		array( 'in_footer' => true, 'strategy' => 'defer' )
	);
}
add_action( 'wp_enqueue_scripts', 'csd_enqueue_styles' );

function csd_editor_styles() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'csd_editor_styles' );

/* PT Sans comes from the theme itself now (assets/fonts, declared as fontFace
   in theme.json), so no visitor IP ends up at Google. WordPress loads it in the
   frontend and in the editor on its own. */

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

/* hero defaults, also used as placeholders in the editor sidebar */
function csd_hero_defaults() {
	return array(
		'kicker'    => apply_filters( 'csd_hero_kicker', 'CHRISTOPHER STREET DAY DARMSTADT' ),
		'title'     => apply_filters( 'csd_hero_title', 'Seid dabei.' ),
		'lead'      => apply_filters( 'csd_hero_lead', 'Der CSD Darmstadt feiert queeres Leben in Darmstadt und Umgebung. Komm mit uns auf die Straße.' ),
		'btn1Label' => 'Mitmachen',
		'btn1Url'   => home_url( '/mitmachen/' ),
		'btn2Label' => 'Zur Anreise',
		'btn2Url'   => home_url( '/anreise/' ),
	);
}

/* hero block. content comes from the csd_frontpage option (see inc/frontpage.php),
   empty fields fall back to the defaults above */
function csd_block_hero( $attributes = array() ) {
	$data     = csd_frontpage_for_render( $attributes );
	$hero     = $data['hero'];
	$defaults = csd_hero_defaults();
	foreach ( $defaults as $key => $default ) {
		if ( '' === $hero[ $key ] ) {
			$hero[ $key ] = $default;
		}
	}
	$kicker     = $hero['kicker'];
	$title      = $hero['title'];
	$lead       = $hero['lead'];
	$btn1_label = $hero['btn1Label'];
	$btn1_url   = $hero['btn1Url'];
	$btn2_label = $hero['btn2Label'];
	$btn2_url   = $hero['btn2Url'];

	$bg = csd_frontpage_image( $hero['bgId'], $hero['bgUrl'], 'full' );
	if ( '' !== $bg ) {
		$media = 'url(' . esc_url( $bg ) . ')';
	} else {
		$media = apply_filters( 'csd_hero_media', 'linear-gradient(135deg,#2a1878,#6546b4)' );
	}

	/* the CSD graphic, date comes from the hero field (inc/flag-date.php) */
	$flag_svg = csd_flag_svg( $hero['flagDate'] );

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
	/* only shown when a tile field in the editor is empty. labels without a
	   year on purpose, so they dont go stale */
	return array(
		array( 'label' => 'After Show Party', 'url' => 'https://www.csd-darmstadt.de/after-show-party-centralstation/', 'color' => 'orange', 'icon' => 'smile'    ),
		array( 'label' => 'Motto',            'url' => 'https://www.csd-darmstadt.de/motto-2026/',                      'color' => 'purple', 'icon' => 'flag'     ),
		array( 'label' => 'Bühnenprogramm',   'url' => 'https://www.csd-darmstadt.de/buehnenprogramm-2/',               'color' => 'green',  'icon' => 'community'),
		array( 'label' => 'Infostände',       'url' => 'https://www.csd-darmstadt.de/infostaende/',                     'color' => 'blue',   'icon' => 'chat'     ),
		array( 'label' => 'Fotos',            'url' => 'https://www.csd-darmstadt.de/bilder2026/',                      'color' => 'orange', 'icon' => 'camera'   ),
		array( 'label' => 'Videos',           'url' => 'https://www.csd-darmstadt.de/videos/',                          'color' => 'ink',    'icon' => 'video'    ),
		array( 'label' => 'Anreise',          'url' => 'https://www.csd-darmstadt.de/anreise/',                         'color' => 'green',  'icon' => 'arrow'    ),
		array( 'label' => 'Demostrecke',      'url' => 'https://www.csd-darmstadt.de/demo-parade/',                     'color' => 'yellow', 'icon' => 'hand'     ),
	);
}

function csd_block_quicklinks( $attributes = array() ) {
	$data     = csd_frontpage_for_render( $attributes );
	$saved    = $data['quicklinks'];
	$defaults = csd_default_tiles();
	$hex      = csd_hex();

	/* label/url/image from the option, empty fields fall back to the defaults. color/icon always from PHP */
	$tiles  = array();
	$images = array();
	foreach ( $defaults as $i => $default ) {
		$override = $saved['tiles'][ $i ];
		$tiles[]  = array(
			'label' => '' !== $override['label'] ? $override['label'] : $default['label'],
			'url'   => '' !== $override['url'] ? $override['url'] : $default['url'],
			'color' => $default['color'],
			'icon'  => $default['icon'],
		);
		$images[ $i ] = array( 'url' => csd_frontpage_image( $override['imgId'], $override['imgUrl'] ) );
	}

	$heading = '' !== $saved['heading'] ? $saved['heading'] : 'Schnellzugriff';

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

/* further announcements as a compact list (picture, title, short text, date).
   starts at post 9 becuase the first 8 are already shown in the grid above.
   until 2.2 this showed the full posts incl. all galleries, which made the
   front page about 66.000px long */
function csd_block_feed( $attributes = array() ) {
	$limit = isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 6;

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

	$out = '<div class="vb-feed">';
	foreach ( $query->posts as $wp_post ) {
		$url     = get_permalink( $wp_post );
		$title   = get_the_title( $wp_post );
		$cats    = get_the_category( $wp_post->ID );
		$cat     = ! empty( $cats ) ? $cats[0]->name : '';
		$excerpt = wp_trim_words( wp_strip_all_tags( strip_shortcodes( $wp_post->post_excerpt ? $wp_post->post_excerpt : $wp_post->post_content ) ), 28 );

		/* picture: featured image, otherwise the first image in the post */
		$thumb = get_the_post_thumbnail( $wp_post, 'medium', array( 'alt' => '', 'loading' => 'lazy' ) );
		if ( ! $thumb ) {
			$img = csd_post_image( $wp_post );
			if ( $img ) {
				$thumb = '<img src="' . esc_url( $img ) . '" alt="" loading="lazy" />';
			}
		}

		$out .= '<article class="vb-feed__row">';
		if ( $thumb ) {
			$out .= '<a class="vb-feed__thumb" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>';
		}
		$out .= '<div class="vb-feed__body">';
		if ( $cat ) {
			$out .= '<span class="vb-feed__cat">' . esc_html( $cat ) . '</span>';
		}
		$out .= '<h3 class="vb-feed__title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>';
		if ( $excerpt ) {
			$out .= '<p class="vb-feed__excerpt">' . esc_html( $excerpt ) . '</p>';
		}
		$out .= '<p class="vb-feed__meta">' . esc_html( get_the_date( '', $wp_post ) ) . '</p>';
		$out .= '</div>';
		$out .= '</article>';
	}
	$out .= '</div>';
	$out .= '<p class="vb-feed__next-wrap"><a class="vb-feed__next" href="' . esc_url( home_url( '/page/2/' ) ) . '">Nächste Seite →</a></p>';

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
		array( 'CSD auf Facebook',       'https://www.facebook.com/csd-darmstadt' ),
		array( 'vielbunt auf Instagram',  'https://instagram.com/vielbunt' ),
		array( 'Datenschutzerklärung',    'https://www.csd-darmstadt.de/datenschutzerklaerung/' ),
		array( 'Impressum',               'https://www.csd-darmstadt.de/impressum/' ),
		array( 'Kontakt',                 'https://www.csd-darmstadt.de/kontakt/' ),
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

/* ──────────────────────────────────────────────────────────────
   Spendenkampagne: Zielmesser + Spenden-Button (Donorbox)
   Gesteuert über den Customizer (Design → Anpassen → "Spendenkampagne").
   Auf csd-darmstadt.de ist die Kampagne csd-darmstadt-2026 per Default
   aktiv und mit den von Donorbox vorgegebenen Einbettungscodes vorbelegt.
   Der Block rendert nur etwas, wenn die Kampagne aktiviert ist UND ein
   Einbettungscode hinterlegt wurde – so bleibt die Seite sauber, sobald
   die Kampagne vorbei ist und deaktiviert wird.
   ────────────────────────────────────────────────────────────── */

/* Von Donorbox vorgegebene Einbettungscodes für die CSD-2026-Kampagne.
   Dienen als Default; im Customizer jederzeit überschreibbar.

   Zielmesser: Donorbox' Code-Generator liefert für den „Ziel-Messer" leider
   den FORMULAR-Code (type="donation_form") – der rendert das ganze Formular,
   nicht den Balken. Der reine Fortschrittsbalken ist aber ein simpler iframe
   auf donorbox.org/embed/<kampagne> mit ?only_donation_meter=true (so rendert
   ihn auch das Donorbox-Widget intern). donation_meter_color setzt die
   Balkenfarbe (%23 = #), hier das CSD-Lila #6546b4. */
define( 'CSD_CAMPAIGN_METER_DEFAULT', '<iframe src="https://donorbox.org/embed/csd-darmstadt-2026?only_donation_meter=true&amp;donation_meter_color=%236546b4" name="donorbox-goal-meter" seamless="seamless" scrolling="no" frameborder="0" loading="lazy" width="100%" height="100" style="max-width:480px;min-width:250px;min-height:90px;border:0;background:transparent;"></iframe>' );
define( 'CSD_CAMPAIGN_BUTTON_DEFAULT', '<a class="dbox-donation-page-button" href="https://donorbox.org/csd-darmstadt-2026?" style="background: rgb(101, 70, 180); color: rgb(255, 255, 255); text-decoration: none; font-family: Verdana, sans-serif; display: block; gap: 8px; width: fit-content; font-size: 16px; border-radius: 5px; line-height: 24px; padding: 8px 24px; margin-right: auto;"><img role="presentation" src="https://donorbox.org/images/white_logo.svg"> Spenden</a>' );

function csd_block_campaign( $attributes = array() ) {
	if ( ! get_theme_mod( 'csd_campaign_enable', false ) ) {
		return '';
	}

	$meter  = get_theme_mod( 'csd_campaign_meter',  CSD_CAMPAIGN_METER_DEFAULT );
	$button = get_theme_mod( 'csd_campaign_button', CSD_CAMPAIGN_BUTTON_DEFAULT );

	// ohne Inhalt nichts ausgeben
	if ( '' === trim( (string) $meter ) && '' === trim( (string) $button ) ) {
		return '';
	}

	$heading = get_theme_mod( 'csd_campaign_heading', 'Unser Spendenziel für den CSD 2026' );
	$text    = get_theme_mod( 'csd_campaign_text', '' );

	ob_start();
	?>
	<section class="vb-campaign">
		<div class="vb-campaign__inner">
			<?php if ( '' !== trim( (string) $heading ) ) : ?>
				<h2 class="vb-campaign__title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $text ) ) : ?>
				<p class="vb-campaign__text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $meter ) ) : ?>
				<div class="vb-campaign__meter"><?php echo $meter; // phpcs:ignore WordPress.Security.EscapeOutput -- Donorbox-Einbettung, beim Speichern per Capability gefiltert ?></div>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $button ) ) : ?>
				<div class="vb-campaign__cta"><?php echo $button; // phpcs:ignore WordPress.Security.EscapeOutput -- s. o. ?></div>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* Die Kampagne hängen wir per render_block-Filter direkt an den Hero-Block an.
   Das ist zuverlässiger als ein Block-Kommentar im front-page-Template: Sobald
   das Template einmal im Site-Editor angepasst wurde, liegt es in der Datenbank
   und Änderungen an der Theme-Datei werden ignoriert. Über den Hero-Block als
   Anker erscheint die Kampagne dagegen immer an der richtigen Stelle. */
function csd_render_campaign_after_hero( $block_content, $block ) {
	if ( ! empty( $block['blockName'] ) && 'csd/hero' === $block['blockName'] && is_front_page() ) {
		$block_content .= csd_block_campaign();
	}
	return $block_content;
}
add_filter( 'render_block', 'csd_render_campaign_after_hero', 10, 2 );

/* Spenden-Buttons ohne laufende Kampagne: Links auf eine CSD-Donorbox-Kampagne
   (z. B. csd-darmstadt-2026) zeigen dann auf die allgemeine Spendenseite.
   Läuft per render_block, damit es auch für einen im Site-Editor angepassten
   Header gilt. Ist die Kampagne an, bleibt alles wie eingetragen. */
define( 'CSD_DONATE_URL_DEFAULT', 'https://www.vielbunt.org/spenden/' );

function csd_donate_url() {
	$url = get_theme_mod( 'csd_donate_url', CSD_DONATE_URL_DEFAULT );
	return $url ? $url : CSD_DONATE_URL_DEFAULT;
}

function csd_donate_links_without_campaign( $content, $block ) {
	if ( empty( $block['blockName'] ) || 'core/button' !== $block['blockName'] || get_theme_mod( 'csd_campaign_enable', false ) ) {
		return $content;
	}
	return preg_replace( '#href="https?://(?:www\.)?donorbox\.org/csd-darmstadt-[^"]*"#i', 'href="' . esc_url( csd_donate_url() ) . '"', $content );
}
add_filter( 'render_block', 'csd_donate_links_without_campaign', 10, 2 );

/* Customizer: Sektion "Spendenkampagne" */
function csd_campaign_sanitize_bool( $value ) {
	return (bool) $value;
}

/* Roh-HTML-Einbettung (Donorbox liefert <script> + Custom-Elements).
   Gleiches Muster wie das Custom-HTML-Widget im Core: Wer Roh-HTML setzen
   darf (Admins, die ohnehin den Customizer bedienen), behält den Code 1:1,
   alle anderen bekommen wp_kses_post. */
function csd_campaign_sanitize_embed( $value ) {
	if ( current_user_can( 'unfiltered_html' ) ) {
		return $value;
	}
	return wp_kses_post( $value );
}

function csd_customize_campaign( $wp_customize ) {
	$wp_customize->add_section( 'csd_campaign', array(
		'title'       => __( 'Spendenkampagne', 'csd-darmstadt' ),
		'priority'    => 130,
		'description' => __( 'Zielmesser (Fortschrittsbalken) und Spenden-Button von Donorbox auf der Startseite. Die Einbettungscodes findest du in Donorbox unter Kampagne → „Ziel-Messer" bzw. „Spenden-Button".', 'csd-darmstadt' ),
	) );

	$wp_customize->add_setting( 'csd_campaign_enable', array(
		'default'           => false,
		'type'              => 'theme_mod',
		'sanitize_callback' => 'csd_campaign_sanitize_bool',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'csd_campaign_enable', array(
		'section' => 'csd_campaign',
		'type'    => 'checkbox',
		'label'   => __( 'Kampagne auf der Startseite anzeigen', 'csd-darmstadt' ),
	) );

	$wp_customize->add_setting( 'csd_donate_url', array(
		'default'           => CSD_DONATE_URL_DEFAULT,
		'type'              => 'theme_mod',
		'sanitize_callback' => 'esc_url_raw',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'csd_donate_url', array(
		'section'     => 'csd_campaign',
		'type'        => 'url',
		'label'       => __( 'Spenden-Link ohne Kampagne', 'csd-darmstadt' ),
		'description' => __( 'Solange die Kampagne aus ist, führen alle Spenden-Buttons (Header, Mobilmenü), die auf eine Donorbox-Kampagne zeigen, hierhin.', 'csd-darmstadt' ),
	) );

	$wp_customize->add_setting( 'csd_campaign_heading', array(
		'default'           => 'Unser Spendenziel für den CSD 2026',
		'type'              => 'theme_mod',
		'sanitize_callback' => 'sanitize_text_field',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'csd_campaign_heading', array(
		'section' => 'csd_campaign',
		'type'    => 'text',
		'label'   => __( 'Überschrift', 'csd-darmstadt' ),
	) );

	$wp_customize->add_setting( 'csd_campaign_text', array(
		'default'           => '',
		'type'              => 'theme_mod',
		'sanitize_callback' => 'sanitize_textarea_field',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'csd_campaign_text', array(
		'section' => 'csd_campaign',
		'type'    => 'textarea',
		'label'   => __( 'Einleitungstext (optional)', 'csd-darmstadt' ),
	) );

	$wp_customize->add_setting( 'csd_campaign_meter', array(
		'default'           => CSD_CAMPAIGN_METER_DEFAULT,
		'type'              => 'theme_mod',
		'sanitize_callback' => 'csd_campaign_sanitize_embed',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'csd_campaign_meter', array(
		'section'     => 'csd_campaign',
		'type'        => 'textarea',
		'label'       => __( 'Zielmesser – Einbettungscode', 'csd-darmstadt' ),
		'description' => __( 'Kompletten Code aus Donorbox („Ziel-Messer" → Code einbetten) hier einfügen.', 'csd-darmstadt' ),
	) );

	$wp_customize->add_setting( 'csd_campaign_button', array(
		'default'           => CSD_CAMPAIGN_BUTTON_DEFAULT,
		'type'              => 'theme_mod',
		'sanitize_callback' => 'csd_campaign_sanitize_embed',
		'transport'         => 'refresh',
	) );
	$wp_customize->add_control( 'csd_campaign_button', array(
		'section'     => 'csd_campaign',
		'type'        => 'textarea',
		'label'       => __( 'Spenden-Button – Einbettungscode', 'csd-darmstadt' ),
		'description' => __( 'Kompletten Code aus Donorbox („Spenden-Button" → Code einbetten) hier einfügen. Leer lassen, um keinen Button anzuzeigen.', 'csd-darmstadt' ),
	) );
}
add_action( 'customize_register', 'csd_customize_campaign' );

/* register all our custom blocks with WordPress */
function csd_register_blocks() {
	$common = array( 'api_version' => 3 );

	/* hero + quicklinks keep their content in the csd_frontpage option, not in
	   block attributes. "preview" is only sent by the editor sidebar and never
	   saved into the template */
	$preview = array( 'preview' => array( 'type' => 'object' ) );
	register_block_type( 'csd/hero', array_merge( $common, array(
		'attributes'      => $preview,
		'render_callback' => 'csd_block_hero',
	) ) );
	register_block_type( 'csd/quicklinks', array_merge( $common, array(
		'attributes'      => $preview,
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
	// Hinweis: Die Spendenkampagne ist KEIN platzierbarer Block – sie wird per
	// render_block-Filter automatisch hinter den Hero gehängt (s. o.).
}
add_action( 'init', 'csd_register_blocks' );

/* load the editor JS so our blocks have a proper sidebar UI */
function csd_block_editor_assets() {
	wp_enqueue_script(
		'csd-blocks',
		get_stylesheet_directory_uri() . '/assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n', 'wp-block-editor', 'wp-components', 'wp-core-data' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
	wp_add_inline_script(
		'csd-blocks',
		'window.csdFrontpage = ' . wp_json_encode( csd_frontpage_editor_data() ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'csd_block_editor_assets' );

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
