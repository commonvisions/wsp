<?php
/**
 * Bearbeiten von Stylesheets
 * @author s.haendler@covi.de
 * @copyright (c) 2022, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.11
 * @lastchange 2022-10-25
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("data/include/usestat.inc.php");
require ("data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/wsplang.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */
$op = checkParamVar('op', '');
$id = checkParamVar('id', 0);
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
// define page specific vars -----------------

// define folders, that should exist
$requiredstructure = array("media","/media/layout");

// do ftp connect
$ftp = doFTP();
foreach ($requiredstructure AS $csk => $csv) {
	if (!(is_dir(str_replace("//","/",$_SERVER['DOCUMENT_ROOT']."/media")))) {
		if ($ftp) { 
			@ftp_mkdir($ftphdl, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$csv)); 
		} else {
			@mkdir($_SERVER['DOCUMENT_ROOT'] . '/' . ($_SESSION['wspvars']['wspbasediradd'] ?? '') . '/' . $csv, 0755, true);
		}
	}
}
if ($ftp) { ftp_close($ftp); }

// define page specific funcs ----------------
if (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="save" && trim($_POST['file'])!=''):
	if (intval($_POST['id'])>0):
		// update exisiting file
		$sql = "UPDATE `stylesheets` SET 
			`file` = '".escapeSQL(trim($_POST['file']))."',
			`stylesheet` = '".escapeSQL($_POST['stylesheet'])."',
			`describ`='".escapeSQL(trim($_POST['describ']))."',
			`media` = '".escapeSQL($_POST['media'])."',
			`browser`='".escapeSQL($_POST['browser'])."',
			`lastchange` = ".time().",
			`cfolder` = '".time()."'
			WHERE `id` = ".intval($_POST['id']);
	else:
		// save new file
		$sql = "INSERT INTO `stylesheets` SET
			`file` = '".escapeSQL(trim($_POST['file']))."',
			`stylesheet`='".escapeSQL($_POST['stylesheet'])."',
			`describ`='".escapeSQL(trim($_POST['describ']))."',
			`media` = '".escapeSQL($_POST['media'])."',
			`browser`='".escapeSQL($_POST['browser'])."',
			`lastchange` = ".time().",
			`cfolder` = '".time()."'";
	endif;
    $res = doSQL($sql);
	if ($res['res']):
		addWSPMsg('noticemsg', returnIntLang('css saved css-file'));
	else:
		addWSPMsg('errormsg', returnIntLang('css error saving css-file'));
	endif;
	
elseif ($op == "savefolder"):
	if (intval($_POST['id'])>0):
		// CSS updaten
		$stylesheet = '';
		if (array_key_exists('stylesheet', $_POST)): $stylesheet = serialize($_POST['stylesheet']); endif;
		$sql = "UPDATE `stylesheets` SET 
			`stylesheet` = '".escapeSQL($stylesheet)."',
			`describ` = '".escapeSQL(trim($_POST['describfolder']))."',
			`lastchange` = '".time()."'
			WHERE `id` = ".intval($_POST['id']);
        $res = doSQL($sql);
		if ($res['res']):
			addWSPMsg('noticemsg', returnIntLang('saved changes to cssfolder', false));	
		else:
			addWSPMsg('errormsg', returnIntLang('error saving changes to cssfolder', false));	
		endif;
	else:
		addWSPMsg('errormsg', returnIntLang('error saving changes to cssfolder', false));	
	endif;
	
elseif ($op == "uploadfile" && $_FILES['fileupload']['type'] == "text/css"):
	
	$upfile = $_FILES['fileupload']['tmp_name'];
	$array = file($upfile);
	
	$sql = "INSERT INTO `stylesheets` SET 
			`file` = '".str_replace(".css","",$_FILES['fileupload']['name'])."',
			`stylesheet` = '".escapeSQL(trim(implode("\n", $array)))."',
			`describ` = '".escapeSQL(trim($_POST['describupload']))."',
			`media` = 'all',
			`browser` = 'all',
			`lastchange` = '".time()."'";
	$res = doSQL($sql);
    if ($res['res']):
        addWSPMsg('noticemsg', returnIntLang('saved uploaded css file', false));	
    else:
        addWSPMsg('errormsg', returnIntLang('error saving uploaded css file', false));	
    endif;
