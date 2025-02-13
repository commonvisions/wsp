<?php
/**
 *
 * @author s.haendler
 * @since 3.1
 * @version 6.9
 * @lastchange 2021-01-19
 */

$_SESSION['wspvars']['workspaceurl'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'devurl'");
$_SESSION['wspvars']['liveurl'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'siteurl'");
if (trim($_SESSION['wspvars']['workspaceurl'])==""): $_SESSION['wspvars']['workspaceurl'] = $_SESSION['wspvars']['liveurl']; endif;
$_SESSION['wspvars']['googlemaps'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'googlemaps'");
$_SESSION['wspvars']['wspstyle'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'use_css'");
$_SESSION['wspvars']['loginfails'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'loginfails'"));
if ($_SESSION['wspvars']['loginfails']<3) { $_SESSION['wspvars']['loginfails'] = 3; }
$_SESSION['wspvars']['errorreporting'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'errorreporting'");
$_SESSION['wspvars']['cookiedays'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'cookiedays'"));
$_SESSION['wspvars']['cookielogin'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'cookielogin'"));
$_SESSION['wspvars']['backupsteps'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'backupsteps'"));
if ($_SESSION['wspvars']['backupsteps']<3) { $_SESSION['wspvars']['backupsteps'] = 3; }
$_SESSION['wspvars']['autologout'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'autologout'"));
if ($_SESSION['wspvars']['autologout']<15) { $_SESSION['wspvars']['autologout'] = 15; }
$_SESSION['wspvars']['sitelanguages'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'languages'");
if ($_SESSION['wspvars']['sitelanguages']=="") {
	$_SESSION['wspvars']['sitelanguages'] = serialize(array("languages" => array('longname' => array('Deutsch'), 'shortcut' => array('de'))));
}
else {
	$_SESSION['wspvars']['sitelanguages'] = serialize(array("languages" => unserializeBroken($_SESSION['wspvars']['sitelanguages'])));	
}
$_SESSION['wspvars']['tmplang'] = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
$_SESSION['wspvars']['lang'] = array();
foreach ($_SESSION['wspvars']['tmplang']['languages']['shortcut'] AS $slk => $slv) {
	$_SESSION['wspvars']['lang'][] = array($_SESSION['wspvars']['tmplang']['languages']['shortcut'][$slk], $_SESSION['wspvars']['tmplang']['languages']['longname'][$slk]);
}
$_SESSION['wspvars']['wspbaselang'] = trim(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'wspbaselang'"));
$_SESSION['wspvars']['showlang'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'showlang'");
$_SESSION['wspvars']['setlang'] = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'setlang'");
$_SESSION['wspvars']['showmedia'] = "liste";
$_SESSION['wspvars']['useiconfont'] = 0;
$_SESSION['wspvars']['stripslashes'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'stripslashes'"));
$_SESSION['wspvars']['usesession'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'use_session'"));
$_SESSION['wspvars']['usetracking'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'use_tracking'")); 
$_SESSION['wspvars']['mailclass'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'mailclass'"));
$_SESSION['wspvars']['menustyle'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'menustyle'"));
$_SESSION['wspvars']['wspstyle'] = trim(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'wspstyle'"));
$_SESSION['wspvars']['handledelete'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'deletedmenu'"));
$_SESSION['wspvars']['overwriteuploads'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'overwriteuploads'"));
$_SESSION['wspvars']['noautoindex'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'noautoindex'"));
$_SESSION['wspvars']['hiddenmenu'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmenu'"));
$_SESSION['wspvars']['nocontentmenu'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'nocontentmenu'"));
$_SESSION['wspvars']['bindcontentview'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'bindcontentview'"));
$_SESSION['wspvars']['autoparsestructure'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'autoparsestructure'"));
$_SESSION['wspvars']['autoparsecontent'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'autoparsecontent'"));
$_SESSION['wspvars']['stripfilenames'] = intval(@doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'stripfilenames'"));
if (intval($_SESSION['wspvars']['stripfilenames'])<60): $_SESSION['wspvars']['stripfilenames'] = 60; endif;
// setup some session vars
if (!(array_key_exists('opentabs', $_SESSION))) $_SESSION['opentabs'] = array();

$checkworklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
if (is_array($checkworklang) && array_key_exists('languages', $checkworklang) && array_key_exists('shortcut', $checkworklang['languages']) && array_key_exists('workspacelang', $_SESSION['wspvars']) && in_array($_SESSION['wspvars']['workspacelang'],$checkworklang['languages']['shortcut'])):
	// language var exists .. do nothing ;)
else:
	$_SESSION['wspvars']['workspacelang'] = 'de';
endif;

if (array_key_exists('wspvars', $_SESSION) && array_key_exists('locallang', $_SESSION['wspvars'])):
	// lang set
elseif (array_key_exists('wspvars', $_SESSION) && array_key_exists('wspbaselang', $_SESSION['wspvars']) && trim($_SESSION['wspvars']['wspbaselang'])!=''):
	$_SESSION['wspvars']['locallang'] = $_SESSION['wspvars']['wspbaselang'];
endif;

// try to get plugin wsplang
$plugin_sql = "SELECT * FROM `wspplugins`";
$plugin_res = doSQL($plugin_sql);
if ($plugin_res['num']>0) {
	for ($pres=0; $pres<$plugin_res['num']; $pres++) {
		$pluginident = $plugin_res['set'][$pres]['guid'];
		$pluginfolder = $plugin_res['set'][$pres]['pluginfolder'];
		if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/data/include/wsplang.inc.php")) {
			@require_once ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/plugins/".$pluginfolder."/data/include/wsplang.inc.php");
		}
	}
}

// set ftp port if not set

if (!(isset($_SESSION['wspvars']['ftpport']))): $_SESSION['wspvars']['ftpport'] = 21; endif;

// EOF ?>