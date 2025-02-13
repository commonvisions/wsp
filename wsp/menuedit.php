<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2023-01-09
 * fixed overwriting errormsg on creation failure
 * fixed error when duplicating contents
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
$mid = checkParamVar('mid', '');
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'sitestructure'; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['mgroup'] = 5; // aktive menuegruppe
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF']; // fuer den eintrag im logfile sowie die entsprechende ueberpruefung der fposcheck
$_SESSION['wspvars']['fposcheck'] = false; // bestimmt, ob ein bereich fuer andere benutzer gesperrt wird (true), wenn sich hier schon ein benutzer befindet, oder nicht (false)
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
$worklang = unserialize($_SESSION['wspvars']['sitelanguages']);
/* page specific funcs and actions */

if (isset($_POST['op']) && $_POST['op']=='new' && isset($_POST['newmenuitem']) && trim($_POST['newmenuitem'])!=""):
	// create new menupoint
	$newfilename = strtolower(removeSpecialChar(trim($_POST['newmenuitem'])));
	// check to prevent double filenames
	$namecheck_sql = "SELECT `filename` FROM `menu` WHERE `connected` = ".intval($_POST['subpointfrom'])." AND `filename` = '".trim($newfilename)."' AND `trash` = 0";
	$namecheck_res = doSQL($namecheck_sql);
	if ($namecheck_res['num']>0): $newfilename = $newfilename."_".time(); endif;
	
    // check for existing index-file - else, set THIS new file to index 
	$indexcheck_sql = "SELECT `isindex` FROM `menu` WHERE `connected` = ".intval($_POST['subpointfrom'])." AND `isindex` = 1";
	$indexcheck_res = doSQL($indexcheck_sql);
	$newindex = false; if ($indexcheck_res['num']==0): $newindex = true; endif;
	// unset index page if defined in editor prefs
	if ($_SESSION['wspvars']['noautoindex']==1): $newindex = false; endif;
	// check for new level
	$levelcheck_sql = "SELECT `level` FROM `menu` WHERE `mid` = ".intval($_POST['subpointfrom']);
	$levelcheck_res = doResultSQL($levelcheck_sql);
	$newlevel = 1; if ($levelcheck_res!==false): $newlevel = intval($levelcheck_res)+1; endif;
	// check for new position
	$poscheck_sql = "SELECT `position` FROM `menu` WHERE `connected` = ".intval($_POST['subpointfrom'])." ORDER BY `position` DESC LIMIT 0,1" ;
	$poscheck_res = doSQL($poscheck_sql);
	$newpos = 1; if ($poscheck_res['num']>0): $newpos = intval($poscheck_res['set'][0]['position'])+1; endif;
	$sql = "INSERT INTO `menu` SET 
		`level` = ".intval($newlevel).", 
		`connected` = ".intval($_POST['subpointfrom']).", 
		`editable` = 1, 
		`breaktree` = 0,
		`position` = ".intval($newpos).",
		`visibility` = 1,
		`description` = '".escapeSQL(trim($_POST['newmenuitem']))."',
		`filename` = '".escapeSQL($newfilename)."',
		`templates_id` = ".intval($_POST['template']).",
		`contentchanged` = 4,
		`changetime` = ".time().",
		`isindex` = ".intval($newindex);
	$sqlsuccess = doSQL($sql);
	if ($sqlsuccess['res']):
		$_SESSION['wspvars']['resultmsg'] = "<p>".returnIntLang('structure new menupoint created', true)."</p>";
		$_SESSION['wspvars']['editmenuid'] = intval($sqlsuccess['inf']);
		if ($_SESSION['wspvars']['editmenuid']>0):
			$_SESSION['opentabs']['structure_basics'] = 'display: block;';
			header('location: menueditdetails.php');
			die();
		endif;
	else:
		addWSPMsg('errormsg', "<p>".returnIntLang('structure error creating new menupoint', true)."</p>");
	endif;
