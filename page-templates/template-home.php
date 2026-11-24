<?php
/**
 * Template Name: Template home
 */
defined('ABSPATH') || exit;
get_header(); ?>
<main id="content">
<?php get_template_part('template-parts/vietride/introduction'); ?>
<div class="vr-editorial">
<section id="how-it-work" class="vr-band"><div class="vr-wrap"><p class="vr-kicker">How it works</p><h2>Your ride, sorted in four simple steps.</h2>
<ol class="vr-steps">
	<?php foreach ( array(
		array( 'motorbike', 'Choose your ride', 'Find a scooter that fits your trip.' ),
		array( 'file-list-3', 'Get verified', 'Check which documents you need to rent.', 'guides/rental-requirements/' ),
		array( 'ball-pen', 'Sign & collect', 'Review the rental details and collect your bike.' ),
		array( 'road-map', 'Ride away', 'Start exploring Vietnam with confidence.' ),
	) as $index => $step ) : ?>
	<li>
		<div class="vr-step-mark"><span aria-hidden="true">0<?php echo $index + 1; ?></span><?php vr_icon( $step[0] ); ?></div>
		<h3><?php echo esc_html( $step[1] ); ?></h3>
		<p>
			<?php if ( isset( $step[3] ) ) : ?>
				<a href="<?php echo vr_url( $step[3] ); ?>"><?php echo esc_html( $step[2] ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $step[2] ); ?>
			<?php endif; ?>
		</p>
	</li>
	<?php endforeach; ?>
</ol></div></section>
<section id="products" class="vr-wrap vr-section"><p class="vr-kicker">Motorbikes</p><h2>Find your ride.</h2><p class="vr-lead">From easy city scooters to premium bikes, choose the ride that fits your journey.</p>
<?php get_template_part('template-parts/vietride/fleet'); vr_link('Explore the full rental fleet','motorbikes/'); ?></section>
<section id="faq" class="vr-band"><div class="vr-wrap vr-split"><div><p class="vr-kicker"><?php echo esc_html(vr_data('info-hub')['title']); ?></p><h2><?php echo esc_html(vr_data('info-hub')['headline']); ?></h2><?php vr_link(vr_data('info-hub')['title'],'guides/'); ?></div><?php get_template_part('template-parts/vietride/guide-list'); ?></div></section>
<section id="routes" class="vr-wrap vr-section"><p class="vr-kicker">Routes & field notes</p><h2>Go beyond the city.</h2><p class="vr-lead">Discover our favourite routes, maps and riding tips for exploring Southern Vietnam.</p>
<?php get_template_part('template-parts/vietride/route-list'); ?></section>
<section id="testimonials" class="vr-band vr-reviews" aria-labelledby="reviews-heading"><div class="vr-wrap">
  <div class="vr-review-heading"><div><p class="vr-kicker">Testimonials</p><h2 id="reviews-heading">What our riders say.</h2></div>
    <a class="vr-link" href="https://www.google.com/maps/place/VietRide/@10.8047983,106.7262495,17z/data=!4m8!3m7!1s0x3175261eb840c7ed:0x8e4e4fa75054f109!8m2!3d10.804793!4d106.7288244!9m1!1b1!16s%2Fg%2F11ggs8mxfy" target="_blank" rel="noopener noreferrer">See all Google reviews <?php vr_icon('arrow-right-up'); ?></a>
  </div>
  <?php
  $reviews = vr_data('reviews');
  if ($reviews) {
      array_unshift($reviews, array_pop($reviews));
  }
  ?>
  <div class="vr-review-list">
    <?php foreach (array_slice($reviews, 0, 3) as $index => $review) {
        get_template_part('template-parts/vietride/review', null, array('review' => $review, 'featured' => $index === 0));
    } ?>
  </div>
  <?php if (count($reviews) > 3) : ?>
  <details class="vr-more-reviews"><summary>More rider reviews (<?php echo count($reviews) - 3; ?>)</summary>
    <div class="vr-review-list"><?php foreach (array_slice($reviews, 3) as $review) {
        get_template_part('template-parts/vietride/review', null, array('review' => $review));
    } ?></div>
  </details><?php endif; ?>
</div></section>
<section id="about" class="vr-wrap vr-section vr-split"><?php echo vr_image_html('img/vietride-story.webp','VietRide in Vietnam',array('class'=>'vr-story-photo','width'=>640,'height'=>520,'loading'=>'lazy','sizes'=>'(min-width: 1200px) 536px, (min-width: 768px) 48vw, calc(100vw - 40px)')); ?><div><p class="vr-kicker">About VietRide</p><h2>Built by riders. Driven by Vietnam.</h2><p>Alec Barnard founded VietRide to share the roads he loves. From our family-run shop in An Khanh, we help travellers and residents find a well-maintained bike for the way they ride.</p><dl class="vr-stats"><div><dt>70+ years</dt><dd>Combined riding experience</dd></div><div><dt>25 years</dt><dd>Riding in Vietnam</dd></div></dl></div></section>
<section id="safety" class="vr-wrap vr-safety"><h2>Support on the road.</h2><div><?php foreach(array('shield-check'=>array('What your rental covers','rental-insurance'),'tools'=>array('If your bike breaks down','breakdown-support'),'file-list-3'=>array('Review the rental agreement','rental-agreement')) as $icon=>$link): ?><a href="<?php echo vr_url('guides/'.$link[1].'/'); ?>"><?php vr_icon($icon); echo esc_html($link[0]); ?></a><?php endforeach; ?></div></section>
<section class="vr-band" id="contact"><div class="vr-wrap vr-split"><div><p class="vr-kicker">An Khanh · Ho Chi Minh City</p><h2>Ready to ride?</h2><p class="vr-lead">Pick your bike and start exploring Ho Chi Minh City.</p><div class="vr-actions"><a class="vr-btn" href="https://wa.me/message/63RJ2KOQ6SMPG1" target="_blank" rel="noopener noreferrer">Rent a Bike <?php vr_icon('whatsapp'); ?></a><a class="vr-link" href="mailto:hello@vietride.vn" target="_blank" rel="noopener noreferrer">Contact VietRide <?php vr_icon('mail'); ?></a></div></div>
<address id="location"><strong>Visit VietRide</strong><p>40 Nguyễn Bá Huân, An Khánh, Hồ Chí Minh 70000, Vietnam<br>Daily, 9:00 AM – 6:00 PM</p><a href="<?php echo esc_url( vr_map_url() ); ?>" target="_blank" rel="noopener noreferrer">Directions on Google Maps</a><p><a href="tel:+84333578594" target="_blank" rel="noopener noreferrer">+84 333 578 594</a> · <a href="https://wa.me/message/63RJ2KOQ6SMPG1" target="_blank" rel="noopener noreferrer">WhatsApp</a> · <a href="https://zalo.me/0333578594" target="_blank" rel="noopener noreferrer">Zalo</a></p></address></div></section>
</div></main><?php get_footer(); ?>
