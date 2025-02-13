<?php
/**
 * Verwaltung von eigenen Variablen
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8.1
 * @lastchange 2019-06-26
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
$op = checkParamVar('op', '');
$id = checkParamVar('id', 0);
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
if (!(array_key_exists('existvars', $_SESSION['opentabs']))) $_SESSION['opentabs']['existvars'] = 'display: block;';
/* define page specific functions ------------ */
$op = checkParamVar('op', '');
$id = checkParamVar('id', 0);

// head der datei
if ($op == 'savevar'):
	if (intval($_POST['id'])==0):
		doSQL("INSERT INTO `selfvars` SET `name` = '".escapeSQL(trim($_POST['varname']))."', `selfvar` = '".escapeSQL($_POST['selfvar'])."'");
	else:
		$sql = "UPDATE `selfvars` SET `name` = '".escapeSQL(trim($_POST['varname']))."', `selfvar` = '".escapeSQL($_POST['selfvar'])."' WHERE `id` = ".intval($_POST['id']);
		$res = doSQL($sql);
        if ($res['res']) {
			addWSPMsg('noticemsg', "Die Variable wurde aktualisiert.");
        }
		$useselfvars_sql = "SELECT `id` FROM `templates` WHERE `template` LIKE '%[%".strtoupper(trim($_POST['varname']))."%]%' ORDER BY `name`";
		$useselfvars_res = doSQL($useselfvars_sql);
		if ($useselfvars_res['num']>0):
            foreach ($useselfvars_res['set'] AS $uresk => $uresv) {
				if (intval($uresv['id'])>0) {
					doSQL("UPDATE `menu` SET `contentchanged` = 1 WHERE `templates_id` = ".intval($uresv['id']));
                }
            }
			addWSPMsg('noticemsg', "Bitte ver&ouml;ffentlichen Sie die Seiten, die ein Template mit dieser Variable verwenden, neu.");
		endif;
	endif;
	$op = "edit";
elseif ($op == 'delvar'):
	if (intval($_POST['id'])>0):
		doSQL("DELETE FROM `selfvars` WHERE `id` = ".intval($_POST['id']));
		addWSPMsg('noticemsg', "Die Variable wurde gel&ouml;scht.");
	endif;
endif;

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");

?>

<script type="text/javascript" language="javascript">
<!--
function checkEditFields() {
	if (document.getElementById('varname').value == '') {
		alert('<?php echo returnIntLang('selfvars pleasefillvarname', false); ?>');
		document.getElementById('varname').focus();
		return false;
	}	// if
	if (document.getElementById('varname').value.search(/^[a-zA-Z0-9\-_ ]*$/)) {
		alert('<?php echo returnIntLang('selfvars pleaseusechars', false); ?>');
		document.getElementById('varname').focus();
		return false;
	}	// if

	document.getElementById('formvar').submit();
	return true;
	}	// checkEditFields()
