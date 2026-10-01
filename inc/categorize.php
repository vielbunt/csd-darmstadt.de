<?php
/**
 * Categories that make sense.
 *
 * Every post has "News" (the front page link "Alle Ankündigungen" shows that
 * category) plus exactly one topic: Programm, Fotos & Rückblick, Aktionswoche,
 * Motto, Mitmachen & Unterstützen, Andere CSDs, Verein, else CSD Allgemein.
 *
 * 1. New posts get "News" and a topic when they are published or scheduled,
 *    but only if nobody picked a topic by hand.
 * 2. One-time cleanup (October 2026) exactly as reviewed
 *    (inc/data/kategorien-2026-10.json). Old assignments are kept in the
 *    option csd_kategorien_backup.
 *
 * @package csd-darmstadt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* topic => category slug. three of them are created by the cleanup */
function csd_cat_topics() {
	return array(
		'programm'     => array( 'slug' => 'programm', 'name' => 'Programm' ),
		'rueckblick'   => array( 'slug' => 'fotos-rueckblick', 'name' => 'Fotos & Rückblick' ),
		'aktionswoche' => array( 'slug' => 'aktionswoche', 'name' => 'Aktionswoche' ),
		'motto'        => array( 'slug' => 'motto', 'name' => 'Motto' ),
		'mitmachen'    => array( 'slug' => 'mitmachen-unterstuetzen', 'name' => 'Mitmachen & Unterstützen' ),
		'andere'       => array( 'slug' => 'andere-csds', 'name' => 'Andere CSDs' ),
		'verein'       => array( 'slug' => 'verein', 'name' => 'Verein' ),
		'allgemein'    => array( 'slug' => 'csd-allgemein', 'name' => 'CSD Allgemein' ),
	);
}

/* first matching rule wins (title, lower case) */
function csd_cat_rules() {
	return array(
		'rueckblick'   => 'aus der sicht von|fotos vom|bilder|highlights|danke,? darmstadt|rückblick|wir sagen danke|danke an unsere|wir danken|vielen dank|danke fürs spenden|setzt starkes zeichen',
		'andere'       => '^mit vielbunt|^gemeinsame fahrt|^gemeinsam zum csd|dyke\*?march|pride march',
		'aktionswoche' => 'aktionswoche|pride week|queergarten|open[- ]air[- ]kino|queerslam|poetry slam|picknick|treffbunt|salsa special|mahnwache',
		'programm'     => 'bühne|podiumsdiskussion|moderation|drag[- ]?show|zeitplan|programm|kundgebung|eröffnung|rede von|gaga|after-?show|barriere|awareness|infostände|der tag im überblick|morgen am',
		'motto'        => 'motto|trailer|teaser|schirr?m(herr|frau)|thema für den csd|politik-workshop|zeigt eure fahnen',
		'mitmachen'    => 'helfer\*?innen|unterstützung|spende|pride-?bändchen|bändchen|merch|anmeldung|mitmachen|sponsor|braucht deine|schnuppersitzung',
		'verein'       => 'demonstration am|demo in darmstadt|wähl liebe',
	);
}

function csd_cat_topic_for( $title ) {
	$t = mb_strtolower( wp_strip_all_tags( html_entity_decode( (string) $title, ENT_QUOTES, 'UTF-8' ) ) );
	foreach ( csd_cat_rules() as $topic => $rx ) {
		if ( preg_match( '/' . $rx . '/u', $t ) ) {
			return $topic;
		}
	}
	return 'allgemein';
}

/* term id for a topic, creates the category if it does not exist yet */
function csd_cat_id( $topic ) {
	$def  = csd_cat_topics()[ $topic ];
	$term = get_term_by( 'slug', $def['slug'], 'category' );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $def['name'], 'category', array( 'slug' => $def['slug'] ) );
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
}

function csd_cat_news_id() {
	$term = get_term_by( 'slug', 'news', 'category' );
	return $term ? (int) $term->term_id : 0;
}

