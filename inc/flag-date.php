<?php
/**
 * Date inside the CSD graphic in the hero (assets/flag.svg).
 *
 * The graphic itself has no fixed date anymore, just a placeholder
 * (<g id="csd-flag-date">). The date comes from the hero field "Datum in der
 * Grafik" and is set here from the Cera Pro Bold outlines in
 * inc/flag-glyphs.php, with exactly the rules of the original artwork:
 * same size, tracking -50, both lines centred on the dark box. So it looks
 * like it was made in the design tool, without loading any font.
 *
 * "21.08.2027" becomes two lines ("21.08" and "2027"), anything else is set
 * as one line. Only digits and dots are possible.
 *
 * @package csd-darmstadt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* measured from the original 2026 artwork, see tools/flag-glyphs.py */
define( 'CSD_FLAG_SCALE', 0.117859 );
define( 'CSD_FLAG_TRACKING', -50 );
define( 'CSD_FLAG_CENTER_X', 859.183 );
define( 'CSD_FLAG_BASELINES', array( 410.845, 523.345 ) );

/* only digits and dots survive, everything else could not be drawn anyway */
function csd_flag_clean_date( $value ) {
	$value = preg_replace( '/[^0-9.]/', '', (string) $value );
	return substr( $value, 0, 12 );
}

/* "21.08.2027" -> array( "21.08", "2027" ), "8.2027" etc. stays one line */
function csd_flag_date_lines( $date ) {
	$date = csd_flag_clean_date( $date );
	if ( '' === $date ) {
		return array();
	}
	if ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $m ) ) {
		return array( sprintf( '%02d.%02d', $m[1], $m[2] ), $m[3] );
	}
	return array( $date );
}

function csd_flag_date_paths( $date ) {
	static $glyphs = null;
	if ( null === $glyphs ) {
		$glyphs = require __DIR__ . '/flag-glyphs.php';
	}
	$lines = csd_flag_date_lines( $date );
	if ( 1 === count( $lines ) ) {
		// eine Zeile sitzt mittig zwischen den beiden Grundlinien
		$baselines = array( ( CSD_FLAG_BASELINES[0] + CSD_FLAG_BASELINES[1] ) / 2 + 20 );
	} else {
		$baselines = CSD_FLAG_BASELINES;
	}

	$out = '';
	foreach ( $lines as $n => $line ) {
		$chars = str_split( $line );
		$width = 0;
		foreach ( $chars as $c ) {
			$width += $glyphs[ $c ]['w'];
		}
		$width += CSD_FLAG_TRACKING * ( count( $chars ) - 1 );
		$x = CSD_FLAG_CENTER_X - $width * CSD_FLAG_SCALE / 2;
		foreach ( $chars as $c ) {
			$out .= sprintf(
				'<path d="%1$s" transform="translate(%2$.4F,%3$.4F) scale(%4$F,-%4$F)" style="fill:white;fill-rule:nonzero;" />',
				$glyphs[ $c ]['d'],
				$x,
				$baselines[ $n ],
				CSD_FLAG_SCALE
			);
			$x += ( $glyphs[ $c ]['w'] + CSD_FLAG_TRACKING ) * CSD_FLAG_SCALE;
		}
	}
	return $out;
}

/* the complete graphic as inline SVG, ready for the hero */
function csd_flag_svg( $date ) {
	$path = get_stylesheet_directory() . '/assets/flag.svg';
	$svg  = is_readable( $path ) ? file_get_contents( $path ) : '';
	if ( '' === $svg ) {
		return '';
	}
	// XML-Kopf raus, sonst mag der Browser das inline nicht
	$svg = preg_replace( '/<\?xml.*?\?>/is', '', $svg );
	$svg = preg_replace( '/<!DOCTYPE.*?>/is', '', $svg );
	$svg = str_replace( '<g id="csd-flag-date"></g>', '<g id="csd-flag-date">' . csd_flag_date_paths( $date ) . '</g>', $svg );
	return trim( $svg );
}
