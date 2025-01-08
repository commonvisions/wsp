<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.11
 * @lastchange 2019-01-06
 * 
 * 2023-01-96
 * 6.11
 * fixed error with less params in call on global content
 * 
 */
session_start();
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='' && isset($_SESSION['wspvars'])):
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php";

if (isset($_POST) && array_key_exists('mid', $_POST) && intval($_POST['mid'])>0):
	// check for selected page if mid is given, otherwise first page of structure
	if (isset($_SESSION['opencontent']) && intval($_SESSION['opencontent'])>0):
		$oc_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".intval($_SESSION['opencontent']);
		$oc_res = doResultSQL($oc_sql);
		if (intval($oc_res)>0):
			$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `mid` = ".intval($oc_res);
		endif;
	elseif (isset($_SESSION['pathmid']) && intval($_SESSION['pathmid'])>0):
		$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `mid` = ".intval($_SESSION['pathmid']);
	else:
		$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `connected` = 0 ORDER BY `level`, `position`";
	endif;
	$fp_res = doSQL($fp_sql);
    if ($fp_res['num']>0):
		$realtemp = getTemplateID(intval($_POST['mid']));
		$templatevars = getTemplateVars($realtemp);
		?>
		<table class="tablelist" id="addcontentlist">
			<tr>
			<td class="tablecell four"><?php echo returnIntLang('contentstructure page', true); ?></td>
			<td class="tablecell four"><select name="mid" id="insertpage" size="1" class="one full" onchange="addContent(this.value, 0);">
				<?php 
				
				// hier sollte noch eine rechteüberprüfung stattfinden, wenn jemand nur menüpunkt und untergeordnet machen kann
				$topmid = 0;
				if ($_SESSION['wspvars']['rights']['contents']==15):
					$topmid = intval($_SESSION['structuremidlist'][0]);
				endif;
				getMenuLevel($topmid, 0, 1, array(intval($_POST['mid'])), $menuallowed); 
				
				?>
			</select></td>
			</tr>
		<?php if (is_array($templatevars) && count($templatevars['contentareas'])>0): ?>
			<tr>
			<td class="tablecell four"><?php echo returnIntLang('contentstructure contentarea', true); ?></td>
			<td class="tablecell four"><select name="carea" id="insertarea" size="1" class="one full" onchange="addContent(document.getElementById('insertpage').value, this.value);"><?php
			
			$siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
			$siteinfo_res = doSQL($siteinfo_sql);
			if ($siteinfo_res['num']>0):
				$contentvardesc = unserializeBroken($siteinfo_res['set'][0]['varvalue']);
			endif;
			
			foreach ($templatevars['contentareas'] AS $carea):
				echo "<option value=".$carea." ";
				if (intval($_POST['carea'])==$carea): echo " selected=\"selected\" "; endif;
				echo ">";
				if (isset($contentvardesc) && is_array($contentvardesc)):
					if (array_key_exists(($carea-1), $contentvardesc) && trim($contentvardesc[($carea-1)])!=''):
						echo $contentvardesc[($carea-1)];
					else:
						echo returnIntLang('contentstructure contentarea', false)." ".$carea."</option>";
					endif;
				else:
					echo returnIntLang('contentstructure contentarea', false)." ".$carea."</option>";
				endif;
			endforeach;
			
			?></select></td>
			</tr>
			<tr>
			<td class="tablecell four"><?php echo returnIntLang('contentstructure paste before', true); ?></td>
			<td class="tablecell four"><select name="posvor" id="posvor" size="1" class="one full"><?php
			
            // select contents ..
			$consel_sql = "SELECT `interpreter_guid`, `globalcontent_id`, `valuefields` FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `trash` = 0 AND `content_area` = ".intval($_POST['carea'])." ORDER BY `position`";
			$consel_res = doSQL($consel_sql);
			foreach ($consel_res['set'] AS $csresk => $csresv) {
				$interpreter_sql = "SELECT `guid`, `name`, `classname` FROM `interpreter` WHERE `guid` = '".trim($csresv['interpreter_guid'])."'";
				$interpreter_res = doSQL($interpreter_sql);
				if ($interpreter_res['num']>0):
					$intname = trim($interpreter_res['set'][0]['name']);
				endif;
				$contentvalue = unserializeBroken($csresv['valuefields']);
				$contentdesc = trim($contentvalue['desc']);
				if ($contentdesc!=""):
					$contentdesc = " - ".$contentdesc;
				endif;
				if (intval($csresv['globalcontent_id'])>0):
					$contentdesc.= "[GLOBAL]";
				endif;
				echo "<option value=\"".($csres+1)."\">".$intname." ".$contentdesc."</option>";
            }
			echo "<option value=\"0\" selected=\"selected\">".returnIntLang('contentstructure paste atend', true)."</option>"; 
			?></select></td>
			</tr>
			<tr>
			<td class="tablecell four"><?php echo returnIntLang('contentstructure new element', true); ?></td>
			<td class="tablecell four"><select name="sid" id="sid" size="1" class="one full">
				<option value="genericwysiwyg"><?php echo returnIntLang('hint generic wysiwyg', false); ?></option>
				<?php
				$interpreter_sql = "SELECT `guid`, `name`, `classname` FROM `interpreter` ORDER BY `classname`, `name`";
				$interpreter_res = doSQL($interpreter_sql);
				if ($interpreter_res['num']>0):
					$classname = "";
					foreach ($interpreter_res['set'] AS $irsk => $irsv) {
						if (trim($irsv["classname"])!=$classname):
							if ($irs>0):
								echo "</optgroup>";
							endif;
							echo "<optgroup label=\"".trim($irsv["classname"])."\">";
							$classname = trim($irsv["classname"]);
						endif;
						echo "<option value=\"".trim($irsv["guid"])."\">".trim($irsv["name"])."</option>\n";
					}
				endif;
			?></select><input type="hidden" name="gcid" id="" value="0" /></td>
			</tr>
			<?php
			
			$gc_sql = "SELECT * FROM `globalcontent` WHERE (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' || `content_lang` = '') AND `trash` = 0 ORDER BY `interpreter_guid`";
			$gc_res = doSQL($gc_sql);
			if ($gc_res['num']>0):
				?>
				<tr>
				<td class="tablecell four"><?php echo returnIntLang('contentstructure global content', true); ?></td>
				<td class="tablecell four"><select name="gcid" id="gcid" size="1" class="one full">
						<option value="0"><?php echo returnIntLang('contentstructure choose globalcontent', false); ?></option>
						<?php
                    
                        foreach ($gc_res['set'] AS $gcrsk => $gcrsv) {
							
							$fieldvalue = unserializeBroken(trim($gcrsv["valuefield"]));
							
							$i_sql = "SELECT `parsefile`, `name` FROM `interpreter` WHERE `guid` = '".escapeSQL(trim($gcrsv["interpreter_guid"]))."'";
							$i_res = doSQL($i_sql);
							if ($i_res['num']>0):
								$file = trim($i_res['set'][0]["parsefile"]);
								$name = trim($i_res['set'][0]["name"]);
								if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file)):
                                    include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file;
                                    $clsInterpreter = new $interpreterClass;
                                    echo "<option value=\"".intval($gcrsv["id"])."\">".$name;
                                    $desc = $clsInterpreter->getView($fieldvalue, null, null);
                                    if (trim($desc)!=''):
                                        echo " - ".trim($desc);
                                    endif;
                                    echo "</option>";
                                    $clsInterpreter->closeInterpreterDB();
                                endif;
							else:
								echo "<option value=\"".intval($gcrsv["id"])."\">".returnIntLang('hint generic wysiwyg', false);
								echo " - ".$fieldvalue['desc'];
								echo "</option>";
							endif;
						}
						
						?>
					</select></td>
				</tr>
			<?php endif; ?>
			</table>
		<input type="hidden" name="op" value="add" />
		<input type="hidden" name="lang" value="<?php echo $_SESSION['wspvars']['workspacelang']; ?>" />
		<fieldset class="innerfieldset options"><p><a href="#" onclick="checkData();" class="greenfield"><?php echo returnIntLang('str create', false); ?></a></p></fieldset>
	<?php else: ?>
		<p><?php echo returnIntLang('contentstructure this menupoint has no template with contentvars defined'); ?></p>
	<?php endif; ?>
	<?php endif;
	endif;
else:
	echo "timeout|false";
endif;

// EOF ?>