<?php
/**
 * Verwaltung von Globalen Inhalten
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-11-11
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

/* define actual system position ------------- */
$_SESSION['wspvars']['menuposition'] = "contentedit";
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['preventleave'] = true;
/* second includes --------------------------- */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/checkuser.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php");
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php");
/* page specific includes */
require ($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php");
/* define page specific vars ----------------- */
$worklang = unserialize($_SESSION['wspvars']['sitelanguages']);
/* define page specific functions ------------ */

if (isset($_POST['op']) && $_POST['op']=='save'):
	// insert new global content
	$sql = "INSERT INTO `content_global` SET `interpreter_guid` = '".escapeSQL($_POST['sid'])."', `content_lang` = '".$_SESSION['wspvars']['workspacelang']."'";
	$ins = doSQL($sql);
	$_SESSION['wspvars']['editglobalcontentid'] = intval($ins['inf']);
	header('location: globalcontentedit.php');
endif;

if (isset($_POST['op']) && $_POST['op']=='delete' && isset($_POST['gcid']) && intval($_POST['gcid'])>0):
	if (intval($_POST['gcid'])>0):
		// update contentchange to menupoints
		$menu_sql = "UPDATE `menu` AS `m`, `content` AS `c` SET `m`.`contentchanged` = 1 WHERE `c`.`mid` = `m`.`mid` AND `c`.`globalcontent_id` = ".intval($_POST['gcid']);
		$menu_res = doSQL($menu_sql);
		$affectedmid = $menu_res['aff'];
		// delete global contents from content table by given id
		$upd_sql = "UPDATE `content` SET `trash` = 1 WHERE `globalcontent_id` = ".intval($_POST['gcid']);
		$upd_res = doSQL($upd_sql);
        if ($upd_res['aff']):
			if ($affectedmid>0):
				addWSPMsg('resultmsg', '<p>'.returnIntLang('globalcontent deleted from contents1', true).' '.$affectedmid.' '.returnIntLang('globalcontent deleted from contents2', true).'</p>');
			else:
				addWSPMsg('resultmsg', '<p>'.returnIntLang('globalcontent deleted from contents no affected', true).'</p>');
			endif;
		else:
			addWSPMsg('errormsg', '<p>'.returnIntLang('globalcontent not deleted from contents', true).'</p>');
		endif;
		// delete global contents from global content table by given id
		$trash_sql = "UPDATE `content_global` SET `trash` = 1 WHERE `id` = ".intval($_POST['gcid']);
        $trash_res = doSQL($trash_sql);
		if ($trash_res['aff']):
			addWSPMsg('resultmsg', '<p>'.returnIntLang('globalcontent deleted from globalcontents', true).'</p>');
		else:
			addWSPMsg('errormsg', '<p>'.returnIntLang('globalcontent not deleted from globalcontents', true).'</p>');
		endif;
	endif;
endif;

// head der datei

include ("data/include/header.inc.php");
include ("data/include/wspmenu.inc.php");
?>
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
		</select><input type="hidden" name="openmid" id="langopenmid" value="<?php echo $openpath; ?>">
		</form>
		<?php
	endif;
	?><h1><?php echo returnIntLang('globalcontent headline', true); ?></h1></fieldset>
	<fieldset>
		<legend><?php echo returnIntLang('str legend', true); ?> <?php echo legendOpenerCloser('wsplegend'); ?></legend>
		<div id="wsplegend">
			<p><?php echo returnIntLang('globalcontent legend', true); ?></p>
		</div>
	</fieldset>
	<?php
	
	$globalcontents_sql = "SELECT * FROM `content_global` WHERE `trash` = 0 AND (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' || `content_lang` = '') ORDER BY `interpreter_guid`";
	$globalcontents_res = doSQL($globalcontents_sql);

    if ($globalcontents_res['num']>0):
		?>
		<fieldset>
			<legend><?php echo returnIntLang('globalcontent existing contents for lang', true); ?> "<?php echo $_SESSION['wspvars']['workspacelang']; ?>" <?php echo legendOpenerCloser('globalexists'); ?></legend>
            <div id="globalexists">
				<ul class="tablelist">
					<li class="tablecell eight head"><?php echo returnIntLang('globalcontent headline', true); ?></li>
				</ul>
				<script language="JavaScript" type="text/javascript">
				<!--
				
				function showUsage(usageid) {
					$('#usage_' + usageid).toggle('blind');
					}
				
				function delGlobalContent(gcid) {
					if (confirm('<?php echo returnIntLang('globalcontent confirm delete', false); ?>')) {
						document.getElementById('opdelete').value = 'delete';
						document.getElementById('iddelete').value = gcid;
						document.getElementById('deleteglobal').submit();
						}
					}
				
				function editGlobalContent(gcid) {
					document.getElementById('opedit').value = 'edit';
					document.getElementById('idedit').value = gcid;
					document.getElementById('editglobal').submit();
					}
				
				// -->
				</script>
				<?php
				$class="";
                foreach ($globalcontents_res['set'] AS $gcresk => $gcresv) {
					
					$interpreter_sql = "SELECT * FROM `interpreter` WHERE `guid` = '".escapeSQL($gcresv['interpreter_guid'])."'";
					$interpreter_res = doSQL($interpreter_sql);
					if ($interpreter_res['num']>0) {
						$file = trim($interpreter_res['set'][0]["parsefile"]);
						$name = trim($interpreter_res['set'][0]["name"]);
					}
                    else {
						$file = 'genericwysiwyg';
						$name = returnIntLang('hint generic wysiwyg', false);
					}
					$guid = trim($gcresv['interpreter_guid']);
					$fieldvalue = unserializeBroken($gcresv['valuefields']);
                    $interpreterdesc = returnIntLang('globalcontent interpreter desc not found');
					// Interpreter einlesen
					if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file)) {
						require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file;
						$clsInterpreter = new $interpreterClass;
						$clsInterpreter->dbCon = $_SESSION['wspvars']['dbcon'];
						if (method_exists($clsinterpreter, 'getView')) {
                            $interpreterdesc = $name." » ".$clsInterpreter->getView($fieldvalue, 0, 0);
                        } else {
                            if (trim($fieldvalue['desc'])!='') {
                                $interpreterdesc = $name." » ".$fieldvalue['desc'];
                            }
                            else {
                                $interpreterdesc = $name;
                            }
                        }
                        if (method_exists($clsinterpreter, 'closeInterpreterDB')) {
                            $clsInterpreter->closeInterpreterDB();
                        }
                    }
					else if ($file=='genericwysiwyg') {
						// genericwysiwyg
						if (trim($fieldvalue['desc'])!='') {
							$interpreterdesc = $name." » ".$fieldvalue['desc'];
						} else {
                            $interpreterdesc = $name;
                        }
					}
                    else {
						addWSPMsg('errormsg', '<p>'.returnIntLang('globalcontent could not read parser file for interpreter', true).' '.$name.'</p>');
					}
					// getting contents
					// jetzt mit zusätzlicher Überprüfung ob die Contents nicht gelöscht sind
					$gcusage_sql = "SELECT m.`description` AS `menudesc`, m.`mid` AS `mid`, c.`cid` AS `cid` FROM `content` AS c, `menu` AS m WHERE c.`globalcontent_id` = ".intval($gcresv['id'])." AND c.`mid` = m.`mid` AND c.`trash`=0 AND m.`trash`=0 GROUP BY c.`mid`";
					$gcusage_res = doSQL($gcusage_sql);
					$gcusage_num = $gcusage_res['num'];
					
					$gctemplate_sql = "SELECT `name` FROM `templates` WHERE `template` LIKE '%[\%GLOBALCONTENT:".intval($gcresv['id'])."\%]%' GROUP BY `name`";
					$gctemplate_res = doSQL($gctemplate_sql);
					$gctemplate_num = $gctemplate_res['num'];
					
					echo "<ul class=\"tablelist\">";
					echo "<li class=\"".$class." tablecell seven\"><a onclick=\"editGlobalContent(".intval($gcresv['id']).");\" style=\"cursor: pointer;\">".$interpreterdesc."</a> [".(intval($gcusage_num)+intval($gctemplate_num))." ".returnIntLang('globalcontent usages', true)."]</li>";
					
					if ($gcusage_num>0):
						echo "<li class=\"".$class." tablecell one\"><a onclick=\"showUsage(".$gcresk.");\"><span class=\"bubblemessage\">".returnIntLang('bubble usage', false)."</span></a> &nbsp;</li>";
					else:
						echo "<li class=\"".$class." tablecell one\"><a onclick=\"editGlobalContent(".intval($gcresv['id']).");\" style=\"cursor: pointer;\"><span class=\"bubblemessage orange\">".returnIntLang('bubble edit', false)."</span></a> <a onclick=\"delGlobalContent(".intval($gcresv['id']).");\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a></li>";
					endif;
					echo "</ul>";
					
					if ($gcusage_num>0) {
						echo "<ul id=\"usage_".$gcresk."\" class=\"tablelist ".$class."\" style=\"display: none;\">";
						foreach ($gcusage_res['set'] AS $gcuresk => $gcuresv) {
							// sgc = show global content
							echo "<li class=\"tablecell eight\"><table class=\"contenttable noborder\"><tr class=\"".$class." nextbordered bottom\"><td><a href=\"/".$wspvars['wspbasedir']."/contentstructure.php?sgc=".intval($gcuresv["mid"])."\">".trim($gcuresv["menudesc"])."</a></td></tr></table></li>";
                        }
						echo "</ul>";
                    }
                }

                ?>
                <form name="deleteglobal" id="deleteglobal" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <input type="hidden" name="op" id="opdelete" value="" /><input type="hidden" name="gcid" id="iddelete" value="" />
                </form>
                <form name="editglobal" id="editglobal" method="post" action="globalcontentedit.php">
                <input type="hidden" name="op" id="opedit" value="" /><input type="hidden" name="gcid" id="idedit" value="" />
                </form>
            </div>
		</fieldset>
	<?php endif; ?>
	<fieldset id="newglobalcontent">
		<legend><?php echo returnIntLang('globalcontent createnew', true); ?></legend>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formnewglobalcontent">
			<input name="op" id="op" type="hidden" value="save" />
			<ul class="tablelist">
				<li class="tablecell two"><?php echo returnIntLang('str interpreter', true); ?></li>
				<li class="tablecell six"><select name="sid" id="sid" size="1" class="six">
					<option value="genericwysiwyg"><?php echo returnIntLang('hint generic wysiwyg', false); ?></option>
					<?php
					$interpreter_sql = "SELECT `guid`, `name`, `classname` FROM `interpreter` ORDER BY `classname`, `name`";
					$interpreter_res = doSQL($interpreter_sql);
					if ($interpreter_res['num']>0) {
						$classname = "";
                        foreach ($interpreter_res['set'] AS $irsk => $irsv) {
							if ($irsv["classname"]!=$classname) {
								if ($irsk > 0) { echo "</optgroup>"; }
								echo "<optgroup label=\"" . $irsv["classname"] . "\">";
								$classname = $irsv["classname"];
                            }
							echo "<option value=\"".$irsv["guid"]."\">".$irsv["name"]."</option>\n";
                        }
                    }
					?>
				</select></li>
			</ul>
			<fieldset class="options innerfieldset"><p><a href="#" onclick="if (document.getElementById('sid').value == 0) { alert(unescape('<?php echo returnIntLang('hint choose interpreter', false); ?>')); } else { document.getElementById('formnewglobalcontent').submit(); } return false;" class="greenfield"><?php echo returnIntLang('str create', true); ?></a></p></fieldset>
		</form>
	</fieldset>
</div>
<?php @ include ("data/include/footer.inc.php"); ?>
<!-- EOF -->