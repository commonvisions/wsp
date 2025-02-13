<?php
/**
 * @description language tools
 * @author stefan@covi.de
 * @since 4.0
 * @version 6.8
 * @lastchange 2019-01-22
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("data/include/usestat.inc.php");
require ("data/include/globalvars.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'langtools';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;

/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$worklang = unserialize($_SESSION['wspvars']['sitelanguages']);
/* define page specific funcs ----------------- */

if (isset($_POST['transformlang']) && in_array(trim($_POST['transformlang']), $worklang['languages']['shortcut']) && trim($_POST['transformlang'])!="de"):
	$break = false;
	if (isset($_POST['replacecontents']) && $_POST['replacecontents']==1):
		if (trim($_POST['adminpass'])==""):
			addWSPMsg("errormsg", "<p>".returnIntLang('localisation confirm with admin pass', true)."</p>");
			$break = true;
		else:
			$admincheck_sql = "SELECT `rid` FROM `restrictions` WHERE `rid` = ".$_SESSION['wspvars']['userid']." AND `usertype` = '1' AND `pass` = '".md5(trim($_POST['adminpass']))."'";
			$admincheck_res = doSQL($admincheck_sql);
			if ($admincheck_res['num']==1):
				$sql = "UPDATE `content` SET `visibility` = 0, `trash` = 1 WHERE `content_lang` = '".$_POST['transformlang']."'";
                $res = doSQL($sql);
				if ($res['res']):
					addWSPMsg("noticemsg", "<p>Die Inhalte [".$_POST['transformlang']."] wurden gel&ouml;scht.</p>");
				else:
					addWSPMsg("errormsg", "<p>".returnIntLang('localisation error overwriting contents', true)."</p>");
					$break = true;
				endif;
			else:
				addWSPMsg("errormsg", "<p>".returnIntLang('localisation confirm with admin pass', true)."</p>");
				$break = true;
			endif;
		endif;
	endif;
	if (!$break):
		if (intval($_POST['contentarea'])==0):
			$content_sql = "SELECT * FROM `content` WHERE `trash` = 0 && `content_lang` = '".$_POST['transformfrom']."' ORDER BY `cid`";
			$content_res = doSQL($content_sql);
            $content_num = $content_res['num'];
		elseif (intval($_POST['includesubs'])==0):
			$content_sql = "SELECT * FROM `content` WHERE `trash` = 0 && `mid` = ".intval($_POST['contentarea'])." && `content_lang` = '".$_POST['transformfrom']."' ORDER BY `cid`";
			$content_res = doSQL($content_sql);
            $content_num = $content_res['num'];
		else:
			$midarray = returnIDRoot(intval($_POST['contentarea']), array(intval($_POST['contentarea'])));
			$content_sql = "SELECT * FROM `content` WHERE `trash` = 0 && `mid` IN ('".implode("','", $midarray)."') && `content_lang` = '".$_POST['transformfrom']."' ORDER BY `cid`";
			$content_res = doSQL($content_sql);
            $content_num = $content_res['num'];
		endif;
		if ($content_num>0):
			$sqlstat = 0;
            foreach ($content_res['set'] AS $cresk => $cresv) {
				$sql = "INSERT INTO `content` (`mid`, `globalcontent_id`, `connected`, `content_area`, `content_lang`, `position`, `visibility`, `showday`, `showtime`, `sid`, `valuefields`, `xajaxfuncnames`, `lastchange`, `interpreter_guid`, `trash`, `container`, `containerclass`, `containeranchor`, `displayclass`) VALUES (".intval($cresv['mid']).", ".intval($cresv['globalcontent_id']).", ".intval($cresv['connected']).", ".intval($cresv['content_area']).", '".trim($_POST['transformlang'])."', ".intval($cresv['position']).", ".intval($cresv['visibility']).", '".escapeSQL(trim($cresv['showday']))."', '".escapeSQL(trim($cresv['showtime']))."', ".intval($cresv['sid']).", '".escapeSQL(trim($cresv['valuefields']))."', '".escapeSQL(trim($cresv['xajaxfuncnames']))."', '".time()."', '".trim($cresv['interpreter_guid'])."', 0, '".escapeSQL(trim($cresv['container']))."', '".escapeSQL(trim($cresv['containerclass']))."', '".escapeSQL(trim($cresv['containeranchor']))."', '".escapeSQL(trim($cresv['displayclass']))."')";
				$res = doSQL($sql);
                if ($res['res']):
					$sqlstat++;
				endif;
			}
			addWSPMsg("noticemsg", "<p>".$sqlstat." Inhalte [".$_POST['transformfrom']."] wurden in die neue Sprache [".$_POST['transformlang']."] &uuml;bertragen.</p>");
		endif;
	endif;
