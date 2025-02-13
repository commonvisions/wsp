<?php
/**
 * RSS-Entry???
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-25
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("data/include/usestat.inc.php");
require ("data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'rss';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$feedid = intval($_SESSION['wspvars']['rsseditid']);

// seitenspezifische funktionen
if (isset($_POST['newentry'])):
	if (isset($_POST['etitle']) && $_POST['etitle']==""):
		addWSPMsg('errormsg', returnIntLang('rssedit insert values in all fields'));
	elseif ($_POST['linktype']=="linkbycms" && $_POST['linkbycms']==0):
		addWSPMsg('errormsg', returnIntLang('rssedit chosse menupoint to connect feed'));
		// weitere fehlerabfragen ...

	else:
		if ($_POST['linktype']=="linkbycms"):
			$econtype = "mid";
			$econ = $_POST['linkbycms'];
		else:
			$econtype = "url";
			$econ = $_POST['linkbyurl'];
		endif;
		if ($_POST['newentry']=="create"):
			$sql = "INSERT INTO `rssentries` SET
				`rid` = ".$feedid.",
				`econtype` = '".$econtype."',
				`econ` = '".$econ."',
				`eauthor` = '".escapeSQL($_SESSION['wspvars']['realname'])."',
				`etitle` = '".escapeSQL(trim($_POST['etitle']))."',
				`esummary` = '".escapeSQL(trim($_POST['esummary']))."',
				`eupdate` = '".time()."'
				";
			if (doSQL($sql)['res']):
				addWSPMsg('noticemsg', "Der Eintrag wurde angelegt.");
				unset($_POST);
			else:
				addWSPMsg("Der Eintrag wurde nicht angelegt.", 'errormsg');
			endif;
		elseif ($_POST['newentry']=="update"):
			$sql = "UPDATE `rssentries` SET
				`econtype` = '".$econtype."',
				`econ` = '".$econ."',
				`eauthor` = '".escapeSQL($_SESSION['wspvars']['realname'])."',
				`etitle` = '".escapeSQL(trim($_POST['etitle']))."',
				`esummary` = '".escapeSQL(trim($_POST['esummary']))."',
				`eupdate` = '".time()."'
				WHERE `eid` = ".intval($_POST['eid']);
			if (doSQL($sql)['res']):
				addWSPMsg('noticemsg', "Der Eintrag wurde aktualisiert.");
				unset($_POST);
			else:
				addWSPMsg('errormsg', "Der Eintrag wurde nicht aktualisiert.");
			endif;
		endif;
	endif;
endif;

if (isset($_POST['newentry']) && $_POST['newentry']=="delete" && isset($_POST['eid']) && intval($_POST['eid'])>0):
	$sql = "DELETE FROM `rssentries` WHERE `eid` = ".intval($_POST['eid']);
	if (doSQL($sql)['res']): addWSPMsg('noticemsg', "Der Eintrag wurde gelöscht."); endif;
endif;

// head der datei
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/header.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wspmenu.inc.php";
?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('rssedit headline'); ?></h1></fieldset>
	<?php
	$rssdata_sql = "SELECT `rsstitle` FROM `rssdata` WHERE `rid` = ".intval($feedid);
	$rssdata_res = doSQL($rssdata_sql);
	if ($rssdata_res['num']!=0):
	?>
	<fieldset><p><?php echo returnIntLang('rssedit create new entry for feed', true); ?> <strong>'<?php echo trim($rssdata_res['set'][0]['rsstitle']); ?>'</strong> <?php echo returnIntLang('rssedit edit entries for feed', true); ?></p></fieldset>
	<fieldset>
		<legend><?php echo returnIntLang('rssedit new entry', true); ?> <?php echo legendOpenerCloser('newentry'); ?></legend>
		<script language="JavaScript" type="text/javascript">
		<!--
		function showField(field) {
			document.getElementById('linkbycms').style.display = 'none';
			document.getElementById('linkbyurl').style.display = 'none';
			document.getElementById(field).style.display = 'block';
			} // showField()
		// -->
		</script>
		<div id="newentry">
		<form method="post" name="rssentry" id="rssentry">
		<table class="contenttable">
		<tr class="secondcol">
			<td width="25%"><?php echo returnIntLang('str title', true); ?></td>
			<td colspan="2"><input name="etitle" id="etitle" type="text" size="40" value="<?php if(isset($_POST) && isset($_POST['etitle'])): echo prepareTextField($_POST['etitle']); endif; ?>" maxlength="150" style="width: 95%;" /></td>
		</tr>
		<tr class="firstcol">
			<td width="25%"><?php echo returnIntLang('str content', true); ?></td>
			<td colspan="2"><textarea name="esummary" id="esummary" rows="3" cols="40" style="width: 95%;"><?php if(isset($_POST) && isset($_POST['esummary'])): echo $_POST['esummary']; endif; ?></textarea></td>
		</tr>
		<tr class="secondcol">
			<td width="25%"><?php echo returnIntLang('rssedit linkto', true); ?></td>
			<td width="25%"><select name="linktype" id="linktype" onChange="showField(document.getElementById('linktype').value)" class="one full">
				<option value="linkbycms"><?php echo returnIntLang('rssedit linkbycms', false); ?></option>
				<option value="linkbyurl"><?php echo returnIntLang('rssedit linkbyurl', false); ?></option>
			</select></td>
			<td width="50%"><select name="linkbycms" id="linkbycms" class="two full">
				<option value="0"><?php echo returnIntLang('hint choose', false); ?></option>
				<?php getMenuLevel(0, 0, 4) ?>
			</select><input type="text" name="linkbyurl" id="linkbyurl" size="40" maxlength="150" value="<?php echo $_SESSION['wspvars']['workspaceurl']; ?>" style="display: none;" /></td>
		</tr>
		</table>
		<input name="newentry" id="newentry" type="hidden" value="create" />
		</form>
		</div>
	</fieldset>
	<fieldset class="options">
		<p><a onClick="document.getElementById('rssentry').submit();" class="greenfield"><?php echo returnIntLang('str create'); ?></a> <a href="rssedit.php" class="orangefield"><?php echo returnIntLang('str back'); ?></a></p>
	</fieldset>
	<?php
	if (isset($feedid)):
		$rssentries_sql = "SELECT * FROM `rssentries` WHERE `rid` = ".intval($feedid)." AND `epublished` = 0 ORDER BY `eid` DESC";
		$rssentries_res = doSQL($rssentries_sql);
	
		if ($rssentries_res['num']!=0):
			foreach ($rssentries_res['set'] AS $rssek => $rssev): ?>
			<fieldset>
				<legend><?php echo returnIntLang('rssedit existing entry', true).": ".trim($rssev["etitle"]); ?> <?php echo legendOpenerCloser('rssentry_'.$rssek); ?></legend>
				<script language="JavaScript" type="text/javascript">
				<!--
				function showField<?php echo $rssek; ?>(field) {
					document.getElementById('linkbycms<?php echo $rssek; ?>').style.display = 'none';
					document.getElementById('linkbyurl<?php echo $rssek; ?>').style.display = 'none';
					document.getElementById(field + '<?php echo $rssek; ?>').style.display = 'block';
					} // showField()
				// -->
				</script>
				<div id="rssentry_<?php echo $rssek; ?>">
				<form method="post" name="rssentry" id="rssentryform_<?php echo $rssek; ?>">
				<ul class="tablelist">
					<li class="tablecell two"><?php echo returnIntLang('str title', true); ?></li>
					<li class="tablecell six"><input name="etitle" id="etitle" type="text" size="40" maxlength="150" value="<?php echo prepareTextField($rssev["etitle"]); ?>" class="full" /></li>
					<li class="tablecell two"><?php echo returnIntLang('str content', true); ?></li>
					<li class="tablecell six"><textarea name="esummary" id="esummary" rows="3" cols="40" class="full medium noresize"><?php echo trim($rssev["esummary"]); ?></textarea></li>
					<li class="tablecell two"><?php echo returnIntLang('rssedit linkto', true); ?></li>
					<li class="tablecell two"><select name="linktype" class="full" id="linktype_<?php echo $rssek; ?>" onChange="showField<?php echo $rssek; ?>(document.getElementById('linktype_<?php echo $rssek; ?>').value)">
						<option value="linkbycms"><?php echo returnIntLang('rssedit linkbycms', false); ?></option>
						<option value="linkbyurl" <?php if (trim($rssev["econtype"])=="url"): echo "selected"; endif; ?>><?php echo returnIntLang('rssedit linkbyurl', false); ?></option>
					</select> &nbsp;</li>
					<li class="tablecell four"><select name="linkbycms" id="linkbycms<?php echo $rssek; ?>" <?php if (trim($rssev["econtype"])=="url"): echo "style=\"display: none;\""; endif; ?>>
						<option value="0">Bitte ausw&auml;hlen</option>
						<?php getMenuLevel(0, '-1', 4, array(intval($rssev["econ"]))) ?>
					</select> <input type="text" name="linkbyurl" id="linkbyurl<?php echo $rssek; ?>" size="40" maxlength="150" value="<?php echo $_SESSION['wspvars']['workspaceurl']; ?>" <?php if (trim($rssev["econtype"])=="mid"): echo "style=\"display: none;\""; endif; ?> /></li>
				</ul>
				<input name="eauthor" id="eauthor" type="hidden" value="<?php echo $_SESSION['wspvars']['realname']; ?>" />
				<input name="eupdate" id="eupdate" type="hidden" value="<?php echo date("YmdHis"); ?>" />
				<input name="eid" id="eid" type="hidden" value="<?php echo intval(trim($rssev["eid"])); ?>" />
				<input name="newentry" id="entry_<?php echo $rssek; ?>" type="hidden" value="update" /></td>
				</form>
				<fieldset class="options innerfieldset">
					<p><a onclick="document.getElementById('entry_<?php echo $rssek; ?>').value = 'delete'; document.getElementById('rssentryform_<?php echo $rssek; ?>').submit();" class="redfield"><?php echo returnIntLang('str delete', true); ?></a> <a onClick="document.getElementById('rssentryform_<?php echo $rssek; ?>').submit();" class="greenfield"><?php echo returnIntLang('str save', true); ?></a></p>
				</fieldset>
			</fieldset>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
	<?php else: ?>
	<p><?php echo returnIntLang('rssedit choosen feed not found', false); ?></p>
	<fieldset class="options">
		<p><a href="rssedit.php" class="orangefield"><?php echo returnIntLang('str back', false); ?></a></p>
	</fieldset>
	<?php endif; ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->