elseif (isset($_POST['op']) && $_POST['op']=='new' && trim($_POST['newmenuitemlist'])!=""):
	// create new list of menupoints
	$newmplist = array();
	$newmplisttmp = explode("<br />", nl2br($_POST['newmenuitemlist']));
	if (is_array($newmplisttmp)):
		foreach ($newmplisttmp AS $nk => $nv):
			if (trim($nv)!='') $newmplist[] = trim($nv);
		endforeach;
	endif;
	if (count($newmplist)>0):
		
        // check for index pages
        $indexcheck_sql = "SELECT `isindex` FROM `menu` WHERE `connected` = ".intval($_POST['subpointfrom'])." AND `isindex` = 1";
		$indexcheck_res = doSQL($indexcheck_sql);
		$newindex = false; if ($indexcheck_res['num']==0): $newindex = true; endif;
		// unset index page if defined in editor prefs
		if ($_SESSION['wspvars']['noautoindex']==1): $newindex = false; endif;
		
        // check for new level
		$levelcheck_sql = "SELECT `level` FROM `menu` WHERE `mid` = ".intval($_POST['subpointfrom']);
		$levelcheck_res = doResultSQL($levelcheck_sql);
		$newlevel = 1; if ($levelcheck_res!==false): $newlevel = intval($levelcheck_res)+1; endif;

        // check for new position
		$poscheck_sql = "SELECT `position` FROM `menu` WHERE `connected` = ".intval($_POST['subpointfrom'])." ORDER BY `position` DESC LIMIT 0,1" ;
		$poscheck_res = doSQL($poscheck_sql);
		$newpos = 1; if ($poscheck_res['num']>0): $newpos = intval($poscheck_res['set'][0]['position'])+1; endif;
		
        $createlist = true;
		foreach($newmplist AS $npk => $npv):
			$newfilename = strtolower(removeSpecialChar(trim($npv)));
			// check to prevent double filenames
			$namecheck_sql = "SELECT `filename` FROM `menu` WHERE `connected` = ".intval($_POST['subpointfrom'])." AND `filename` = '".trim($newfilename)."'";
			$namecheck_res = doSQL($namecheck_sql);
			if ($namecheck_res['num']>0): $newfilename = $newfilename."_".time(); endif;
			$sql = "INSERT INTO `menu` SET 
				`level` = ".intval($newlevel).", 
				`connected` = ".intval($_POST['subpointfrom']).", 
				`editable` = 1, 
				`breaktree` = 0,
				`position` = ".intval($newpos).",
				`visibility` = 1,
				`description` = '".escapeSQL(trim($npv))."',
				`filename` = '".escapeSQL($newfilename)."',
				`templates_id` = ".intval($_POST['template']).",
				`contentchanged` = 4,
				`changetime` = ".time().",
				`isindex` = ".intval($newindex);
            $res = doSQL($sql);
			if (!($res['res'])): $createlist = false; endif;
			$newindex = false;
			$newpos++;
		endforeach;
		if ($createlist):
            addWSPMsg('resultmsg', returnIntLang('structure success creating new menupointlist', true));
		else:
			addWSPMsg('errormsg', returnIntLang('structure error creating new menupointlist', true));
		endif;
	else:	
		addWSPMsg('errormsg', returnIntLang('structure error creating new menupointlist', true));
	endif;
