<?php
/**
 * Bearbeiten von JavaScript
 * @author stefan@covi.de
 * @since 3.4
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
$op = checkParamVar('op', '');
$id = checkParamVar('id', 0);
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'javascript';
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific funcs ---------------- */
if (isset($_POST['op']) && $_POST['op'] == "save"):
	if (intval($id) > 0):
		// JavaScript updaten
		$sql = "UPDATE `javascript` SET `file`='".escapeSQL(urltext($_POST['file']))."', `scriptcode`='".escapeSQL(trim($_POST['scriptcode']))."', `describ`='".escapeSQL(trim($_POST['describ']))."', `lastchange` = ".time()." WHERE `id` = ".intval($id);
	else:
		// neues JavaScript
		$sql = "INSERT INTO `javascript` SET `file`='".escapeSQL(urltext($_POST['file']))."', `scriptcode`='".escapeSQL(trim($_POST['scriptcode']))."', `describ`='".escapeSQL(trim($_POST['describ']))."', `lastchange` = ".time();
    endif;
    if (doSQL($sql)) {
        addWSPMsg('resultmsg', returnIntLang('saved changes to jsfile', false));
    }	
    else {
        addWSPMsg('errormsg', returnIntLang('could not save changes to jsfile', false));
    }
elseif ($op == "savefolder"):
	if ($id > 0):
		// JavaScript updaten
		if (array_key_exists('scriptcode', $_POST)):
			$scriptcode = serialize($_POST['scriptcode']);
		else:
			$scriptcode = '';
		endif;
		$sql = "UPDATE `javascript` SET 
			`scriptcode` = '".escapeSQL($scriptcode)."',
			`describ` = '".escapeSQL(trim($_POST['describfolder']))."',
			`lastchange` = '".time()."'
			WHERE `id` = ".$id;
		doSQL($sql);
		addWSPMsg('resultmsg', returnIntLang('saved changes to jsfolder', false));	
	else:
		addWSPMsg('errormsg', returnIntLang('could not save changes to jsfolder', false));	
	endif;
elseif ($op == "uploadfile" && $_FILES['fileupload']['type'] == "application/x-javascript"): 
	$upfile = $_FILES['fileupload']['tmp_name'];
	$array = file($upfile);   
	$sql = "INSERT INTO `javascript` SET 
			`file`='".escapeSQL(urltext(str_replace(".js","",$_FILES['fileupload']['name'])))."',
			`scriptcode` = '".escapeSQL(trim(implode("\n", $array)))."',
			`describ`='".escapeSQL(trim($_POST['describupload']))."',
			`lastchange` = ".time();
	if (doSQL($sql)) {
        addWSPMsg('resultmsg', returnIntLang('uploaded jsfile', false));
    }	
    else {
        addWSPMsg('errormsg', returnIntLang('could not upload jsfile', false));
    }
elseif ($op == "delete"):
	doSQL("DELETE FROM `javascript` WHERE `id` = ".intval($id));
elseif ($op == "deletefolder"):
	doSQL("DELETE FROM `javascript` WHERE `id` = ".intval($id));
endif;

if ((isset($_REQUEST['action']) && $_REQUEST['action']=="menuedit") && intval($_GET['id']>0) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) && (strpos($_SERVER['HTTP_REFERER'], $wspvars['wspbasedir'])) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']))):
	$_SESSION['getvars']['mid'] = intval($_GET['id']);
	$_SESSION['getvars']['op'] = "edit";
	header ("location: menueditdetails.php");
endif;

// site specific definitions

// definition des zugriffes der head-erweiterung um scriptaculous-bibliothek, xajax, googlemap
$wspvars['usescriptaculous'] = true;

// head der datei

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");

