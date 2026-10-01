<?php
/**
 * Front page content (hero + Schnellzugriff) as ONE wp_options row.
 *
 * Why: until 2.1.x the hero texts and tile images lived as block attributes
 * inside the front-page template. WordPress re-serialises templates in PHP
 * on save, which turns {"0":{…},"1":{…}} into a JSON list [{…},{…}]. The
 * editor then rejects that list for an "object" attribute and silently drops
 * ALL tile images the next time the template is opened. On top of that every
 * theme re-upload or template reset threw the attributes away.
 *
 * Now the block attributes are not used for content at all. Everything lives
 * in the option "csd_frontpage", which
 *  - is registered with a strict REST schema (wp/v2/settings),
 *  - is edited in the site editor through the core "site" entity, so it is
 *    saved together with everything else when you click "Speichern",
 *  - never depends on the theme folder, the template or a theme update.
 *
 * @package csd-darmstadt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CSD_FRONTPAGE_OPTION', 'csd_frontpage' );
define( 'CSD_FRONTPAGE_TILES', 8 );

/* empty, fully shaped value. every stored value always has exactly this shape */
function csd_frontpage_empty() {
	$tiles = array();
	for ( $i = 0; $i < CSD_FRONTPAGE_TILES; $i++ ) {
		$tiles[] = array( 'label' => '', 'url' => '', 'imgId' => 0, 'imgUrl' => '' );
	}
	return array(
		'hero'       => array(
			'kicker'    => '',
			'title'     => '',
			'lead'      => '',
			'btn1Label' => '',
			'btn1Url'   => '',
			'btn2Label' => '',
			'btn2Url'   => '',
			'bgId'      => 0,
			'bgUrl'     => '',
			'flagDate'  => '',
		),
		'quicklinks' => array(
			'heading' => '',
			'tiles'   => $tiles,
		),
	);
}

/* REST schema for wp/v2/settings. has to match csd_frontpage_empty() exactly */
function csd_frontpage_schema() {
	$str  = array( 'type' => 'string' );
	$int  = array( 'type' => 'integer' );
	$hero = array();
	foreach ( csd_frontpage_empty()['hero'] as $key => $default ) {
		$hero[ $key ] = is_int( $default ) ? $int : $str;
	}
	return array(
		'type'                 => 'object',
		'additionalProperties' => false,
		'properties'           => array(
			'hero'       => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => $hero,
			),
			'quicklinks' => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'heading' => $str,
					'tiles'   => array(
						'type'  => 'array',
						'items' => array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => array(
								'label'  => $str,
								'url'    => $str,
								'imgId'  => $int,
								'imgUrl' => $str,
							),
						),
					),
				),
			),
		),
	);
}

/* tiles may come in as a list or as a map with numeric keys ("0", "1", …).
   both are accepted, the result is always a list of CSD_FRONTPAGE_TILES */
function csd_frontpage_pick( $list, $i ) {
	if ( ! is_array( $list ) ) {
		return array();
	}
	if ( isset( $list[ $i ] ) && is_array( $list[ $i ] ) ) {
		return $list[ $i ];
	}
	if ( isset( $list[ (string) $i ] ) && is_array( $list[ (string) $i ] ) ) {
		return $list[ (string) $i ];
	}
	return array();
}

function csd_frontpage_clean_field( $key, $value ) {
	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}
	if ( 'Url' === substr( $key, -3 ) || 'url' === $key ) {
		return esc_url_raw( trim( (string) $value ) );
	}
	if ( 'lead' === $key ) {
		return sanitize_textarea_field( (string) $value );
	}
	if ( 'flagDate' === $key ) {
		return csd_flag_clean_date( $value );
	}
	return sanitize_text_field( (string) $value );
}

/* brings any input (REST, migration, editor preview) into the exact shape */
function csd_frontpage_sanitize( $value ) {
	if ( is_object( $value ) ) {
		$value = json_decode( wp_json_encode( $value ), true );
	}
	$value = is_array( $value ) ? $value : array();
	$out   = csd_frontpage_empty();

	$hero = ( isset( $value['hero'] ) && is_array( $value['hero'] ) ) ? $value['hero'] : array();
	foreach ( $out['hero'] as $key => $default ) {
		if ( ! array_key_exists( $key, $hero ) ) {
			continue;
		}
		$out['hero'][ $key ] = is_int( $default ) ? absint( $hero[ $key ] ) : csd_frontpage_clean_field( $key, $hero[ $key ] );
	}

	$ql = ( isset( $value['quicklinks'] ) && is_array( $value['quicklinks'] ) ) ? $value['quicklinks'] : array();
	if ( isset( $ql['heading'] ) ) {
		$out['quicklinks']['heading'] = csd_frontpage_clean_field( 'heading', $ql['heading'] );
	}
	$tiles = isset( $ql['tiles'] ) ? $ql['tiles'] : array();
	for ( $i = 0; $i < CSD_FRONTPAGE_TILES; $i++ ) {
		$t = csd_frontpage_pick( $tiles, $i );
		foreach ( $out['quicklinks']['tiles'][ $i ] as $key => $default ) {
			if ( ! array_key_exists( $key, $t ) ) {
				continue;
			}
			$out['quicklinks']['tiles'][ $i ][ $key ] = is_int( $default ) ? absint( $t[ $key ] ) : csd_frontpage_clean_field( $key, $t[ $key ] );
		}
	}
	return $out;
}

