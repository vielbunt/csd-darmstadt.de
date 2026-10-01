<?php
/**
 * Structured data (JSON-LD) on the front page, so Google knows what this
 * site is and when the next CSD happens.
 *
 * - WebSite + Organization "CSD Darmstadt" (run by vielbunt e.V.)
 * - Event for the next CSD. The date comes from the hero field "Datum in der
 *   Grafik", so it is maintained in exactly one place. Once the day is over,
 *   the event disappears until a new date is entered.
 *
 * @package csd-darmstadt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* where the CSD takes place. if that ever changes, change it here */
define( 'CSD_EVENT_PLACE', 'Karolinenplatz' );
define( 'CSD_EVENT_STREET', 'Karolinenplatz' );
define( 'CSD_EVENT_ZIP', '64289' );

/* "21.08.2027" -> "2027-08-21", empty if it is no complete date */
function csd_schema_event_date() {
	$date = csd_frontpage_get()['hero']['flagDate'];
	if ( ! preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', (string) $date, $m ) || ! checkdate( (int) $m[2], (int) $m[1], (int) $m[3] ) ) {
		return '';
	}
	return sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] );
}

function csd_schema_graph() {
	$home    = home_url( '/' );
	$logo    = get_stylesheet_directory_uri() . '/assets/icons/icon-512.png';
	$vielbunt = array(
		'@type' => 'NGO',
		'@id'   => 'https://www.vielbunt.org/#organization',
		'name'  => 'vielbunt e.V.',
		'url'   => 'https://www.vielbunt.org/',
	);
	$graph = array(
		array(
			'@type'     => 'WebSite',
			'@id'       => $home . '#website',
			'url'       => $home,
			'name'      => 'CSD Darmstadt',
			'inLanguage' => 'de-DE',
			'publisher' => array( '@id' => $home . '#organization' ),
		),
		array(
			'@type'              => 'Organization',
			'@id'                => $home . '#organization',
			'name'               => 'CSD Darmstadt',
			'alternateName'      => 'Christopher Street Day Darmstadt',
			'url'                => $home,
			'logo'               => $logo,
			'sameAs'             => array( 'https://www.facebook.com/csd-darmstadt', 'https://instagram.com/vielbunt' ),
			'parentOrganization' => $vielbunt,
		),
	);

	$date = csd_schema_event_date();
	if ( $date && $date >= wp_date( 'Y-m-d' ) ) {
		$hero  = csd_frontpage_get()['hero'];
		$lead  = '' !== $hero['lead'] ? $hero['lead'] : csd_hero_defaults()['lead'];
		$image = csd_frontpage_image( $hero['bgId'], $hero['bgUrl'], 'full' );
		$graph[] = array(
			'@type'               => 'Event',
			'@id'                 => $home . '#csd-' . substr( $date, 0, 4 ),
			'name'                => 'Christopher Street Day Darmstadt ' . substr( $date, 0, 4 ),
			'description'         => wp_strip_all_tags( $lead ),
			'startDate'           => $date,
			'endDate'             => $date,
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'isAccessibleForFree' => true,
			// free entry, Google likes to see that as an offer too
			'offers'              => array(
				'@type'         => 'Offer',
				'price'         => '0',
				'priceCurrency' => 'EUR',
				'availability'  => 'https://schema.org/InStock',
				'url'           => $home,
			),
			'url'                 => $home,
			'image'               => array( $image ? $image : $logo ),
			'location'            => array(
				'@type'   => 'Place',
				'name'    => CSD_EVENT_PLACE . ', Darmstadt',
				'address' => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => CSD_EVENT_STREET,
					'postalCode'      => CSD_EVENT_ZIP,
					'addressLocality' => 'Darmstadt',
					'addressCountry'  => 'DE',
				),
			),
			'organizer'           => $vielbunt,
		);
	}
	return $graph;
}

function csd_schema_output() {
	if ( ! is_front_page() ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => csd_schema_graph() ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'csd_schema_output', 5 );
