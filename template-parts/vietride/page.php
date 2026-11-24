<?php
defined('ABSPATH') || exit;
get_header();
$route_meta = vr_pages()[vr_route()];
$kind = $route_meta['kind'];
$item = $route_meta['item'] ?? array();
?>
<main id="content" class="vr-editorial vr-wrap vr-inside">
<nav aria-label="Breadcrumb" class="vr-crumb"><?php $crumbs=vr_breadcrumbs($route_meta); foreach($crumbs as $index=>$crumb): if($index): ?><span aria-hidden="true"> / </span><?php endif; if($index===count($crumbs)-1): ?><span aria-current="page"><?php echo esc_html($crumb['name']); ?></span><?php else: ?><a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['name']); ?></a><?php endif; endforeach; ?></nav>
<?php if($kind==='fleet'): ?>
<h1><?php echo esc_html( $route_meta['title'] ); ?></h1>
<p class="vr-lead">Compare automatic scooters, semi-automatic motorbikes and electric bikes available to rent from our shop in An Khanh.</p>
<p>Check the published rates on each bike. For a longer stay, see <a href="<?php echo vr_url('guides/long-term-rates/'); ?>">how weekly and monthly rates work</a> and <a href="<?php echo vr_url('guides/minimum-rental-period/'); ?>">the minimum rental period</a> for your trip.</p>
<?php get_template_part('template-parts/vietride/fleet', null, array('heading'=>'h2')); ?>
<?php elseif($kind==='guides'): ?>
<div class="vr-info-heading"><h1><?php echo esc_html(vr_data('info-hub')['title']); ?></h1><div><p class="vr-lead"><?php echo esc_html(vr_data('info-hub')['headline']); ?></p><?php vr_link('Compare bikes and rental rates', 'motorbikes/'); ?></div></div>
<div class="vr-info-layout">
<nav class="vr-info-nav" aria-label="Info Hub topics"><?php foreach(vr_data('info-hub')['groups'] as $index=>$group): ?><a href="#<?php echo esc_attr('info-'.sanitize_title($group)); ?>"><span aria-hidden="true"><?php echo esc_html(sprintf('%02d',$index+1)); ?></span><?php echo esc_html($group); ?></a><?php endforeach; ?></nav>
<?php get_template_part('template-parts/vietride/guide-list', null, array('expanded'=>true)); ?>
</div>
<?php elseif($kind==='routes'): ?>
<h1><?php echo esc_html( $route_meta['title'] ); ?></h1><p class="vr-lead"><?php echo esc_html($route_meta['description']); ?></p><?php get_template_part('template-parts/vietride/route-list', null, array('heading'=>'h2')); ?>
<p>Need a bike for your trip? <a href="<?php echo vr_url('motorbikes/'); ?>">Explore the Ho Chi Minh City rental fleet</a>.</p>
<?php elseif($kind==='bike'): $name=$item['brand'].' '.$item['name']; $chat='https://wa.me/84333578594?text='.rawurlencode('Hi VietRide, is the '.$name.' available for my dates? My dates are '); ?>
<article><div class="vr-split vr-bike-hero">
<?php get_template_part('template-parts/vietride/gallery', null, array('bike'=>$item)); ?>
<div class="vr-bike-summary"><h1><?php echo esc_html($name . ' Rental'); ?></h1>
<p class="vr-bike-spec-line"><?php echo esc_html($item['engine'].' · '.$item['type'].' · '.(vr_bike_fuel($item)==='electric'?'Electric':'Petrol')); ?></p>
<div class="vr-detail-prices"><?php get_template_part('template-parts/vietride/rates', null, array('bike'=>$item)); ?></div>
<p class="vr-availability" data-availability="<?php echo esc_attr($item['availability'] ?? 'on_request'); ?>"><?php echo esc_html(vr_availability($item)); ?></p>
<p class="vr-lead"><?php echo esc_html(rtrim(vr_use($item), '.')); ?>.</p><a class="vr-btn" href="<?php echo esc_url($chat); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(vr_rental_cta($item)); ?> <?php vr_icon('whatsapp'); ?></a>
<div class="vr-rental-help"><?php vr_link('Check rental requirements','guides/rental-requirements/'); ?></div>
</div></div>
<div class="vr-split vr-subsection"><div><h2>Quick specs</h2><dl class="vr-specs"><?php foreach(array('Engine'=>$item['engine'],'Transmission'=>$item['type'],'Fuel / power'=>vr_bike_fuel($item)==='electric'?'Electric':'Petrol','Capacity'=>'Check passenger and luggage space with the team','Suitable for'=>vr_use($item)) as $label=>$value): ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endforeach; ?></dl></div>
<div><h2>Why this bike?</h2><p><?php echo esc_html($item['name']); ?> combines <?php echo esc_html($item['engine']); ?> power with <?php echo esc_html(strtolower($item['type'])); ?> transmission. <?php echo $item['type']==='Semi-Automatic'?'Choose it if you prefer to select your gears as the road changes.':'Choose it if you prefer to focus on traffic and stops without changing gears.'; ?></p><h3>Included with your rental</h3><ul class="vr-included"><?php foreach($item['included'] as $include): ?><li><?php vr_icon('check'); echo esc_html($include); ?></li><?php endforeach; ?></ul></div></div>
<section class="vr-subsection vr-split"><div><h2>Rental information</h2><?php foreach(array('deposit'=>'Deposit') as $field=>$label): if(!empty($item[$field])): ?><p><strong><?php echo esc_html($label); ?></strong> <?php echo esc_html($item[$field]); ?></p><?php endif; endforeach; ?><p>Traveller rentals start at three days. Commuter rentals start at one month. Send your dates for the price and deposit for this bike.</p><?php vr_link('Read the minimum rental periods','guides/minimum-rental-period/'); ?></div><div><h3>What to bring</h3><ul><?php foreach($item['docs'] as $doc): ?><li><?php echo esc_html($doc); ?></li><?php endforeach; ?></ul><?php vr_link('Check your rental documents','guides/rental-requirements/'); ?></div></section>
<h2>Before collecting your bike</h2><p>Check the bike, helmet fit and rental agreement at collection. Review the insurance terms and save VietRide’s contact details before setting off.</p>
<div class="vr-actions"><a class="vr-btn" href="<?php echo esc_url($chat); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(vr_rental_cta($item)); ?></a><?php vr_link('Info Hub','guides/'); vr_link('Compare other bikes','motorbikes/'); vr_link('Contact the team','#contact'); ?></div>
<?php $related = vr_related_bikes( $item ); if ( $related ) : ?>
<section class="vr-subsection">
<h2>Compare similar rental bikes</h2>
<div class="vr-actions">
<?php foreach ( $related as $bike ) {
	vr_link( $bike['brand'] . ' ' . $bike['name'] . ' rental', 'motorbikes/' . $bike['slug'] . '/' );
} ?>
</div>
</section>
<?php endif; ?>
</article>
<?php elseif($kind==='guide'): ?>
<article class="vr-reading vr-guide-article"><header><p class="vr-kicker"><?php echo esc_html($item['group']); ?></p><h1><?php echo esc_html($item['title']); ?></h1></header>
<?php get_template_part('template-parts/vietride/guide-rental-link', null, array('path'=>vr_route())); ?>
<div class="vr-answer vr-guide-prose"><?php echo vr_format_guide_body($item['body']); ?></div></article>
<?php else: ?>
<article><p class="vr-kicker"><?php echo esc_html($item['category']); ?> · 1 min read</p><h1><?php echo esc_html($route_meta['title']); ?></h1><p class="vr-lead"><?php echo esc_html($item['intro']); ?></p><?php echo vr_image_html('img/'.$item['photo'],$item['alt'],array('class'=>'vr-landscape','width'=>1000,'height'=>540,'sizes'=>'(min-width: 1200px) 1168px, calc(100vw - 40px)','fetchpriority'=>'high')); ?>
<div class="vr-split vr-subsection"><div><?php foreach($item['sections'] as $title=>$copy): ?><h2><?php echo esc_html($title); ?></h2><p><?php echo esc_html($copy); ?></p><?php endforeach; ?><a href="<?php echo esc_url($item['source']); ?>">Photography & destination information · Vietnam Tourism</a></div>
<aside><h2>Take the map</h2><?php foreach($item['maps'] as $i=>$map): ?><a href="<?php echo vr_asset('img/'.$map); ?>"><?php echo vr_image_html('img/'.$map,$item['title'].' route map, sheet '.($i+1),array('width'=>600,'height'=>360,'loading'=>'lazy','sizes'=>'(min-width: 1200px) 536px, (min-width: 768px) 48vw, calc(100vw - 40px)')); ?></a><?php endforeach; ?><a class="vr-btn" href="<?php echo esc_url($item['pdf']); ?>">Open the route PDF <?php vr_icon('map'); ?></a></aside></div>
<div class="vr-actions"><?php $recommended = vr_pages()['motorbikes/'.$item['bike']] ?? null; if ($recommended) { vr_link('See '.$recommended['item']['brand'].' '.$recommended['item']['name'].' rental rates','motorbikes/'.$item['bike'].'/'); } else { vr_link('Explore the rental fleet','motorbikes/'); } vr_link('Check what you need to rent','guides/rental-requirements/'); ?></div></article>
<?php endif; ?></main><?php get_footer(); ?>
