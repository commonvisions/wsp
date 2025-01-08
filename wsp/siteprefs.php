<?php
/**
 * global site-setup
 * @author s.haendler@covi.de
 * @copyright (c) 2016, Common Visions Media.Agentur (COVI)
 * @since 3.1
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
require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'siteprops';
$_SESSION['wspvars']['mgroup'] = 3;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = false;
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ------------ */
if (isset($_POST['save_data'])):
	foreach ($_POST AS $key => $value):
		if ($key!="save_data"):
			doSQL("DELETE FROM `wspproperties` WHERE `varname` = '".escapeSQL(trim($key))."'");
			if (is_array($value)):
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL(trim($key))."', `varvalue` = '".escapeSQL(serialize($value))."'");
			else:
				doSQL("INSERT INTO `wspproperties` SET `varname` = '".escapeSQL(trim($key))."', `varvalue` = '".escapeSQL($value)."'");
			endif;
		endif;
	endforeach;
endif;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

$siteinfo_sql = "SELECT * FROM `wspproperties`";
$siteinfo_res = doSQL($siteinfo_sql);
if ($siteinfo_res['num']>0):
	foreach ($siteinfo_res['set'] AS $sresk => $sresv):
		$sitedata[trim($sresv['varname'])] = $sresv['varvalue'];
	endforeach;
