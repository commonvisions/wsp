<?php
/**
 * Templates bearbeiten
 * @author COVI
 * @copyright (c) 2022, Common Visions Media.Agentur (COVI)
 * @since 3.2.4
 * @version 6.11
 * @lastchange 2022-10-25
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
$op = checkParamVar('op', '');
$id = checkParamVar('id', 0);
/* define actual system position ------------- */
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].';id='.$id;
$_SESSION['wspvars']['fposcheck'] = true;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");

/* define page specific vars ----------------- */
if (!(array_key_exists('menuedit_templates', $_SESSION['opentabs']))) $_SESSION['opentabs']['menuedit_templates'] = 'display: block;';
/* define page specific functions ------------ */

if ($op=="new"):
	
	$new_guid = md5(date("Y-m-d H:i:s"));
	$new_guid = substr($new_guid,0,8)."-".substr($new_guid,8,4)."-".substr($new_guid,12,4)."-".substr($new_guid,16,4)."-".substr($new_guid,20);
	
	$menu_num = 0;
	$menu_sql = "SELECT * FROM `templates_menu` WHERE `title` LIKE '".$_POST['menu_title']."'";
	$menu_res = doSQL($menu_sql);
	if ($menu_res['num']>0):
		$_SESSION['wspvars']['errormsg'] .= "Ein Men&uuml; mit diesem Titel ist bereits vorhanden.";
	else:
		$sql = "INSERT INTO `templates_menu` SET `title` = '".escapeSQL($_POST['menu_title'])."', `describ` = '".escapeSQL($_POST['menu_describ'])."', `startlevel` = '".intval($_POST['menu_slevel'])."', `code` = '".escapeSQL($_POST['menu_code'])."', `guid` = '".$new_guid."'";
		$res = doSQL($sql);
		if ($res['res']) {
            addWSPMsg('noticemsg', returnIntLang('menutmp template created'));
        } else {
            addWSPMsg('errormsg', returnIntLang('menutmp template creation failed'));
        }
	endif;
elseif ($op=="save"):
	$sql = "UPDATE `templates_menu` SET `title` = '".escapeSQL($_POST['menu_title'])."', `describ` = '".escapeSQL($_POST['menu_describ'])."', `startlevel` = '".intval($_POST['menu_slevel'])."'";
	if ($_POST['type']=="code"):
		$sql.= ", `code` = '".escapeSQL($_POST['menu_code'])."' ";
	endif;
	$sql.= " WHERE `id` = ".intval($_POST['id']);
	$res = doSQL($sql);
    if ($res['res']) {
        addWSPMsg('noticemsg', returnIntLang('menutmp template properties saved'));
    } else {
        addWSPMsg('errormsg', returnIntLang('menutmp template property saving failed'));
    }
elseif ($op=="delete"):
	$menu_sql = "SELECT `guid` FROM `templates_menu` WHERE `id` = ".intval($id);
	$menu_res = doSQL($menu_sql);
	if ($menu_res['num']>0) {
        foreach ($menu_res['set'] AS $mresk => $mresv) {
			$template_sql = "SELECT * FROM `templates` WHERE `template` LIKE '".('%[%MENUVAR:'.trim($mresv['guid']).'%]%')."'";
			$template_res = doSQL($template_sql);
			if ($template_res['num']>0) {
				foreach ($template_res['set'] AS $tresk => $tresv) {
					$removefrom[] = '"'.$tresv['name'].'"';
                }
				// replace menuvar with empty value
                doSQL("UPDATE `templates` SET `template` = REPLACE( `template` , '" . ('[%MENUVAR:'.strtoupper(trim($mresv['guid'])).'%]') . "', '' ) WHERE `template` LIKE '%[%MENUVAR:".trim($mresv['guid'])."%]%'");
                // remove menuvar style < wsp6 
				doSQL("UPDATE `templates` SET `template` = REPLACE( `template` , '" . ('[%MENUVAR:'.strtoupper(trim($mresv['guid'])).'%]') . "', '' ) WHERE `template` LIKE '%[%MENUVAR ".trim($mresv['guid'])."%]%'");
				// markup menupoints using that template
				addWSPMsg('errormsg', "Das von Ihnen gel&ouml;schte Men&uuml; wurde aus ".(($template_res['num']==1)?"dem Template ".$removefrom[0]:"den Templates ".implode(", ", $removefrom))."entfernt.");
            }
        }
    }
	doSQL("DELETE FROM `templates_menu` WHERE `id` = ".intval($id));
	addWSPMsg('noticemsg', returnIntLang('menutmp template removed'));
