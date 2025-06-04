<?php
/**
 * Globale Inhalte editieren
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.9
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
$gcid = 0;
if (array_key_exists('wspvars', $_SESSION) && array_key_exists('editglobalcontentid', $_SESSION['wspvars']) && intval($_SESSION['wspvars']['editglobalcontentid'])>0):
	$gcid = intval($_SESSION['wspvars']['editglobalcontentid']);
endif;
if (isset($_POST['gcid']) && intval($_POST['gcid'])>0):
	$_SESSION['wspvars']['editglobalcontentid'] = intval($_POST['gcid']);
	$gcid = intval($_POST['gcid']);
endif;
/* define actual system position ------------- */
$_SESSION['wspvars']['menuposition'] = "contentedit";
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'].";gcid=".$gcid;
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* page specific includes -------------------- */
require ("./data/include/clsinterpreter.inc.php");
/* define page specific vars ----------------- */
$worklang = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
/* define page specific funcs ---------------- */

// deleteglobalfrompage
if (isset($_REQUEST) && array_key_exists('op', $_REQUEST) && trim($_REQUEST['op'])=='deleteglobalfrompage' && intval($_REQUEST['gcid'])>0):
	doSQL("UPDATE `content` SET `visibility` = 0, trash = 1, lastchange = ".time()." WHERE `globcalcontent_id` = ".intval($_REQUEST['gcid']));
	header("Location: ./globalcontent.php");
endif;

// text2generic
if (isset($_REQUEST) && array_key_exists('op', $_REQUEST) && trim($_REQUEST['op'])=='togeneric'):
	doSQL("UPDATE `globalcontent` SET `interpreter_guid` = 'genericwysiwyg' WHERE `id` = ".intval($_REQUEST['gcid']));
endif;