/* new posts: News + topic, unless a topic was picked by hand */
function csd_categorize_on_publish( $post_id, $post, $update, $post_before ) {
	if ( 'post' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'future' ), true ) ) {
		return;
	}
	if ( $post_before && in_array( $post_before->post_status, array( 'publish', 'future' ), true ) ) {
		return;
	}
	$current   = array_map( 'intval', wp_get_post_categories( $post_id ) );
	$topic_ids = array();
	foreach ( array_keys( csd_cat_topics() ) as $topic ) {
		$term = get_term_by( 'slug', csd_cat_topics()[ $topic ]['slug'], 'category' );
		if ( $term ) {
			$topic_ids[] = (int) $term->term_id;
		}
	}
	$new = $current;
	if ( ! array_intersect( $current, $topic_ids ) ) {
		$new[] = csd_cat_id( csd_cat_topic_for( $post->post_title ) );
	}
	$new[] = csd_cat_news_id();
	$new   = array_diff( $new, array( (int) get_option( 'default_category' ) ) ); // "Uncategorized" raus
	$new   = array_values( array_unique( array_filter( $new ) ) );
	sort( $new );
	sort( $current );
	if ( $new !== $current ) {
		wp_set_post_categories( $post_id, $new );
	}
}
add_action( 'wp_after_insert_post', 'csd_categorize_on_publish', 10, 4 );

/* one-time cleanup exactly as reviewed. posts changed since then get the rules */
function csd_once_categories() {
	$file = __DIR__ . '/data/kategorien-2026-10.json';
	$map  = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : null;
	if ( ! is_array( $map ) ) {
		return 'Datendatei fehlt, nichts geändert';
	}
	// "Programm-News" heißt jetzt nur noch "Programm"
	$prog = get_term_by( 'slug', 'programm', 'category' );
	if ( $prog && 'Programm' !== $prog->name ) {
		wp_update_term( $prog->term_id, 'category', array( 'name' => 'Programm' ) );
	}
	$ids = array();
	foreach ( array_keys( csd_cat_topics() ) as $topic ) {
		$ids[ $topic ] = csd_cat_id( $topic );
	}
	$news = csd_cat_news_id();

	wp_defer_term_counting( true );
	$backup = array();
	$counts = array();
	foreach ( $map as $id => $entry ) {
		$post = get_post( (int) $id );
		if ( ! $post || 'post' !== $post->post_type ) {
			continue;
		}
		$current = array_map( 'intval', wp_get_post_categories( $post->ID ) );
		sort( $current );
		$backup[ $post->ID ] = $current;
		$topic = ( $current === $entry['old'] ) ? $entry['topic'] : csd_cat_topic_for( $post->post_title );
		wp_set_post_categories( $post->ID, array_values( array_filter( array( $news, $ids[ $topic ] ) ) ) );
		$counts[ $topic ] = isset( $counts[ $topic ] ) ? $counts[ $topic ] + 1 : 1;
	}
	wp_defer_term_counting( false );
	update_option( 'csd_kategorien_backup', $backup, false );
	$parts = array();
	foreach ( $counts as $topic => $n ) {
		$parts[] = csd_cat_topics()[ $topic ]['name'] . ' ' . $n;
	}
	return array_sum( $counts ) . ' Beiträge eingeordnet: ' . implode( ', ', $parts ) . '. Backup in csd_kategorien_backup';
}

/* menu: "Alle Beiträge" as last item under "Der CSD". backup: csd_menu_backup */
function csd_once_menu() {
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( ! $posts_page ) {
		return 'Keine Beitragsseite, Menü nicht geändert';
	}
	$all     = get_permalink( $posts_page );
	$changed = vielbunt_once_edit_submenu(
		'Der CSD',
		function ( $block ) use ( $posts_page, $all ) {
			foreach ( $block['innerBlocks'] as $child ) {
				if ( isset( $child['attrs']['url'] ) && untrailingslashit( $child['attrs']['url'] ) === untrailingslashit( $all ) ) {
					return $block; // schon drin
				}
			}
			$children   = $block['innerBlocks'];
			$children[] = vielbunt_once_nav_link( 'Alle Beiträge', $all, $posts_page );
			return vielbunt_once_set_children( $block, $children );
		},
		'csd_menu_backup'
	);
	return $changed ? 'Menü "' . implode( '", "', $changed ) . '": "Alle Beiträge" unter "Der CSD" ergänzt' : 'Kein Untermenü "Der CSD" gefunden, nichts geändert';
}
