<?php
/**
 * global site-setup
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 4.0
 * @version 6.8
 * @lastchange 2019-01-22
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("data/include/usestat.inc.php");
require ("data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

// definition der aktiven position und rahmenbedingungen zur benutzung der seite
$_SESSION['wspvars']['lockstat'] = '';
$_SESSION['wspvars']['menuposition'] = 'siteprops';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ------------ */
if (isset($_POST['save_data'])):
	if ($_POST['save_data']=="mobile"):
		doSQL("DELETE FROM `wspproperties` WHERE `varname` = 'mobile_pages'");
		doSQL("DELETE FROM `wspproperties` WHERE `varname` = 'mobile_path'");
		doSQL("INSERT INTO `wspproperties` SET `varname` = 'mobile_pages', `varvalue` = '".intval($_POST['mobile_pages'])."'");
		doSQL("INSERT INTO `wspproperties` SET `varname` = 'mobile_path', `varvalue` = '".escapeSQL(trim($_POST['mobile_path']))."'");
	endif;
	if ($_POST['save_data']=="url"):
		$i = 0;
		foreach ($_POST AS $key => $value):
			if ($key!="save_data"):
				if (trim($value['url'])!='' && intval($value['target'])>0):
					doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'");
					doSQL("INSERT INTO `wspproperties` SET `varname` = 'url_forward_".$i."', `varvalue` = '".escapeSQL(serialize($value))."'");
					$i++;
				elseif (trim($value['url'])=='' || intval($value['target'])==0):
					doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'");
				endif;
			endif;
		endforeach;
		addWSPMsg('noticemsg', returnIntLang('redirect saved urlproperties', false));
	endif;
	if ($_POST['save_data']=="var"):
		$i = 0;
		foreach ($_POST AS $key => $value):
			if ($key!="save_data"):
				if (trim($value['varname'])!='' && intval($value['target'])>0):
					doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'");
					doSQL("INSERT INTO `wspproperties` SET `varname` = 'var_forward_".$i."', `varvalue` = '".escapeSQL(serialize($value))."'");
					addWSPMsg('noticemsg', returnIntLang('redirect saved varproperties1')." \"<strong>".trim($value['varname'])."=".trim($value['varvalue'])."</strong>\" ".returnIntLang('redirect saved varproperties2'));
					$i++;
				elseif (trim($value['varname'])=='' || intval($value['target'])==0):
					doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'");
				endif;
			endif;
		endforeach;
	endif;
endif;

// head der datei
include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");

$siteinfo_sql = "SELECT * FROM `wspproperties`";
$siteinfo_res = doSQL($siteinfo_sql);
if ($siteinfo_res['num']>0):
	foreach ($siteinfo_res['set'] AS $sresk => $sresv):
		$sitedata[trim($sresv['varname'])] = $sresv['varvalue'];
	endforeach;
