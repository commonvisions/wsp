<?php
/**
 *
 * @author stefan@covi.de
 * @since 3.1.2
 * @version 7.0
 * @lastchange 2021-09-15
*/

ksort($_SESSION);

?><!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <meta name="author" content="https://www.covi.de" />
    <meta name="robots" content="nofollow" />
    <title>WSP 7.0</title>
    <!-- get bootstrap -->
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/bootstrap/bootstrap.min.css" />
    <!-- get fonts -->
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/fontface.css" />
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/font-awesome-5-7-2.css" />
    <!-- extended desktop stylesheets -->
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/bootstrap-tour.min.css" />
    <!-- get page based stylesheets -->
    <?php if(isset($_SESSION['wspvars']['addpagecss']) && is_array($_SESSION['wspvars']['addpagecss']) && count($_SESSION['wspvars']['addpagecss'])>0) { foreach($_SESSION['wspvars']['addpagecss'] AS $addk => $addv) { echo '<link rel="stylesheet" href="' . cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR) . 'media/layout/' . trim($addv) . '" />'; }} ?>
    <!-- base desktop stylesheet -->
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/wsp7.css" media="screen" type="text/css" />
    <?php if ((isset($_SESSION['wspvars']['nightmode']) && intval($_SESSION['wspvars']['nightmode'])==1) && (date('H')>=intval($_SESSION['wspvars']['startnight']) || date('H')<=intval($_SESSION['wspvars']['endnight']))) { ?>
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/wsp7-nightly.css" media="screen" type="text/css" />
    <?php } ?>
    <!-- self colorize extensions -->
    <?php if (isset($_SESSION['wspvars']['wspstyle']) && trim($_SESSION['wspvars']['wspstyle'])!="" && is_file(DOCUMENT_ROOT."/".WSP_DIR."/media/layout/".trim($_SESSION['wspvars']['wspstyle']).".css")): echo "<link rel='stylesheet' href='/".WSP_DIR."/media/layout/".trim($_SESSION['wspvars']['wspstyle']).".css' media='screen' type='text/css'>\n"; endif; ?>
    <!-- base klorofill stylesheet -->
    <link rel="stylesheet" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/klorofil.css" />
    <!-- load icons -->
    <link rel="shortcut icon" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/screen/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/screen/iphone_favicon.png" />
    <!-- load generic javascript -->
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/jquery/js/jquery-3.7.1.js"></script>
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/jquery/js/jquery-ui.min.js"></script>
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/wspbase.min.js"></script>
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/bootstrap/bootstrap.min.js"></script>
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/bootstrap/bootstrap-tour.min.js"></script>
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/jquery/jquery.slimscroll.min.js"></script>
    <!-- load page based javascript -->
<?php if(isset($_SESSION['wspvars']['addpagejs']) && is_array($_SESSION['wspvars']['addpagejs']) && count($_SESSION['wspvars']['addpagejs'])>0): foreach($_SESSION['wspvars']['addpagejs'] AS $addk => $addv): ?>
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/<?php echo trim($addv); ?>"></script>
<?php endforeach; endif; ?>
    <script src="<?php echo cleanpath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/klorofilpro.min.js"></script>
</head>
<body id="wspbody" <?php if (isset($_SESSION['wspvars']['lockscreen']) && $_SESSION['wspvars']['lockscreen']===true): echo ' class="lockscreen" '; elseif (isset($preview) && $preview===true): echo ' class="layout-fullwidth" '; endif; ?>>
    <?php if(!(isset($preview)) || (isset($preview) && !($preview))) { ?><div id="lockscreen" <?php echo (isset($_SESSION['wspvars']['lockscreen']) && $_SESSION['wspvars']['lockscreen']===true) ? '' : 'style="display: none;"'; ?>></div>
    <?php } ?>
    <div id="wrapper" <?php echo (isset($_SESSION['wspvars']['lockscreen']) && $_SESSION['wspvars']['lockscreen']===true) ? 'class="lockscreen"' : ''; ?>>
        <!-- WRAPPER -->
		