<?php
/**
 *
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0.1
 * @lastchange 2021-09-21
 */

// redirect to secure connection
if (intval(getWSPProperties('sslmode'))==1 && $_SERVER['REQUEST_SCHEME']=='http') { header('location: https://'.str_replace("//", "/", $_SERVER['HTTP_HOST'].'/'.$_SERVER['SCRIPT_URL'])); }

if (isset($_REQUEST['night']) && $_REQUEST['night']=='off') { $_SESSION['wspvars']['daily'] = true; }

$_SESSION['wspvars']['workspaceurl'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'devurl'");
$_SESSION['wspvars']['liveurl'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'siteurl'");
if (trim($_SESSION['wspvars']['workspaceurl'])==""): $_SESSION['wspvars']['workspaceurl'] = $_SESSION['wspvars']['liveurl']; endif;
$_SESSION['wspvars']['googlemaps'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'googlemaps'");
$_SESSION['wspvars']['wspstyle'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'use_css'");
$_SESSION['wspvars']['failedlogins'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'failedlogins'");
$_SESSION['wspvars']['errorreporting'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'errorreporting'");
$_SESSION['wspvars']['cookiedays'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'cookiedays'"));
$_SESSION['wspvars']['cookielogin'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'cookielogin'"));
$_SESSION['wspvars']['backupsteps'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'backupsteps'"));
$_SESSION['wspvars']['localversion'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'lastversion'");
if ($_SESSION['wspvars']['backupsteps']<3): $_SESSION['wspvars']['backupsteps'] = 3; endif;
$_SESSION['wspvars']['autologout'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'autologout'"));
if ($_SESSION['wspvars']['autologout']<15): $_SESSION['wspvars']['autologout'] = 15; endif;
$_SESSION['wspvars']['wspbaselang'] = trim(getWSPProperties('wspbaselang'));
$_SESSION['wspvars']['sitelanguages'] = trim(getWSPProperties('languages'));
if ($_SESSION['wspvars']['sitelanguages']=="") {
	$_SESSION['wspvars']['sitelanguages'] = array('longname' => array('Deutsch'), 'shortcut' => array('de'));
}
else {
    $tmpsitelang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
    if (isset($tmpsitelang) && count($tmpsitelang['shortcut'])>0) {
        $_SESSION['wspvars']['sitelanguages'] = $tmpsitelang;
    }
    else {
        if ($_SESSION['wspvars']['wspbaselang']!='') {
            $_SESSION['wspvars']['sitelanguages'] = array('longname' => array($_SESSION['wspvars']['wspbaselang']), 'shortcut' => array($_SESSION['wspvars']['wspbaselang']));
        }
        else if (defined('WSP_LANG')) {
            $_SESSION['wspvars']['sitelanguages'] = array('longname' => array(WSP_LANG), 'shortcut' => array(WSP_LANG));
        }
        else {
            $_SESSION['wspvars']['sitelanguages'] = array('longname' => array('Deutsch'), 'shortcut' => array('de'));
        }
    }
}
$_SESSION['wspvars']['lang'] = array();
foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $slk => $slv) {
	$_SESSION['wspvars']['lang'][] = array($_SESSION['wspvars']['sitelanguages']['shortcut'][$slk], $_SESSION['wspvars']['sitelanguages']['longname'][$slk]);
}
$_SESSION['wspvars']['showlang'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'showlang'");
$_SESSION['wspvars']['setlang'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'setlang'");
if (!(isset($_SESSION['wspvars']['workspacelang']))) {
    if (defined('WSP_LANG')) {
        $_SESSION['wspvars']['workspacelang'] = WSP_LANG;
    } 
    else {
        $_SESSION['wspvars']['workspacelang'] = 'de';
    }
}
else {
    if (!(in_array($_SESSION['wspvars']['workspacelang'], $_SESSION['wspvars']['sitelanguages']['shortcut']))) {
        if (defined('WSP_LANG')) {
            $_SESSION['wspvars']['workspacelang'] = WSP_LANG;
        } 
        else {
            $_SESSION['wspvars']['workspacelang'] = 'de';
        }
    }
}

$_SESSION['wspvars']['displaymedia'] = doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'displaymedia'");
$_SESSION['wspvars']['stripslashes'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'stripslashes'"));
$_SESSION['wspvars']['usesession'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'use_session'")); // to parser
$_SESSION['wspvars']['usetracking'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'use_tracking'")); // to parser
$_SESSION['wspvars']['mailclass'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'mailclass'")); // to parser
$_SESSION['wspvars']['menustyle'] = intval(getWSPProperties('menustyle'));
$_SESSION['wspvars']['wspstyle'] = trim(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'wspstyle'"));
$_SESSION['wspvars']['handledelete'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'deletedmenu'"));
$_SESSION['wspvars']['overwriteuploads'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'overwriteuploads'"));
$_SESSION['wspvars']['noautoindex'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'noautoindex'"));
$_SESSION['wspvars']['hiddenmenu'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddenmenu'"));
$_SESSION['wspvars']['nocontentmenu'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'nocontentmenu'"));
$_SESSION['wspvars']['bindcontentview'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'bindcontentview'"));
$_SESSION['wspvars']['autoparsestructure'] = intval(getWSPProperties('autoparsestructure'));
$_SESSION['wspvars']['autoparsecontent'] = intval(getWSPProperties('autoparsecontent'));
$_SESSION['wspvars']['shownotice'] = intval(getWSPProperties('shownotice'));
$_SESSION['wspvars']['showlegend'] = intval(getWSPProperties('showlegend'));
$_SESSION['wspvars']['nightmode'] = intval(getWSPProperties('nightmode'));
$_SESSION['wspvars']['startnight'] = ((intval(getWSPProperties('startnight'))!=0)?intval(getWSPProperties('startnight')):20);
$_SESSION['wspvars']['endnight'] = ((intval(getWSPProperties('endnight'))!=0)?intval(getWSPProperties('endnight')):8);
$_SESSION['wspvars']['stripfilenames'] = intval(getWSPProperties('stripfilenames'));
if (intval($_SESSION['wspvars']['stripfilenames'])<60): $_SESSION['wspvars']['stripfilenames'] = 60; endif;
if (!(isset($_SESSION['wspvars']['opentabs']))) $_SESSION['wspvars']['panelopener'] = array();

if (isset($_SESSION['wspvars']['wspbaselang']) && trim($_SESSION['wspvars']['wspbaselang'])!='') {
    if (isset($_SESSION['wspvars']['locallang']) && trim($_SESSION['wspvars']['locallang'])!='') {
        $_SESSION['wspvars']['locallang'] = $_SESSION['wspvars']['locallang'];
    }
    else {
        $_SESSION['wspvars']['locallang'] = $_SESSION['wspvars']['wspbaselang'];
    }
}
else if (defined('WSP_LANG')) {
    $_SESSION['wspvars']['wspbaselang'] = WSP_LANG;
    $_SESSION['wspvars']['locallang'] = WSP_LANG;
} 
else {
    $_SESSION['wspvars']['wspbaselang'] = 'de';
    $_SESSION['wspvars']['locallang'] = 'de';
}

/* temp. disabled

// get plugin wsplang
$plugin_sql = "SELECT * FROM `wspplugins`";
$plugin_res = doSQL($plugin_sql);
$plugin_num = $plugin_res['num'];
if ($plugin_num>0): foreach ($plugin_res['set'] AS $pk => $pv): $pluginident = $pv['guid']; $pluginfolder = $pv['pluginfolder']; if (is_file(DOCUMENT_ROOT."/".WSP_DIR."/plugins/".$pluginfolder."/data/include/wsplang.inc.php")): @require_once (DOCUMENT_ROOT."/".WSP_DIR."/plugins/".$pluginfolder."/data/include/wsplang.inc.php"); endif; endforeach; endif; 

*/
$plugin_num = 0;

ksort($_SESSION['wspvars']);

