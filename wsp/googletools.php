<?php
/**
 * google-setup
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 4.0
 * @version 6.8
 * @lastchange 2019-01-19
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/funcs.inc.php");
// checkParamVar -----------------------------
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------

if (isset($_POST['save_data'])):
	foreach ($_POST AS $key => $value):
		if ($key!="save_data"):
			$deletedata_sql = "DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'";
			doSQL($deletedata_sql);
			$insertdata_sql = "INSERT INTO `wspproperties` SET `varname` = '".escapeSQL(trim($key))."', `varvalue` = '".escapeSQL(trim($value))."'";
			doSQL($insertdata_sql);
		endif;
	endforeach;
	$_SESSION['wspvars']['resultmsg'] = "<p>".returnIntLang('text props saved', false)."</p>";
endif;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

$siteinfo_sql = "SELECT * FROM `wspproperties`";
$siteinfo_res = doSQL($siteinfo_sql);
if ($siteinfo_res['num']>0):
	foreach ($siteinfo_res['set'] AS $sresk => $sresv):
		$sitedata[trim($sresv['varname'])] = stripslashes($sresv['varvalue']);
	endforeach;
endif;

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('googlepref headline'); ?></h1></fieldset>
	<fieldset><?php echo returnIntLang('googlepref used'); ?></fieldset>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="application/x-www-form-urlencoded" id="frmprefs" style="margin: 0px;">
	<fieldset id="fieldset_google">
		<legend><?php echo returnIntLang('googlepref headline'); ?> <?php echo legendOpenerCloser('fieldset_google_content', 'closed'); ?></legend>
		<div id="fieldset_google_content">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('googlepref verifyid'); ?></td>
					<td class="tablecell six"><input name="googleverify" type="text" value="<?php echo (isset($sitedata['googleverify'])?$sitedata['googleverify']:''); ?>" maxlength="50" style="width: 98%;"></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('googlepref analytics'); ?></td>
					<td class="tablecell six"><textarea name="googleanalytics" id="googleanalytics" class="full large noresize"><?php echo (isset($sitedata['googleverify'])?$sitedata['googleanalytics']:''); ?></textarea></td>
				</tr>
			</table>
			<p><?php echo returnIntLang('googlepref analytics info'); ?></p>
		</div>
		</fieldset>
		<fieldset class="options">
			<p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a><input name="save_data" type="hidden" value="Speichern" /></p>
		</fieldset>
	</form>
</div>
<?php include ("data/include/footer.inc.php"); ?>
<!-- EOF -->