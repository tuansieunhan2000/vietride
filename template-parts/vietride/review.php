<?php
defined( 'ABSPATH' ) || exit;
$review = $args['review'];
$rating = (int) $review['rating'];
?>
<figure class="vr-review<?php echo empty($args['featured']) ? '' : ' vr-review-featured'; ?>">
  <figcaption>
    <strong><?php echo esc_html($review['name']); ?></strong>
    <span class="vr-review-stars" role="img" aria-label="<?php echo esc_attr($rating . ' out of 5 stars'); ?>">
      <?php for ($star = 0; $star < $rating; $star++) { vr_icon('star'); } ?>
    </span>
  </figcaption>
  <blockquote cite="<?php echo esc_url($review['url']); ?>"><?php echo esc_html($review['quote']); ?></blockquote>
  <a class="vr-review-source" href="<?php echo esc_url($review['url']); ?>" target="_blank" rel="noopener noreferrer">Read on Google <?php vr_icon('arrow-right-up'); ?></a>
</figure>
