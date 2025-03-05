<?php

if (WSP_UPDSRV=='git') {
    ?>
    <div class="col-md-3">
        <div class="widget widget-metric_6">
            <span class="icon-wrapper custom-bg-<?php echo ($_SESSION['wspvars']['updatesystem']===true)?'red':'green'; ?>"><i class="fab fa-github"></i></span>
            <div class="right">
                <span class="value"><?php echo returnIntLang('home widget sys info'); ?> <small><?php echo $_SESSION['wspvars']['localversion']; ?></small></span>
                <span class="title"><?php echo returnIntLang('home widget sys lastdate').' '.date(returnIntLang('format date time'), getWSPProperties('lastupdate')); ?> </span>
                <span class="title"><?php echo returnIntLang('home widget sys git update').' '.date(returnIntLang('format date time'), $_SESSION['wspvars']['updatedate']); ?> </span>
            </div>
        </div>
    </div>
<?php } else if ($_SESSION['wspvars']['updatesystem']===false) { ?>
<div class="col-md-3">
    <div class="widget widget-metric_6">
        <span class="icon-wrapper custom-bg-red"><i class="fa fa-warning"></i></span>
        <div class="right">
            <span class="value"><?php echo returnIntLang('home widget sys no connect'); ?></span>
            <span class="title"><?php echo returnIntLang('home widget sys info'); ?></span>
        </div>
    </div>
</div>
<?php } else if ($_SESSION['wspvars']['updatesystem']===true) { ?>
<div class="col-md-3">
    <div class="widget widget-metric_6">
        <span class="icon-wrapper custom-bg-red"><i class="fa fa-cloud-download-alt"></i></span>
        <div class="right">
            <span class="value"><?php echo $_SESSION['wspvars']['updateversion']; ?>/<small><?php echo $_SESSION['wspvars']['localversion']; ?></small></span>
            <span class="title"><?php echo returnIntLang('home widget sys info'); ?></span>
        </div>
    </div>
</div>
<?php } else { ?>
<div class="col-md-3">
    <div class="widget widget-metric_6">
        <span class="icon-wrapper custom-bg-green"><i class="fa fa-cloud"></i></span>
        <div class="right">
            <span class="value"><?php echo $_SESSION['wspvars']['localversion']; ?></span>
            <span class="title"><?php echo returnIntLang('home widget sys info'); ?></span>
        </div>
    </div>
</div>
<?php } 
