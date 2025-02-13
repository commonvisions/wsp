<?php
/**
 * search engine related properties
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-22
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

@include 'data/include/config.inc.php';

if (isset($_POST['save_data'])):
	foreach ($_POST AS $key => $value):
		if ($key!="save_data"):
			doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL($key)."'");
			if (is_array($value)):
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL(serialize($value))."'");
			else:
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL($key)."', `varvalue` = '".escapeSQL($value)."'");
			endif;
		endif;
	endforeach;
endif;

$siteinfo_sql = "SELECT * FROM `wspproperties`";
$siteinfo_res = doSQL($siteinfo_sql);
if ($siteinfo_res['num']>0):
	foreach ($siteinfo_res['set'] AS $sresk => $sresv):
		$sitedata[trim($sresv['varname'])] = $sresv['varvalue'];
	endforeach;
endif;

// head der datei
include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('seo headline'); ?></h1></fieldset>
	<fieldset class="text"><p><?php echo returnIntLang('seo info'); ?></p></fieldset>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmprefs" style="margin: 0px;">
	<fieldset id="fieldset_3" class="text">
		<legend><?php echo returnIntLang('seo legend'); ?></legend>
		<div id="fieldset_semanagement_content">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('seo index'); ?></td>
					<td class="tablecell two"><select name="siterobots" id="siterobots" size="1" class="four full">
					<option value="none" <?php if ($sitedata['siterobots']=="none") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots none', false); ?></option>
					<option value="nofollow" <?php if ($sitedata['siterobots']=="nofollow") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots nofollow', false); ?></option>
					<option value="noindex" <?php if ($sitedata['siterobots']=="noindex") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots noindex', false); ?></option>
					<option value="all" <?php if ($sitedata['siterobots']=="all") echo "selected=\"selected\""; ?>><?php echo returnIntLang('seo robots all', false); ?></option>
				</select></td>
					<td class="tablecell two"><?php echo returnIntLang('seo robotsinterval'); ?></td>
					<td class="tablecell two"><input name="robotinterval" value="<?php echo $sitedata['robotinterval']; ?>" size="3em" > <?php echo returnIntLang('str days'); ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('seo title'); ?> <?php helptext(returnIntLang('seo title help', false)); ?></td>
					<td class="tablecell four"><input name="sitetitle" id="sitetitle" type="text" value="<?php echo prepareTextField(stripslashes($sitedata['sitetitle'])); ?>" maxlength="250" class="four full" onkeyup="showQuality('sitetitle',80,200);" /></td>
					<td class="tablecell two"><span id="show_sitetitle_length"><?php echo strlen(prepareTextField(stripslashes($sitedata['sitetitle']))); ?></span> (<?php echo returnIntLang('seo str max'); ?> 200) <?php echo returnIntLang('str chars'); ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('str shortdesc'); ?></td>
					<td class="tablecell four"><textarea name="sitedesc" id="sitedesc" cols="20" rows="5" class="four full small noresize" onkeyup="showQuality('sitedesc',150,300);"><?php echo (isset($sitedata['sitedesc'])?prepareTextField(stripslashes($sitedata['sitedesc'])):''); ?></textarea></td>
					<td class="tablecell two"><span id="show_sitedesc_length"><?php echo strlen((isset($sitedata['sitedesc'])?prepareTextField(stripslashes($sitedata['sitedesc'])):'')); ?></span> (<?php echo returnIntLang('seo str max'); ?> 300) <?php echo returnIntLang('str chars'); ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('seo keywords'); ?></td>
					<td class="tablecell four"><textarea name="sitekeys" id="sitekeys" cols="20" rows="7" class="four full medium noresize" onkeyup="showQuality('sitekeys',300,1000);"><?php echo (isset($sitedata['sitekeys'])?prepareTextField(stripslashes($sitedata['sitekeys'])):''); ?></textarea></td>
					<td class="tablecell two"><span id="show_sitekeys_length"><?php echo strlen((isset($sitedata['sitekeys'])?prepareTextField(stripslashes($sitedata['sitekeys'])):'')); ?></span> (<?php echo returnIntLang('seo str max'); ?> 1000) <?php echo returnIntLang('str chars'); ?></td>
				</tr>
			</table>
			<p><?php echo returnIntLang('seo user var in title1'); ?> [%PAGENAME%] <?php echo returnIntLang('seo user var in title2'); ?></p>
		</div>
	</fieldset>
	<fieldset class="options">
		<p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a><input name="save_data" type="hidden" value="Speichern" /></p>
	</fieldset>
	</form>
	<script language="JavaScript" type="text/javascript">
	<!--
	
	showQuality('sitetitle',80,200);
	showQuality('sitedesc',150,300);
	showQuality('sitekeys',300,1000);
	
	// -->
	</script>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->