if (isset($_POST['op']) && ($_POST['op']=='save' || $_POST['op']=='saveglobal') && intval($gcid)>0):
	// Interpreter einlesen

	$value = serialize($_POST['field']);
	doSQL("UPDATE `globalcontent` SET `valuefield`= '".escapeSQL(trim($value))."', `content_lang` = '".$_POST['content_lang']."' WHERE `id` = " . intval($gcid));
	doSQL("UPDATE `menu` `m`, `content` `c` SET `m`.`contentchanged` = 2, `m`.`lastchange` = ".time().", `m`.`changetime` = ".time()." WHERE `c`.`mid` = `m`.`mid` AND `c`.`globalcontent_id` = ".intval($gcid));
	doSQL("UPDATE `content` SET `lastchange` = ".time().", `interpreter_guid` = '".$_POST['interpreter_guid']."' WHERE `globalcontent_id` = ".intval($gcid));
	
	$gctemplate_sql = "SELECT `id` FROM `templates` WHERE `template` LIKE '%[\%GLOBALCONTENT:".intval($gcid)."\%]%'";
	$gctemplate_res = doSQL($gctemplate_sql);
	if ($gctemplate_res['num']>0):
		$submid = array();
		foreach ($gctemplate_res['set'] AS $gresk => $gresv) {
        	$menutpl_sql = "SELECT `mid` FROM `menu` WHERE `templates_id` = ".intval($gresv['id']);
			$menutpl_res = doSQL($menutpl_sql);
			if ($menutpl_res['num']>0):
				$submid = array();
				foreach ($menutpl_res['set'] AS $mtresk => $mtresv):
					$subtplmid = returnIDRoot(intval($mtresv['mid']));
					$submid = array_merge($submid, $subtplmid);
				endforeach;
			endif;
			$submid = array_unique($submid);
			foreach ($submid AS $sk => $sv):
				if (getTemplateID($sv)==intval($gresv['id'])):
					doSQL("UPDATE `menu` SET `contentchanged` = 3 WHERE `mid` = ".intval($sv));
				endif;
			endforeach;
			doSQL("UPDATE `menu` SET `contentchanged` = 3 WHERE `templates_id` = ".intval($gresv['id']));
        }
	endif;
	
	// share global contents
	$intoct = intval($_POST['content_template']);
	$intoca = intval($_POST['content_area']);
	$intoeo = false; // into empty only
	$intoep = true; // into empty pages
	$newpos = 99;
	if (isset($_POST['into_empty_only']) && intval($_POST['into_empty_only'])>0): $intoeo = true; $newpos = 1; endif;
	if (isset($_POST['not_emtpy_pages']) && intval($_POST['not_emtpy_pages'])>0): $intoep = false; endif;

	if($_POST['op']=='saveglobal' && $intoca>0):
		if($intoct!='all'):
			// inserting into all templates
			$allmwt = get_mid_with_template(intval($intoct));
			$gc_into_mid = array();
			foreach ($allmwt AS $ak => $av):
				if ($intoep):
					if($intoeo):
						if(emptyca($av, $intoca)): $gc_into_mid[] = $av; endif;
					else:
						if(gcexists($av, $gcid, $intoca)): $gc_into_mid[] = $av; endif;
					endif;
				else:
					if (!(emptypage($av))):
						if($intoeo):
							if(emptyca($av, $intoca)): $gc_into_mid[] = $av; endif;
						else:
							if(gcexists($av, $gcid, $intoca)): $gc_into_mid[] = $av; endif;
						endif;
					endif;
				endif;
			endforeach;
		else:
			$menu_sql = "SELECT `mid` FROM `menu` WHERE `trash` = 0";
			$menu_res = doSQL($menu_sql);
			if ($menu_res['num']>0):
				$gc_into_mid = array();
				foreach ($menu_res['set'] AS $mtresk => $mtresv) {
					$av = intval($mtresv["mid"]);
					if ($intoep):
						if($intoeo):
							if(emptyca($av, $intoca)): $gc_into_mid[] = $av; endif;
						else:
							if(gcexists($av, $gcid, $intoca)): $gc_into_mid[] = $av; endif;
						endif;
					else:
						if (!(emptypage($av))):
							if($intoeo):
								if(emptyca($av, $intoca)): $gc_into_mid[] = $av; endif;
							else:
								if(gcexists($av, $gcid, $intoca)): $gc_into_mid[] = $av; endif;
							endif;
						endif;
					endif;
				}
			endif;
		endif;
		
		for($cmid=0;$cmid<count($gc_into_mid);$cmid++):
			$ngc_sql = "INSERT INTO `content` SET 
				`mid` = ".intval($gc_into_mid[$cmid]).",
				`globalcontent_id` = ".intval($gcid).",
				`connected` = 0,
				`content_area` = ".intval($intoca).",
				`content_lang` = '".escapeSQL(trim($_POST['content_lang']))."',
				`position` = ".intval($newpos).",
				`visibility` = 1,
				`showday` = 0,
				`showtime` = '',
				`sid` = '',
				`valuefields` = '',
				`xajaxfunc` = '',
				`xajaxfuncnames` = '',
				`lastchange` = ".time().",
				`interpreter_guid` = '".escapeSQL($_POST['interpreter_guid'])."'";
            doSQL($ngc_sql);
			doSQL("UPDATE `menu` SET `contentchanged` = 3 WHERE `mid` = ".intval($gc_into_mid[$cmid]));
		endfor;
		
		addWSPMsg('resultmsg', returnIntLang('globalcontent shared to1').intval(count($gc_into_mid)).returnIntLang('globalcontent shared to2'));
		
	endif;
	
	if ($_POST['op_back']):
		header("Location: ./globalcontent.php");
	endif;
	$noticemsg = "<p>Ihre &Auml;nderungen wurden erfolgreich gespeichert. (".date('d.m.Y H:i:s').")</p>";
endif;


function gcexists($mid, $gc_id, $ca_id) {
	$stat = false;
	$cid_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid) . " AND `globalcontent_id`=".intval($gc_id) . " AND `content_area`=".intval($ca_id) . " AND `trash`=0";
	$cid_res = doSQL($cid_sql);
	if ($cid_res['num']>0):
		$stat = false;
	else:
		$stat = true;
	endif;
	return $stat;
	}