elseif (isset($_POST['op']) && $_POST['op']=='clone' && intval($_POST['mid'])>0):
	// clone
	$mid = $_POST['mid'];
	// get the new mid
	$sql = "SELECT MAX(`mid`) FROM `menu`";
	$res = doResultSQL($sql);
	$_SESSION['clone']['maxmid'] = intval($res);
	// read rekursiv the menupoints
	function subMenu($mid) {
		$connected_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($mid);
		$connected_res = doSQL($connected_sql);
		if ($connected_res['num']> 0):
            foreach ($connected_res['set'] AS $crsk => $crsv) {
				$_SESSION['clone']['clonemid'][$crsv['mid']] = ++$_SESSION['clone']['maxmid'];
				$_SESSION['clone']['clonemids'].= ','.$crsv['mid'];
				subMenu($crsv['mid']);
            }
		else:
			$_SESSION['clone']['clonemid'][0] = 0;
		endif;
	} //subMenu();
	$_SESSION['clone']['clonemid'][$mid] = ++$_SESSION['clone']['maxmid'];
	$_SESSION['clone']['clonemids'] = $mid;
	subMenu($mid);

	// read all menupoint to be cloned
	$sql = "SELECT * FROM `menu` WHERE `mid` IN(".$_SESSION['clone']['clonemids'].")";
	$res = doSQL($sql);
    foreach ($res['set'] AS $crk => $crv) { 
		// the menupoint who will clone get an news position
		if ($crv['mid'] == $mid):
			$pos_sql = 'SELECT MAX(`position`) AS `pos` FROM `menu` WHERE `connected` = '.intval($crv['connected']);
			$pos_res = doResultSQL($pos_sql);
			$crv['position'] = (intval($pos_res)+1);
			$midclone = $_SESSION['clone']['clonemid'][$crv['mid']];
			$crv['description'].= ' Copy';
			
			$filename_sql = "SELECT `mid` FROM `menu` WHERE `filename` LIKE '".$crv['filename']."-".date("Ymd")."%' ORDER BY LENGTH(`filename`) DESC";
			$filename_res = doSQL($filename_sql);
			if ($filename_res['num']>0):
				$crv['filename'].= '-'.date("Ymd").'-'.$filename_res['num'];
			else:
				$crv['filename'].= '-'.date("Ymd");
			endif;
		endif;
		// get the new mid
		$crv['mid'] = $_SESSION['clone']['clonemid'][$crv['mid']];
		// set menupoints changed
		$crv['contentchanged'] = 1;
		// get the new connected, when ist over null
		if (isset($_SESSION['clone']['clonemid'][$crv['connected']])):
			$crv['connected'] = $_SESSION['clone']['clonemid'][$crv['connected']];
		endif;
		//generate the sql-querys for clone menupoints
		$query = array();
		foreach ($crv as $key => $value):
			if ($key!='isindex'):
				$query[] = " `".$key."` = '".$value."' ";
			endif;
		endforeach;
 		$sql = "INSERT INTO `menu` SET ".implode(", ", $query);
		$res = doSQL($sql);
        if (!($res['res'])) {
            addWSPMsg('errormsg', 'menuedit error duplicating menupoints');
        }
	}
		
	// get content !!!
	$sql = "SELECT * FROM `content` WHERE `mid` IN(".$_SESSION['clone']['clonemids'].")";
	$res = doSQL($sql);
    foreach ($res['set'] AS $crk => $crv) { 
		$query = '';
		unset($crv['cid']);
		$crv['mid'] = $_SESSION['clone']['clonemid'][$crv['mid']];
		foreach ($crv as $key => $value):
			if($key=="valuefields"):
				$scount = 0;
				while(($scount<5) AND (!is_array(unserializeBroken($value)))):
					$value = stripslashes($value);
					$scount++;
				endwhile;
				$value = escapeSQL($value);
			endif;
			if (is_numeric($value) || empty($value)) {
				$query.= "`".$key."` = " . intval($value) . ",";
			} else {
				$query.= "`".$key."` = '" . $value . "',";
			}
		endforeach;
		$sql = "INSERT INTO `content` SET ".substr($query,0,-1);
		$res = doSQL($sql);
        if (!($res['res'])) {
			addWSPMsg('errormsg', var_export($sql, true));
            addWSPMsg('errormsg', 'menuedit error adding contents to duplicated menupoints');
        }
    }
	
	if ($_SESSION['wspvars']['usertype']!=1):
		$rights_sql = "SELECT `rights`, `idrights` FROM `restrictions` WHERE `rid` = ".intval($_SESSION['wspvars']['userid']);
		$rights_res = doSQL($rights_sql);
		if ($rights_res['num']>0):
			$rights = unserializeBroken(trim($rights_res['set'][0]['rights']));
			$idrights = unserializeBroken(trim($rights_res['set'][0]['idrights']));
			if (isset($rights['contents']) && $rights['contents']>1):
				if (isset($idrights['contents']) && is_array($idrights['contents'])):
					array_merge($idrights['contents'],explode(",",$_SESSION['clone']['clonemids']));
				else:
					$idrights['contents'] = explode(",",$_SESSION['clone']['clonemids']);
				endif;
			endif;
			if (isset($rights['publisher']) && $rights['publisher']>1):
				if (isset($idrights['publisher']) && is_array($idrights['publisher'])):
					array_merge($idrights['publisher'],explode(",",$_SESSION['clone']['clonemids']));
				else:
					$idrights['publisher'] = explode(",",$_SESSION['clone']['clonemids']);
				endif;
			endif;
			doSQL("UPDATE `restrictions` SET `idrights` = '".escapeSQL(serialize($idrights))."' WHERE `rid` = ".intval($_SESSION['wspvars']['userid']));
		endif;
	endif;
	$mid = $midclone;
endif;

// get information about opened structure
if (isset($_POST['openmid']) && intval($_POST['openmid']) > 0):
	returnReverseStructure(intval($_POST['openmid']));
	$openpath = $midpath[0];
elseif (isset($_SESSION['pathmid']) && intval($_SESSION['pathmid']) > 0):
	returnReverseStructure(intval($_SESSION['pathmid']));
	$openpath = $midpath[1];
elseif (isset($_SESSION['opencontent']) && intval($_SESSION['opencontent']) > 0):
	$oc_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".$_SESSION['opencontent'];
	$oc_res = doResultSQL($oc_sql);
	if ($oc_res!==false):
		returnReverseStructure(intval($oc_res));
		$openpath = $midpath[1];
	endif;
