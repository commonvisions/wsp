<?php
/**
 * privacy-setup
 * @author stefan@covi.de
  * @since 6.11
 * @version 6.11.1
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

if (!isset($sitedata['privacy_text']) || empty($sitedata['privacy_text'])!='') {
	$sitedata['privacy_text'] = 'Um unsere Webseite für Sie optimal zu gestalten und fortlaufend verbessern zu können, verwenden wir Cookies. Durch die weitere Nutzung der Webseite stimmen Sie der Verwendung von Cookies zu. Mehr dazu erfahren Sie in unseren Datenschutzbestimmungen.';
	$sitedata['privacy_linktext'] = 'Datenschutzbestimmungen';
	$sitedata['privacy_link'] = 0;
	$sitedata['privacy_accept'] = 'Akzeptieren';
	$sitedata['privacy_placement'] = 0;
}

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('privacy headline'); ?></h1></fieldset>
	<fieldset><p>Ein Cookie-Hinweis ist bei SESSION-Cookies <strong>nicht</strong> notwendig. Nutzen Sie daher den Cookie-Layer als Inhalt, wenn sie langfristige Cookies einsetzen. Der Aufruf des Cookie-Layers sollte sich in diesem Falle auf <strong>allen</strong> Seiten befinden. Sie können den Cookie-Layer über den 'DSGVO Cookie Overlay' Interpreter gezielt einbinden oder die Einstellungen seitenweit anwenden. Um das Cookie-Banner nachträglich aufzurufen, erstellen sie eine Verlinkung auf '?set_privacy' auf den Seiten, auf denen es eingebunden ist.</p></fieldset>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="application/x-www-form-urlencoded" id="frmprefs" style="margin: 0px;">
		<fieldset id="fieldset_privacy">
			<legend><?php echo returnIntLang('privacypref headline'); ?> <?php echo legendOpenerCloser('fieldset_privacy_content', 'open'); ?></legend>
			<div id="fieldset_privacy_content" style="<?php echo isset($_SESSION['opentabs']['fieldset_privacy_content']) ? $_SESSION['opentabs']['fieldset_privacy_content'] : ''; ?>">
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('privacy use sidewide'); ?></td>
						<td class="tablecell six"><input type="hidden" name="privacy_sidewide" value="0" /><input type="checkbox" name="privacy_sidewide" id="privacy_sidewide" value="1" <?php if(isset($sitedata['privacy_sidewide']) && intval($sitedata['privacy_sidewide'])==1) { echo ' checked="checked" '; } ?> /></td>
					</tr>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('privacy content'); ?></td>
						<td class="tablecell six"><textarea name="privacy_text" id="field_text" placeholder="Mit Benutzung unserer Seite stimmen Sie den Datenschutzbestimmungen zu." class="full"><?php echo prepareTextField($sitedata['privacy_text']); ?></textarea></td>
					</tr>
					<tr>
						<td class="tablecell two">Verlinkung auf DSE (optional)</td>
						<td class="tablecell two"><input type="text" name="privacy_linktext" id="privacy_linktext" value="<?php echo prepareTextField($sitedata['privacy_linktext']); ?>" placeholder="z. B. Datenschutzbestimmungen" class="full" /></td>
						<td class="tablecell two">Verlinkte Seite DSE (optional)</td>
						<td class="tablecell two"><select name="privacy_link" id="privacy_link">
							<?php echo getMenuLevel('', '', 4, array($sitedata['privacy_link']), ''); ?>
						</select></td>
					</tr>
					<tr>
						<td class="tablecell two">Bestätigungstext</td>
						<td class="tablecell two"><input type="text" name="privacy_accept" id="privacy_accept" value="<?php echo prepareTextField($sitedata['privacy_accept']); ?>" placeholder="Einstellungen speichern" class="full" /></td>
						<td class="tablecell two">Platzierung</td>
						<td class="tablecell two"><select name="privacy_placement" id="privacy_placement">
							<option value="0" <?php if($sitedata['privacy_placement']==0) echo " selected='selected' "; ?>>Oben</option>
							<option value="1" <?php if($sitedata['privacy_placement']==1) echo " selected='selected' "; ?>>Unten</option>
						</select></td>
					</tr>
					<tr>
						<td class="tablecell two">Anzeige Technische Cookies</td>
						<td class="tablecell two"><input type="hidden" name="privacy_disabletech" value="0" /><input type="checkbox" name="privacy_disabletech" id="privacy_disabletech" value="1" <?php if(isset($sitedata['privacy_disabletech']) && intval($sitedata['privacy_disabletech'])==1) { echo ' checked="checked" '; } ?> /></td>
						<td class="tablecell two">Standardwert?</td>
						<td class="tablecell two">Aktiv - nicht abstellbar<input type="hidden" name="privacy_disabletech_pref" value="2" /></td>
					</tr>
					<tr>
						<td class="tablecell two">Auswahl externe Fonts anbieten?</td>
						<td class="tablecell two"><input type="hidden" name="privacy_disablefonts" value="0" /><input type="checkbox" name="privacy_disablefonts" id="privacy_disablefonts" value="1" <?php if(isset($sitedata['privacy_disablefonts']) && intval($sitedata['privacy_disablefonts'])==1) { echo ' checked="checked" '; } ?> /></td>
						<td class="tablecell two">Standardwert?</td>
						<td class="tablecell two"><select name="privacy_disablefonts_pref">
							<option value="0" <?php echo (!isset($sitedata['privacy_disablefonts_pref']) || $sitedata['privacy_disablefonts_pref']==0) ? 'selected="selected"' : ''; ?>>Aktiv</option>
							<option value="1" <?php echo (isset($sitedata['privacy_disablefonts_pref']) && $sitedata['privacy_disablefonts_pref']==1) ? 'selected="selected"' : ''; ?>>Inaktiv</option>
							<option value="2" <?php echo (isset($sitedata['privacy_disablefonts_pref']) && $sitedata['privacy_disablefonts_pref']==2) ? 'selected="selected"' : ''; ?>>Aktiv - nicht abstellbar</option>
							<option value="3" <?php echo (isset($sitedata['privacy_disablefonts_pref']) && $sitedata['privacy_disablefonts_pref']==3) ? 'selected="selected"' : ''; ?>>Inaktiv - nicht abstellbar</option>
						</select></td>
					</tr>
					<tr>
						<td class="tablecell eight"><em>This will block the external font sources setup in 'Templates'</em></td>
					</tr>
					<tr>
						<td class="tablecell two">Auswahl Statistik-Cookies anbieten?</td>
						<td class="tablecell two"><input type="hidden" name="privacy_disablestats" value="0" /><input type="checkbox" name="privacy_disablestats" id="privacy_disablestats" value="1" <?php if(isset($sitedata['privacy_disablestats']) && intval($sitedata['privacy_disablestats'])==1) { echo ' checked="checked" '; } ?> /></td>
						<td class="tablecell two">Standardwert?</td>
						<td class="tablecell two"><select name="privacy_disablestats_pref">
							<option value="1" <?php echo (isset($sitedata['privacy_disablestats_pref']) && $sitedata['privacy_disablestats_pref']==1) ? 'selected="selected"' : ''; ?>>Inaktiv</option>
							<option value="0" <?php echo (isset($sitedata['privacy_disablestats_pref']) && $sitedata['privacy_disablestats_pref']==0) ? 'selected="selected"' : ''; ?>>Aktiv</option>
							<option value="2" <?php echo (isset($sitedata['privacy_disablestats_pref']) && $sitedata['privacy_disablestats_pref']==2) ? 'selected="selected"' : ''; ?>>Aktiv - nicht abstellbar</option>
							<option value="3" <?php echo (isset($sitedata['privacy_disablestats_pref']) && $sitedata['privacy_disablestats_pref']==3) ? 'selected="selected"' : ''; ?>>Inaktiv - nicht abstellbar</option>
						</select></td>
					</tr>
					<tr>
						<td class="tablecell eight"><em>This will block all Scripts setup in 'Analytics Tools'</em></td>
					</tr>
				</table>
			</div>
		</fieldset>

		<fieldset id="fieldset_privacy">
			<legend><?php echo returnIntLang('blockelem headline'); ?> <?php echo legendOpenerCloser('fieldset_block_elem', 'closed'); ?></legend>
			<div id="fieldset_block_elem" style="<?php echo isset($_SESSION['opentabs']['fieldset_block_elem']) ? $_SESSION['opentabs']['fieldset_block_elem'] : ''; ?>">
				<p>Element blocking allows to bind Elements by ID or class to visitor preference. If an interpreter supports the getBlockedElem() function it will block elements wrapped within Interpreters, too. 
				<?php for ($e=0; $e<5; $e++) { ?>
					<div class="row">
						<div class="col col-md-6"><input type="text" name="privacy_blockelement_<?php echo $e; ?>_text" id="privacy_blockelement_<?php echo $e; ?>_text" value="<?php echo isset($sitedata['privacy_blockelement_'.$e.'_text']) ? prepareTextField($sitedata['privacy_blockelement_'.$e.'_text']) : ''; ?>" placeholder="Beschreibungstext" class="full form-control" /></div>
						<div class="col col-md-3"><input type="text" name="privacy_blockelement_<?php echo $e; ?>_identifier" id="privacy_blockelement_<?php echo $e; ?>_identifier" value="<?php echo isset($sitedata['privacy_blockelement_' . $e . '_identifier']) ? prepareTextField($sitedata['privacy_blockelement_' . $e . '_identifier']) : ''; ?>" placeholder="Identifier, …, Klasse, …" class="full form-control" /></div>
						<div class="col col-md-3"><select name="privacy_blockelement_<?php echo $e; ?>_pref" id="privacy_blockelement_<?php echo $e; ?>_pref"  class="full form-control">
							<option value="0" <?php echo (!isset($sitedata['privacy_blockelement_' . $e . '_pref']) || $sitedata['privacy_blockelement_' . $e . '_pref']==0) ? 'selected="selected"' : ''; ?>>Aktiv</option>
							<option value="1" <?php echo (isset($sitedata['privacy_blockelement_' . $e . '_pref']) && $sitedata['privacy_blockelement_' . $e . '_pref']==1) ? 'selected="selected"' : ''; ?>>Inaktiv</option>
							<option value="2" <?php echo (isset($sitedata['privacy_blockelement_' . $e . '_pref']) && $sitedata['privacy_blockelement_' . $e . '_pref']==2) ? 'selected="selected"' : ''; ?>>Aktiv - nicht abstellbar</option>
							<option value="3" <?php echo (isset($sitedata['privacy_blockelement_' . $e . '_pref']) && $sitedata['privacy_blockelement_' . $e . '_pref']==3) ? 'selected="selected"' : ''; ?>>Inaktiv - nicht abstellbar</option>
						</select></div>
					</div>
				<?php } ?>
				<p>Join multiple classes and IDs with ','. No dots or hashtags allowed.</p>
			</div>
		</fieldset>

		<fieldset class="options">
			<p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a><input name="save_data" type="hidden" value="Speichern" /></p>
		</fieldset>
	</form>
</div>
<?php include ("data/include/footer.inc.php"); ?>
<!-- EOF -->