?><div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('js headline'); ?></h1></fieldset>
	<fieldset><p><?php echo returnIntLang('js info'); ?></p></fieldset>
	<?php
	
	$js_sql = "SELECT `id`, `cfolder`, `describ` FROM `javascript` WHERE `cfolder` != '' AND `lastchange` != `cfolder` ORDER BY `describ`";
	$js_res = doSQL($js_sql);
	$js_num = $js_res['num'];
	
	if ($js_num<5 && intval($id)==0):
		$tmpshowopen = "open";
	endif;
	
	if (!(isset($_POST['op']) && $_POST['op']=='editfolder') && !(isset($_POST['op']) && $_POST['op']=='edit')):
	?>
	<fieldset>
		<legend><?php echo returnIntLang('js existingfolder'); ?> <?php echo legendOpenerCloser('jsfolder'); ?></legend>
		<?php
		
		if ($js_num==0) {
			echo "<p>".returnIntLang('js nofolder')."</p>\n";
		} else {
			?>
			<div id="jsfolder">
			<table class="tablelist">
			<tr>
				<td class="tablecell two head"><?php echo returnIntLang('str folder'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str description'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str usage'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str action'); ?></td>
			</tr>
			<?php
		
            foreach ($js_res['set'] AS $jsrsk => $jsrsv) {

                echo "<tr>\n";
                echo "\t<form name=\"edit_scriptfolder_".$jsrsk."\" id=\"edit_scriptfolder_".$jsrsk."\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
                echo "<input name=\"op\" id=\"\" type=\"hidden\" value=\"editfolder\" />";
                echo "<input name=\"id\" id=\"\" type=\"hidden\" value=\"".intval($jsrsv['id'])."\" />";
                echo "</form>\n";
			
                echo "<td class='tablecell two'><a href=\"#\" title=\"JavaScript-Sammlung bearbeiten\" onmouseover=\"status='JavaScript-Sammlung bearbeiten'; return true;\" onmouseout=\"status=''; return true;\" onClick=\"document.getElementById('edit_scriptfolder_".$jsrsk."').submit();\">".$jsrsv['cfolder']."</a></td>";
                echo "<td class='tablecell two'>".$jsrsv['describ']."</td>";
                echo "<td class='tablecell two'>";
			
                $scriptuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_jscript` AS rtj, `templates` AS t WHERE rtj.`templates_id` = t.`id` AND rtj.`javascript_id` = ".intval($jsrsv['id']);
                $scriptuse_res = doSQL($scriptuse_sql);
                if ($scriptuse_res['num']>0): 
                    for($sures=0; $sures<$scriptuse_res['num']; $sures++):
                        echo "<a href=\"/".$wspvars['wspbasedir']."/templatesedit.php?op=edit&id=".intval($scriptuse_res['set'][$sures]['tid'])."\">".setUTF8($scriptuse_res['set'][$sures]['tname'])."</a><br />";
				    endfor;
                endif;
			
                $scriptmenuuse_sql = "SELECT mj.`description` AS mdesc, mj.`mid` AS mid FROM `menu` AS mj WHERE mj.`addscript` LIKE '%\"".intval($jsrsv['id'])."\"%'";
                $scriptmenuuse_res = doSQL($scriptmenuuse_sql);
                if ($scriptmenuuse_res['num']>0):
                    echo "Men&uuml;punkte:<br />"; 
                    if ($scriptmenuuse_res['num']>5):
                        $smushow = 5;
                    else:
					   $smushow = $scriptmenuuse_res['num'];
                    endif;
                    for($sures=0; $sures<$smushow; $sures++):
                        echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".$scriptmenuuse_res['set'][$sures]['mid']."\">".$scriptmenuuse_res['set'][$sures]['mdesc']."</a><br />";
                    endfor;
                    if ($scriptmenuuse_res['num']>5):
                        echo "<a style=\"cursor: pointer;\" id=\"showmore\" onclick=\"document.getElementById('hidemore').style.display = 'block'; document.getElementById('showmore').style.display = 'none';\" >".($scriptmenuuse_res['num']-5)." weitere ..</a>";
                        echo "<span id=\"hidemore\" style=\"display: none;\">";
                        for($sures=5; $sures<$scriptmenuuse_res['num']; $sures++):
                            echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".$scriptmenuuse_res['set'][$sures]['mid']."\">".$scriptmenuuse_res['set'][$sures]['mdesc']."</a><br />";
                        endfor;
                        echo "</span>";
                    endif;
                endif;
			
                echo "</td>";
                echo "<td class='tablecell two'><a href=\"".$_SERVER['PHP_SELF']."?op=deletefolder&id=".intval($jsrsv['id'])."\" onclick=\"return confirm('".returnIntLang('js confirmdeletefolder', false)."');\"><span class=\"bubblemessage red\">".strtoupper(returnIntLang('bubble delete', false))."</span></a></td>";
                echo "</tr>\n";
            }
			echo '</table></div>';

		}
	   ?>
	</fieldset>
	<?php
	
	endif;
	
	if ($op=="editfolder" && $id>0):
		$scripts_sql = "SELECT `id`, `cfolder`, `describ`, `scriptcode` FROM `javascript` WHERE `id` = '".$id."'";
		$scripts_res = doSQL($scripts_sql);
		
		if ($scripts_res['num']>0):
			$describ = trim($scripts_res['set'][0]['describ']);
			$cfolder = trim($scripts_res['set'][0]['cfolder']);
			$usedones = unserializeBroken(trim($scripts_res['set'][0]['scriptcode']));
		endif;
	endif;
	
	?>
	<fieldset id="editjsfolder" <?php if($op!="editfolder"): ?>style="display: none;"<?php endif; ?>>
		<legend><?php echo returnIntLang('js editfolder'); ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscriptfolder" name="formscriptfolder">
		<table class="tablelist">
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('str description'); ?></td>
			<td class="tablecell six"><input type="text" size="50" maxlength="70" name="describfolder" id="describfolder" value="<?php echo $describ; ?>" style="width: 94%;" /></td>
		</tr>
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('str folder'); ?></td>
			<td class="tablecell six"><input type="text" size="50" maxlength="70" disabled="disabled" readonly="readonly" value="<?php echo $cfolder; ?>" style="width: 94%;" /></td>
		</tr>
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('js folderfiles'); ?></td>
			<td class="tablecell six"><?php
			
			$path = "/data/script/".$cfolder."/";
			$usefiles = array();
			
			$usedones[] = "/test.js";
			
			function listFiles($path, $startpath) {
				if (is_dir($_SERVER['DOCUMENT_ROOT'].$path)):
					$dir = opendir ($_SERVER['DOCUMENT_ROOT'].$path);
				    while ($entry = readdir($dir)):
						if (!(substr($entry, 0, 1)==".")):
							if (is_dir($_SERVER['DOCUMENT_ROOT'].$path."/".$entry)):
								listFiles($path."/".$entry, $startpath."/".$entry);
							elseif (substr($entry, -3) == ".js"):
								$GLOBALS['usefiles'][] = $startpath."/".$entry;
							endif;
						endif;
				    endwhile;
					closedir ($dir);
			    endif;
				}
			
			if (is_dir($_SERVER['DOCUMENT_ROOT'].$path)):
				listFiles($path, '');
				
				if (is_array($usedones)):
					$temporderusefiles = array();
					foreach ($usefiles AS $key => $value):
						if (in_array($value, $usedones)):
							$upkey = array_keys($usedones, $value);
							$temporderusefiles[($upkey[0])] = $value;
						else:
							$temporderusefiles[(count($usefiles)+$key)] = $value;
						endif;
						$temporderusefiles[] = $value;
					endforeach;
					unset($usefiles);
					$usefiles = array_unique($temporderusefiles);
					ksort($usefiles);
				endif;
				echo "<ul id=\"sortscripts\" style=\"margin: 0px; padding: 0px; list-style-type: none;\">";
				foreach ($usefiles AS $key => $value):
					echo "<li id=\"sortscripts_".$key."\"><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td>";
					echo "<input type=\"checkbox\" name=\"scriptcode[]\" id=\"usefiles_".$key."\" value=\"".$value."\" ";
					if (is_array($usedones)):
						if (in_array($value, $usedones)):
							echo " checked=\"checked\" ";
						endif;
					endif;
					echo " /></td><td style=\"cursor: move;\">".$value."</td></tr></table></li>";
				endforeach;
				echo "</ul>";
								
			else:
				echo "Der Scriptordner ist nicht vorhanden";
		    endif;
			
			?></td>
		</tr>
		</table>
		<p><?php echo returnIntLang('js movefileshint'); ?></p>
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		<input type="hidden" name="op" value="savefolder" />
		<fieldset class="options innerfieldset"><p><a href="javascript: document.getElementById('formscriptfolder').submit();" onclick="return checkFieldsScriptfolder();" class="greenfield"><?php echo returnIntLang('str save'); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" title="Abbrechen" onmouseover="status='Abbrechen'; return true;" onmouseout="status=''; return true;" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p></fieldset>
		</form>
		<script type="text/javascript" language="javascript" charset="utf-8">
		
		$(function() {
    $( "#sortscripts" ).sortable({
      placeholder: "ui-state-highlight"
    });
    $( "#sortscripts" ).disableSelection();
  });
		
		</script>
		<div id="debug"></div>
	</fieldset>
	
	<?php if (!(isset($_POST['op']) && $_POST['op']=='editfolder') && !(isset($_POST['op']) && $_POST['op']=='edit')): ?>
        <fieldset>
            <legend><?php echo returnIntLang('js existingfiles'); ?> <?php echo legendOpenerCloser('jsfiles'); ?></legend>
            <?php
            
            $js_sql = "SELECT `id`, `file`, `describ` FROM `javascript` WHERE `file` != '' ORDER BY `describ`";
            $js_res = doSQL($js_sql);
            
            if ($js_res['num']<5 && intval($id)==0) {
                $tmpshowopen = "open";
            }
		
		if ($js_res['num']==0):
			echo "<p>Es sind noch keine JavaScript-Dateien definiert!</p>\n";
		else:

			?>
			<div id="jsfiles">
			<table class="tablelist">
			<tr>
				<td class="tablecell two head"><?php echo returnIntLang('str filename'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str description'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str usage'); ?></td>
				<td class="tablecell two head"><?php echo returnIntLang('str action'); ?></td>
			</tr>
			<?php
                
            foreach ($js_res['set'] AS $jsrsk => $jsrsv) {
		
                echo "<tr>\n";
				echo "<form name=\"edit_script_".$jsrsk."\" id=\"edit_script_".$jsrsk."\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
				echo "<input name='op' type='hidden' value='edit' />";
				echo "<input name='id' type='hidden' value='".intval($jsrsv['id'])."' />";
				echo "</form>\n";
				
				echo "<td class='tablecell two'><a href=\"#\" title=\"JavaScript bearbeiten\" onmouseover=\"status='JavaScript bearbeiten'; return true;\" onmouseout=\"status=''; return true;\" onClick=\"document.getElementById('edit_script_".$jsrsk."').submit();\">".trim($jsrsv['file']).".js</a></td>";
				echo "<td class='tablecell two'>".trim($jsrsv['describ'])."</td>";
				echo "<td class='tablecell two'>";
			
				$scriptuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_jscript` AS rtj, `templates` AS t WHERE rtj.`templates_id` = t.`id` AND rtj.`javascript_id` = ".intval($jsrsv['id']);
				$scriptuse_res = doSQL($scriptuse_sql);
				if ($scriptuse_res['num']>0):
					foreach ($scriptuse_res['set'] AS $suresk => $suresv):
						echo "<a href=\"./templatesedit.php?op=edit&id=".intval($suresv['tid'])."\">".setUTF8(trim($suresv["tname"]))."</a><br />";
					endforeach;
				else:
					echo "-";
				endif;
				
				echo "</td>";
				echo "<td class='tablecell two'><a href=\"".$_SERVER['PHP_SELF']."?op=delete&id=".intval($jsrsv['id'])."\" onclick=\"return confirm('".returnIntLang('js confirmdeletefile', false)."');\"><span class=\"bubblemessage red\">".strtoupper(returnIntLang('bubble delete', false))."</span></a></td>";
				echo "</tr>\n";
            
            }
			
			?>
			</table>
			</div>
			<?php
		endif;
		?>
	</fieldset>
	<?php
	endif;
	
	if ($op=="edit" && $id>0):
		$scripts_sql = "SELECT `id`, `file`, `describ`, `scriptcode` FROM `javascript` WHERE `id` = ".intval($id);
		$scripts_res = doSQL($scripts_sql);
	
		if ($scripts_res['num']>0):
			$describ = trim($scripts_res['set'][0]['describ']);
			$file = trim($scripts_res['set'][0]['file']);
			$scriptcode = trim($scripts_res['set'][0]['scriptcode']);
		else:
			$id = 0;
		endif;
	endif;
	
	if ($id==0):
		$describ = '';
		$file ='';
		$scriptcode = '';
	endif;
	
	?>
	<script language="javascript" type="text/javascript">
		<!--
		function checkFields() {
			if (document.getElementById('describ').value == '') {
				alert('Bitte geben Sie eine Beschreibung f'+String.fromCharCode(252)+'r dieses JavaScript ein.');
				document.getElementById('describ').focus();
				return false;
				}
				
			if (document.getElementById('file').value == '') {
				alert('Bitte geben Sie einen Dateinamen f'+String.fromCharCode(252)+'r dieses JavaScript ein.');
				document.getElementById('file').focus();
				return false;
				}

			return true;
			}	// checkFields()
			
		function pasteTab() {
			var input = document.getElementById('scriptcode');
  			input.focus();
  			/* fr Internet Explorer */
			if(typeof document.selection != 'undefined') {
    			/* Einfgen des Formatierungscodes */
    			var range = document.selection.createRange();
    			var insText = range.text;
    			range.text = "	" + insText;
    			/* Anpassen der Cursorposition */
    			range = document.selection.createRange();
    			range.moveStart('character', insText.length);      
				range.select();
				}
				
  /* for newer, Gecko based Browser */
  else if(typeof input.selectionStart != 'undefined')
  {
    /* Einfgen des Formatierungscodes */
    var start = input.selectionStart;
    var end = input.selectionEnd;
    var insText = input.value.substring(start, end);
    input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);
    /* Anpassen der Cursorposition */
    var pos;
    if (insText.length == 0) {
      pos = start + aTag.length;
    } else {
      pos = start + aTag.length + insText.length + eTag.length;
    }
    input.selectionStart = pos;
    input.selectionEnd = pos;
  }
  /* fr die brigen Browser */
  else
  {
    /* Abfrage der Einfgeposition */
    var pos;
    var re = new RegExp('^[0-9]{0,3}$');
    while(!re.test(pos)) {
      pos = prompt("Einfgen an Position (0.." + input.value.length + "):", "0");
    }
    if(pos > input.value.length) {
      pos = input.value.length;
    }
    /* Einfgen des Formatierungscodes */
    var insText = prompt("Bitte geben Sie den zu formatierenden Text ein:");
    input.value = input.value.substr(0, pos) + aTag + insText + eTag + input.value.substr(pos);
  }
		
	function uploadJS() {
		document.getElementById('editjs').style.display = 'none';
		document.getElementById('uploadjs').style.display = 'block';
		}
	
	function checkFieldsUpload() {
		if (document.getElementById('describupload').value == '') {
			alert('Bitte geben Sie eine Beschreibung f'+String.fromCharCode(252)+'r dieses JavaScript ein.');
			document.getElementById('describupload').focus();
			return false;
			}
			
		if (document.getElementById('fileupload').value == '') {
			alert('Bitte waehlen Sie eine Datei zum Upload aus.');
			document.getElementById('fileupload').focus();
			return false;
			}

		return true;
		}	// checkFields()
	
	function uploadJSFolder() {
		document.getElementById('editjs').style.display = 'none';
		alert ('uploadJSFolder');
		}
	
	//-->
	</script>
	<fieldset id="editjs" <?php if($op!="edit"): ?>style="display: none;"<?php endif; ?>>
		<legend><?php if($op=="edit" && $id>0): ?><?php echo returnIntLang('js editfile'); ?><?php else: ?><?php echo returnIntLang('js createnewfile'); ?><?php endif; ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscript">
		<table class="tablelist">
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('str description'); ?></td>
			<td class="tablecell six"><input type="text" size="50" maxlength="70" name="describ" id="describ" value="<?php echo $describ; ?>" style="width: 94%;" /></td>
		</tr>
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('str filename'); ?></td>
			<td class="tablecell six"><input type="text" size="20" maxlength="50" name="file" id="file" value="<?php echo $file; ?>" />.js</td>
		</tr>
		<tr class="secondcol">
			<td class="tablecell two"><?php echo returnIntLang('str content'); ?></td>
			<td class="tablecell six"><textarea name="scriptcode" id="scriptcode" cols="80" rows="15" style="width: 95%;"><?php
			$scriptrows = explode("\n",$scriptcode);
			for ($r=0;$r<count($scriptrows);$r++):
				if (trim($scriptrows[$r])!=""):
					if (!strstr($scriptrows[$r],"{")):
						echo "\t";
					endif;
					echo trim($scriptrows[$r])."\n";
					if (strstr($scriptrows[$r],"}")):
						echo "\n";
					endif;
				endif;
			endfor; ?></textarea></td>
		</tr>
		</table>
		<input type="hidden" name="op" value="save" /><input type="hidden" name="id" value="<?php echo $id; ?>" />
		<fieldset class="options innerfieldset"><p><a href="javascript: document.getElementById('formscript').submit();" onclick="return checkFields();" class="greenfield"><?php echo returnIntLang('str save'); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p></fieldset>
		</form>
	</fieldset>
	
	<fieldset id="uploadjs" style="display: none;">
		<legend><?php echo returnIntLang('js uploadfile'); ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscriptupload">
		<table class="tablelist">
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('str description'); ?></td>
			<td class="tablecell six"><input type="text" size="50" maxlength="70" name="describupload" id="describupload" value="" style="width: 94%;" /></td>
		</tr>
		<tr>
			<td class="tablecell two"><?php echo returnIntLang('str file'); ?></td>
			<td class="tablecell six"><input type="file" size="30" maxlength="50" name="fileupload" id="fileupload" accept="application/x-javascript" style="width: 50%;" /></td>
		</tr>
		</table>
		<input type="hidden" name="op" value="uploadfile" /><input type="hidden" name="id" value="<?php echo $id; ?>" />
		<fieldset class="options innerfieldset"><p><a href="javascript: document.getElementById('formscriptupload').submit();" onclick="return checkFieldsUpload();" class="greenfield"><?php echo returnIntLang('str upload'); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p></fieldset>
		</form>
	</fieldset>
	
	<fieldset id="options" class="options">
		<p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?op=edit&id=0" class="greenfield"><?php echo returnIntLang('js createnewfile'); ?></a> <a href="#" onclick="uploadJS();" class="greenfield"><?php echo returnIntLang('js uploadfile'); ?></a> <!-- <a onclick="uploadJSFolder();" class="greenfield"><?php echo returnIntLang('js uploadfolder'); ?></a> --></p>
	</fieldset>
</div>
<?php @ include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->