<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?>
<noscript><link rel="stylesheet" href="<?php echo esc_url(get_theme_file_uri('css/vietride-nojs.min.css').'?ver='.filemtime(get_theme_file_path('css/vietride-nojs.min.css'))); ?>"></noscript>
</head>
<body <?php body_class(); ?> <?php understrap_body_attributes(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
<a class="vr-skip" href="#content">Skip to content</a>
<header class="vr-nav"><div class="vr-wrap vr-nav-inner">
<a href="<?php echo vr_url(); ?>"><?php echo vr_image_html('img/logo_red_square.webp','VietRide home',array('width'=>64,'height'=>64,'sizes'=>'64px','max_width'=>160)); ?></a>
<button hidden type="button" id="vr-toggle" aria-expanded="false" aria-controls="vr-menu" aria-label="Open menu"><?php vr_icon('menu'); vr_icon('close'); ?></button>
<nav id="vr-menu" aria-label="Main navigation"><?php foreach (array('Motorbikes'=>'motorbikes/','How it works'=>'#how-it-work','Info Hub'=>'guides/','Routes'=>'routes/','Reviews'=>'#testimonials','About'=>'#about','Contact'=>'#contact') as $name=>$url) : ?>
<a href="<?php echo vr_url($url); ?>"><?php echo esc_html($name); ?></a><?php endforeach; ?></nav>
<div class="vr-nav-shade" hidden></div>
<a class="vr-nav-rent vr-btn" href="https://wa.me/message/63RJ2KOQ6SMPG1" target="_blank" rel="noopener noreferrer">Rent a Bike <?php vr_icon('arrow-right'); ?></a>
</div></header>