elseif (isset($_POST) && array_key_exists('op', $_POST) && $_POST['op']=="delete" && intval($_POST['id'])>0):
	$sql = "DELETE FROM `stylesheets` WHERE `id` = ".intval($_POST['id']);
    $res = doSQL($sql);
	if ($res['res']):
		addWSPMsg('noticemsg', returnIntLang('css removed css-data without removing file'));
	endif;
	// css-datei aus filesystem loeschen ??
	// hier sollte irgendwo ein flag gesetzt werden. das loeschen bringt ja auch mit sich,
	// dass bestehende seiten nicht mehr richtig dargestellt werden. hier brauchen wir noch
	// eine routine, die in den entsprechenden menuepunkten zum einen das gesetzte css loescht
	// und zum anderen vielleicht ein neues (z.b. das standard-css) einsetzt und die seiten parst
endif;

// site specific definitions

$mediaarray = array(
	"all" => returnIntLang('css mediatype allmedia', false),
	"screen" => returnIntLang('css mediatype screen', false),
	"print" => returnIntLang('css mediatype print', false),
	"handheld" => returnIntLang('css mediatype handheld', false),
	"only screen and (max-device-width: 480px)" => returnIntLang('css mediatype appleiphone', false),
	"only screen and (max-device-width: 1024px)" => returnIntLang('css mediatype applemobile', false)
	);
	
$browserarray = array(
	"all" => returnIntLang('css browser allbrowser', false),
	"IE" => returnIntLang('css browser ieall', false),
	"lte IE 6" => returnIntLang('css browser ie6lower', false),
	"IE 6" => returnIntLang('css browser ie6', false),
	"gte IE 6" => returnIntLang('css browser ie6upper', false),
	"lte IE 7" => returnIntLang('css browser ie7lower', false),
	"IE 7" => returnIntLang('css browser ie7', false),
	"gte IE 7" => returnIntLang('css browser ie7upper', false),
	"lte IE 8" => returnIntLang('css browser ie8lower', false),
	"IE 8" => returnIntLang('css browser ie8', false),
	"gte IE 8" => returnIntLang('css browser ie8upper', false),
	"lte IE 9" => returnIntLang('css browser ie9lower', false),
	"IE 9" => returnIntLang('css browser ie9', false)
	);

