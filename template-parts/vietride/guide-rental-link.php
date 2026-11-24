<?php
defined( 'ABSPATH' ) || exit;

$links = array(
	'guides/traveller-and-commuter-rates' => array( 'Compare rates for each bike', 'motorbikes/' ),
	'guides/long-term-rates'             => array( 'Check weekly and monthly rates by model', 'motorbikes/' ),
	'guides/minimum-rental-period'       => array( 'See current bike rental rates', 'motorbikes/' ),
	'guides/rental-requirements'         => array( 'Browse bikes for your rental dates', 'motorbikes/' ),
	'guides/rental-deposit'              => array( 'Compare rental bikes before booking', 'motorbikes/' ),
	'guides/rental-agreement'            => array( 'Check the documents needed at pickup', 'guides/rental-requirements/' ),
);
$link = $links[ $args['path'] ?? '' ] ?? null;
if ( $link ) : ?>
	<p><?php vr_link( $link[0], $link[1] ); ?></p>
<?php endif; ?>
