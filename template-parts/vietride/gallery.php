<?php
defined('ABSPATH') || exit;
$bike = $args['bike'];
$name = $bike['brand'].' '.$bike['name'];
$photos = vr_bike_photos($bike);
$sizes = '(min-width: 1200px) 644px, (min-width: 1024px) 55vw, (min-width: 680px) 640px, calc(100vw - 40px)';
?>
<div class="vr-product-gallery" data-bike-gallery aria-label="<?php echo esc_attr($name.' photos'); ?>">
	<?php echo vr_image_html($photos[0],$name.' rental bike, photo 1',array('class'=>'vr-gallery-main','data-gallery-main'=>true,'width'=>900,'height'=>900,'fetchpriority'=>'high','sizes'=>$sizes)); ?>
	<?php if(count($photos)>1): ?>
	<div class="vr-gallery-controls">
		<button type="button" class="vr-gallery-arrow" data-gallery-prev aria-label="Previous photo" hidden><i class="ri-arrow-left-s-line" aria-hidden="true"></i></button>
		<div class="vr-gallery-thumbs" data-gallery-thumbs aria-label="Choose a photo">
			<?php foreach($photos as $index=>$photo): $display=vr_image_data($photo,array('width'=>900,'sizes'=>$sizes)); ?>
			<a class="vr-gallery-thumb" href="<?php echo vr_asset($photo); ?>" data-gallery-thumb data-gallery-src="<?php echo esc_url($display['src']); ?>" data-gallery-srcset="<?php echo esc_attr($display['srcset'] ?? ''); ?>" data-gallery-sizes="<?php echo esc_attr($display['sizes'] ?? ''); ?>" data-alt="<?php echo esc_attr($name.' rental bike, photo '.($index+1)); ?>" aria-label="<?php echo esc_attr('View photo '.($index+1).' of '.$name); ?>" <?php if($index===0) echo 'aria-current="true"'; ?>>
				<?php echo vr_image_html($photo,'',array('width'=>88,'height'=>88,'sizes'=>'88px','max_width'=>192,'loading'=>'lazy')); ?>
			</a>
			<?php endforeach; ?>
		</div>
		<button type="button" class="vr-gallery-arrow" data-gallery-next aria-label="Next photo" hidden><i class="ri-arrow-right-s-line" aria-hidden="true"></i></button>
	</div>
	<?php endif; ?>
	<span class="screen-reader-text" data-gallery-announcement aria-live="polite"></span>
</div>