endif;

if (isset($_POST['freelang']) && trim($_POST['freelang'])!="de"):
	if (trim($_POST['adminpass'])==""):
		addWSPMsg("errormsg", "<p>".returnIntLang('localisation confirm with admin pass', true)."</p>");
	else:
		$admincheck_sql = "SELECT `rid` FROM `restrictions` WHERE `rid` = ".$wspvars['userid']." AND `usertype` = '1' AND `pass` = '".md5(trim($_POST['adminpass']))."'";
		$admincheck_res = doSQL($admincheck_sql);
        if ($admincheck_res['num']==1):
			$sql = "DELETE FROM `content` WHERE `content_lang` = '".$_POST['freelang']."'";
            $res = doSQL($sql);
			if ($res['res']):
				addWSPMsg('noticemsg', "Die Inhalte [".$_POST['freelang']."] wurden gel&ouml;scht.");
			endif;
		else:
			addWSPMsg("errormsg", returnIntLang('localisation confirm with admin pass', true));
		endif;
	endif;
endif;

// head der datei

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>
<div id="contentholder">
	<fieldset class="text"><h1><?php echo returnIntLang('localisation headline', true); ?></h1></fieldset>
	<fieldset class="text">
		<legend><?php echo returnIntLang('str legend', true); ?> <span class="opencloseButton bubblemessage" rel="wsplegend">↕</span></legend>
		<div id="wsplegend" style="<?php echo $_SESSION['opentabs']['wsplegend']; ?>">
			<p><?php echo returnIntLang('localisation legend', true); ?></p>
		</div>
	</fieldset>
	<?php if (count($worklang['languages']['shortcut'])>1): ?>
	<fieldset id="fieldset_1">
		<legend><?php echo returnIntLang('localisation usecontent', true); ?> <span class="opencloseButton bubblemessage" rel="contenttranslate">↕</span></legend>
		<div id="contenttranslate" style="<?php echo $_SESSION['opentabs']['contenttranslate']; ?>">
			<script language="JavaScript1.2" type="text/javascript">
			<!--
			
			function checkforpass() {
				if (document.getElementById('replacecontents').checked) {
					document.getElementById('adminpass').disabled = false;
					}
				else {
					document.getElementById('adminpass').value = '';
					document.getElementById('adminpass').disabled = true;
					}
				}
			
			function langtransform() {
				if (document.getElementById('replacecontents').checked) {
					if (document.getElementById('adminpass').value!="") {
						if (confirm('Wollen Sie sicher die bestehenden lokalisierten Inhalte durch die neuen Inhalte der Hauptsprache [Deutsch] ersetzen?')) {
							document.getElementById('translator').submit();
							}
						}
					else {
						alert ('<?php echo returnIntLang('localisation confirm with admin pass', false); ?>');
						}
					}
				else {
					document.getElementById('translator').submit();
					}
				}
			
			// -->
			</script>
			<form name="translator" id="translator" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<ul class="tablelist">
				<li class="tablecell two"><?php echo returnIntLang('localisation copycontent', true); ?></li>
				<li class="tablecell two"><select name="transformfrom" class="full"><?php
				
				foreach ($worklang['languages']['shortcut'] AS $key => $value):
					echo "<option value=\"".$worklang['languages']['shortcut'][$key]."\">".$worklang['languages']['longname'][$key]."</option>";
				endforeach;
				
				?></select></li>
				<li class="tablecell two"><?php echo returnIntLang('localisation pastecontent', true); ?></li>
				<li class="tablecell two"><select name="transformlang" class="full"><?php
				
				foreach ($worklang['languages']['shortcut'] AS $key => $value):
					if ($worklang['languages']['shortcut'][$key]!="de"):
						echo "<option value=\"".$worklang['languages']['shortcut'][$key]."\">".$worklang['languages']['longname'][$key]."</option>";
					endif;
				endforeach;
				
				?></select></li>
				<li class="tablecell two"><?php echo returnIntLang('localisation contentarea', true); ?></li>
				<li class="tablecell two"><select id="contentarea" name="contentarea" size="1" class="full">
					<option value="0"><?php echo returnIntLang('localisation fullpage'); ?></option>
					<?php getMenuLevel(0, 0, 1); ?>
				</select></li>
				<li class="tablecell two"><?php echo returnIntLang('localisation includesubs', true); ?></li>
				<li class="tablecell two"><input type="hidden" name="includesubs" value="0" /><input type="checkbox" name="includesubs" id="includesubs" value="1" /></li>
				<li class="tablecell two"><?php echo returnIntLang('localisation overwritecontents', true); ?> <?php helpText(returnIntLang('localisation overwritecontents helptext', false)); ?></li>
				<li class="tablecell two"><input type="checkbox" name="replacecontents" id="replacecontents" value="1" onchange="checkforpass();"></li>
				<li class="tablecell two"><?php echo returnIntLang('str password', true); ?> <?php helpText(returnIntLang('localisation password helptext', false)); ?></li>
				<li class="tablecell two"><input name="adminpass" id="adminpass" type="password" disabled="disabled" value="" class="full"></li>
			</ul>
			<fieldset class="options innerfieldset"><p><a href="#" onclick="langtransform();" class="greenfield"><?php echo returnIntLang('str doaction', false); ?></a></p></fieldset>
			</form>
		</div>
	</fieldset>
	<?php endif;
	
	$findlang_sql = "SELECT DISTINCT `content_lang` FROM `content`";
	$findlang_res = doSQL($findlang_sql);
	if ($findlang_res['num']>0):
		$contentlang = array();
		foreach ($findlang_res['set'] AS $flrk => $flrv) {
			if (trim($flrv['content_lang'])!=''):
				$contentlang[] = trim($flrv['content_lang']);
			endif;
        }
	endif;	

	$difflang = array_diff($contentlang, $worklang['languages']['shortcut']);
	if (count($difflang)>0):
		?>
		<fieldset id="fieldset_2">
			<legend><?php echo returnIntLang('localisation freelanguage', true); ?> <?php echo legendOpenerCloser('contentfreelang'); ?></legend>
			<div id="contentfreelang">
			<span id="fieldset_2_content">
				<script language="JavaScript1.2" type="text/javascript">
				<!--
				
				function freelang() {
					if (document.getElementById('free_adminpass').value!="") {
						if (confirm('<?php echo returnIntLang('localisation freelang confirm', true); ?>')) {
							document.getElementById('freezer').submit();
							}
						}
					else {
						alert ('<?php echo returnIntLang('localisation password freelang helptext', true); ?>');
						}
					}
				
				// -->
				</script>
				<form name="freezer" id="freezer" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
				<ul class="tablelist">
					<li class="tablecell two"><?php echo returnIntLang('localisation freelangfrom', true); ?></li>
					<li class="tablecell two"><select name="freelang"><?php
					
					foreach ($difflang AS $key => $value):
						if ($value!="de"):
							echo "<option value=\"".$value."\">".$value."</option>";
						endif;
					endforeach;
					
					?></select></li>
					<li class="tablecell two"><?php echo returnIntLang('str password', true); ?> <?php helpText(returnIntLang('localisation password freelang helptext', false)); ?>
					<li class="tablecell two"><input name="adminpass" id="free_adminpass" type="password" value=""></li>
					<li class="tablecell eight"><?php echo returnIntLang('localisation freelangdesc', true); ?></li>
				</li>
				<fieldset class="options innerfieldset"><p><a href="#" onclick="freelang();" class="greenfield"><?php echo returnIntLang('str doaction', false); ?></a></p></fieldset>
				</form>
			</div>
		</fieldset>
	<?php endif; ?>
</div>
<?php include ("data/include/footer.inc.php"); ?>
<!-- EOF -->