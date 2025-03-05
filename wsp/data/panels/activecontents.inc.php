<?php
/**
 * active contents info widget
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-03-08
 */
?>
<div class="widget widget-metric_6">
    <span class="icon-wrapper custom-bg-yellow"><i class="far fa-file-alt"></i></span>
    <div class="right">
        <span class="value"><?php echo getNumSQL('SELECT `cid` FROM `content` WHERE `trash` = 0'); ?>/<small><?php echo getNumSQL('SELECT `cid` FROM `content`'); ?></small></span>
        <span class="title"><?php echo returnIntLang('home widget activecontents'); ?></span>
    </div>
</div>