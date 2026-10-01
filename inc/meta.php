<?php
/**
 * <meta name="description"> for search engines and link previews.
 *
 * Open Graph (og:title, og:image, …) is done by the "OG" plugin on this site,
 * so we only add the description here. If an SEO plugin takes over one day,
 * we step back on our own.
 *
 * Front page: the hero lead from the editor. Posts and pages: the excerpt,
 * otherwise the beginning of the text. Everything else: the tagline.
 *
 * @package csd-darmstadt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function csd_meta_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function csd_meta_description() {
	$text = '';
	if ( is_front_page() ) {
		$hero = csd_frontpage_get()['hero'];
		$text = '' !== $hero['lead'] ? $hero['lead'] : csd_hero_defaults()['lead'];
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$text = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$text = term_description();
	}
	if ( '' === trim( wp_strip_all_tags( (string) $text ) ) ) {
		$text = get_bloginfo( 'description' );
	}
	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );
	// Suchmaschinen zeigen gut 150 Zeichen, danach wird abgeschnitten
	if ( mb_strlen( $text ) > 160 ) {
		$text = rtrim( mb_substr( $text, 0, 157 ), " ,.;:-" ) . '…';
	}
	return $text;
}

function csd_meta_head() {
	if ( csd_meta_seo_plugin_active() || is_404() || is_search() ) {
		return;
	}
	$desc = csd_meta_description();
	if ( '' !== $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'csd_meta_head', 1 );
