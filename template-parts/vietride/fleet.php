<?php defined('ABSPATH') || exit;
$bikes=vr_data('bikes');
$heading=($args['heading'] ?? '')==='h2'?'h2':'h3';
$featured=array('honda-air-blade','piaggio-vespa-lx125','honda-wave-alpha');
usort($bikes, static function($a,$b) use($featured) {
	$order=array_flip($featured);
	return ($order[$a['slug']] ?? 99) <=> ($order[$b['slug']] ?? 99);
});
?>
<div data-fleet class="<?php echo vr_route() ? '' : 'vr-preview'; ?>">
<div class="vr-filterbar"><div class="vr-filters" role="group" aria-label="Filter motorbikes" hidden>
<?php foreach(array('All','Scooter','Premium','Automatic','Semi-Automatic','Electric') as $filter): ?><button type="button" data-filter="<?php echo esc_attr(sanitize_title($filter)); ?>" aria-pressed="<?php echo $filter==='All'?'true':'false'; ?>"><?php echo esc_html($filter); ?></button><?php endforeach; ?></div>
<output aria-live="polite"><?php echo vr_route() ? '' : 'Showing '.min(3, count($bikes)).' of '; echo count($bikes); ?> bikes</output></div>
<div class="vr-bikes"><?php foreach($bikes as $i=>$bike): $name=$bike['brand'].' '.$bike['name']; $url=vr_url('motorbikes/'.$bike['slug'].'/'); ?>
<article class="vr-bike" data-tags="<?php echo esc_attr(implode(' ',vr_bike_tags($bike))); ?>" <?php if(!vr_route() && $i>2) echo 'hidden'; ?>>
<div class="vr-bike-media">
	<a href="<?php echo $url; ?>"><?php echo vr_image_html($bike['thumb'],$name.' rental bike in Ho Chi Minh City',array('width'=>600,'height'=>420,'loading'=>vr_route() && $i===0?'eager':'lazy','sizes'=>vr_fleet_image_sizes($bike['thumb']))); ?></a>
	<p class="vr-availability" data-availability="<?php echo esc_attr($bike['availability'] ?? 'on_request'); ?>"><?php echo esc_html(vr_availability($bike)); ?></p>
</div>
<div class="vr-bike-info">
	<p class="vr-meta"><?php echo esc_html($bike['engine'].' · '.$bike['type']); ?></p>
	<<?php echo $heading; ?>><a href="<?php echo $url; ?>"><?php echo esc_html($name); ?></a></<?php echo $heading; ?>>
	<p class="vr-bike-description"><?php echo esc_html(rtrim(vr_use($bike), '.')); ?>.</p>
	<?php get_template_part('template-parts/vietride/rates', null, array('bike'=>$bike, 'compact'=>true)); ?>
	<a class="vr-link vr-bike-link" href="<?php echo $url; ?>" aria-label="<?php echo esc_attr('View bike: '.$name); ?>">View bike <?php vr_icon('arrow-right'); ?></a>
</div>
</article><?php endforeach; ?></div></div>