//-->
</script>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('selfvars headline'); ?></h1></fieldset>
	<fieldset><p><?php echo returnIntLang('selfvars info'); ?></p></fieldset>
	<?php
	
	$selfvars_sql = "SELECT `id`, `name`, `selfvar` FROM `selfvars` ORDER BY `name`";
	$selfvars_res = doSQL($selfvars_sql);
	
	$existvarstat = "open";
	
	if ($op=="edit" || $selfvars_res['num']>10):
		$existvarstat = "closed";
	endif;
	
	?>
	<fieldset>
		<legend><?php echo returnIntLang('selfvars existingvars'); ?> <?php echo legendOpenerCloser('existvars'); ?></legend>
		<div id="existvars">
		<?php
		if ($selfvars_res['num']==0):
			echo "<p>Es sind noch keine Variablen definiert!</p>\n";
		else:
		?>
		<table class="tablelist">
		<tr>
			<td class="tablecell two head"><?php echo returnIntLang('selfvars varname'); ?></td>
			<td class="tablecell two head"><?php echo returnIntLang('selfvars vartype'); ?></td>
			<td class="tablecell two head"><?php echo returnIntLang('str usage'); ?></td>
			<td class="tablecell two head"><?php echo returnIntLang('str action'); ?></td>
		</tr>
		<?php
		
        foreach ($selfvars_res['set'] AS $svrk => $svrv) {
			echo "<tr>\n";
			echo "\t<td class=\"tablecell two\"><a href=\"".$_SERVER['PHP_SELF']."?op=edit&id=".$svrv['id']."\">".$svrv['name']."</a></td>";

			$checkfortags = strip_tags($svrv['selfvar']);
			if ($checkfortags==$svrv['selfvar']):
				echo "<td class=\"tablecell two\">".returnIntLang('selfvars text')."</td>";
			else:
				$checkforphp = stristr($svrv['selfvar'], "?>"); ?>
				<?php if ($checkforphp==FALSE):
					echo "<td class=\"tablecell two\">".returnIntLang('selfvars html')."</td>";
				else:
					echo "<td class=\"tablecell two\">".returnIntLang('selfvars php')."</td>";
				endif;
			endif;
			
			$useselfvars_sql = "SELECT `id`, `name` FROM `templates` WHERE `template` LIKE '%[%".strtoupper($svrv['name'])."%]%' ORDER BY `name`";
			$useselfvars_res = doSQL($useselfvars_sql);
			
			if ($useselfvars_res['num']!=0):
				echo "<td class=\"tablecell two\">";
				foreach ($useselfvars_res['set'] AS $usvrk => $usvrv):
					echo "<a href=\"templatesedit.php?op=edit&id=".intval($usvrv['id'])."\">".trim($usvrv['name'])."</a><br />";
				endforeach;
				echo "</td>";
			else:
				echo "<td class=\"tablecell two\">-</td>";
			endif;

			echo "<td class=\"tablecell two\">&nbsp;<a href=\"".$_SERVER['PHP_SELF']."?op=edit&id=".$svrv['id']."\"><span class=\"bubblemessage orange\">".returnIntLang('bubble edit', false)."</span></a> <a href=\"".$_SERVER['PHP_SELF']."?op=delvar&id=".$svrv['id']."\" onclick=\"return confirm(unescape('Soll diese Variable wirklich gel%f6scht werden?";
			if ($useselfvars_res['num']!=0):
				echo " Das L%f6schen der Variable führt dazu, dass diese Variable auch aus den Templates gel%f6scht wird, in denen Sie in Verwendung ist.";
			endif;
			echo "'));\" class=\"red\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a></td>";
			echo "</tr>\n";
        
        }
		
        ?>
		</table>
		<?php endif; ?>
		</div>
	</fieldset>
	<?php
	if ($op=="edit" && $id>0):
		$selvars_sql = "SELECT `id`, `name`, `selfvar` FROM `selfvars` WHERE `id` = ".intval($id);
		$selvars_res = doSQL($selvars_sql);
		if ($selvars_res['num']>0):
			$varname = trim($selvars_res['set'][0]['name']);
			$selfvar = stripslashes(trim($selvars_res['set'][0]['selfvar']));
			if (isset($_SESSION['wspvars']['stripslashes']) && $_SESSION['wspvars']['stripslashes']>0):
				for ($strip=0; $strip<$_SESSION['wspvars']['stripslashes']; $strip++):
					$selfvar = stripslashes($selfvar);
				endfor;
			endif;
		else:
			$id = 0;
			$selfvar = '';
			$varname = '';
		endif;
	elseif ($op=="edit"):
		$id = 0;
		$selfvar = '';
		$varname = '';
	endif;
	?>
	<fieldset id="newvar" <?php if($op!="edit"): ?>style="display: none;"<?php endif; ?>>
		<legend><?php if($op=="edit" && $id>0): ?><?php echo returnIntLang('selfvars editvar'); ?><?php else: ?><?php echo returnIntLang('selfvars createnewvar'); ?><?php endif; ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="formvar">
		<table class="tablelist">
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('selfvars varname'); ?></td>
			<td class="tablecell six"><input type="text" name="varname" id="varname" size="20" maxlength="50" value="<?php echo $varname; ?>" class="three full"></td>
		</tr>
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('selfvars varcontent'); ?></td>
			<td class="tablecell six"><textarea name="selfvar" id="selfvar" cols="70" rows="10" class="three full"><?php echo $selfvar; ?></textarea></td>
		</tr>
		</table>
	</fieldset>
	<?php if ($op=="edit"):  ?>
	<fieldset class="options">
		<p><input type="hidden" name="op" value="savevar" /><input type="hidden" name="id" id="id" value="<?php echo $id; ?>" /><input type="hidden" name="usevar" value="<?php echo $usevar; ?>" /><a href="#" onclick="checkEditFields(); return false;" class="greenfield"><?php echo returnIntLang('str save'); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" onclick="document.getElementById('varname').value=''; document.getElementById('selfvar').value='';" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p>
	</fieldset>
	</form>
	<?php else: ?>
	<fieldset id="options" class="options">
		<p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?op=edit&id=0" class="greenfield"><?php echo returnIntLang('selfvars createnewvar', false); ?></a></p>
	</fieldset>
	<?php endif; ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->