endif;

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('generell headline'); ?></h1></fieldset>
	<!-- <fieldset><p><?php echo returnIntLang('generell info'); ?></p></fieldset> -->
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="frmprefs" style="margin: 0px;">
	<fieldset id="fieldset_session">
		<legend><?php echo returnIntLang('generell server'); ?> <?php echo legendOpenerCloser('prefs_session'); ?></legend>
		<div id="prefs_session">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('generell server usesession'); ?></td>
					<td class="tablecell six"><input type="hidden" name="use_session" value="0"><input name="use_session" id="use_session" type="checkbox" value="1" <?php if(intval($sitedata['use_session'])==1): echo "checked=\"checked\""; endif; ?> /></td>
				</tr>
			</table>
			<p><?php echo returnIntLang('generell server sessiondesc'); ?></p>
			<input type="hidden" name="use_tracking" value="0">
		</div>
	</fieldset>
	<fieldset id="fieldset_code" class="text">
		<legend><?php echo returnIntLang('generell code'); ?> <?php echo legendOpenerCloser('prefs_code'); ?></legend>
		<div id="prefs_code">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('generell doctype'); ?></td>
					<td class="tablecell two"><select name="doctype">
						<option value="html4trans">HTML 4 Transitional</option>
						<option value="html4strict" <?php if($sitedata['doctype']=="html4strict") echo "selected=\"selected\"";?>>HTML 4 Strict</option>
						<option value="html5" <?php if($sitedata['doctype']=="html5") echo "selected=\"selected\"";?>>HTML 5 (Strict)</option>
						<option value="xhtml1trans" <?php if($sitedata['doctype']=="xhtml1trans") echo "selected=\"selected\"";?>>XHTML 1 Transitional</option>
						<option value="xhtml1strict" <?php if($sitedata['doctype']=="xhtml1strict") echo "selected=\"selected\"";?>>XHTML 1 Strict</option>
						<option value="xhtml1-1" <?php if($sitedata['doctype']=="xhtml1-1") echo "selected=\"selected\"";?>>XHTML 1.1 (Strict)</option>
					</select></td>
					<td class="tablecell two"><?php echo returnIntLang('generell code'); ?></td>
					<td class="tablecell two"><select name="codepage">
						<option value="utf-8" <?php if($sitedata['codepage']!="iso-8859-1") echo "selected=\"selected\"";?>>UTF-8</option>
						<option value="iso-8859-1">ISO-8859-1</option>
					</select></td>
				</tr>
			</table>
		</div>
	</fieldset>
	<fieldset id="fieldset_contentvars" class="text">
		<legend><?php echo returnIntLang('generell contentvars'); ?> <?php echo legendOpenerCloser('prefs_contentvars'); ?></legend>
		<div id="prefs_contentvars">
			<table class="tablelist">
			<?php if(array_key_exists('contentvardesc', $sitedata)): $sitedata['contentvardesc'] = unserializeBroken($sitedata['contentvardesc']); foreach($sitedata['contentvardesc'] AS $sk => $sv): if (trim($sv)==""): unset($sitedata['contentvardesc'][$sk]); endif; endforeach; ?>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 1</td>
					<td class="tablecell two"><input type="text" value="<?php if(isset($sitedata['contentvardesc'][0])): echo prepareTextField($sitedata['contentvardesc'][0]); endif; ?>" name="contentvardesc[]" class="full" /></td>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 2</td>
					<td class="tablecell two"><input type="text" value="<?php if(isset($sitedata['contentvardesc'][1])): echo prepareTextField($sitedata['contentvardesc'][1]); endif; ?>" name="contentvardesc[]" class="full" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 3</td>
					<td class="tablecell two"><input type="text" value="<?php if(isset($sitedata['contentvardesc'][2])): echo prepareTextField($sitedata['contentvardesc'][2]); endif; ?>" name="contentvardesc[]" class="full" /></td>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 4</td>
					<td class="tablecell two"><input type="text" value="<?php if(isset($sitedata['contentvardesc'][3])): echo prepareTextField($sitedata['contentvardesc'][3]); endif; ?>" name="contentvardesc[]" class="full" /></td>
				</tr>
				<?php if (isset($sitedata['contentvardesc']) && count($sitedata['contentvardesc'])>=3):
					for ($cv=2; $cv<=ceil(count($sitedata['contentvardesc'])/2); $cv++):
						?>
						<tr>
							<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> <?php echo (($cv*2)+1); ?></td>
							<td class="tablecell two"><input type="text" value="<?php if(isset($sitedata['contentvardesc'][(($cv*2))])): echo prepareTextField($sitedata['contentvardesc'][(($cv*2))]); endif; ?>" class="full" name="contentvardesc[]" /></td>
							<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> <?php echo (($cv*2)+2); ?></td>
							<td class="tablecell two"><input type="text" value="<?php if(isset($sitedata['contentvardesc'][(($cv*2)+1)])): echo prepareTextField($sitedata['contentvardesc'][(($cv*2)+1)]); endif; ?>" class="full" name="contentvardesc[]" /></td>
						</tr>
						<?php
					endfor;
				endif; ?>					
			<?php else: ?>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 1</td>
					<td class="tablecell two"><input type="text" value="" name="contentvardesc[]" /></td>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 2</td>
					<td class="tablecell two"><input type="text" value="" name="contentvardesc[]" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 3</td>
					<td class="tablecell two"><input type="text" value="" name="contentvardesc[]" /></td>
					<td class="tablecell two"><?php echo returnIntLang('templates contentelement', false); ?> 4</td>
					<td class="tablecell two"><input type="text" value="" name="contentvardesc[]" /></td>
				</tr>
			<?php endif; ?>
			</table>
		</div>
	</fieldset>
	<?php
	
	$tempdata = unserialize($sitedata['languages']);
	unset($sitedata['languages']);

	if (array_key_exists('languages', $tempdata) && is_array($tempdata['languages']['longname'])):
		$sitedata['languages'] = $tempdata['languages'];
	else:
		$sitedata['languages'] = $tempdata;
	endif;

	if (!(is_array($sitedata['languages']))):
		$sitedata['languages']['longname'] = array('Deutsch');
		$sitedata['languages']['shortcut'] = array('de');
	endif;
	if (count($sitedata['languages'])==0):
		$sitedata['languages']['longname'] = array('Deutsch');
		$sitedata['languages']['shortcut'] = array('de');
	endif;

	?>
	<script language="JavaScript" type="text/javascript">
	<!--
	
	var formelements = new Array();
			
	function abblenden(element, start) {
        document.getElementById(element).style.opacity = start/100;
        document.getElementById(element).style.filter = 'alpha(opacity: ' + start + ')';
        if(start>=5) {
            setTimeout("abblenden('" + element + "', " + (start-5) + ")",50);
            }
        else {
            document.getElementById(element).innerHTML = '';
            document.getElementById(element).style.display = 'none';
            var re = document.getElementById('formelements');
			var oldli = document.getElementById('element_' + id);
            re.removeChild(oldli);
            }
	    }

	function removeElement(id) {
		abblenden('element_' + id, 100);
		}
		
	function addElement() {
		if (document.getElementById('add_newlang_longname').value!="" && document.getElementById('add_newlang_shortcut').value!="") {
			var newlang = document.createElement('tr');
			var timestamp = new Date().getTime();
			newlang.setAttribute("id", 'element_' + timestamp);
			var createtable = '<td class="tablecell two"><?php echo returnIntLang('str new', false); ?></td>';
			createtable = createtable + '<td class="tablecell three"><input type="text" name="languages[longname][]" value="' + document.getElementById('add_newlang_longname').value + '" readonly="readonly" class="full" /></td>';
			createtable = createtable + '<td class="tablecell one"><input type="text" name="languages[shortcut][]" value="' + document.getElementById('add_newlang_shortcut').value + '" readonly="readonly" style="width: 3em;" /></td>';
			createtable = createtable + '<td class="tablecell one"></td>';
			createtable = createtable + '<td class="tablecell one"><a href="#" onclick="abblenden(\'element_' + timestamp + '\', 100);"><span class="bubblemessage red"><?php echo returnIntLang('bubble delete', 'false'); ?></span></a></td>';
			newlang.innerHTML = createtable;
			$(newlang).insertBefore("#element_add");
			document.getElementById('add_newlang_longname').value = '';
			document.getElementById('add_newlang_shortcut').value = '';
			createFloatingTable();
			}
		}


	// -->
	</script>
	<fieldset id="fieldset_language" class="text">
		<legend><?php echo returnIntLang('generell language'); ?> <?php echo legendOpenerCloser('prefs_language'); ?></legend>
		<div id="prefs_language">
			<table class="tablelist">
				<tr class="tablehead">
					<td class="tablecell two"><?php echo returnIntLang('generell language setlang'); ?></td>
					<td class="tablecell three info"><?php echo returnIntLang('str language'); ?> <?php helptext(returnIntLang('generell language setlang help', false)); ?></td>
					<td class="tablecell one info"><?php echo returnIntLang('str shortcut'); ?>  <?php helpText(returnIntLang('generell language shortcutdesc', false)); ?></td>
					<td class="tablecell one info"><?php echo returnIntLang('str icon'); ?> <?php helpText(returnIntLang('generell language icondesc', false)); ?></td>
					<td class="tablecell one info"></td>
				</tr>
				<?php foreach($sitedata['languages']['longname'] AS $key => $value): ?>
				<tr id="element_<?php echo $key; ?>">
					<td class="tablecell two"><?php echo ($key+1); ?>. <?php echo returnIntLang('str language'); ?></td>
					<td class="tablecell three"><input type="text" name="languages[longname][]" value="<?php echo $sitedata['languages']['longname'][$key]; ?>" readonly="readonly" class="full" /></td>
					<td class="tablecell one"><input type="text" name="languages[shortcut][]" value="<?php echo $sitedata['languages']['shortcut'][$key]; ?>" readonly="readonly" style="width: 3em;" /></td>
					<td class="tablecell one"><?php
								
					if (is_file($_SERVER['DOCUMENT_ROOT']."/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".png")):
						echo "<img src=\"/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".png\" />";
					elseif (is_file($_SERVER['DOCUMENT_ROOT']."/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".gif")):
						echo "<img src=\"/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".gif\" />";
					elseif (is_file($_SERVER['DOCUMENT_ROOT']."/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".jpg")):
						echo "<img src=\"/media/screen/lang/".$sitedata['languages']['shortcut'][$key].".jpg\" />";
					else:
						echo "<em>".returnIntLang('generell language noicon')."</em>";
					endif;
								
					?></td>
					<td class="tablecell one"><a href="#" onclick="abblenden('element_<?php echo $key; ?>', 100);"><span class="bubblemessage red"><?php echo returnIntLang('bubble delete', 'false'); ?></span></a></td>
				</tr>
				<?php endforeach; ?>
				<tr id="element_add">
					<td class="tablecell two head"><?php echo returnIntLang('generell language addlang'); ?></td>
					<td class="tablecell three head"><input type="text" id="add_newlang_longname" value="" class="full" /></td>
					<td class="tablecell one head"><input type="text" id="add_newlang_shortcut" value="" maxlength="3" style="width: 3em;" /></td>
					<td class="tablecell one head">&nbsp;</td>
					<td class="tablecell one head"><a onclick="addElement();" style="cursor: pointer;"><span class="bubblemessage green"><?php echo returnIntLang('bubble add', 'false'); ?></span></a></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('generell language showmode'); ?></td>
					<td class="tablecell two"><select name="showlang" class="full">
						<option value="text" <?php if($sitedata['showlang']!="icon") echo "selected=\"selected\""; ?>><?php echo returnIntLang('generell language textlist'); ?></option>
						<option value="icon" <?php if($sitedata['showlang']=="icon") echo "selected=\"selected\""; ?>><?php echo returnIntLang('str icon'); ?></option>
						<option value="dropdown" <?php if($sitedata['showlang']=="dropdown") echo "selected=\"selected\""; ?>><?php echo returnIntLang('str dropdown'); ?></option>
					</select></td>
					<td class="tablecell two"><?php echo returnIntLang('generell language alternative'); ?></td>
					<td  class="tablecell two"><select name="setoutputlang" class="full">
						<option value="page" <?php if($sitedata['setoutputlang']!="content") echo "selected=\"selected\""; ?>><?php echo returnIntLang('generell language pageitem'); ?></option>
						<option value="content" <?php if($sitedata['setoutputlang']=="content") echo "selected=\"selected\""; ?>><?php echo returnIntLang('generell language contentitem'); ?></option>
					</select></td>
				</tr>
			</table>
		</div>
	</fieldset>
	<fieldset id="fieldset_meta" class="text">
		<legend><?php echo returnIntLang('generell meta'); ?> <?php echo legendOpenerCloser('prefs_meta'); ?></legend>
		<div id="prefs_meta">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('generell meta copy'); ?></td>
					<td class="tablecell six"><input name="sitecopy" type="text" value="<?php echo (isset($sitedata['sitecopy'])?$sitedata['sitecopy']:''); ?>" placeholder="<?php echo returnIntLang('generell meta copy url without http://', false); ?>" class="full" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('generell meta baseurl'); ?> <?php helpText(returnIntLang('generell meta baseurl help', false)); ?></td>
					<td class="tablecell six"><input name="siteurl" type="text" value="<?php echo (isset($sitedata['siteurl'])?$sitedata['siteurl']:''); ?>" placeholder="<?php echo returnIntLang('generell meta baseurl url without http://', false); ?>" class="full" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('generell meta author'); ?> <?php helpText(returnIntLang('generell meta author help', false)); ?></td>
					<td class="tablecell six"><input name="siteauthor" type="text" value="<?php echo (isset($sitedata['siteauthor'])?$sitedata['siteauthor']:''); ?>" class="full" /></td>
				</tr>
			</table>
		</div>
	</fieldset>
	<fieldset class="options">
		<p><a href="#" onclick="document.getElementById('frmprefs').submit(); return false;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a><input name="save_data" type="hidden" value="Speichern" /></p>
	</fieldset>
	</form>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- // EOF -->