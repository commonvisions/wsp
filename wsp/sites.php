<?php
/**
 * global site-setup
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-22
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

// definition der aktiven position und rahmenbedingungen zur benutzung der seite
$_SESSION['wspvars']['lockstat'] = 'siteprops';
$_SESSION['wspvars']['menuposition'] = 'sites';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$s = 0; // for post and save activity
/* define page specific functions ------------ */
if (isset($_POST['save_data']) && $_POST['save_data']=="sites"):
	if (is_array($_POST['sites'])):
		for ($sp=0; $sp<=max(array_flip($_POST['sites'])); $sp++):
			doSQL("DELETE FROM `wspproperties` WHERE `varname` = 'sites_".$sp."'");
			if (isset($_POST['sites'][$sp]) && trim($_POST['sites'][$sp])!=''):
				doSQL("INSERT INTO `wspproperties` SET `varname` = 'sites_".$sp."', `varvalue` = '".escapeSQL(trim($_POST['sites'][$sp]))."'");
			else:
				doSQL("DELETE FROM `wspproperties` WHERE `varname` LIKE 'sites_".$sp."%'");
			endif;
		endfor;
	endif;
endif;
if (isset($_POST['save_siteprop'])):
	foreach ($_POST['siteprop'][intval($_POST['save_siteprop'])] AS $pk => $pv):
		doSQL("DELETE FROM `wspproperties` WHERE `varname` = 'sites_".intval($_POST['save_siteprop'])."_".trim($pk)."'");
		doSQL("INSERT INTO `wspproperties` SET `varname` = 'sites_".intval($_POST['save_siteprop'])."_".trim($pk)."', `varvalue` = '".trim($pv)."'");
	endforeach;
endif;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

$siteinfo_sql = "SELECT * FROM `wspproperties` WHERE `varname` LIKE 'sites_%'";
$siteinfo_res = doSQL($siteinfo_sql);
$sitedata = array();
if ($siteinfo_res['num']>0) {
    foreach ($siteinfo_res['set'] AS $sresk => $sresv) {
		$siteinfo = explode("_", $sresv['varname']);
		if (count($siteinfo)==2):
			$sitedata[($siteinfo[0])][($siteinfo[1])]['name'] = $sresv['varvalue'];
		elseif (count($siteinfo)==3):
			$sitedata[($siteinfo[0])][($siteinfo[1])][($siteinfo[2])] = $sresv['varvalue'];
		endif;
    }
}

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('sites headline'); ?></h1></fieldset>
	<fieldset><p><?php echo returnIntLang('sites info'); ?></p></fieldset>
	<script type="text/javascript">
	<!--
	
	function removeSite(siteID) {
		$('#site-'+siteID).remove();
		$('#fieldset_site_-'+siteID).remove();
		}
	
	// -->
	</script>
	<fieldset>
		<legend><?php echo returnIntLang('sites overview legend'); ?> <?php echo legendOpenerCloser('sites_overview'); ?></legend>
		<div id="sites_overview">
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="sites">
			<table class="tablelist">
				<?php $sk = 0; if(isset($sitedata['sites']) && is_array($sitedata['sites']) && count($sitedata['sites'])>0): foreach($sitedata['sites'] AS $sk => $sv): if(isset($sv['name'])): ?>
				<tr id='site-<?php echo $sk; ?>'>
					<td class="tablecell two"><?php echo returnIntLang('sites overview existing'); ?></td>
					<td class="tablecell five"><input type="text" name="sites[<?php echo $sk; ?>]" value="<?php echo $sv['name']; ?>" class="full" /></td>
					<td class="tablecell one"><a onclick="removeSite(<?php echo $sk; ?>);"><span class="bubblemessage red">ENTF.</span></a></td>
				</tr>
				<?php endif; endforeach; endif; ?>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('sites overview create'); ?></td>
					<td class="tablecell six"><input type="text" name="sites[<?php echo ($sk+1); ?>]" value="" class="full" /></td>
				</tr>
			</table>
			<fieldset class="options innerfieldset">
				<p><a href="#" onclick="document.getElementById('sites').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save'); ?></a><input name="save_data" type="hidden" value="sites" /></p>
			</fieldset>
			</form>
		</div>
	</fieldset>
	<?php if(isset($sitedata['sites']) && is_array($sitedata['sites']) && count($sitedata['sites'])>0): foreach($sitedata['sites'] AS $sk => $sv): ?>
	<fieldset id="fieldset_site_<?php echo $sk; ?>" class="text">
		<legend><?php echo returnIntLang('site prop legend'); ?> "<?php echo $sv['name']; ?>" <?php echo legendOpenerCloser('fieldset_siteprop_'.$sk); ?></legend>
		<div id="fieldset_siteprop_<?php echo $sk; ?>">
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="siteprop_<?php echo $sk; ?>">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('site prop entrypoint'); ?></td>
					<td class="tablecell six"><select class="full" name="siteprop[<?php echo $sk; ?>][homepage]">
						<?php getMenuLevel(0, 3, 4, array($sv['homepage'])); ?>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('site prop images'); ?></td>
					<td class="tablecell six"><select class="full" name="siteprop[<?php echo $sk; ?>][images]">
						<option value="0"><?php echo returnIntLang('site prop allmedia'); ?></option>
						<?php 
						
						$directory = array();
						mediaDirList('/media/images/');
						sort($directory);
						foreach ($directory AS $k => $v):
							echo "<option value='".$v."' ";
							if (isset($sv['images']) && $v==$sv['images']): echo " selected='selected' "; endif;
							echo ">".str_replace("/media/images/", "/", $v)."</option>";
						endforeach;
						
						?>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('site prop documents'); ?></td>
					<td class="tablecell six"><select class="full" name="siteprop[<?php echo $sk; ?>][download]">
						<option value="0"><?php echo returnIntLang('site prop allmedia'); ?></option>
						<?php 
						
						$directory = array();
						mediaDirList('/media/download/');
						sort($directory);
						foreach ($directory AS $k => $v):
							echo "<option value='".$v."' ";
							if (isset($sv['download']) && $v==$sv['download']): echo " selected='selected' "; endif;
							echo ">".str_replace("/media/download/", "/", $v)."</option>";
						endforeach;
						
						?>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('site prop media'); ?></td>
					<td class="tablecell six"><select class="full" name="siteprop[<?php echo $sk; ?>][video]">
						<option value="0"><?php echo returnIntLang('site prop allmedia'); ?></option>
						<?php 
						
						$directory = array();
						mediaDirList('/media/video/');
						sort($directory);
						foreach ($directory AS $k => $v):
							echo "<option value='".$v."' ";
							if (isset($sv['media']) && $v==$sv['media']): echo " selected='selected' "; endif;
							echo ">".str_replace("/media/video/", "/", $v)."</option>";
						endforeach;
						
						?>
					</select></td>
				</tr>
			</table>
			<input name="save_siteprop" type="hidden" value="<?php echo $sk; ?>" />
			</form>
			<fieldset class="options innerfieldset">
				<p><a href="#" onclick="document.getElementById('siteprop_<?php echo $sk; ?>').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save'); ?></a></p>
			</fieldset>
		</div>
	</fieldset>
	<?php endforeach; endif; ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- // EOF -->