// head der datei

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('css headline'); ?></h1></fieldset>
	<fieldset><p><?php echo returnIntLang('css info'); ?></p></fieldset>
	<?php

	if ($op!='edit'):
	
	// run folder for files ...
	$foundcssfiles = array();
	$cssdir = str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/media/layout/");
	$dirrun = dir($cssdir);
	while (false !== ($entry = $dirrun->read())):
		if ((substr($entry, 0, 1)!='.') && (is_file($cssdir."/".$entry))):
			$foundcssfiles[] = $entry;
			$foundcsssize[$entry] = filesize($cssdir."/".$entry);
			$foundcssdate[$entry] = filemtime($cssdir."/".$entry);
			clearstatcache();
		endif;
	endwhile;
	$dirrun->close();
	// run database for saved files
	$syscssfiles = array();
    $css_sql = "SELECT `file` FROM `stylesheets` WHERE file != '' ORDER BY `file`";
	$css_res = doSQL($css_sql);
	if ($css_res['num']>0) {
        $syscssfiles = array();
        foreach ($css_res['set'] AS $cresk => $cresv) {
            if (in_array(trim($cresv['file']).".css", $foundcssfiles)) {
                $syscssfiles[] = trim($cresv['file']).".css";
            }
        }
    }
	
	$lostcssfiles = array();
	$lostcssfiles = array_diff($foundcssfiles, $syscssfiles);
	
	// setting lost to 0 for version 6.0
	$lostcssfiles = array();
	
	if (count($lostcssfiles)>0):
		?>
		<fieldset <?php if(isset($_POST) && array_key_exists('op', $_POST) && ($_POST['op']=="editfolder")): echo "style=\"display: none;\""; endif; ?>>
			<legend><?php echo returnIntLang('css found files'); ?> <?php echo legendOpenerCloser('cssfoundfiles'); ?></legend>
			<div id="cssfoundfiles">
				<ul class="tablelist">
					<li class="tablecell head two"><?php echo returnIntLang('str filename'); ?></li>
					<li class="tablecell head two"><?php echo returnIntLang('str lastchange'); ?></li>
					<li class="tablecell head two"><?php echo returnIntLang('str filesize'); ?></li>
					<li class="tablecell head two"><?php echo returnIntLang('str action'); ?></li>
					<?php foreach ($lostcssfiles AS $lckey => $lcvalue): ?>
						<li class="tablecell two"><?php echo $lcvalue; ?></li>
						<li class="tablecell two"><?php echo date(returnIntLang('format date', false), $foundcssdate[$lcvalue]); ?></li>
						<li class="tablecell two"><?php echo $foundcsssize[$lcvalue]; echo " ".returnIntLang('mediadetails space Byte', true); ?></li>
						<li class="tablecell two"><span class="bubblemessage green"><?php echo returnIntLang('bubble import', false); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</fieldset>
	<?php endif;
	
	$css_sql = "SELECT `id`, `cfolder`, `describ`, `media`, `browser` FROM `stylesheets` WHERE `cfolder` != '' AND `file` = '' ORDER BY `describ`";
	$css_res = doSQL($css_sql);
	if ($css_res['num'] <5 && intval($id)==0) { $tmpshowopen = "open"; }
	
	?>
	<fieldset <?php if(isset($_POST) && array_key_exists('op', $_POST) && ($_POST['op']=="editfolder")): echo "style=\"display: none;\""; endif; ?>>
		<legend><?php echo returnIntLang('css existingfolder'); ?> <?php echo legendOpenerCloser('cssfolder'); ?></legend>
		<?php
		
		if ($css_res['num']==0):
			echo "<p>".returnIntLang('css nofolder')."</p>\n";
		else:
			?>
			<div id="cssfolder">
			<ul class="tablelist">
				<li class="tablecell head two"><?php echo returnIntLang('str folder'); ?></li>
				<li class="tablecell head two"><?php echo returnIntLang('str description'); ?></li>
				<li class="tablecell head two"><?php echo returnIntLang('str usage'); ?></li>
				<li class="tablecell head two"><?php echo returnIntLang('str action'); ?></li>
				<?php 
				
				foreach ($css_res['set'] AS $crsk => $crsv) {
					echo "<li class=\"tablecell two\">";
					
					echo "<form name=\"edit_cssfolder_".$crsk."\" id=\"edit_cssfolder_".$crsk."\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
					echo "<input name=\"op\" id=\"\" type=\"hidden\" value=\"editfolder\" />";
					echo "<input name=\"id\" id=\"\" type=\"hidden\" value=\"".intval($crsv['id'])."\" />";
					echo "</form>\n";
					
					echo "<a href=\"#\" onClick=\"document.getElementById('edit_cssfolder_".$crsk."').submit();\">".trim($crsv['cfolder'])."</a></li>";
					echo "<li class=\"tablecell two\">".trim($crsv['describ'])."</li>";
					echo "<li class=\"tablecell two\">";
					
					$cssuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_styles` AS rtc, `templates` AS t WHERE rtc.`templates_id` = t.`id` AND rtc.`stylesheets_id` = ".intval($crsv['id']);
					$cssuse_res = doSQL($cssuse_sql);
					
					if ($cssuse_res['num']>0) {
						foreach ($cssuse_res['set'] AS $curesk => $curesv) {
							echo setUTF8(trim($curesv['tname']))."<br />";
                        }
                    }
					
					$cssmenuuse_sql = "SELECT mj.`description` AS mdesc, mj.`mid` AS mid FROM `menu` AS mj WHERE mj.`addcss` LIKE '%\"".intval($crsv['id'])."\"%'";
					$cssmenuuse_res = doSQL($cssmenuuse_sql);
					
					if ($cssmenuuse_res['num']>0):
						echo "Men&uuml;punkte:<br />"; 
						$smushow = ($cssmenuuse_res['num']>5)?5:intval($cssmenuuse_res['num']);
						for($cures=0; $cures<$smushow; $cures++):
							echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".intval($cssmenuuse_res['set'][$cures]['mid'])."\">".trim($cssmenuuse_res['set'][$cures]['mdesc'])."</a><br />";
						endfor;
						if ($cssmenuuse_res['num']>5):
							echo "<a style=\"cursor: pointer;\" id=\"showmore\" onclick=\"document.getElementById('hidemore').style.display = 'block'; document.getElementById('showmore').style.display = 'none';\" >".(intval($cssmenuuse_res['num'])-5)." weitere ..</a>";
							echo "<span id=\"hidemore\" style=\"display: none;\">";
							for ($cures=5; $cures<$cssmenuuse_res['num']; $cures++):
								echo "<a href=\"".$_SERVER['PHP_SELF']."?action=menuedit&id=".intval($cssmenuuse_res['set'][$cures]['mid'])."\">".trim($cssmenuuse_res['set'][$cures]['mdesc'])."</a><br />";
							endfor;
							echo "</span>";
						endif;
					endif;
					
					echo "</li>";
					echo "<li class=\"tablecell two\"><a href=\"".$_SERVER['PHP_SELF']."?op=deletefolder&id=".intval($crsv['id'])."\" onclick=\"return confirm('".returnIntLang('css confirmdeletefolder', false)."');\"><span class=\"bubblemessage red\">".strtoupper(returnIntLang('bubble delete', false))."</span></a></li>";
                }
				
				?>
			</ul>
		</div>			
	<?php endif; ?>
	</fieldset>
	<?php
	
	if ($op=="editfolder" && $id>0) {
		$cssfolder_sql = "SELECT `id`, `cfolder`, `describ`, `stylesheet` FROM `stylesheets` WHERE `id` = ".intval($id);
		$cssfolder_res = doSQL($cssfolder_sql);
		if ($cssfolder_res['num']>0) {
			$describ = trim($cssfolder_res['set'][0]['describ']);
			$cfolder = trim($cssfolder_res['set'][0]['cfolder']);
			$usedones = unserializeBroken(trim($cssfolder_res['set'][0]['stylesheet']));
        }
    }
	
	?>
	<fieldset id="editcssfolder" <?php if($op!="editfolder"): ?>style="display: none;"<?php endif; ?>>
		<legend><?php echo returnIntLang('css editfolder'); ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscriptfolder" name="formscriptfolder">
		<table class="contenttable">
		<tr class="secondcol">
			<td width="25%"><?php echo returnIntLang('str description'); ?></td>
			<td width="75%"><input type="text" size="50" maxlength="70" name="describfolder" id="describfolder" value="<?php echo $describ; ?>" style="width: 94%;" /></td>
		</tr>
		<tr class="firstcol">
			<td width="25%"><?php echo returnIntLang('str folder'); ?></td>
			<td width="75%"><input type="text" size="50" maxlength="70" disabled="disabled" readonly="readonly" value="<?php echo $cfolder; ?>" style="width: 94%;" /></td>
		</tr>
		<tr class="secondcol">
			<td width="25%"><?php echo returnIntLang('css folderfiles'); ?></td>
			<td width="75%"><?php
			
			$path = "/media/layout/".$cfolder."/";
			$usefiles = array();
//			$usedones = array();
			
			function listFiles($path, $startpath) {
				if (is_dir($_SERVER['DOCUMENT_ROOT'].$path)):
					$dir = opendir ($_SERVER['DOCUMENT_ROOT'].$path);
				    while ($entry = readdir($dir)):
						if (!(substr($entry, 0, 1)==".")):
							if (is_dir($_SERVER['DOCUMENT_ROOT'].$path."/".$entry)):
								listFiles($path."/".$entry, $startpath."/".$entry);
							elseif (substr($entry, -4) == ".css"):
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

				echo "<ul id=\"sortcssfolder\" style=\"margin: 0px; padding: 0px; list-style-type: none;\">";
				foreach ($usefiles AS $key => $value):
					echo "<li id=\"sortcssfolder_".$key."\"><table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td>";
					echo "<input type=\"checkbox\" name=\"stylesheet[]\" id=\"usefiles_".$key."\" value=\"".$value."\" ";
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
		<tr>
			<td width="25%">&nbsp;</td><td width="75%" class="tooltip"><?php echo returnIntLang('js movefileshint'); ?></td>
		</tr>
		</table>
		<input type="hidden" name="id" value="<?php echo $id; ?>" />
		<input type="hidden" name="op" value="savefolder" />
		<fieldset class="options innerfieldset"><p><a href="javascript: document.getElementById('formscriptfolder').submit();" onclick="return checkFieldsScriptfolder();" class="greenfield"><?php echo returnIntLang('str save'); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" title="Abbrechen" onmouseover="status='Abbrechen'; return true;" onmouseout="status=''; return true;" class="orangefield"><?php echo returnIntLang('str cancel'); ?></a></p></fieldset>
		</form>
		<script type="text/javascript" language="javascript" charset="utf-8">
		
		$(function() {
    $( "#sortcssfolder" ).sortable({
      placeholder: "ui-state-highlight"
    });
    $( "#sortcssfolder" ).disableSelection();
  });
		
		</script>
		<div id="debug"></div>
	</fieldset>
	<?php
	
	$css_sql = "SELECT `id`, `file`, `describ`, `media`, `browser` FROM `stylesheets` WHERE file != '' ORDER BY `describ`";
	$css_res = doSQL($css_sql);
	if ($css_res['num']<10 && $op!='edit'): $tmpshowopen = "open"; endif;
	
	?>
	<fieldset <?php if(isset($_POST) && array_key_exists('op', $_POST) && ($_POST['op']=="editfolder")): echo "style=\"display: none;\""; endif; ?>>
		<script language="JavaScript" type="text/javascript">
		<!--
		
		function confirmDelete(designname, cssid) {
			if (confirm('<?php echo returnIntLang('css removemessage1', false); ?>' + designname + '<?php echo returnIntLang('css removemessage2', false); ?>')) {
				document.getElementById('deleteid').value = cssid;
				document.getElementById('deletecss').submit();
				}
			}
		
		// -->
		</script>
		<legend><?php echo returnIntLang('css existingfiles'); ?> <?php echo legendOpenerCloser('cssfiles'); ?></legend>
		<div id="cssfiles">
		<?php
		if ($css_res['num']==0):
			echo "<p>".returnIntLang('css nofiles')."</p>\n";
		else:
			?>
			<ul class="tablelist">
				<li class="tablecell head two"><?php echo returnIntLang('str description'); ?> [ <?php echo returnIntLang('str filename'); ?> ]</li>
				<li class="tablecell head two"><?php echo returnIntLang('css mediatype'); ?></li>
				<li class="tablecell head two"><?php echo returnIntLang('str usage'); ?></li>
				<li class="tablecell head two"><?php echo returnIntLang('str action'); ?></li>
			</ul>
			<?php foreach ($css_res['set'] AS $crsk => $crsv) { ?>
                <ul class="tablelist connected top">
                    <li class="tablecell two"><?php 
                    echo "\t<form name=\"edit_design_".$crsk."\" id=\"edit_design_".$crsk."\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
                    echo "<input name=\"op\" id=\"\" type=\"hidden\" value=\"edit\" />";
                    echo "<input name=\"id\" id=\"\" type=\"hidden\" value=\"".intval($crsv['id'])."\" />";
                    echo "</form>\n";
                    echo "<a style=\"cursor: pointer;\" onClick=\"document.getElementById('edit_design_".$crsk."').submit();\">".trim($crsv['describ'])." [ ".trim($crsv['file']).".css ]</a>"; ?></li>
                    <li class="tablecell two"><?php echo $mediaarray[trim($crsv['media'])]." | ".$browserarray[trim($crsv['browser'])]; ?></li>
                    <li class="tablecell two"><?php
                    $cssuse_sql = "SELECT t.`name` AS tname, t.`id` AS tid FROM `r_temp_styles` AS rtc, `templates` AS t WHERE rtc.`templates_id` = t.`id` AND rtc.`stylesheets_id` = ".intval($crsv['id']);
                    $cssuse_res = doSQL($cssuse_sql);
                    if ($cssuse_res['num']>0):
                        foreach ($cssuse_res['set'] AS $curesk => $curesv) {
                            echo "<a href=\"/".$_SESSION['wspvars']['wspbasedir']."/templatesedit.php?op=edit&id=".intval($curesv['tid'])."\">".setUTF8(trim($curesv['tname']))."</a><br />";
                        }
                    else:
                        echo returnIntLang('str no usage', false);
                    endif;
                    ?></li>
                    <li class="tablecell two"><?php
                    echo "<a onClick=\"document.getElementById('edit_design_".$crsk."').submit();\"><span class=\"bubblemessage orange\">".returnIntLang('bubble edit', false)."</span></a> ";
                    if ($cssuse_res['num']==0):
                        echo "<a onclick=\"return confirmDelete('".trim($crsv['describ'])."', ".intval($crsv['id']).");\" ><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>";
                    endif;
                    ?></li>
                </ul>
			<?php } ?>
		<?php endif; ?>
		<?php if($op!="edit"): ?>
			<fieldset id="options" class="options">
				<p><a href="<?php echo $_SERVER['PHP_SELF'] ?>?op=edit&id=0" title="Neue CSS-Datei anlegen" class="greenfield"><?php echo returnIntLang('css createnewcss', false); ?></a></p>
			</fieldset>
		<?php endif; ?>

	</div>
	</fieldset>
	<form name="deletecss" id="deletecss" method="post">
		<input type="hidden" name="op" value="delete" />
		<input type="hidden" name="id" id="deleteid" value="" />
	</form>
	
	
	<?php
	
	endif;
	
	if ($op=="edit" && $id>0):
		$designs_sql = "SELECT `id`, `file`, `describ`, `stylesheet`, `media`, `browser` FROM `stylesheets` WHERE `id` = ".intval($id);
		$designs_res = doSQL($designs_sql);

        if ($designs_res['num']>0):
			$describ = trim($designs_res['set'][0]['describ']);
			$file = trim($designs_res['set'][0]['file']);
			$stylesheet = stripslashes(trim($designs_res['set'][0]['stylesheet']));
			$media = trim($designs_res['set'][0]['media']);
			$browser = trim($designs_res['set'][0]['browser']);
		else:
			$id = 0;
		endif;
	endif;
	
	if ($id==0):
		$describ = '';
		$file ='';
		$stylesheet = '';
		$media = 'all';
		$browser = 'all';
	endif;
	
	?>
	<script language="javascript" type="text/javascript">
		<!--
		function checkFields() {
			if (document.getElementById('describ').value == '') {
				alert('Bitte geben Sie eine Beschreibung f'+String.fromCharCode(252)+'r dieses Design ein.');
				document.getElementById('describ').focus();
				return false;
				}
				
			if (document.getElementById('file').value == '') {
				alert('Bitte geben Sie einen Dateinamen f'+String.fromCharCode(252)+'r dieses Design ein.');
				document.getElementById('file').focus();
				return false;
				}

			return true;
			}	// checkFields()
			
		function pasteTab() {
			var input = document.getElementById('stylesheet');
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
				
  /* fr neuere auf Gecko basierende Browser */
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
  /* fuer die uebrigen Browser */
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
} // pasteTab
	
	function uploadCSS() {
		document.getElementById('editcss').style.display = 'none';
		document.getElementById('uploadcss').style.display = 'block';
		}
	
	function checkFieldsUpload() {
		if (document.getElementById('describupload').value == '') {
			alert('Bitte geben Sie eine Beschreibung f'+String.fromCharCode(252)+'r dieses CSS ein.');
			document.getElementById('describupload').focus();
			return false;
			}
			
		if (document.getElementById('fileupload').value == '') {
			alert('Bitte waehlen Sie eine Datei zum Upload aus.');
			document.getElementById('fileupload').focus();
			return false;
			}

		return true;
		}	// checkFieldsUpload()


$(function() {
	$(".allowTabChar").allowTabChar();
})

	
	//-->
	</script>
	<?php if($op=="edit"): ?>
	<fieldset id="editcss" <?php if(isset($_POST) && array_key_exists('op', $_POST) && ($_POST['op']=="editfolder")): echo "style=\"display: none;\""; endif; ?>>
		<legend><?php if($op=="edit" && $id>0): echo returnIntLang('css editfile'); else: echo returnIntLang('css createfile'); endif; ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formdesign">
		<ul class="tablelist">
			<li class="tablecell two"><?php echo returnIntLang('str description'); ?></li>
			<li class="tablecell two"><input type="text" maxlength="70" name="describ" id="describ" value="<?php echo $describ; ?>" /></li>
			<li class="tablecell two"><?php echo returnIntLang('str filename'); ?></li>
			<li class="tablecell two"><input type="text" maxlength="50" name="file" id="file" value="<?php echo $file; ?>" /> .css</li>
			<li class="tablecell two"><?php echo returnIntLang('css contents'); ?></li>
			<li class="tablecell six"><textarea name="stylesheet" id="stylesheet_area" class="full large allowTabChar" wrap="off"><?php
			$stylerows = explode("\n",$stylesheet);
			for ($r=0;$r<count($stylerows);$r++):
				echo $stylerows[$r]."\n";
			endfor; ?></textarea></li>
			<li class="tablecell two"><?php echo returnIntLang('css mediatype label'); ?></li>
			<li class="tablecell two"><select name="media"><?php
			
			foreach ($mediaarray AS $key => $value):
				echo "<option value=\"".$key."\"";
				if ($media==$key):
					echo " selected=\"selected\"";
				endif;
				echo ">".$value."</option>";
			endforeach;
			
			?></select></li>
			<li class="tablecell two"><?php echo returnIntLang('css browser'); ?></li>
			<li class="tablecell two"><select name="browser"><?php
			
			foreach ($browserarray AS $key => $value):
				echo "<option value=\"".$key."\"";
				if ($browser==$key):
					echo " selected=\"selected\"";
				endif;
				echo ">".$value."</option>";
			endforeach;
			
			?></select></li>
		</ul>
		<input type="hidden" name="op" value="save" /><input type="hidden" name="id" value="<?php echo $id; ?>" />
		</form>
		<fieldset class="options innerfieldset">
			<p> <a href="javascript: document.getElementById('formdesign').submit();" onclick="return checkFields();" class="greenfield"><?php echo returnIntLang('str save', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a></p>
		</fieldset>
	</fieldset>
	<?php endif; ?>
	
	<fieldset id="editclasses" style="display: none;" <?php if(isset($_POST) && array_key_exists('op', $_POST) && ($_POST['op']=="edit" || $_POST['op']=="editfolder")): echo "style=\"display: none;\""; endif; ?>>
		<legend><?php echo returnIntLang('css editclasses'); ?> <?php echo legendOpenerCloser('contentclasses'); ?></legend>
		<div id="contentclasses">
			<p><?php echo returnIntLang('css contentclasses description'); ?></p>
			<form name="saveclasses" id="saveclasses" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
			<ul class="tablelist">
				<li class="tablecell two"><?php echo returnIntLang('css classnames content'); ?></li>
				<li class="tablecell six"><textarea name="contentclasses" class="full medium noresize"></textarea></li>
				<li class="tablecell two"><?php echo returnIntLang('css classnames contentholder'); ?></li>
				<li class="tablecell six"><textarea name="contentholderclasses" class="full medium noresize"></textarea></li>
			</ul>
			</form>
			<fieldset id="options" class="options">
				<p><a onclick="document.getElementById('saveclasses').submit();" class="greenfield"><?php echo returnIntLang('css saveclasses', false); ?></a></p>
			</fieldset>
		</div>
	</fieldset>
	
	<fieldset id="uploadcss" style="display: none;">
		<legend><?php echo returnIntLang('css uploadcssfile'); ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formscriptupload">
		<table class="contenttable">
		<tr>
			<td width="25%"><?php echo returnIntLang('str description'); ?></td>
			<td width="75%"><input type="text" size="50" maxlength="70" name="describupload" id="describupload" value="" style="width: 94%;" /></td>
		</tr>
		<tr>
			<td width="25%"><?php echo returnIntLang('str file'); ?></td>
			<td width="75%"><input type="file" size="30" maxlength="50" name="fileupload" id="fileupload" accept="text/css" style="width: 50%;" /></td>
		</tr>
		</table>
		<fieldset class="options innerfieldset">
			<p><input type="hidden" name="op" value="uploadfile" /><input type="hidden" name="id" value="<?php echo $id; ?>" /><a href="javascript: document.getElementById('formscriptupload').submit();" onclick="return checkFieldsUpload();" class="greenfield"><?php echo returnIntLang('str upload', false); ?></a> <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="orangefield"><?php echo returnIntLang('str cancel', false); ?></a></p>
		</fieldset>
		</form>
	</fieldset>
</div>
<?php
@ include ("data/include/footer.inc.php");
?>
<!-- EOF -->