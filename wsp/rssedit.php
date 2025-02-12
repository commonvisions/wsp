<?php
/**
 * editor for RSS-feeds
 * @author COVI
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
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
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
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

/* define page specific funcs ---------------- */

if (isset($_POST['op']) && $_POST['op']=='edit' && isset($_POST['id']) && intval($_POST['id'])>0):
	$_SESSION['wspvars']['rsseditid'] = intval($_POST['id']);
	header ("location: rssentry.php");
endif;

if (isset($_POST['op']) && $_POST['op']=='config' && isset($_POST['id']) && intval($_POST['id'])>0):
	$_SESSION['wspvars']['rssfeedid'] = intval($_POST['id']);
	header ("location: rssfeed.php");
	die();
endif;

if (isset($_POST['op']) && $_POST['op']=='preview' && isset($_POST['id']) && intval($_POST['id'])>0):
	$_SESSION['wspvars']['rssfeedid'] = intval($_POST['id']);
	header ("location: rssparser.php");
	die();
endif;

if (isset($_POST['op']) && $_POST['op']=='delete' && isset($_POST['id']) && intval($_POST['id'])>0):
	$rssdel_sql = "SELECT * FROM `rssdata` WHERE `rid` = ".intval($_POST['id']);
	$rssdel_res = doSQL($rssdel_sql);
	if ($rssdel_res['num']>0):
		doSQL("DELETE FROM `rssdata` WHERE `rid` = ".intval($_POST['id']));
		doSQL("DELETE FROM `rssentries` WHERE `rid` = ".intval($_POST['id']));
		
        addWSPMsg('resultmsg', "Der Feed <strong>".trim($rssdel_res['set'][0]['rsstitle'])."</strong> wurde gel&ouml;scht.");
		
		// ftp-connect
        $ftp = false;

		if ($ftp!==false):
			$tmppath = str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/");
			$tmpfile = tempnam($tmppath, '');
			if (!(@ftp_delete($ftp, str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/media/rss/".trim($rssdel_res['set'][0]['rssfilename']).'.rss')))):
				addWSPMsg('errormsg', "Die Feed-Datei konnte nicht gel&ouml;scht werden. (<em>File not found</em>)");
			else:
				addWSPMsg('resultmsg', "Die Datei zum Feed <strong>".trim($rssdel_res['set'][0]['rsstitle'])."</strong> wurde gel&ouml;scht.");
			endif;
		endif;
		ftp_close($ftp);
	endif;
endif;

$_SESSION['wspvars']['rssfeedid'] = 0;

// head der datei
require ("./data/include/header.inc.php");
require ("./data/include/wspmenu.inc.php");

?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('rssedit headline'); ?></h1></fieldset>
	<fieldset><p><?php echo returnIntLang('rssedit desc'); ?></p></fieldset>
	<?php
	$rssdata_sql = "SELECT * FROM `rssdata`";
	$rssdata_res = doSQL($rssdata_sql);

	if ($rssdata_res['num']>0): ?>
		<fieldset>
			<legend><strong><?php echo returnIntLang('rssedit existingrss'); ?></strong></legend>
			<script language="JavaScript1.2" type="text/javascript">
			<!--
			
			function confirmDelete(rssid) {
				if (confirm('<?php echo returnIntLang('rssedit confirm delete', false); ?>')) {
					document.getElementById('rssid').value = rssid; 
					document.getElementById('rssop').value = 'delete'; 
					document.getElementById('rssaction').submit();
					}
				}
			
			// -->
			</script>
			<ul class="tablelist">
				<li class="tablecell two head"><?php echo returnIntLang('rssedit rssname'); ?></li>
				<li class="tablecell two head"><?php echo returnIntLang('str author'); ?></li>
				<li class="tablecell two head"><?php echo returnIntLang('rssedit entries'); ?></li>
				<li class="tablecell two head"><?php echo returnIntLang('str action'); ?></li>
				<?php foreach ($rssdata_res['set'] AS $rssdk => $rssdv): ?>
					<li class="tablecell two"><?php
					
					if (trim($rssdv["rsstitle"])!=""):
						echo trim($rssdv["rsstitle"]);
					else:
						echo returnIntLang('rssedit no name specified');
					endif;
					
					?></li>
					<li class="tablecell two"><?php echo trim($rssdv["rssauthor"]); ?></li>
					<li class="tablecell two"><?php
					
					$rssentry_sql = "SELECT `eid` FROM `rssentries` WHERE `rid` = ".intval($rssdv["rid"]);
					$rssentry_res = doSQL($rssentry_sql);
					if ($rssentry_res['num']!=1):
						echo $rssentry_res['num']." ".returnIntLang('str entries');
					else:
						echo $rssentry_res['num']." ".returnIntLang('str entry');
					endif;
					
					$rssopen_sql = "SELECT `eid` FROM `rssentries` WHERE `rid` = ".intval($rssdv["rid"])." AND `epublished` = 0";
					$rssopen_res = doSQL($rssopen_sql);
					if ($rssopen_res['num']>0):
						echo " [".$rssopen_res['num']." ".returnIntLang('str new')."]";
					endif;
					
					?></li>
					<li class="tablecell two"><?php
					
					echo "<a onclick=\"document.getElementById('rssid').value = '".intval($rssdv["rid"])."'; document.getElementById('rssop').value = 'edit'; document.getElementById('rssaction').submit(); \"><span class=\"bubblemessage green\">";
					if ($rssopen_res['num']>0):
						echo strtoupper(returnIntLang('bubble edit', false));
					else:
						echo strtoupper(returnIntLang('bubble add', false));
					endif;
					echo "</span></a> ";
					
					echo "<a onclick=\"document.getElementById('rssid').value = '".intval($rssdv["rid"])."'; document.getElementById('rssop').value = 'config'; document.getElementById('rssaction').submit(); \"><span class=\"bubblemessage orange\">".strtoupper(returnIntLang('str config', false))."</span></a> ";
					if ($rssentry_res['num']>0):
						echo "<a onclick=\"document.getElementById('rssid').value = '".intval($rssdv["rid"])."'; document.getElementById('rssop').value = 'preview'; document.getElementById('rssaction').submit(); \"><span class=\"bubblemessage\">RSS</span></a> ";
					endif; 
					
                    echo "<a onclick=\"confirmDelete(".intval($rssdv["rid"]).")\"><span class=\"bubblemessage red\">".strtoupper(returnIntLang('bubble delete', false))."</span></a> ";
					
					?></li>
				<?php endforeach; ?>
			</ul>
		</fieldset>
		<form name="rssaction" id="rssaction" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="hidden" name="op" id="rssop" value="" />
		<input type="hidden" name="id" id="rssid" value="" />
		</form>
	<?php else: ?>
		<fieldset><p><?php echo returnIntLang('rssedit nofeed') ?></p></fieldset>
	<?php endif; ?>
	<fieldset class="options">
		<p><a href="rssfeed.php" class="greenfield"><?php echo returnIntLang('rssedit newfeed', false); ?></a></p>
	</fieldset>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->