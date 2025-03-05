<?php
/**
 *
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2021-09-15
*/

ksort($_SESSION);
	
?><!doctype html>
<html lang="de" class="fullscreen-bg">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
		<meta name="author" content="https://www.covi.de" />
		<meta name="robots" content="nofollow" />
		<title>LOGIN | WSP 7.0</title>
		<!-- get bootstrap -->
        <link rel="stylesheet" href="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/bootstrap.css" />
		<!-- get fonts -->
        <link rel="stylesheet" href="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/fontface.css" />
		<link rel="stylesheet" href="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/font-awesome-5-7-2.css" />
        <!-- base desktop stylesheet -->
		<link rel="stylesheet" href="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/wsp7.css" media="screen" type="text/css" />
        <?php if ((isset($_SESSION['wspvars']['nightmode']) && intval($_SESSION['wspvars']['nightmode'])==1) && (date('H')>=intval($_SESSION['wspvars']['startnight']) || date('H')<=intval($_SESSION['wspvars']['endnight']))) { ?>
        <link rel="stylesheet" href="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/layout/wsp7-nightly.css" media="screen" type="text/css" />
        <?php } ?>
		<!-- get icons -->
        <link rel="shortcut icon" href="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/screen/favicon.ico">
		<link rel="apple-touch-icon" href="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>media/screen/iphone_favicon.png" />
		<!-- get WSP supported and/or required base scripts -->
		<script src="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/jquery/js/jquery-3.3.1.js"></script>
		<script src="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/wspbase.min.js"></script>       
        <script src="<?php echo cleanPath(DIRECTORY_SEPARATOR.WSP_DIR.DIRECTORY_SEPARATOR); ?>data/script/bootstrap/bootstrap.min.js"></script>
	</head>
<body id="wspbody" class="autharea">