<?php
defined('ABSPATH') || exit;
$bike = $args['bike'];
$compact = !empty($args['compact']);
$periods = array('price_daily'=>'1 day', 'price_weekly'=>'1 week', 'price_monthly'=>'1 month');
$has_rates = false;
foreach(array_keys($periods) as $field) {
	$has_rates = $has_rates || ($bike[$field] ?? '') !== '';
}
if($compact && !$has_rates): ?>
	<p class="vr-rate-enquiry"><?php echo empty($bike['price']) ? 'Contact for rates' : esc_html($bike['price']); ?></p>
<?php else: ?>
<dl class="vr-rental-rates<?php echo $compact ? ' vr-rates-compact' : ''; ?>">
<?php foreach($periods as $field=>$period): $value=$bike[$field] ?? ''; ?>
	<div><dt><?php echo esc_html($period); ?></dt><dd><?php if($value === ''): echo 'Contact for rates'; else: ?><span><?php echo esc_html(number_format_i18n((float)$value, 0)); ?></span> <small>VND</small><?php endif; ?></dd></div>
<?php endforeach; ?>
</dl>
<?php if(!$has_rates && !empty($bike['price'])): ?><p><?php echo esc_html($bike['price']); ?></p><?php endif; ?>
<?php endif; ?>
