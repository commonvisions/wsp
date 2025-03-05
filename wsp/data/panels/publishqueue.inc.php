<?php $qnum = getNumSQL('SELECT `id` FROM `wspqueue` WHERE `done` = 0'); if ($qnum>0) { ?>
<div class="widget widget-metric_6">
    <span class="icon-wrapper custom-bg-yellow"><i class="fa fa-globe"></i></span>
    <div class="right">
        <span class="value"><?php echo intval($qnum); ?></span>
        <span class="title"><?php echo returnIntLang('home widget publish queue'); ?></span>
    </div>
</div>
<?php } ?>