endif;

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('redirect headline'); ?></h1></fieldset>
	<fieldset><p><?php echo returnIntLang('redirect info'); ?></p></fieldset>
	<fieldset id="fieldset_mobile">
		<legend><?php echo returnIntLang('redirect mobile legend'); ?> <?php echo legendOpenerCloser('prefs_mobile'); ?></legend>
		<div id="prefs_mobile">
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="mobileprefs">
			<p>Wenn Sie eine <strong>echte</strong> mobile Ansicht anbieten wollen, aktivieren Sie die Checkbox und setzen Sie einen Mobil-Prefix. Es werden dann alle Dateien mit der Vorlage für mobile Inhalte innerhalb des Prefix-Pfades veröffentlicht.</p>
			<table class="tablelist">
				<tr>
					<td class="tablecell two">Mobile Ansicht anbieten</td>
					<td class="tablecell two"><input type="hidden" name="mobile_pages" value="0" /><input type="checkbox" name="mobile_pages" value="1" <?php if(isset($sitedata['mobile_pages']) && intval($sitedata['mobile_pages'])==1) echo "checked=\"checked\""; ?> />&nbsp;</td>
					<td class="tablecell two">Prefix für mobile Ansicht</td>
					<td class="tablecell two"><input type="text" name="mobile_path" value="<?php if(isset($sitedata['mobile_path']) && trim($sitedata['mobile_path'])!="") echo $sitedata['mobile_path']; ?>" class="two" /></td>
				</tr>
			</table>
			<fieldset class="options innerfieldset">
				<p><a href="#" onclick="document.getElementById('mobileprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save'); ?></a><input name="save_data" type="hidden" value="mobile" /></p>
			</fieldset>
			</form>
		</div>
	</fieldset>
	<fieldset id="fieldset_url" class="text">
		<legend><?php echo returnIntLang('redirect url based legend'); ?> <?php echo legendOpenerCloser('fieldset_url_content'); ?></legend>
		<div id="fieldset_url_content">
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="urlprefs">
			<table class="tablelist">
				<?php 
				
				$ufk=0;
				if(isset($sitedata['url_forward_0']) && $sitedata['url_forward_0']!=""):
					$forwarddata = array();
					foreach($sitedata AS $sitekey => $sitevalue):
						if (substr($sitekey,0,12)=="url_forward_"):
							$forwarddata[] = unserialize($sitevalue);
						endif;
					endforeach; 
					
					?>
					<tr>
						<td class="tablecell two head"><?php echo returnIntLang('str url'); ?></td>
						<td class="tablecell two head"><?php echo returnIntLang('redirect rewrite'); ?></td>
						<td  class="tablecell four head"><?php echo returnIntLang('redirect target'); ?></td>
					</tr>
					<?php for($ufk=0; $ufk<count($forwarddata); $ufk++): ?>
						<tr>
							<td class="tablecell two"><input type="text" id="url_forward_<?php echo intval($ufk); ?>" name="url_forward_<?php echo intval($ufk); ?>[url]" value="<?php echo $forwarddata[$ufk]['url'] ?>" class="one full"></td>
							<td class="tablecell two"><input type="hidden" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" value="0" /><input type="checkbox" value="1" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" <?php if(intval($forwarddata[$ufk]['rewrite'])==1): echo "checked=\"checked\""; endif; ?> /></td>
							<td class="tablecell three"><select style="width: 98%;" name="url_forward_<?php echo intval($ufk); ?>[target]">
								<option value="0"><?php echo returnIntLang('hint choose'); ?></option>
								<?php getMenuLevel(0, 3, 4, array($forwarddata[$ufk]['target'])); ?>
							</select></td>
							<td class="tablecell one"><a href="#" onclick="document.getElementById('url_forward_<?php echo intval($ufk); ?>').value=''; document.getElementById('urlprefs').submit(); return false;"><span class="bubblemessage red"><?php echo returnIntLang('str delete', false); ?></span></a></td>
						</tr>
					<?php endfor; ?>
				<?php endif; ?>
				<tr>
					<td class="tablecell two head"><?php echo returnIntLang('redirect new url'); ?></td>
					<td class="tablecell two head info"><?php echo returnIntLang('redirect rewrite'); ?></td>
					<td class="tablecell four head info"><?php echo returnIntLang('redirect target'); ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><input type="text" class="one full" name="url_forward_<?php echo intval($ufk); ?>[url]" placeholder="<?php echo prepareTextField(returnIntLang('redirect new url', false)); ?>" /></td>
					<td class="tablecell two"><input type="hidden" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" value="0" /><input type="checkbox" value="1" name="url_forward_<?php echo intval($ufk); ?>[rewrite]" /></td>
					<td class="tablecell four"><select class="two full" name="url_forward_<?php echo intval($ufk); ?>[target]">
						<option value="0"><?php echo returnIntLang('hint choose'); ?></option>
						<?php getMenuLevel(0, 3, 4); ?>
					</select></td>
				</tr>
			</table>
			<p><?php echo returnIntLang('redirect url based info'); ?></p>
			<input name="save_data" type="hidden" value="url" />
			</form>
			<fieldset class="options innerfieldset">
				<p><a href="#" onclick="document.getElementById('urlprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save'); ?></a></p>
			</fieldset>
		</div>
	</fieldset>
	<fieldset id="fieldset_vars" class="text">
		<legend><?php echo returnIntLang('redirect var based legend'); ?> <?php echo legendOpenerCloser('fieldset_vars_content'); ?></legend>
		<div id="fieldset_vars_content">
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="varprefs">
			<table class="tablelist">
				<?php 
				
				$vfk = 0;
				if(isset($sitedata['var_forward_0']) && $sitedata['var_forward_0']!=""):
					$forwarddata = array();
					foreach($sitedata AS $sitekey => $sitevalue):
						if (substr($sitekey,0,12)=="var_forward_"):
							$forwarddata[] = unserialize($sitevalue);
						endif;
					endforeach; 
					
					?>
				
					<tr>
						<td class="tablecell two head"><?php echo returnIntLang('str varname'); ?></td>
						<td class="tablecell two head info"><?php echo returnIntLang('str varvalue'); ?></td>
						<td class="tablecell four head info"><?php echo returnIntLang('redirect target'); ?></td>
					</tr>
					<?php for($vfk=0; $vfk<count($forwarddata); $vfk++): ?>
						<tr>
							<td class="tablecell two"><input type="text" id="var_forward_<?php echo intval($vfk); ?>" name="var_forward_<?php echo intval($vfk); ?>[varname]" value="<?php echo $forwarddata[$vfk]['varname'] ?>"></td>
							<td class="tablecell two"><input type="text" name="var_forward_<?php echo intval($vfk); ?>[varvalue]" value="<?php echo $forwarddata[$vfk]['varvalue'] ?>"></td>
							<td class="tablecell three"><select style="width: 98%;" name="var_forward_<?php echo intval($vfk); ?>[target]">
						<option value="0"><?php echo returnIntLang('hint choose'); ?></option>
						<?php getMenuLevel(0, 3, 4, array($forwarddata[$vfk]['target'])); ?>
					</select></td>
					<td nowrap><a href="#" onclick="document.getElementById('var_forward_<?php echo intval($vfk); ?>').value=''; document.getElementById('varprefs').submit(); return false;"><span class="bubblemessage red"><?php echo returnIntLang('str delete', false); ?></span></a></td>
						</tr>
					<?php endfor; ?>
				<?php endif; ?>
				<tr>
					<td class="tablecell two head"><?php echo returnIntLang('redirect new varname'); ?></td>
					<td class="tablecell two head info"><?php echo returnIntLang('str varvalue'); ?></td>
					<td class="tablecell four head info"><?php echo returnIntLang('redirect target'); ?></td>
				</tr>
				<tr>
					<td class="tablecell two"><input type="text" name="var_forward_<?php echo intval($vfk); ?>[varname]" placeholder="<?php echo prepareTextField(returnIntLang('redirect new varname', false)); ?>" /></td>
					<td class="tablecell two"><input type="text" name="var_forward_<?php echo intval($vfk); ?>[varvalue]" placeholder="<?php echo prepareTextField(returnIntLang('str varvalue', false)); ?>" /></td>
					<td class="tablecell four"><select style="width: 98%;" name="var_forward_<?php echo intval($vfk); ?>[target]">
						<option value="0"><?php echo returnIntLang('hint choose'); ?></option>
						<?php getMenuLevel(0, 3, 4); ?>
					</select></td>
				</tr>
			</table>
			<input name="save_data" type="hidden" value="var" />
			</form>
			<fieldset class="options innerfieldset">
				<p><a href="#" onclick="document.getElementById('varprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save'); ?></a></p>
			</fieldset>
		</div>
	</fieldset>
</div>
<?php include ("data/include/footer.inc.php"); ?>
<!-- EOF -->