function csd_frontpage_register_setting() {
	register_setting(
		'csd_frontpage',
		CSD_FRONTPAGE_OPTION,
		array(
			'type'              => 'object',
			'label'             => 'Startseite: Hero und Schnellzugriff',
			'description'       => 'Startseite: Hero und Schnellzugriff',
			'default'           => csd_frontpage_empty(),
			'sanitize_callback' => 'csd_frontpage_sanitize',
			'show_in_rest'      => array(
				'name'   => CSD_FRONTPAGE_OPTION,
				'schema' => csd_frontpage_schema(),
			),
		)
	);
}
add_action( 'init', 'csd_frontpage_register_setting' );

/* stored value, always fully shaped */
function csd_frontpage_get() {
	return csd_frontpage_sanitize( get_option( CSD_FRONTPAGE_OPTION, array() ) );
}

/* what the render callbacks use. inside the editor the sidebar sends the
   unsaved state as "preview" attribute so the canvas updates live. only
   people who may edit the site get to see a preview, everyone else gets
   the stored value */
function csd_frontpage_for_render( $attributes ) {
	if ( ! empty( $attributes['preview'] ) && is_array( $attributes['preview'] )
		&& defined( 'REST_REQUEST' ) && REST_REQUEST
		&& current_user_can( 'edit_theme_options' ) ) {
		return csd_frontpage_sanitize( $attributes['preview'] );
	}
	return csd_frontpage_get();
}

/* image URL: prefer the attachment ID (survives regenerated sizes and domain
   changes), fall back to the stored URL */
function csd_frontpage_image( $id, $url, $size = 'large' ) {
	if ( $id ) {
		$src = wp_get_attachment_image_url( (int) $id, $size );
		if ( $src ) {
			return $src;
		}
	}
	return (string) $url;
}

/* first non-empty value wins */
function csd_frontpage_first() {
	foreach ( func_get_args() as $v ) {
		if ( is_scalar( $v ) && '' !== (string) $v && 0 !== $v && '0' !== $v ) {
			return $v;
		}
	}
	return '';
}

/* finds the attributes of the first block called $name, also in nested blocks */
function csd_frontpage_find_block_attrs( $blocks, $name ) {
	foreach ( $blocks as $block ) {
		if ( isset( $block['blockName'] ) && $name === $block['blockName'] ) {
			return is_array( $block['attrs'] ) ? $block['attrs'] : array();
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = csd_frontpage_find_block_attrs( $block['innerBlocks'], $name );
			if ( null !== $found ) {
				return $found;
			}
		}
	}
	return null;
}

/* one-time migration from 2.1.x: picks up the content from the customised
   front-page template (block attributes) and from the old options
   csd_hero_settings / csd_quicklinks_settings. block attributes win because
   that is what the front page showed. the old options stay untouched as backup */