endif;

// head der datei

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('menutmp headline'); ?></h1></fieldset>
	<?php
	
	$menu_sql = "SELECT * FROM `templates_menu` ORDER BY `title`";
	$menu_res = doSQL($menu_sql);
	if ($menu_res['num']<10 && $op!="edit"):
		$tmpshowopen = "open";
	endif;
	
	if (!($op=="edit")):
	?>
	<fieldset>
		<legend><?php echo returnIntLang('menutmp existingtemplates'); ?> <?php echo legendOpenerCloser('menuedit_templates'); ?></legend>
		<div id="menuedit_templates">
		<table class="tablelist">
		<tr>
			<td class="tablecell two head"><?php echo returnIntLang('menutmp templatename'); ?></td>
			<td class="tablecell two head"><?php echo returnIntLang('menutmp templatetype'); ?></td>
			<td class="tablecell two head"><?php echo returnIntLang('str usage'); ?></td>
			<td class="tablecell two head"><?php echo returnIntLang('str startlevel'); ?></td>
		</tr>
		<?php 
		
		foreach ($menu_res['set'] AS $mresk => $mresv):
			echo "<tr>";
			echo "<td class='tablecell two'><a style=\"cursor: pointer;\" onclick=\"document.getElementById('editform".intval($mresv['id'])."').submit();\">".((trim($mresv['title'])!='')?setUTF8(trim($mresv['title'])):'<em>no title</em>')."</a></td>";
			echo "<td class='tablecell two'>";
			if (trim($mresv['parser'])!=""):
				echo returnIntLang('menutmp parserfile');
			elseif (trim($mresv['code'])!=""):
				echo returnIntLang('menutmp cmcode');
			else:
				echo returnIntLang('menutmp emptycode');
			endif;
			echo "</td>";
			echo "<td class='tablecell two'>";
			
			$usage_sql = "SELECT `name` FROM `templates` WHERE `template` LIKE '%".trim($mresv['guid'])."%'";
			$usage_res = doSQL($usage_sql);
			if ($usage_res['num']>0):
				foreach ($usage_res['set'] AS $uresk => $uresv):
					echo returnIntLang('menutmp template')." ".trim($uresv['name'])."<br />";
				endforeach;
			else:
				echo "-";
			endif;
			
			echo "</td>";
			echo "<td class='tablecell two'>".intval($mresv['startlevel'])." <form id=\"editform".intval($mresv['id'])."\" name=\"editform".intval($mresv['id'])."\" method=\"post\" style=\"margin: 0px;\"><input type=\"hidden\" name=\"op\" value=\"edit\"><input type=\"hidden\" name=\"id\" value=\"".intval($mresv['id'])."\"></form></td>";
			echo "</tr>";
		endforeach;
		
		?>
		</table>
		</div>
	</fieldset>
	<?php 
	endif;
	
	$editmenu_sql = "SELECT * FROM `templates_menu` WHERE `id` = ".(isset($_POST['id']) ? intval($_POST['id']) : 0);
	$editmenu_res = doSQL($editmenu_sql);
	if ($editmenu_res['num']>0):
		$title = prepareTextField(stripslashes(trim($editmenu_res['set'][0]['title'])));
		$desc = prepareTextField(stripslashes(trim($editmenu_res['set'][0]['describ'])));
		$slevel = intval($editmenu_res['set'][0]['startlevel']);
		if (trim($editmenu_res['set'][0]['parser'])!=""):
			$type = "parser";
			$code = trimg($editmenu_res['set'][0]['parser']);
			$jobkind = "save";
		else:
			$code = trim($editmenu_res['set'][0]['code']);
			$type = "code";
			$jobkind = "save";
		endif;
	else:
		$title = "Menütitel";
		$desc = "Kurze Beschreibung";
		$slevel = 1;
		$code = "";
		$type = "code";
		$jobkind = "new";
	endif;
		
	if ($op=="edit" || $op=="save"):
		$_SESSION['opentabs']['menuedit_editor'] = 'display: block;';
		?>
		<form id="menutemplateform" name="menutemplateform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<fieldset id="menuedit">
			<legend><?php echo returnIntLang('menutmp editmenu'); ?> <?php echo legendOpenerCloser('menuedit_editor'); ?></legend>
			<div id="menuedit_editor">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('menutmp menuname'); ?></td>
					<td class="tablecell two"><input name="menu_title" id="menu_title" type="text" value="<?php echo $title; ?>" class="one full" /></td>
					<td class="tablecell two"><?php echo returnIntLang('menutmp startlevel'); ?></td>
					<td class="tablecell two"><input name="menu_slevel" id="menu_slevel" type="text" value="<?php echo $slevel; ?>" maxlength="2" class="one full" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('menutmp menudesc'); ?></td>
					<td class="tablecell six"><input name="menu_describ" id="menu_describ" type="text" value="<?php echo $desc; ?>" class="three full" /></td>
				</tr>
				<?php if ($type!="parser"): ?>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('menutmp sourcecode'); ?></td>
						<td class="tablecell six"><textarea name="menu_code" id="menu_code" rows="15" cols="50" class="three full"><?php echo stripslashes(stripslashes(stripslashes($code))); ?></textarea></td>
					</tr>
				<?php else: ?>
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('menutmp sourcefile'); ?></td>
						<td class="tablecell six"><input name="parser" id="parser" type="text" value="<?php echo $code; ?>" readonly="readonly" disabled="disabled" style="width: 98%;" /></td>
					</tr>
				<?php endif; ?>
			</table>
			<fieldset class="options innerfieldset">
				<p><input name="op" id="op" type="hidden" value="<?php echo $jobkind; ?>" /><input name="type" id="type" type="hidden" value="<?php echo $type; ?>" /><input name="id" id="id" type="hidden" value="<?php echo $id; ?>" /><a href="#" class="greenfield" onClick="document.getElementById('menutemplateform').submit();"><?php echo returnIntLang('str save', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a> <?php if ($id>0): ?><a href="#" onclick="if (confirm('Soll das Men' + String.fromCharCode(252) + 'template ' + String.fromCharCode(34) + '<?php echo $title; ?>' + String.fromCharCode(34) + ' wirklich gel' + String.fromCharCode(246) + 'scht werden? Die Men' + String.fromCharCode(252) + 'eintr' + String.fromCharCode(228) + 'ge werden automatisch aus den Templates entfernt, in denen sie verwendet werden.')) { document.getElementById('deletemenu').submit(); }" class="redfield"><?php echo returnIntLang('str delete', false); ?></a><?php endif; ?></p>
			</fieldset>
			</div>
		</fieldset>
		</form>
		<form id="deletemenu" name="deletemenu" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<input name="op" id="delop" type="hidden" value="delete" /><input name="id" id="delid" type="hidden" value="<?php echo $id; ?>" />
		</form>
		<?php if ($type!="parser"): ?>
		<script type="text/javascript">
		// <![CDATA[
		function generateCode () {
			var generateValue = "LEVEL {\n";
			var generateType = document.getElementById('menu_TYPE').value;
			
			generateValue = generateValue + "	TYPE = '" + document.getElementById('menu_TYPE').value + "'\n";
			
			if (generateType == 'LINK' && document.getElementById('menu_SPACER').value != '') {
				generateValue = generateValue + "	SPACER = '" + document.getElementById('menu_SPACER').value + "'\n";
				}
			
			if (generateType == 'LIST') {
				if (document.getElementById('menu_CONTAINER.CLASS').value != '') {
					generateValue = generateValue + "	CONTAINER.CLASS = '" + document.getElementById('menu_CONTAINER.CLASS').value + "'\n";
					}
				}
			
			if (generateType == 'LIST' || generateType == 'LINK') {
				if (document.getElementById('menu_DELIMITER.CLASS').value != '') {
					generateValue = generateValue + "	DELIMITER.CLASS = '" + document.getElementById('menu_DELIMITER.CLASS').value + "'\n";
					}
				if (document.getElementById('menu_LINK.CLASS').value != '') {
					generateValue = generateValue + "	LINK.CLASS = '" + document.getElementById('menu_LINK.CLASS').value + "'\n";
					}
				}

			if (document.getElementById('showMenuPos').value!='') {
				generateValue = generateValue + "	MENU.SHOW = '" + document.getElementById('showMenuPos').value + "'\n";
				}
				
			if (document.getElementById('hideMenuPos').value!='') {
				generateValue = generateValue + "	MENU.HIDE = '" + document.getElementById('hideMenuPos').value + "'\n";
				}

			generateValue = generateValue + "	}";
			
			document.getElementById('showcode').style.display = 'block';
			document.getElementById('generatedcode').value = generateValue;
			}
		
		function addOption(theSel, theText, theValue) {
			var newOpt = new Option(theText, theValue);
			var selLength = theSel.length;
			theSel.options[selLength] = newOpt;
			}
		
		function deleteOption(theSel, theIndex) {
			var selLength = theSel.length;
			if(selLength>0) {
				theSel.options[theIndex] = null;
				}
			}
		
		function moveOptions(theSelFrom, theSelTo) {
			var NS4 = (navigator.appName == "Netscape" && parseInt(navigator.appVersion) < 5);
			var selLength = theSelFrom.length;
			var selectedText = new Array();
			var selectedValues = new Array();
			var selectedCount = 0;
			var i;
  			// Find the selected Options in reverse order
  			// and delete them from the 'from' Select.
			for(i=selLength-1; i>=0; i--) {
				if(theSelFrom.options[i].selected) {
					selectedText[selectedCount] = theSelFrom.options[i].text;
					selectedValues[selectedCount] = theSelFrom.options[i].value;
					deleteOption(theSelFrom, i);
					selectedCount++;
				}
			}
			// Add the selected text/values in reverse order.
			// This will add the Options to the 'to' Select
			// in the same order as they were in the 'from' Select.
			for(i=selectedCount-1; i>=0; i--) {
				addOption(theSelTo, selectedText[i], selectedValues[i]);
			}
			if(NS4) history.go(0);
			}
		
		function moveUp(menuvalue) {
			if (menuvalue>0) {
				tempvalue = document.getElementById('selectMenuPos').options[menuvalue].value;
				temptext = document.getElementById('selectMenuPos').options[menuvalue].text;
				document.getElementById('selectMenuPos').options[menuvalue].value = document.getElementById('selectMenuPos').options[menuvalue-1].value;
				document.getElementById('selectMenuPos').options[menuvalue].text = document.getElementById('selectMenuPos').options[menuvalue-1].text;
				document.getElementById('selectMenuPos').options[menuvalue-1].value = tempvalue;
				document.getElementById('selectMenuPos').options[menuvalue-1].text = temptext;
				document.getElementById('selectMenuPos').options[menuvalue-1].selected = true;
				}
			}
		
		function moveDown(menuvalue) {
			if (menuvalue<document.getElementById('selectMenuPos').length && menuvalue!=-1) {
				tempvalue = document.getElementById('selectMenuPos').options[menuvalue].value;
				temptext = document.getElementById('selectMenuPos').options[menuvalue].text;
				document.getElementById('selectMenuPos').options[menuvalue].value = document.getElementById('selectMenuPos').options[menuvalue+1].value;
				document.getElementById('selectMenuPos').options[menuvalue].text = document.getElementById('selectMenuPos').options[menuvalue+1].text;
				document.getElementById('selectMenuPos').options[menuvalue+1].value = tempvalue;
				document.getElementById('selectMenuPos').options[menuvalue+1].text = temptext;
				document.getElementById('selectMenuPos').options[menuvalue+1].selected = true;
				}
			}

		// ]]>
		</script>
		
		<fieldset id="code">
			<legend><?php echo returnIntLang('menutmp codebuilder'); ?> <?php echo legendOpenerCloser('codebuilder'); ?></legend>
			<div id="codebuilder">
			<script language="JavaScript" type="text/javascript">
			<!--
			function showhidespacer(val) {
				document.getElementById('spacer').style.display = 'none';
				document.getElementById('container').style.display = 'none';
				document.getElementById('containerclass').style.display = 'none';
				document.getElementById('containerdisplay').style.display = 'none';
				document.getElementById('delimiter').style.display = 'none';
				document.getElementById('delimiterclass').style.display = 'none';
				document.getElementById('delimiteractive').style.display = 'none';
				document.getElementById('delimiterinactive').style.display = 'none';
				document.getElementById('link').style.display = 'none';
				if (val=='LIST') {
					document.getElementById('container').style.display = 'block';
					document.getElementById('containerclass').style.display = 'block';
					document.getElementById('containerdisplay').style.display = 'block';
					document.getElementById('delimiter').style.display = 'block';
					document.getElementById('delimiterclass').style.display = 'block';
					document.getElementById('delimiteractive').style.display = 'block';
					document.getElementById('delimiterinactive').style.display = 'block';
					document.getElementById('link').style.display = 'block';
					}
				if (val=='DIV') {
					document.getElementById('spacer').style.display = 'block';
					document.getElementById('container').style.display = 'block';
					document.getElementById('containerclass').style.display = 'block';
					document.getElementById('containerdisplay').style.display = 'block';
					document.getElementById('delimiter').style.display = 'block';
					document.getElementById('delimiterclass').style.display = 'block';
					document.getElementById('delimiteractive').style.display = 'block';
					document.getElementById('delimiterinactive').style.display = 'block';
					document.getElementById('link').style.display = 'block';
					}
				if (val=='SELECT') {
					document.getElementById('container').style.display = 'block';
					document.getElementById('containerclass').style.display = 'block';
					}
				}
			// -->
			</script>
			<form id="selectMenuForm" name="selectMenuForm" method="post">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('menutmp code type'); ?></td>
					<td class="tablecell six"><select name="TYPE" id="menu_TYPE" size="1" onchange="showhidespacer(this.value)">
						<option value="LIST"><?php echo returnIntLang('menutmp code type listsub', false); ?> [LIST]</option>
						<option value="LINK"><?php echo returnIntLang('menutmp code type link'); ?> [LINK]</option>
						<option value="SELECT"><?php echo returnIntLang('menutmp code type select'); ?> [SELECT]</option>
					</select></td>
				</tr>
			</table>
			<div id="containerclass" style="display: _none;">
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('menutmp code containerclass'); ?></td>
						<td class="tablecell three"><input type="text" name="CONTAINER.CLASS" id="menu_CONTAINER.CLASS" value="" style="width: 95%;"></td>
						<td class="tablecell three"><?php echo returnIntLang('menutmp code containerclass desc'); ?></td>
					</tr>
				</table>
			</div>
			<div id="containerdisplay" style="display: _none;">
				<table class="tablelist">
					<tr>
						<td class="tablecell two">CONTAINER.DISPLAY</td>
						<td class="tablecell three"><select name="CONTAINER.DISPLAY" id="menu_CONTAINER.DISPLAY" size="1">
							<option value="inline">'inline'</option>
							<option value="block">'block'</option>
							<option value="inline-block">'inline-block'</option>
							<option value="table-cell">'table cell'</option>
							<option value="none">Ausblenden</option>
						</select></td>
						<td class="tablecell three"><p>Ausblenden der Liste bei Inaktivit&auml;t (nur bei TYPE 'LIST' & TYPE 'LISTIMAGE')</p></td>
					</tr>
				</table>
			</div>
			<div id="delimiterclass" style="display: _none;">
				<table class="tablelist">
					<tr>
						<td class="tablecell two">DELIMITER.CLASS</td>
						<td class="tablecell six"><input type="text" name="DELIMITER.CLASS" id="menu_DELIMITER.CLASS" value="" style="width: 95%;" placeholder="Klasse der den Link umschließenden Elemente (nur bei TYPE 'LIST' & TYPE 'LINK')" /></td>
					</tr>
				</table>
			</div>
			<div id="link" style="display: _none;">
				<table class="tablelist">
					<tr>
						<td class="tablecell two">LINK.CLASS</td>
						<td class="tablecell six"><input type="text" name="LINK.CLASS" id="menu_LINK.CLASS" value="" style="width: 95%;" placeholder="Klasse für &lt;a&gt;-Element" /></td>
					</tr>
				</table>
			</div>
			<div id="spacer" style="display: _none;">
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('menutmp code spacer'); ?></td>
						<td class="tablecell three"><input type="text" name="SPACER" id="menu_SPACER" value=""></td>
						<td class="tablecell three"><?php echo returnIntLang('menutmp code spacer desc'); ?></td>
					</tr>
				</table>
			</div>
			<?php $menupoints = subpMenu(0); ?>
			<style>
			
			#menushow-options { list-style-type: none; }
			#menushow-data { height: 100%; }
			#menushow-options li, #menushow-data li { border: 1px solid #354E65; float: left; margin-right: 3px; margin-bottom: 3px; display: block; padding: 3px; white-space: nowrap; }
			#menushow-options li .remover { display: none; }
			#menushow-data li .remover { display: inline; cursor: pointer; }
			
			#menushow-options li .handle, #menushow-data li .handle { cursor: move; }
			
			</style>
			<table class="tablelist">
				<tr>
					<td class="tablecell two">MENU.SHOW</td>
					<td class="tablecell three"><div style="width: 100%; max-height: 20em; overflow: hidden; overflow-y: auto;"><ul id="menushow-options" class="menushow"><?php foreach ($menupoints AS $mk => $mv): 					
					$m_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($mv);
					$m_res = doSQL($m_sql);
					if ($m_res['num']>0): 
						echo "<li class='' rel='".intval($mv)."'><span class='remover' onclick='$(this).parent(\"li\").hide();$(this).parent(\"li\").remove();setupShown();'>✖ </span><span class='handle'>";
						foreach ($m_res['set'] AS $mrsk => $mrsv): echo ". "; endforeach;
						echo trim($m_res['set'][0]['description']);
						echo "</span></li>"; 
					endif; endforeach; ?></ul></div></td>
					<td class="tablecell three" valign="top"><div style="width: 100%; height: 100%; overflow: hidden; overflow-y: auto;"><ul id="menushow-data" class="menushow"></ul></td>
				</tr>
				<tr>
					<td class="tablecell eight">W&auml;hlen sie hier bei Bedarf die Men&uuml;punkte aus, die im Men&uuml; dargestellt werden sollen, wenn sich die Darstellung nicht nach der allgemeinen Struktur richten soll. Verschieben und Sortierung per Drag & Drop</td>
				</tr>
			</table>
			<input type="hidden" id="showMenuPos" value="" />
			<script type="text/javascript">

			function setupShown() {
				var shownID = "";
				$("#menushow-data li").each(function(h){
					shownID+= $(this).attr('rel')+";";
					});
				$("#showMenuPos").attr('value',shownID);
				}
			
			$(document).ready(function() {      
				
				$( "#menushow-options" ).sortable({
      				connectWith: ".menushow",
      				handle: ".handle",
      				start: function(event, ui) {
      					$(ui.item).show();
      					},
      				remove: function(event, ui) {
						ui.item.clone().appendTo('#menushow-data');
						$(this).sortable('cancel');
						}
    				}).disableSelection();
				
				$( "#menushow-data").sortable({
      				handle: ".handle",
					receive: function(event, ui) {
						setupShown();
						},
					update: function(event, ui) {
						setupShown();
						}
      				}).disableSelection();
				
				});
					
			</script>
			<style>
			
			#menuhide-options { list-style-type: none; }
			#menuhide-options li { border: 1px solid #354E65; float: left; margin-right: 3px; margin-bottom: 3px; display: block; padding: 3px; cursor: pointer; white-space: nowrap; }
			#menuhide-options li.hidden { border-color: #952320; opacity: 0.2; }
			
			</style>
			<table class="tablelist">
				<tr>
					<td class="tablecell two">MENU.HIDE</td>
					<td class="tablecell six" valign="top"><div style="width: 100%; max-height: 20em; overflow: hidden; overflow-y: auto;"><ul id="menuhide-options" class=""><?php foreach ($menupoints AS $mk => $mv): 					
					$m_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($mv);
					$m_res = doSQL($m_sql);
					if ($m_res['num']>0): 
						echo "<li class='deselect' rel='".intval($mv)."'>";
						foreach ($m_res['set'] AS $mrsk => $mrsv): echo ". "; endforeach;
						echo trim($m_res['set'][0]['description']);
						echo "</li>"; 
					endif; endforeach; ?></ul></div></td>
				</tr>
				<tr>
					<td class="tablecell eight">W&auml;hlen sie hier bei Bedarf die Men&uuml;punkte aus, die im Men&uuml; nicht dargestellt werden sollen. Sie können die Menüpunkte entsprechend per Klick markieren.</td>
				</tr>
			</table>
			<input type="hidden" id="hideMenuPos" value="" />
			<script type="text/javascript">

			function setupHidden() {
				var hiddenID = "";
				$("#menuhide-options li").each(function(h){
					if ($(this).hasClass('hidden')) {
						hiddenID+= $(this).attr('rel')+";";
						}
					});
				$("#hideMenuPos").attr('value',hiddenID);
				}

			$(document).ready(function() {      
				$("li.deselect").click(function(){
					$(this).toggleClass('hidden');
					setupHidden();
					});
			
				});
					
			</script>
			<fieldset class="options innerfieldset">
				<p><a href="#showgencode" class="greenfield" onClick="generateCode();">Generate</a></p>
			</fieldset>
			<span id="showcode" style="display: none; margin-top: 8px;">
			<table class="tablelist">
				<tr>
					<td class="tablecell two">Ihr Men&uuml;code</td>
					<td class="tablecell six"><textarea name="generatedcode" id="generatedcode" rows="10" cols="15" class="three full"></textarea><a name="showgencode"></a></td>
				</tr>
			</table>
			</span>
			</form>
			</div>
		</fieldset>
		<?php endif; ?>
	<?php endif; ?>
	<?php if($op!='edit'): ?>
	<fieldset class="options">
		<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?op=edit&id=0" title="Neues Men&uuml;template anlegen" class="greenfield"><?php echo returnIntLang('menutmp createnewtemplate', false); ?></a></p>
	</fieldset>
	<?php endif; ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->