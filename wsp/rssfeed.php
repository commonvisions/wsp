<?php
/**
 * Editor fuer die RSS-Feeds
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-24
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'rss';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

// seitenspezifische funktionen

if (isset($_POST) && array_key_exists('saverss', $_POST) && $_POST['saverss']=="save"):
	if ($_POST['rss_filename']=="" || $_POST['rss_title']=="" || $_POST['rss_href']=="" || $_POST['rss_author']==""):
		addWSPMsg('errormsg', "Bitte tragen Sie in alle Felder au&szlig;er 'Kurzbeschreibung', dieses Feld ist optional, Werte ein.");
		// weitere fehlerabfragen integrieren (z.b. richtiger href, richtiger dateiname, doppelter dateiname
	else:
		if (intval($_POST['rssfeedid'])==0):
			$sql = "INSERT INTO `rssdata` SET 
				`rssfilename` = '".escapeSQL(trim($_POST['rss_filename']))."', 
				`rsstitle` = '".escapeSQL(trim($_POST['rss_title']))."',
				`rsssubtitle` = '".escapeSQL(trim($_POST['rss_subtitle']))."',
				`rsshref` = '".escapeSQL(trim($_POST['rss_href']))."',
				`rssauthor` = '".escapeSQL(trim($_POST['rss_author']))."',
				`rssid` = '".escapeSQL(trim($_POST['rss_href']))."'";
			$res = doSQL($sql);
			$_SESSION['wspvars']['noticemsg'] = "Der Feed <strong>'".$_POST['rss_title']."'</strong> wurde angelegt.<br />";
			$_SESSION['wspvars']['rssfeedid'] = intval($res['inf']);
		else:
			$sql = "UPDATE `rssdata` SET 
				`rssfilename` = '".escapeSQL(trim($_POST['rss_filename']))."', 
				`rsstitle` = '".escapeSQL(trim($_POST['rss_title']))."',
				`rsssubtitle` = '".escapeSQL(trim($_POST['rss_subtitle']))."',
				`rsshref` = '".escapeSQL(trim($_POST['rss_href']))."',
				`rssauthor` = '".escapeSQL(trim($_POST['rss_author']))."',
				`rssid` = '".escapeSQL(trim($_POST['rss_href']))."'
				WHERE `rid` = ".intval($_POST['rssfeedid']);
			doSQL($sql);
			addWSPMsg('noticemsg', "Die Einstellungen zum Feed <strong>'".$_POST['rss_title']."'</strong> wurde aktualisiert.");
			$_SESSION['wspvars']['rssfeedid'] = intval(intval($_POST['rssfeedid']));
		endif;
	endif;
endif;

$rssinfo_num = 0; 
if (isset($_SESSION) && intval($_SESSION['wspvars']['rssfeedid'])>0):
	$rssinfo_sql = "SELECT * FROM `rssdata` WHERE `rid` = ".intval($_SESSION['wspvars']['rssfeedid']);
	$rssinfo_res = doSQL($rssinfo_sql);
	$rssinfo_num = intval($rssinfo_res['num']);
endif;
if ($rssinfo_num>0):
	$rss_filename = trim($rssinfo_res['set'][0]['rssfilename']);
	$rss_title = trim($rssinfo_res['set'][0]['rsstitle']);
	$rss_shortdesc = trim($rssinfo_res['set'][0]['rsssubtitle']);
	$rss_baselink = trim($rssinfo_res['set'][0]['rsshref']);
	$rss_author = trim($rssinfo_res['set'][0]['rssauthor']);
	$rss_feedid = intval($rssinfo_res['set'][0]['rid']);
else:
	$rss_filename = '';
	$rss_title = '';
	$rss_shortdesc = '';
	$rss_baselink = $_SESSION['wspvars']['workspaceurl'];
	$rss_author = '';
	$rss_feedid = 0;
endif;

// head der datei
require "./data/include/header.inc.php";
require "./data/include/wspmenu.inc.php";
?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('rssedit headline'); ?></h1></fieldset>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" name="rssfeed" id="rssfeed" method="post">
	<fieldset>
		<legend><?php echo returnIntLang('rssedit new basics legend'); ?></legend>
		<table class="tablelist">
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('str filename'); ?></td>
				<td class="tablecell two"><input name="rss_filename" id="rss_filename" type="text" maxlength="60" value="<?php echo $rss_filename; ?>" />&nbsp;.&nbsp;rss</td>
				<td class="tablecell two"><?php echo returnIntLang('str title'); ?></td>
				<td class="tablecell two"><input name="rss_title" id="rss_title" type="text" value="<?php echo $rss_title; ?>" maxlength="150" class="six" /></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('rssedit new shortdesc'); ?></td>
				<td class="tablecell six"><textarea name="rss_subtitle" id="rss_subtitle" rows="3" cols="80" class="full medium noresize"><?php echo $rss_shortdesc; ?></textarea></td>
			</tr>
			<tr>
				<td class="tablecell two"><?php echo returnIntLang('rssedit new baselink'); ?></td>
				<td class="tablecell two"><input name="rss_href" id="rss_href" type="text" class="four" maxlength="150" value="<?php echo $rss_baselink; ?>" /></td>
				<td class="tablecell two"><?php echo returnIntLang('str author'); ?></td>
				<td class="tablecell two"><input name="rss_author" id="rss_author" type="text" size="40" maxlength="150" value="<?php echo $rss_author; ?>" /><input type="hidden" name="saverss" value="save" /><input type="hidden" name="rssfeedid" value="<?php echo intval($rss_feedid); ?>" /></td>
			</tr>
		</table>
	</fieldset>
	<fieldset class="options">
		<p><a onClick="document.getElementById('rssfeed').submit();" class="greenfield"><?php echo returnIntLang('str save'); ?></a> <a href="rssedit.php" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p>
	</fieldset>
	</form>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->