<?php

$media_sql = "SELECT `mid` FROM `wspmedia` WHERE `filepath` LIKE '/media/download/%'";
$media_res = getResultSQL($media_sql);

?><div class="widget widget-metric_6">
    <span class="icon-wrapper custom-bg-yellow"><i class="fa fa-file-o"></i></span>
    <div class="right">
        <span class="value"><?php if ($media_res!==false): echo count($media_res); else: echo 0; endif; ?></span>
        <span class="title"><?php echo returnIntLang('home widget documentcount'); ?></span>
    </div>
</div>