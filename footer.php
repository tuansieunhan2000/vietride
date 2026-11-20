<?php defined('ABSPATH') || exit; get_template_part( 'sidebar-templates/sidebar', 'footerfull' ); ?>
<footer class="vr-footer"><div class="vr-wrap vr-footgrid">
<div><a href="<?php echo vr_url(); ?>"><?php echo vr_image_html('img/logo_red_square.webp','VietRide home',array('width'=>62,'height'=>62,'sizes'=>'62px','max_width'=>160,'loading'=>'lazy')); ?></a><p>Family-owned scooter rentals<br>in An Khanh, Ho Chi Minh City.</p></div>
<?php foreach (array('Explore'=>array('Motorbikes'=>'motorbikes/','How it works'=>'#how-it-work','Routes'=>'routes/','About'=>'#about'), 'Support'=>array('Info Hub'=>'guides/','Rental requirements'=>'guides/rental-requirements/','Rental terms'=>'guides/rental-agreement/','Safety & support'=>'#safety')) as $heading=>$links) : ?>
<div><h2><?php echo esc_html($heading); ?></h2><?php foreach($links as $label=>$path): ?><a href="<?php echo vr_url($path); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></div><?php endforeach; ?>
<div><h2>Contact</h2><a href="<?php echo vr_url('#contact'); ?>">Hours, address & directions</a><a href="mailto:hello@vietride.vn" target="_blank" rel="noopener noreferrer">hello@vietride.vn</a><a href="https://www.instagram.com/vietride.vn/" target="_blank" rel="noopener noreferrer">Instagram</a><a href="https://www.facebook.com/VIETRIDE" target="_blank" rel="noopener noreferrer">Facebook</a></div>
</div><div class="vr-wrap vr-footnote">© <?php echo esc_html(wp_date('Y')); ?> VietRide · <a href="<?php echo vr_url('guides/rental-agreement/'); ?>">Rental terms</a><?php $privacy_url=get_privacy_policy_url(); if($privacy_url): ?> · <a href="<?php echo esc_url($privacy_url); ?>">Privacy</a><?php endif; ?></div></footer>
<details class="vr-contact-dock">
  <summary aria-label="Contact VietRide"><?php vr_icon('chat-3'); vr_icon('close'); ?></summary>
  <nav aria-label="Contact VietRide" class="vr-contact-links">
    <?php foreach ( array(
      'WhatsApp' => array('whatsapp', 'https://wa.me/message/63RJ2KOQ6SMPG1'),
      'Zalo' => array('zalo', 'https://zalo.me/0333578594'),
      'Instagram' => array('instagram', 'https://www.instagram.com/vietride.vn/'),
      'Facebook' => array('facebook-circle', 'https://www.facebook.com/VIETRIDE'),
      'Call VietRide' => array('phone', 'tel:+84333578594'),
      'Email VietRide' => array('mail', 'mailto:hello@vietride.vn'),
    ) as $label => $channel ) : ?>
      <a href="<?php echo esc_url($channel[1]); ?>" target="_blank" rel="noopener noreferrer"><?php vr_icon($channel[0]); echo esc_html($label); ?></a>
    <?php endforeach; ?>
  </nav>
</details>
</div><?php wp_footer(); ?></body></html>
