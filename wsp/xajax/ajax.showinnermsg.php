<?php
/**
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2018-09-14
 */
session_start();
if (is_array($_SESSION) && array_key_exists('wspvars', $_SESSION) && array_key_exists('wspbasediradd', $_SESSION['wspvars']) && array_key_exists('wspbasedir', $_SESSION['wspvars'])):
	include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
	include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
	include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/dbaccess.inc.php';
endif;

if (isset($_POST['msgtype']) && is_array($_SESSION) && array_key_exists('wspvars', $_SESSION) && array_key_exists($_POST['msgtype'], $_SESSION['wspvars'])):
	echo $_SESSION['wspvars'][$_POST['msgtype']];
	$_SESSION['wspvars'][$_POST['msgtype']] = '';
endif;
// EOF ?>