function emptyca($mid, $ca_id) {
	$stat = false;
	$cid_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid) . " AND `content_area` = ".intval($ca_id). " AND `trash` = 0";
	$cid_res = doSQL($cid_sql);
	if ($cid_res['num']>0):
		$stat = false;
	endif;
	return $stat;
	}

function emptypage($mid) {
	// check if active contents are connected to mid => so this page is not only a forwarding page 
	$stat = true;
	$ep_sql = "SELECT `cid` FROM `content` WHERE `mid` = ".intval($mid)." AND `visibility` = 1 AND `trash` = 0";
	$ep_res = doSQL($ep_sql);
	if ($ep_res['num']>0): $stat = false; endif;
	return $stat;	
	}

function get_mid_with_template($tid, $con_id=0){
	$mwt = array();
	if($con_id>0):
		$mwt_sql = "SELECT `mid`, `connected` FROM `menu` WHERE `connected` = " . intval($con_id) . " AND `templates_id` = 0 AND `trash` = 0";
	else:
		$mwt_sql = "SELECT `mid`, `connected` FROM `menu` WHERE `templates_id` = ". intval($tid). " AND `trash`=0";
	endif;
	$mwt_res = doSQL($mwt_sql);
	if ($mwt_res['num']>0):
        foreach ($mwt_res['set'] AS $smpk => $smpv) {
			$mwt[] = intval($smpv["mid"]);
			$mwt = array_merge($mwt, get_mid_with_template($tid, intval($smpv["mid"])));
		}
	endif;

	
	return $mwt;
}


// head of file
include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>
<div id="contentholder"><?php

