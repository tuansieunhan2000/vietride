<?php
defined('ABSPATH') || exit;
$info=vr_data('info-hub');
$expanded=!empty($args['expanded']);
$heading=$expanded?'h2':'h3';
$icons=array('file-list-3','motorbike','file-text');
?>
<div class="vr-guide-list">
<?php foreach($info['groups'] as $index=>$group): ?>
<details class="vr-guide-group" id="<?php echo esc_attr('info-'.sanitize_title($group)); ?>" <?php if($expanded) echo 'open'; ?>>
<summary><?php vr_icon($icons[$index]); ?><<?php echo $heading; ?> class="vr-guide-group-title"><?php echo esc_html($group); ?></<?php echo $heading; ?>><?php vr_icon('arrow-down-s'); ?></summary>
<div class="vr-guide-questions">
<?php foreach(vr_data('guides') as $slug=>$guide): if($guide['group']!==$group) continue; ?>
<details class="vr-guide-question" id="<?php echo esc_attr($slug); ?>">
<summary><span><?php echo esc_html($guide['title']); ?></span><span class="vr-question-toggle" aria-hidden="true"><?php vr_icon('add'); vr_icon('subtract'); ?></span></summary>
<div class="vr-guide-answer vr-guide-prose"><?php echo vr_format_guide_body($guide['body']); ?></div>
<a class="vr-guide-permalink" href="<?php echo vr_url('guides/'.$slug.'/'); ?>" aria-label="<?php echo esc_attr('Open guide: '.$guide['title']); ?>">Open guide <?php vr_icon('arrow-right-up'); ?></a>
</details>
<?php endforeach; ?>
</div></details>
<?php endforeach; ?></div>
