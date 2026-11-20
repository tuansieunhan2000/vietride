<?php defined('ABSPATH') || exit; $heading=($args['heading'] ?? '')==='h2'?'h2':'h3'; ?>
<div class="vr-routes"><?php foreach(vr_data('routes') as $slug=>$route): $url=vr_url('routes/'.$slug.'/'); ?>
<article><a href="<?php echo $url; ?>"><?php echo vr_image_html('img/'.$route['photo'],$route['alt'],array('width'=>900,'height'=>510,'loading'=>'lazy','sizes'=>'(min-width: 1200px) 620px, (min-width: 768px) 55vw, calc(100vw - 40px)')); ?></a>
<p class="vr-meta"><?php echo esc_html($route['category']); ?> · 1 min read</p><<?php echo $heading; ?>><a href="<?php echo $url; ?>"><?php echo esc_html($route['title']); ?></a></<?php echo $heading; ?>><p><?php echo esc_html($route['intro']); ?></p><?php vr_link('Explore route','routes/'.$slug.'/'); ?></article>
<?php endforeach; ?></div>
