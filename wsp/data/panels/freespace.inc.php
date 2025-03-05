<?php

/*
$usage['db'] = floatval(doResultSQL("SELECT SUM((data_length + index_length) / (1024 * 1024)) AS `Database_Size` FROM information_schema.TABLES WHERE table_schema = '".DB_NAME."' GROUP BY table_schema ORDER BY `Database_Size` DESC"));
$usage['content'] = floatval(doResultSQL("SELECT (data_length+index_length)/power(1024,2) tablesize_mb FROM information_schema.tables WHERE `table_name`= 'content'"));
*/

// get free space on disc
$freespace = disk_free_space(DOCUMENT_ROOT);
$freespace = $freespace/(1024*1024*1024);
// get total disc space
$totalspace = disk_total_space(DOCUMENT_ROOT);
$totalspace = $totalspace/(1024*1024*1024);
// calculating by 
$usagevalue = $totalspace-$freespace;

if (defined('WSP_SPACE')):
    if (WSP_SPACE<$totalspace):
        $free = WSP_SPACE-$usagevalue;
        $space = WSP_SPACE;
    else:
        $free = $freespace;
        $space = $totalspace;
    endif;
else:
    $free = $freespace;
    $space = $totalspace;
endif;
// calculating warning sign
$sign = "green";
if ($free<64): $sign = "yellow"; endif;
if ($free<32): $sign = "red"; endif;
if ($free<32 && ($free/($space/100))>25): $sign = "yellow"; endif;

?>
<a href="./cleanup.php"><span class="icon-wrapper custom-bg-<?php echo $sign; ?>"><i class="far fa-hdd"></i></span></a>
<div class="right">
    <span class="value"><a href="./cleanup.php"><?php echo ceil($free); ?>/<small><?php echo ceil($space); ?> MB</small></a></span>
    <span class="title"><?php echo returnIntLang('home widget free space'); ?>/<small><?php echo returnIntLang('home widget total space'); ?></small></span>
</div>
