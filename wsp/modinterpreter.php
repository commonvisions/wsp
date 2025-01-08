<?php
/**
 * WSP-Modul ausfuehren
 * @author COVI
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-25
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
require ("./data/include/filesystemfuncs.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'modinterpreter';
$_SESSION['wspvars']['mgroup'] = 99;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$op = checkParamVar('op', '');
$mod = checkParamVar('mod', '');
$wspvars['mod']['mid'] = checkParamVar('modid', 0);
$mod_sql = 'SELECT w.`link`, w.`parent_id`, w.`guid` FROM `wspmenu` w, `modules` m WHERE w.`module_guid` = m.`guid` && w.`id` = '.intval($wspvars['mod']['mid']);
$mod_res = doSQL($mod_sql);
$mod_num = $mod_res['num'];
// redefining FPOS
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";mod=".intval($wspvars['mod']['mid']);
if ($mod_num>0):
	if (intval($mod_res['set'][0]["parent_id"])!=0):
		$wspvars['mgroup'] = 20 + intval($mod_res['set'][0]["parent_id"]);
	else:
		$wspvars['mgroup'] = 20 + $wspvars['mod']['mid'];
	endif;
	$wspvars['lockstat'] = trim($mod_res['set'][0]["guid"]);
endif;

// get request vars from goto
if (isset($_SESSION['modgotoparam']) && is_array($_SESSION['modgotoparam'])):
    $_POST = $_SESSION['modgotoparam'];
    unset($_SESSION['modgotoparam']);
endif;

// head der datei

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

?>
<div id="contentholder">
<?php

if (!($mod_num>0) && !(is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/modules/".trim($mod_res['set'][0]["link"])))):
	$errormsg .= returnIntLang('module not found', true);
	if ($errormsg!=""):
		echo "<fieldset class=\"errormsg\">".$errormsg."</fieldset>";
	endif;
else:
	$moddir = explode("/", trim($mod_res['set'][0]["link"]));
	if (trim($moddir[0])!=""):
		if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/modules/".trim($mod_res['set'][0]["link"]))):
            if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/modules/".$moddir[0]."/lang.inc.php")):
                include($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/modules/".$moddir[0]."/lang.inc.php");
                if (is_array($modlang[$_SESSION['wspvars']['locallang']])):
                    $lang[$_SESSION['wspvars']['locallang']] = array_merge($lang[$_SESSION['wspvars']['locallang']], $modlang[$_SESSION['wspvars']['locallang']]);
                else:
                    echo "<fieldset><p>".returnIntLang('module not localized', true)."</p></fieldset>";
                endif;
            else:
                echo "<fieldset><p>".returnIntLang('module not localized', true)."</p></fieldset>";
            endif;
        endif;
	endif;
	if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/modules/".trim($mod_res['set'][0]["link"]))):
        include($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/modules/".trim($mod_res['set'][0]["link"]));
    else:
        echo "<fieldset class='errormsg'>".returnIntLang('module not found', true)."</fieldset>";
    endif;
endif;

?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->