function csd_frontpage_migrate() {
	if ( false !== get_option( CSD_FRONTPAGE_OPTION, false ) ) {
		return;
	}

	$hero_attrs = array();
	$ql_attrs   = array();
	$template   = function_exists( 'get_block_template' ) ? get_block_template( get_stylesheet() . '//front-page' ) : null;
	if ( $template && ! empty( $template->content ) ) {
		$blocks     = parse_blocks( $template->content );
		$hero_attrs = (array) csd_frontpage_find_block_attrs( $blocks, 'csd/hero' );
		$ql_attrs   = (array) csd_frontpage_find_block_attrs( $blocks, 'csd/quicklinks' );
	}
	$hero_old = (array) get_option( 'csd_hero_settings', array() );
	$ql_old   = (array) get_option( 'csd_quicklinks_settings', array() );

	$new = csd_frontpage_empty();
	foreach ( $new['hero'] as $key => $default ) {
		if ( 'flagDate' === $key ) {
			$new['hero'][ $key ] = CSD_FLAG_DATE_INITIAL;
			continue;
		}
		$new['hero'][ $key ] = csd_frontpage_first(
			isset( $hero_attrs[ $key ] ) ? $hero_attrs[ $key ] : '',
			isset( $hero_old[ $key ] ) ? $hero_old[ $key ] : ''
		);
	}

	$heading = csd_frontpage_first(
		isset( $ql_attrs['heading'] ) ? $ql_attrs['heading'] : '',
		isset( $ql_old['heading'] ) ? $ql_old['heading'] : ''
	);
	$new['quicklinks']['heading'] = ( 'Schnellzugriff' === $heading ) ? '' : $heading;

	$attr_tiles  = isset( $ql_attrs['tiles'] ) ? $ql_attrs['tiles'] : array();
	$old_tiles   = isset( $ql_old['tiles'] ) ? $ql_old['tiles'] : array();
	$attr_images = isset( $ql_attrs['images'] ) ? $ql_attrs['images'] : array();
	$old_images  = isset( $ql_old['images'] ) ? $ql_old['images'] : array();
	for ( $i = 0; $i < CSD_FRONTPAGE_TILES; $i++ ) {
		$at = csd_frontpage_pick( $attr_tiles, $i );
		$ot = csd_frontpage_pick( $old_tiles, $i );
		$ai = csd_frontpage_pick( $attr_images, $i );
		$oi = csd_frontpage_pick( $old_images, $i );
		/* take the image as a pair, otherwise id and url could come from different sources */
		$img = ! empty( $ai['url'] ) || ! empty( $ai['id'] ) ? $ai : $oi;

		$new['quicklinks']['tiles'][ $i ] = array(
			'label'  => csd_frontpage_first( isset( $at['label'] ) ? $at['label'] : '', isset( $ot['label'] ) ? $ot['label'] : '' ),
			'url'    => csd_frontpage_first( isset( $at['url'] ) ? $at['url'] : '', isset( $ot['url'] ) ? $ot['url'] : '' ),
			'imgId'  => isset( $img['id'] ) ? (int) $img['id'] : 0,
			'imgUrl' => isset( $img['url'] ) ? (string) $img['url'] : '',
		);
	}

	update_option( CSD_FRONTPAGE_OPTION, csd_frontpage_sanitize( $new ) );

	if ( $template && 'custom' === $template->source && ! empty( $template->wp_id ) ) {
		csd_frontpage_clean_template( (int) $template->wp_id, $template->content );
	}
}
add_action( 'init', 'csd_frontpage_migrate', 20 );

/* the old front-page.html had plain HTML comments ("<!-- HERO … -->") inside
   the <main> group. the editor flags that group as "invalid content" because
   of them. we remove exactly those old comments from the customised template
   once, nothing else. straight via $wpdb so no content filter touches the
   template (this can run on a normal page view without a logged in user) */
function csd_frontpage_clean_template( $post_id, $content ) {
	$cleaned = preg_replace(
		'/[ \t]*<!--\s*(?:HERO \(|Die Spendenkampagne \(Zielmesser|SCHNELLZUGRIFF|AKTUELLES\s|CTA-BAND|WEITERE ANKÜNDIGUNGEN)(?:(?!-->|<!--)[\s\S])*-->[ \t]*\n?/u',
		'',
		$content
	);
	if ( ! is_string( $cleaned ) || $cleaned === $content ) {
		return;
	}
	global $wpdb;
	$wpdb->update( $wpdb->posts, array( 'post_content' => $cleaned ), array( 'ID' => $post_id ) );
	clean_post_cache( $post_id );
}

/* 2.3: the date in the graphic became a field. sites that already have the
   option get the date that was baked into the old artwork once, after that
   the key exists and this does nothing anymore */
define( 'CSD_FLAG_DATE_INITIAL', '21.08.2027' );

function csd_frontpage_upgrade() {
	$raw = get_option( CSD_FRONTPAGE_OPTION, false );
	if ( ! is_array( $raw ) || ! isset( $raw['hero'] ) || ! is_array( $raw['hero'] ) || array_key_exists( 'flagDate', $raw['hero'] ) ) {
		return;
	}
	$raw['hero']['flagDate'] = CSD_FLAG_DATE_INITIAL;
	update_option( CSD_FRONTPAGE_OPTION, csd_frontpage_sanitize( $raw ) );
}
add_action( 'init', 'csd_frontpage_upgrade', 21 );

/* defaults for the editor sidebar (placeholders), so they are only defined once in PHP */
function csd_frontpage_editor_data() {
	$tiles = array();
	foreach ( csd_default_tiles() as $t ) {
		$tiles[] = array( 'label' => $t['label'], 'url' => $t['url'] );
	}
	return array(
		'option'   => CSD_FRONTPAGE_OPTION,
		'defaults' => array(
			'hero'    => csd_hero_defaults(),
			'heading' => 'Schnellzugriff',
			'tiles'   => $tiles,
		),
	);
}