$gc_sql = "SELECT * FROM `globalcontent` WHERE `id` = ".intval($gcid);
$gc_res = doSQL($gc_sql);
if ($gc_res['num']>0):
	$interpreter_sql = "SELECT * FROM `interpreter` WHERE `guid` = '".escapeSQL(trim($gc_res['set'][0]["interpreter_guid"]))."'";
	$interpreter_res = doSQL($interpreter_sql);
	if ($interpreter_res['num']>0):
		$file = trim($interpreter_res['set'][0]["parsefile"]);
		$name = trim($interpreter_res['set'][0]["name"]);
	else:
		$file = 'genericwysiwyg';
		$name = 'genericwysiwyg';
	endif;
	$guid = trim($gc_res['set'][0]["interpreter_guid"]);

	if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file)):
		// read interpreter file
		require_once $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file;
		if (is_array(unserializeBroken(trim($gc_res['set'][0]["valuefield"])))):
			$fieldvalue = unserializeBroken(trim($gc_res['set'][0]["valuefield"]));
			if(isset($fieldvalue['desc'])):
				$fieldvalue[0] = $fieldvalue['desc'];
			else:
				$fieldvalue[0] = "";
			endif;
			$fieldvaluestyle = "array";
		elseif (trim($gc_res['set'][0]["valuefield"])==""):
			$fieldvalue = "";
			$fieldvaluestyle = "";
		else:
			$fieldvalue = explode('<#>', trim($gc_res['set'][0]["valuefield"]));
			$fieldvaluestyle = "string";
		endif;
		if (is_array($fieldvalue)):
			foreach ($fieldvalue AS $key => $value):
				if(is_array($value)):
					$fieldvalue[$key] = $value;
				else:
					$fieldvalue[$key] = stripslashes($value);
				endif;
			endforeach;
		endif;
		$clsInterpreter = new $interpreterClass;

		$tinymcetextarea = false; if (property_exists($clsInterpreter, 'textarea')) $tinymcetextarea = $clsInterpreter -> textarea;

		if (is_array($tinymcetextarea)) {
			if ((is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/script/tinymce/langs/" .$_SESSION['wspvars']['locallang'] . ".js"))) {		
				$tiny_lang = $_SESSION['wspvars']['locallang'];
			}
            else {
				$tiny_lang = "en";
			}

			?><script type="text/javascript" src="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/tinymce.min.js"></script><?php			

            if (in_array('normal', $tinymcetextarea)) {
				?><script language="javascript" type="text/javascript">
				<!--
				
				tinymce.init({
					language : '<?php echo $tiny_lang; ?>',
		   			selector: ".mceNormal",
		   			license_key: 'gpl',
					height: 150,
					plugins: [
						"compat3x advlist autolink link image lists charmap hr anchor pagebreak spellchecker",
						"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime nonbreaking",
						"save table contextmenu directionality emoticons template paste textcolor"
						],
					image_advtab: true,
					relative_urls: false,
					convert_urls: false,
					target_list: [
				        {title: '<?php echo returnIntLang('tinymce target same page', false); ?>', value: '_self'},
				        {title: '<?php echo returnIntLang('tinymce target new page', false); ?>', value: '_blank'}
					    ],
					document_base_url: "http://<?php echo $_SESSION['wspvars']['workspaceurl'] ?>/",
					image_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/image/imagelist.json.php?<?php echo time(); ?>",
					link_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/pagelist.json.php?<?php echo time(); ?>",
					document_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/medialist.json.php?<?php echo time(); ?>",
					class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/classlist.json.php?<?php echo time(); ?>",
					table_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
					table_row_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
					table_cell_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
					lang_list: [
						<?php
						if($_SESSION['wspvars']['sitelanguages']):
							$langs = unserialize($_SESSION['wspvars']['sitelanguages']);
							if(count($langs['languages']['longname'])>0):
								for($l=0;$l<count($langs['languages']['longname']);$l++):
									echo "{title: '" . $langs['languages']['longname'][$l] . "', value: '" . $langs['languages']['shortcut'][$l] . "'}";
									if($l<(count($langs['languages']['longname'])-1)):
										echo ",\n"; //'
									endif;
								endfor;
							endif;
						endif;
						?>
						],
					toolbar: "insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | formatselect textcolor | charmap | bullist numlist outdent indent | link unlink image | table | visualblocks code", 
						contextmenu: "link image inserttable removeformat",
						autoresize_min_height: 100,
						menu: { 
				        edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'}, 
				        insert: {title: 'Insert', items: '|'}, 
				        format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'}, 
				        table: {title: 'Table'}, 
				        tools: {title: 'Tools'} 
					    }
						});
				
				//-->
				</script>
				<?php
			}
			if (in_array('short', $tinymcetextarea)) {
				?><script language="javascript" type="text/javascript">
<!--

tinymce.init({
	language : '<?php echo $tiny_lang; ?>',
 			selector: ".mceShort",
 			skin : "wsp",
	height: 150,
	plugins: [
		"compat3x advlist autolink link image lists charmap hr anchor pagebreak spellchecker",
		"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime nonbreaking",
		"save table contextmenu directionality emoticons template paste textcolor"
		],
	image_advtab: true,
	relative_urls: false,
	convert_urls: false,
	target_list: [
        {title: '<?php echo returnIntLang('tinymce target same page', false); ?>', value: '_self'},
        {title: '<?php echo returnIntLang('tinymce target new page', false); ?>', value: '_blank'}
	    ],
	document_base_url: "http://<?php echo $_SESSION['wspvars']['workspaceurl'] ?>/",
	image_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/image/imagelist.json.php?<?php echo time(); ?>",
	link_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/pagelist.json.php?<?php echo time(); ?>",
	document_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/medialist.json.php?<?php echo time(); ?>",
	class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/classlist.json.php?<?php echo time(); ?>",
	table_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
	table_row_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
	table_cell_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
	lang_list: [
		<?php
		if($_SESSION['wspvars']['sitelanguages']):
			$langs = unserialize($_SESSION['wspvars']['sitelanguages']);
			if(count($langs['languages']['longname'])>0):
				for($l=0;$l<count($langs['languages']['longname']);$l++):
					echo "{title: '" . $langs['languages']['longname'][$l] . "', value: '" . $langs['languages']['shortcut'][$l] . "'}";
					if($l<(count($langs['languages']['longname'])-1)):
						echo ",\n"; //'
					endif;
				endfor;
			endif;
		endif;
		?>
		],
	toolbar: "insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | charmap | link unlink | visualblocks code", 
		contextmenu: "link image inserttable removeformat",
		autoresize_min_height: 100,
		menu: { 
        edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'}, 
        insert: {title: 'Insert', items: '|'}, 
        format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'}, 
        table: {title: 'Table'}, 
        tools: {title: 'Tools'} 
	    }
		});

//-->
</script><?php
			}
		}

		?>
		<fieldset>
			<h1><?php echo returnIntLang('globalcontent editor headline', true); ?></h1>
		</fieldset>
		<form name="editcontent" id="editcontent" method="post" enctype="multipart/form-data">
		<input type="hidden" name="op_back" id="op_back" value="" />
		<input type="hidden" name="op" id="op" value="" />
		<input type="hidden" name="gcid" id="" value="<?php echo $gcid; ?>" />
		<input type="hidden" name="interpreter" id="interpreter" value="<?php echo $file; ?>" />
		<input type="hidden" name="interpreter_guid" id="interpreter_guid" value="<?php echo $guid; ?>" />
		<fieldset>
			<legend><?php echo returnIntLang('str legend', true); ?> <?php echo legendOpenerCloser('wsplegend'); ?></legend>
			<div id="wsplegend">
				<p><?php echo returnIntLang('globalcontent modlanginfo', true); ?></p>
			</div>
		</fieldset>

		<span id="configurator">
		<?php
		if ($fieldvaluestyle=="string"):
		?><fieldset id="formatnotice" class="options"><p>Die Daten dieses Elements liegen eventuell im 'alten' WSP-Datenbankformat vor und werden - wenn vom Modul unterst&uuml;tzt - beim n&auml;chsten Speichern in das neue WSP-Datenbankformat umgewandelt.</p></fieldset><?php
		endif;
		
		if ($clsInterpreter):
			// call interpreter function
            $multilangcontent = false; if (property_exists($clsInterpreter, 'multilang')) $multilangcontent = $clsInterpreter -> multilang;
            $flexiblecontent = false; if (property_exists($clsInterpreter, 'flexible')) $flexiblecontent = $clsInterpreter -> flexible;
		if (!(is_array($multilangcontent)) || ($fieldvaluestyle=="string" && !(@implode($fieldvalue)=="")) || !($flexiblecontent)):
			echo "<fieldset><p>";
			if (!(is_array($multilangcontent))):
				echo returnIntLang('interpreter none multilang', true)." ";
			endif;
			if ($fieldvaluestyle=="string" && !(@implode($fieldvalue)=="")):
				echo returnIntLang('interpreter old format', true)." ";
			endif;
			if (!($flexiblecontent)):
				echo returnIntLang('interpreter non flexible', true)." ";
			endif;
			echo "</p></fieldset>";
		endif;
		if (is_array($multilangcontent)):
			foreach($lang AS $lkey => $lvalue):
				if (array_key_exists($lkey, $multilangcontent) && is_array($multilangcontent[$lkey])):
					$lang[$lkey] = array_merge($lang[$lkey], $multilangcontent[$lkey]);
				endif;
			endforeach;
		endif;
			echo $clsInterpreter -> getEdit($fieldvalue, 0, 0);
            $clsInterpreter->closeInterpreterDB();
		endif;
		?>
		</span>
		<?php 
		//
		// block to define workspace language
		//
		if (key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']==""):
			$_SESSION['wspvars']['workspacelang'] = $worklang['languages']['shortcut'][0];
		endif;
		if (isset($_POST['workspacelang']) && $_POST['workspacelang']!=""):
			$_SESSION['wspvars']['workspacelang'] = $_POST['workspacelang'];
		endif;
		if (is_array($worklang['languages']['shortcut'])):
			if (!(in_array($_SESSION['wspvars']['workspacelang'],$worklang['languages']['shortcut']))):
				$_SESSION['wspvars']['workspacelang'] = $worklang['languages']['shortcut'][0];
			endif;
		endif;
		if (intval(count($worklang['languages']['shortcut']))>1): ?>
			<fieldset>
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('globalcontent binding language', true); ?></td>
						<td class="tablecell six"><select name="content_lang">
						<?php
						echo "<option value=\"\">".returnIntLang('globalcontent nobinding', false)."</option>";
						foreach ($worklang['languages']['shortcut'] AS $key => $value):
							if (trim($gc_res['set'][0]["content_lang"])!=""):
								if (trim($gc_res['set'][0]["content_lang"])==$worklang['languages']['shortcut'][$key]):
									echo "<option value=\"".$worklang['languages']['shortcut'][$key]."\" selected=\"selected\">".$worklang['languages']['longname'][$key]."</option>";
								endif;
							else:
								echo "<option value=\"".$worklang['languages']['shortcut'][$key]."\">".$worklang['languages']['longname'][$key]."</option>";
							endif;
						endforeach;
						
						?>
					</select></td>
					</tr>
				</table>
			</fieldset>
		<?php endif; ?>
	<?php else:
        if ((is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/script/tinymce/langs/" .$_SESSION['wspvars']['locallang'] . ".js"))):			
			$tiny_lang = $_SESSION['wspvars']['locallang'];
		else:
			$tiny_lang = "en";
		endif;
		
		$fieldvalue = unserializeBroken(trim($gc_res['set'][0]["valuefield"]));
		$fieldvaluestyle = "array";
		$interpreterClass = "genericwysiwyg";

		?>
		<form name="editcontent" id="editcontent" method="post" enctype="multipart/form-data">
		<input type="hidden" name="op_back" id="op_back" value="" />
		<input type="hidden" name="op" id="op" value="" />
		<input type="hidden" name="gcid" id="" value="<?php echo $gcid; ?>" />
		<input type="hidden" name="interpreter" id="interpreter" value="<?php echo $file; ?>" />
		<input type="hidden" name="interpreter_guid" id="interpreter_guid" value="<?php echo $guid; ?>" />
		<?php if (strlen(stripslashes($fieldvalue['content']))<500): $tiny_height=150; else: $tiny_height=300; endif; ?>
		<script type="text/javascript" src="/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/tinymce.min.js"></script>
		<script language="javascript" type="text/javascript">
		<!--
		tinymce.init({
			language : '<?php echo $tiny_lang; ?>',
   			selector: "textarea",
   			license_key: 'gpl',
			height: <?php echo $tiny_height; ?>,
			external_plugins: {
				//"advlink": "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/advlink/editor_plugin.js"
				},
			plugins: [
				"compat3x advlist autolink link image  lists charmap hr anchor pagebreak spellchecker",
				"searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime nonbreaking",
				"save table contextmenu directionality emoticons template paste textcolor"
				],
			image_advtab: true,
			relative_urls: false,
			convert_urls: false,
			target_list: [
		        {title: 'Gleiche Seite', value: '_self'},
		        {title: 'Neue Seite', value: '_blank'}
			    ],
			document_base_url: "http://<?php echo $_SESSION['wspvars']['workspaceurl'] ?>/",
			image_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/image/imagelist.json.php?<?php echo time(); ?>",
			link_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/pagelist.json.php?<?php echo time(); ?>",
			document_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/medialist.json.php?<?php echo time(); ?>",
			class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/link/classlist.json.php?<?php echo time(); ?>",
			table_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
			table_row_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
			table_cell_class_list: "/<?php echo $_SESSION['wspvars']['wspbasedir']; ?>/data/script/tinymce/plugins/table/tableclasslist.json.php?<?php echo time(); ?>",
			lang_list: [
				<?php

				if($_SESSION['wspvars']['sitelanguages']):
					$langs = unserializeBroken($_SESSION['wspvars']['sitelanguages']);
					
					if(count($langs['languages']['longname'])>0):
						for($l=0;$l<count($langs['languages']['longname']);$l++):
							echo "{title: '" . $langs['languages']['longname'][$l] . "', value: '" . $langs['languages']['shortcut'][$l] . "'}";
							if($l<(count($langs['languages']['longname'])-1)):
								echo ",\n"; //'
							endif;
						endfor;
					endif;
				endif;
				?>
				],
			toolbar: "insertfile undo redo | bold italic | alignleft aligncenter alignright alignjustify | formatselect textcolor | charmap | bullist numlist outdent indent | link unlink image | table | visualblocks code", 
				contextmenu: "link image inserttable removeformat",
				autoresize_min_height: 100,
				menu: { 
		        edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'}, 
		        insert: {title: 'Insert', items: '|'}, 
		        format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'}, 
		        table: {title: 'Table'}, 
		        tools: {title: 'Tools'} 
			    }
				});
		
		//-->
		</script>
		
		<fieldset>
			<legend><?php echo returnIntLang('contentedit generic wysiwyg legend'); ?></legend>
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('contentedit generic wysiwyg desc'); ?></td>
					<td class="tablecell six"><input type="text" name="field[desc]" id="field_desc" value="<?php if (is_array($fieldvalue) && array_key_exists('desc', $fieldvalue)) echo prepareTextField($fieldvalue['desc']); ?>" class="six full" /></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('contentedit generic wysiwyg content'); ?></td>
					<td class="tablecell six"><textarea name="field[content]" id="field_content" class="medium six"><?php if (is_array($fieldvalue) && array_key_exists('content', $fieldvalue)) echo stripslashes($fieldvalue['content']); ?></textarea></td>
				</tr>
			</table>
		</fieldset>
		<?php if (intval(count($langs['languages']['shortcut']))>1): ?>
			<fieldset>
				<table class="tablelist">
					<tr>
						<td class="tablecell two"><?php echo returnIntLang('globalcontent binding language', true); ?></td>
						<td class="tablecell six"><select name="content_lang">
						<?php
						echo "<option value=\"\">".returnIntLang('globalcontent nobinding', false)."</option>";
						foreach ($langs['languages']['shortcut'] AS $key => $value):
							if (trim($gc_res['set'][0]["content_lang"])!=""):
								if (trim($gc_res['set'][0]["content_lang"])==$langs['languages']['shortcut'][$key]):
									echo "<option value=\"".$langs['languages']['shortcut'][$key]."\" selected=\"selected\">".$langs['languages']['longname'][$key]."</option>";
								endif;
							else:
								echo "<option value=\"".$langs['languages']['shortcut'][$key]."\">".$langs['languages']['longname'][$key]."</option>";
							endif;
						endforeach;
						
						?>
						</select></td>
					</tr>
				</table>
			</fieldset>
		<?php endif; ?>
	<?php endif; ?>
		<fieldset class="text">
			<legend><?php echo returnIntLang('globalcontent options legend'); ?> <?php echo legendOpenerCloser('globalcontent-placements', 0); ?></legend>
			<div id="globalcontent-placements">
			<table class="tablelist">
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('globalcontent option template', true); ?></td>
					<td class="tablecell two"><select name="content_template"  id="content_template" onchange="showCAreas(this.value)">
						<?php
							$gctemplate_sql = "SELECT `id`, `name`, `template` FROM `templates`";
							$gctemplate_res = doSQL($gctemplate_sql);
							if ($gctemplate_res['num']>0):
								$cas = array();
								echo "<option value=\"all\">".returnIntLang('globalcontent option all templates', false)."</option>";
								foreach ($gctemplate_res['set'] AS $gresk => $gresv):
									echo "<option value=\"".intval($gresv["id"])."\">".trim($gresv["name"])."</option>";
									$template = trim($gresv["template"]);
									@preg_match_all("/\[%CONTENTVAR.*%\]/",$template, $mvars);
									if(is_array($mvars)):
										$cas['' . intval($gresv["id"])] = count($mvars[0]);
									else:
										$cas['' .intval($gresv["id"])] = 0;
									endif;
								endforeach;
							endif;
						
						?>
					</select></td>
					<td class="tablecell two"><?php echo returnIntLang('globalcontent option contentarea', true); ?></td>
					<td class="tablecell two"><?php
			
					$tmp_cas = $cas;
					sort($tmp_cas);
					$cas_min = $tmp_cas[0];
					$castring = '';
						
					for($i=0;$i<intval($cas_min);$i++):
						$castring .= '<option value="' . ($i+1) . '" >' . ($i+1) . '</option>'; 
					endfor;
						
					?>	
					<script language="javascript" type="text/javascript">
					<!--
						function showCAreas(caid) {
							var cas = new Array();
					<?php
							echo "cas['all'] = " . $cas_min . ";\n";
							foreach($cas AS $caskey => $casvalue):
								echo "				cas['" . $caskey . "'] = " . $casvalue . ";\n";
							endforeach;
					?>
						//	var caid = $('#content_template').value;
							var countcas = cas[caid];
							var castring = '';
							
							for(var i=0;i<countcas;i++) {
								castring = castring + '<option value="' + (i+1) + '" >' + (i+1) + '</option>'; 
							}
							
							$('#content_area').html(castring);
						}
					//-->
					</script>
					<select name="content_area" id="content_area">
					<?php
						echo $castring;
						?>
					</select></td>
				</tr>
				<tr>
					<td class="tablecell two"><?php echo returnIntLang('globalcontent option into empty areas only', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="into_empty_only" value="0" /><input type="checkbox" name="into_empty_only" value="1" /></td>
					<td class="tablecell two"><?php echo returnIntLang('globalcontent option only not empty pages', true); ?></td>
					<td class="tablecell two"><input type="hidden" name="not_emtpy_pages" value="0" /><input type="checkbox" name="not_emtpy_pages" value="1" /></td>
				</tr>
			</table>
			<fieldset class="options innerfieldset">
				<p><a onclick="document.getElementById('op_back').value=1; document.getElementById('op').value='saveglobal'; document.getElementById('editcontent').submit();"  class="greenfield"><?php echo returnIntLang('btn save and back and global', false); ?></a> <a onclick="document.getElementById('deleteglobalfrompage').submit();"  class="redfield"><?php echo returnIntLang('btn delete from pages'); ?></a></p>
			</fieldset>
			</div>
		</fieldset>
		<fieldset class="options">
			<p><a href="#" onclick="document.getElementById('op_back').value=0; document.getElementById('op').value='save'; document.getElementById('editcontent').submit(); return false;" title="Globalen Inhalt &auml;ndern" onmouseover="status='Globalen Inhalt &auml;ndern'; return false;" onmouseout="status=''; return true;" class="greenfield"><?php echo returnIntLang('str save', false); ?></a> <a href="#" onclick="document.getElementById('op_back').value=1; document.getElementById('op').value='save'; document.getElementById('editcontent').submit();" title="Globalen Inhalt &auml;ndern" onmouseover="status='Globalen inhalt &auml;ndern'; return false;" onmouseout="status=''; return true;"  class="greenfield"><?php echo returnIntLang('btn save and back', false); ?></a>  <?php if($interpreterClass=='text'): ?><a href="globalcontentedit.php?gcid=<?php echo intval($gcid); ?>&op=togeneric" class="orangefield"><?php echo returnIntLang('str text2generic', false); ?></a><?php endif; ?> <a href="globalcontent.php" class="orangefield"><?php echo returnIntLang('str back', false); ?></a></p>
		</fieldset>
	</form>
	<form id="deleteglobalfrompage">
		<input type="hidden" name="op_back" id="op_back" value="1" />
		<input type="hidden" name="op" id="op" value="deleteglobalfrompage" />
		<input type="hidden" name="gcid" id="" value="<?php echo $gcid; ?>" />
	</form>
	<?php else: ?>
		<fieldset><?php echo returnIntLang('globalcontentedit requested global content does not exist'); ?></fieldset>
<?php endif; ?>
</div>
<?php @ include ("data/include/footer.inc.php"); ?>
<!-- EOF -->