else:
	$openpath = 0;
endif;

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

?>
<script language="JavaScript" type="text/javascript">
<!--

function menuTemp(mid) {
	if (temp) {
		document.getElementById('template').options[1].selected = true;
		temp = false;
		}
	}

function showSub(mid, showlevel) {
	if (mid>0) {
		$.post("xajax/ajax.showstructure.php", { 'mid': mid, 'showlevel': showlevel, 'type': 'structure'}).done (function(data) {
			$("#ul_" + mid).html(data);
			$("#ul_" + mid).toggle('blind', 500, function() {
				$("#ul_" + mid).addClass('open');
				$( ".sortable" ).sortable({
					connectWith: ".sortable",
					handle: '.handle',
					receive: function( event, ui ) {
	      				moveItem(ui.item.attr('id'), ui.sender.attr('id'), ui.item.parent('ul').attr('id'), ui.item.parent('ul').sortable('serialize'));
	      				},
	      			update: function( event, ui ) {
	      				updateItem (ui.item.attr('id'), ui.item.parent('ul').sortable('serialize'));
	      				}
				    }).disableSelection();
				});
			passLiTable('ul.sortable', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
			})
		}
	}

function updateItem(movedMID, listOrder) {
	$.post("xajax/ajax.movestructure.php", { 'mid': movedMID, 'listorder': listOrder})
		.done (function(data) {
			passLiTable('ul.sortable', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
			});
	}

function moveItem(movedMID, movedFrom, movedTo, listOrder) {
	$.post("xajax/ajax.neststructure.php", { 'mid': movedMID, 'listorder': listOrder, 'selector': movedFrom, 'target': movedTo})
		.done (function(data) {
			passLiTable('ul.sortable', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
			});
	}

// -->
</script>

<div id="contentholder">
	<fieldset><?php 
	// block to define workspace language
	if ((array_key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']=="") || (!(array_key_exists('workspacelang', $_SESSION['wspvars'])))):
		$_SESSION['wspvars']['workspacelang'] = $worklang['languages']['shortcut'][0];
	endif;
	if (isset($_POST['workspacelang']) && $_POST['workspacelang']!=""):
		$_SESSION['wspvars']['workspacelang'] = $_POST['workspacelang'];
	endif;
	
	if (intval(count($worklang['languages']['shortcut']))>1):
			?>
			<form name="changeworkspacelang" id="changeworkspacelang" method="post" style="float: right;">
			<select name="workspacelang" onchange="document.getElementById('changeworkspacelang').submit();">
				<?php
				
				foreach ($worklang['languages']['shortcut'] AS $key => $value):
					echo "<option value=\"".$worklang['languages']['shortcut'][$key]."\" ";
					if ($_SESSION['wspvars']['workspacelang']==$worklang['languages']['shortcut'][$key]):
						echo " selected=\"selected\"";
					endif;
					echo ">".$worklang['languages']['longname'][$key]."</option>";
				endforeach;
				
				?>
			</select>
			</form>
			<?php
		endif;
		
	?><h1><?php echo returnIntLang('structure headline'); ?></h1></fieldset>
	<?php
	
	if (isset($_SESSION['opentabs']['legends_opener']) && $_SESSION['opentabs']['legends_opener']!=''):
		$_SESSION['opentabs']['legends_opener'] = $_SESSION['opentabs']['legends_opener'];
	else:
		$_SESSION['opentabs']['legends_opener'] = 'closed';
	endif;
	
	?>
	<fieldset>
		<legend><?php echo returnIntLang('str legend'); ?> <?php echo legendOpenerCloser('wsplegend', 'closed'); ?></legend>
		<div id="wsplegend" style="<?php echo $_SESSION['opentabs']['wsplegend']; ?>">
		<p><?php echo returnIntLang('structure legend1'); ?></p>
		<p><?php echo returnIntLang('structure legend2'); ?><?php if ($_SESSION['wspvars']['rights']['sitestructure']==1 || $_SESSION['wspvars']['rights']['sitestructure']==7): echo returnIntLang('structure legend3'); endif; ?>.</p>
		<?php if ($_SESSION['wspvars']['rights']['sitestructure']==1 || $_SESSION['wspvars']['rights']['sitestructure']==7): ?>
		<p><?php echo returnIntLang('structure legend4'); ?></p>
		<?php endif; ?>
		<p><?php echo returnIntLang('structure legend5'); ?></p>
		<p><?php echo returnIntLang('structure legend6'); ?></p>
		</div>
	</fieldset>
	
	<?php if ($_SESSION['wspvars']['rights']['sitestructure']==1 || $_SESSION['wspvars']['rights']['sitestructure']==7): ?>
	<script language="JavaScript" type="text/javascript">
	<!--
	
	function createNewMP() {
		if (document.getElementById('newmenuitem').value!='' || document.getElementById('newmenuitemlist').value!='') {
			document.getElementById('formnewmenuitem').submit();
			}
		else { 
			alert(unescape('<?php echo returnIntLang('structure please fill in menupoint or list of menupoints', false); ?>')); document.getElementById('newmenuitem').focus(); 
			}
		return false;
		}
	
	function showList() {
		$('.addlist').css('display', 'block');
		if ($(window).width()>480) {
			passLiTable('#createlist', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
			}
		}
	
	// -->
	</script>
	<fieldset id="newmenu">
		<legend><?php echo returnIntLang('structure createnewmenupoint'); ?> <?php echo legendOpenerCloser('createnewmenupoint', 'closed'); ?></legend>
		<div id="createnewmenupoint" style="<?php echo $_SESSION['opentabs']['createnewmenupoint']; ?>">
		<form action="menuedit.php" method="post" id="formnewmenuitem" enctype="multipart/form-data">
		<ul class="tablelist" id="createlist">
			<li class="tablecell two"><?php echo returnIntLang('structure newmenupointname'); ?></li>
			<li class="tablecell two"><input type="text" name="newmenuitem" id="newmenuitem" value="" maxlength="150" class="one full" /></li>
			<li class="tablecell four">&nbsp;<a onclick="showList();"><span class="bubblemessage"><?php echo returnIntLang('structure bubble menupointlist'); ?></span></a></li>
			<li class="tablecell two addlist" style="display: none;"><?php echo returnIntLang('structure newmenupointlist'); ?></li>
			<li class="tablecell six addlist" style="display: none;"><textarea name="newmenuitemlist" id="newmenuitemlist" size="40" rows="6" class="three full"></textarea></li>
			<li class="tablecell two"><?php echo returnIntLang('structure subfrom'); ?></li>
			<li class="tablecell six"><select id="subpointfrom" name="subpointfrom" size="1" class="three full" onchange="menuTemp(document.getElementById('template').selectedIndex)">
				<?php if ($_SESSION['wspvars']['rights']['sitestructure']==1): ?>
					<option value="0"><?php echo returnIntLang('structure mainmenu'); ?></option>
					<?php getMenuLevel(0, 0, 1); ?>
				<?php elseif ($_SESSION['wspvars']['rights']['sitestructure']==7 && intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])>0): ?>
					<option value="<?php echo intval($_SESSION['wspvars']['rights']['sitestructure_array'][0]); ?>"><?php 
					
					$mpname_sql = "SELECT `description`, `level` FROM `menu` WHERE `mid` = ".intval($_SESSION['wspvars']['rights']['sitestructure_array'][0])." ORDER BY `level`, `position`";
					$mpname_res = doSQL($mpname_sql);
					if ($mpname_res['num']>0):
						echo trim($mpname_res['set'][0]['description']); 
						?></option>
						<?php getMenuLevel($_SESSION['wspvars']['rights']['sitestructure_array'][0], (intval($mpname_res['set'][0]['level'])*3), 1);
					endif;
				endif;?>
			</select></li>
			<li class="tablecell two"><?php echo returnIntLang('structure templatename'); ?></li>
			<li class="tablecell six"><select name="template" id="template" class="three full">
				<option value="-1"><?php echo returnIntLang('structure pleasechoosetemplate', false); ?></option>
				<option value="0" selected="selected"><?php echo returnIntLang('structure chooseuppertemplate', false); ?></option>
				<?php

				$template_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'templates_id'";
				$template_res = intval(doResultSQL($template_sql));
				
				$template_sql = "SELECT `id`, `name` FROM `templates` ORDER BY `name`";
				$template_res = doSQL($template_sql);
                if ($template_res['num']>0) {
                    foreach ($template_res['set'] AS $rsk => $row) {
                        if (isset($defaultTmpl) && $row['id']==$defaultTmpl) {
                            echo "<option value=\"".$row['id']."\" selected=\"selected\">".$row['name']." [".returnIntLang('structure standardtemplate', false)."]</option>\n";
						} else {
                            echo "<option value=\"".$row['id']."\">".$row['name']."</option>\n";
                        }
                    }
                }
                
				?>
			</select></li>
		</ul>
		<input type="hidden" name="mid" value="0" /><input type="hidden" name="op" value="new" />
		<fieldset class="options innerfieldset">
			<p><a href="#" title="Erstellen" onclick="createNewMP();" class="greenfield"><?php echo returnIntLang('str create', false); ?></a></p>
		</fieldset>
		</form>
		</div>
	</fieldset>
	<?php
	
	endif;
	
	$cleanstructure_sql = "SELECT `mid`, `filename`, `connected`, `level`, `position` FROM `menu` WHERE `trash` = 0 ORDER BY `level`, `position` ASC";
	$cleanstructure_res = doSQL($cleanstructure_sql);
    if ($cleanstructure_res['num']>0) {
		foreach ($cleanstructure_res['set'] AS $cssk => $cssv) {
			if (intval($cssv["connected"])>0) {
				$toplevel_sql = "SELECT `level` FROM `menu` WHERE `mid` = ".intval($cssv["connected"]);
				$toplevel_res = doResultSQL($toplevel_sql);
                if ($toplevel_res!==false) {
					doSQL("UPDATE `menu` SET `level` = ".(intval($toplevel_res)+1)." WHERE `mid` = ".intval($cssv["mid"]));
				}
            }
			else {
				doSQL("UPDATE `menu` SET `level` = '1' WHERE `mid` = ".intval($cssv["mid"]));
			}
        }
        unset($cleanstructure_res);
	}

	?>
	<script type="text/javascript" language="javascript">
	<!--
	
	function confirmDelete(mid, mname) {
		if (confirm('<?php echo returnIntLang('structure really delete menupoint1', false); ?> "' + mname + '" <?php echo returnIntLang('structure really delete menupoint2', false); ?>')) {
			$.post("xajax/ajax.deletestructure.php", { 'mid': mid})
				.done (function(data) {
					$(data).toggle('fade', {}, 500);
					});
			}
		}	// confirmDelete()
		
	function confirmVisibility(mid) {
		console.log(mid);
		$.post("xajax/ajax.changevisibilitystructure.php", { 'mid': mid, 'language': '<?php echo $_SESSION['wspvars']['workspacelang']; ?>'})
			.done (function(data) {
				if (data==mid) {
					if ($('#conttab_' + data).hasClass('shownstructure')) {
						$('#conttab_' + data).switchClass('shownstructure', 'hiddenstructure');
						$('#acv_' + data).html('<span class="bubblemessage red"><?php echo returnIntLang('bubble show',false); ?></span>');
						}
					else {
						$('#conttab_' + data).switchClass('hiddenstructure', 'shownstructure');
						$('#acv_' + data).html('<span class="bubblemessage green"><?php echo returnIntLang('bubble hide',false); ?></span>');
						}
					}
				else {
					console.log(data);
					}
				});
		}	// confirmVisibility()
	
	function addCreateSub(mid) {
		document.getElementById('subpointfrom').value = mid;
		if ($('#createnewmenupoint').css('display')=='none') {
			$('#createnewmenupoint').toggle('blind', {}, 300);
			passLiTable('#createlist', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
			}
		document.getElementById('newmenuitem').focus();
		}
	
	function confirmClone(mid, mname) {
		if (confirm('<?php echo returnIntLang('structure really dublicate menupoint1', false); ?> "' + mname + '" <?php echo returnIntLang('structure really dublicate menupoint2', false); ?>')) {
			document.getElementById('menuclonemid').value = mid;
			document.getElementById('menucloneop').value = 'clone';
			document.getElementById('menucloneform').submit();
			}
		}	// confirmClone()
	
	function editMenupoint(mid) {
		document.getElementById('menueditmid').value = mid;
		document.getElementById('menueditop').value = 'edit';
		document.getElementById('menueditform').submit();
		}	// editMenupoint()


	function showhideVisibility() {
		$.post("xajax/ajax.showhidevisibility.php")
			.done (function(data) {
				if(data=="show") {
					$(".hiddenstructure").parent().css('height', '');
					$(".hiddenstructure").parent().css('min-height', '');
					$(".hiddenstructure").parent().css('overflow', '');
					$('#btnshowhide').html('<?php echo returnIntLang('structure showhide hide', true); ?>');
				} else {
					$(".hiddenstructure").parent().css('height', '0px');
					$(".hiddenstructure").parent().css('min-height', '0px');
					$(".hiddenstructure").parent().css('overflow', 'hidden');
					$('#btnshowhide').html('<?php echo returnIntLang('structure showhide show', true); ?>');
				}
				console.log(data);
			});
		}
	
	function showPreviewTemplate(aID, Tmplt) {
		$('.chstpl.mid'+aID).hide();
		$('.chstpl.mid'+aID+'.tpl'+Tmplt).show();
		}
	
	// -->
	</script>
	<pre id="debugcontent"></pre>
	<fieldset>
		<?php if ($_SESSION['wspvars']['rights']['sitestructure']==1): ?>
			<legend><?php echo returnIntLang('structure actualstructure', true); ?></legend>
		<?php else: ?>
			<legend><?php echo returnIntLang('structure restrictedstructure', true); ?></legend>
		<?php endif; ?>
		<?php
				
		if ($_SESSION['wspvars']['rights']['sitestructure']==4):
			if (count($_SESSION['wspvars']['rights']['sitestructure_array'])>0):
				$menuIDs = $_SESSION['wspvars']['rights']['sitestructure_array'];
				$menuallowed = $_SESSION['wspvars']['rights']['sitestructure_array'];
			else:
				$menuIDs = @explode(",", $_SESSION['wspvars']['rights']['sitestructure_id']);
			endif;
		elseif ($_SESSION['wspvars']['rights']['sitestructure']==7):
			if (count($_SESSION['wspvars']['rights']['sitestructure_array'])>0):
				
				// Aenderungen, damit User mit Rechten auf einen MP mit UnterMP die richtigen MP angezeigt bekommen				
				$_SESSION['clone']['midlist'] = array($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
				$_SESSION['mylist'] = returnIDRoot($_SESSION['wspvars']['rights']['sitestructure_array'][0]);
				$_SESSION['mylistid'] = $_SESSION['wspvars']['rights']['sitestructure_array'][0];
				$menuIDs = $_SESSION['clone']['midlist'];
				$menuallowed = $_SESSION['mylist'];
				$_SESSION['structuremidlist'] = $_SESSION['mylist'];
				array_unshift($_SESSION['structuremidlist'], $_SESSION['mylistid']);
//				$menuallowed = $_SESSION['wspvars']['midlist'];
//				$_SESSION['structuremidlist'] = $_SESSION['wspvars']['midlist'];
			else:
				$menuIDs = @explode(",", $_SESSION['wspvars']['rights']['sitestructure_id']);
			endif;
		else:
			$menuIDs = array();
			$_SESSION['structuremidlist'] = array();
		endif;
		// built array with structure based on user rights
		if (isset($menuallowed) && is_array($menuallowed)):
			$allowedstructure = array();
			foreach ($menuallowed AS $makey => $mavalue):
				$allowedstructure = array_merge($allowedstructure, returnIDTree($mavalue));
			endforeach;
			$allowedstructure = array_unique($allowedstructure);
			$menuallowed = $allowedstructure;
		else:
			$menuallowed = array();
		endif;

		?>
		<div id="display_sitestructure">
			<?php 
			
			$topmid = 0;
			if ($_SESSION['wspvars']['rights']['sitestructure']==7 && is_array($_SESSION['structuremidlist'])):
				if (!(in_array($openpath, $_SESSION['structuremidlist']))):
					$openpath = 0;
				endif;
				$topm_sql = "SELECT `connected` FROM `menu` WHERE `mid` = ".intval($_SESSION['structuremidlist'][0]);
				$topm_res = doResultSQL($topm_sql);
				if (intval($topm_res)>0): $topmid = intval($topm_res); endif;
			endif;
			
			if ($_SESSION['wspvars']['rights']['sitestructure']==4 && is_array($menuallowed)):
				if (!(in_array($openpath, $menuallowed))):
					$openpath = 0;
				endif;
			endif;
			?>
			<style type="text/css">
		
			.placeholder {
				border: 1px dashed #4183C4;
				-webkit-border-radius: 3px;
				-moz-border-radius: 3px;
				border-radius: 3px;
				}
				
			</style>
			<ul id="ul_0" class="sortable" style="padding-left: 0px; list-style-type: none;">
			<?php
			
			if (!(isset($_SESSION['wspvars']['editmenuid']))) $_SESSION['wspvars']['editmenuid'] = 0;
			$openlist = array_reverse(returnMIDList($_SESSION['wspvars']['editmenuid']));
			array_pop($openlist);
			
			echo getjMenuStructure($topmid, $menuIDs, $menuallowed, $openlist, 'structure', $_SESSION['wspvars']['workspacelang']);
			
			?>
			</ul>
			<?php if(isset($_SESSION['wspvars']['showhideInvStruc']) && $_SESSION['wspvars']['showhideInvStruc']=="hide"): $showhide = "show"; else: $showhide = "hide"; endif; ?>
			<fieldset class="options innerfieldset">
				<p><a href="#" title="Ausblenden" onclick="showhideVisibility();" class="greenfield" id="btnshowhide"><?php echo returnIntLang('structure showhide ' . $showhide, true); ?></a></p>
			</fieldset>

			<form name="menudeleteform" id="menudeleteform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
				<input type="hidden" name="op" id="menudeleteop" value="">
				<input type="hidden" name="mid" id="menudeletemid" value="">
			</form>
			<form name="menucloneform" id="menucloneform" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
				<input type="hidden" name="op" id="menucloneop" value="">
				<input type="hidden" name="mid" id="menuclonemid" value="">
			</form>
			<form name="menueditform" id="menueditform" action="menueditdetails.php" method="post">
				<input type="hidden" name="op" id="menueditop" value="">
				<input type="hidden" name="mid" id="menueditmid" value="">
			</form>
		<script>
		$(function() {
			$( ".sortable" ).sortable({
				connectWith: ".sortable",
				handle: '.handle',
		    	update: function( event, ui ) {
					updateItem (ui.item.attr('id'), ui.item.parent('ul').sortable('serialize'));
					if ($(window).width()>480) {
						passLiTable('ul.tablelist', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
						}
					},
				receive: function( event, ui ) {
					moveItem(ui.item.attr('id'), ui.sender.attr('id'), ui.item.parent('ul').attr('id'), ui.item.parent('ul').sortable('serialize'));
					if ($(window).width()>480) {
						passLiTable('ul.sortable', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
						}
					}
			}).disableSelection();
		});
<?php
	if(isset($_SESSION['wspvars']['showhideInvStruc']) && $_SESSION['wspvars']['showhideInvStruc']=="hide"):
		echo "$(\".hiddenstructure\").parent().css('height', '0px');";
		echo "$(\".hiddenstructure\").parent().css('min-height', '0px');";
		echo "$(\".hiddenstructure\").parent().css('overflow', 'hidden');";

	endif;
?>				
		</script>
		</div>
		</fieldset>
		<fieldset>
			<ul class="icondesc">
				<li class="icondescitem"><span class="bubblemessage orange "><?php echo returnIntLang('bubble showsub', false); ?></span> <?php echo returnIntLang('bubble showsub icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage "><?php echo returnIntLang('bubble showsub', false); ?></span> <?php echo returnIntLang('bubble showsub nosub icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage "><?php echo returnIntLang('bubble externlink', false); ?></span> <?php echo returnIntLang('bubble externlink icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage "><?php echo returnIntLang('bubble internlink', false); ?></span> <?php echo returnIntLang('bubble internlink icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage "><?php echo returnIntLang('bubble forwarder', false); ?></span> <?php echo returnIntLang('bubble forwarder icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble move', false); ?></span> <?php echo returnIntLang('bubble move structure icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble edit', false); ?></span> <?php echo returnIntLang('bubble edit structure icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage red"><?php echo returnIntLang('bubble delete', false); ?></span> <?php echo returnIntLang('bubble delete structure icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble clone', false); ?></span> <?php echo returnIntLang('bubble clone structure icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble addsubmenu', false); ?></span> <?php echo returnIntLang('bubble addsubmenu icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage green"><?php echo returnIntLang('bubble hide', false); ?></span> <?php echo returnIntLang('bubble shown structure icondesc'); ?></li>
				<li class="icondescitem red"><span class="bubblemessage red"><?php echo returnIntLang('bubble show', false); ?></span> <?php echo returnIntLang('bubble hidden structure icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage orange"><?php echo returnIntLang('bubble info', false); ?></span> <?php echo returnIntLang('structure bubble info icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage green"><?php echo returnIntLang('structure bubble indexpage', false); ?></span> <?php echo returnIntLang('structure bubble indexpage icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage"><?php echo returnIntLang('structure bubble contentlock1', false); ?></span> <?php echo returnIntLang('structure bubble contentlock1 icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage"><?php echo returnIntLang('structure bubble contentlock2', false); ?></span> <?php echo returnIntLang('structure bubble contentlock2 icondesc'); ?></li>
				<li class="icondescitem"><span class="bubblemessage"><?php echo returnIntLang('structure bubble time', false); ?></span> <?php echo returnIntLang('structure bubble time icondesc'); ?></li>
			</ul>
		</fieldset>	
	</div>
	<form name="setactionpoint">
	<input type="hidden" name="action" id="setaction" value="">
	<input type="hidden" name="openmid" id="actionopenmid" value="<?php echo $openpath; ?>">
	</form>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->