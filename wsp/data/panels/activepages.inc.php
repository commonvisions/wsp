<?php
/**
 * active pages info widget
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-03-08
 */
?>
<div class="widget widget-metric_6">
    <span class="icon-wrapper custom-bg-yellow"><i class="fa fa-sitemap"></i></span>
    <div class="right">
        <span class="value"><?php echo getNumSQL('SELECT `mid` FROM `menu` WHERE `trash` = 0'); ?>/<small><?php echo getNumSQL('SELECT `mid` FROM `menu`'); ?></small></span>
        <span class="title"><?php echo returnIntLang('home widget activepages'); ?></span>
    </div>
</div>