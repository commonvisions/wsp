<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version 6.8
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

	require $wspdir.'/data/include/globalvars.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/filesystemfuncs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";
	require $wspdir."/data/include/clsinterpreter.inc.php";

	if (intval($_POST['mid'] ?? 0)>0):
		// check for selected page if mid is given, otherwise first page of structure
		
		// deprecated until wsp 7
		/*
		if (isset($_SESSION['opencontent']) && intval($_SESSION['opencontent'])>0):
			$oc_sql = "SELECT `mid` FROM `content` WHERE `cid` = ".$_SESSION['opencontent'];
			$oc_res = mysql_query($oc_sql);
			if ($oc_res):
				$oc_num = mysql_num_rows($oc_res);
			endif;
			if ($oc_num>0):
				$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `mid` = ".intval(mysql_result($oc_res, 0));
			endif;
		elseif (isset($_SESSION['pathmid']) && intval($_SESSION['pathmid'])>0):
			$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `mid` = ".intval($_SESSION['pathmid']);
		else:
			$fp_sql = "SELECT `mid`, `templates_id` FROM `menu` WHERE `connected` = 0 ORDER BY `level`, `position`";
		endif;
		$fp_res = mysql_query($fp_sql);
		if ($fp_res):
			$fp_num = mysql_num_rows($fp_res);
		endif;
		if ($fp_num>0):
			$realtemp = getTemplateID(intval($_POST['mid']));
			$templatevars = getTemplateVars($realtemp);
			
			if (is_array($templatevars) && count($templatevars['contentareas'])>0): ?>
			
			<ul class="tablelist" id="addcontentlist">
				<li class="tablecell two"><?php echo returnIntLang('contentstructure contentarea', true); ?></li>
				<li class="tablecell two"><select name="carea" id="insertarea" size="1" class="one full" onchange="addContentOnPage(this.value);"><?php
				
				$siteinfo_num = 0;
				$siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
				$siteinfo_res = mysql_query($siteinfo_sql);
				if ($siteinfo_res):
					$siteinfo_num = mysql_num_rows($siteinfo_res);
				endif;
				if ($siteinfo_num>0):
					$contentvardesc = unserializeBroken(mysql_result($siteinfo_res, 0));
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
				
				?></select></li>
				<li class="tablecell two"><?php echo returnIntLang('contentstructure paste before', true); ?></li>
				<li class="tablecell two"><select name="posvor" id="posvor" size="1" class="one full"><?php
				// select contents ..
				$consel_sql = "SELECT `interpreter_guid`, `globalcontent_id`, `valuefields` FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `content_area` = ".intval($_POST['carea'])." AND (`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' OR `content_lang` = '') AND `trash` = 0 ORDER BY `position`";
				$consel_res = mysql_query($consel_sql);
				if ($consel_res):
					$consel_num = mysql_num_rows($consel_res);
				endif;
				for ($csres=0; $csres<$consel_num; $csres++):
					$interpreter_sql = "SELECT `guid`, `name`, `classname` FROM `interpreter` WHERE `guid` = '".mysql_result($consel_res, $csres, 'interpreter_guid')."'";
					$interpreter_res = mysql_query($interpreter_sql);
					if ($interpreter_res):
						$interpreter_num = mysql_num_rows($interpreter_res);
					endif;
					if ($interpreter_num>0):
						$intname = mysql_result($interpreter_res, 0, 'name');
					endif;
					$contentvalue = unserializeBroken(mysql_result($consel_res, $csres, 'valuefields'));
					$contentdesc = trim($contentvalue['desc']);
					if ($contentdesc!=""):
						$contentdesc = "- ".$contentdesc;
					endif;
					if (mysql_result($consel_res, $csres, 'globalcontent_id')>0):
						$contentdesc.= "[GlobalContent]";
					endif;
					echo "<option value=\"".($csres+1)."\">".$intname." ".$contentdesc."</option>";
				endfor;
				echo "<option value=\"0\" selected=\"selected\">".returnIntLang('contentstructure paste atend', true)."</option>"; 
				?></select></li>
				<li class="tablecell two"><?php echo returnIntLang('contentstructure new element', true); ?></li>
				<li class="tablecell two"><select name="sid" id="sid" size="1" class="one full">
					<option value="genericwysiwyg"><?php echo returnIntLang('hint generic wysiwyg', false); ?></option>
					<?php
					$interpreter_sql = "SELECT `guid`, `name`, `classname` FROM `interpreter` ORDER BY `classname`, `name`";
					$interpreter_res = mysql_query($interpreter_sql);
					if ($interpreter_res):
						$interpreter_num = mysql_num_rows($interpreter_res);
					endif;
					if ($interpreter_num>0):
						$classname = "";
						for ($irs=0;$irs<$interpreter_num;$irs++):
							if (mysql_result($interpreter_res,$irs,"classname")!=$classname):
								if ($irs>0):
									echo "</optgroup>";
								endif;
								echo "<optgroup label=\"".mysql_result($interpreter_res,$irs,"classname")."\">";
								$classname = mysql_result($interpreter_res,$irs,"classname");
							endif;
							echo "<option value=\"".mysql_result($interpreter_res,$irs,"guid")."\">".mysql_result($interpreter_res,$irs,"name")."</option>\n";
						endfor;
					endif;
				?></select><input type="hidden" name="gcid" id="" value="0" /></li>
				<?php
				$globalcontents_sql = "SELECT gc.`id`, gc.`interpreter_guid`, gc.`valuefield`, gc.`content_lang`, i.`parsefile`, i.`name` FROM `globalcontent` gc, `interpreter` i WHERE gc.`interpreter_guid`=i.`guid` AND (gc.`content_lang` = '".$_SESSION['wspvars']['workspacelang']."' || gc.`content_lang` = '')";
				$globalcontents_res = mysql_query($globalcontents_sql);
				if ($globalcontents_res):
					$globalcontents_num = mysql_num_rows($globalcontents_res);
				endif;
				if ($globalcontents_num>0):
					?>
					<li class="tablecell two"><?php echo returnIntLang('contentstructure global content', true); ?></li>
					<li class="tablecell two"><select name="gcid" id="gcid" size="1" class="one full">
							<option value="0"><?php echo returnIntLang('contentstructure choose globalcontent', false); ?></option>
							<?php
							for ($gres=0;$gres<$globalcontents_num;$gres++):
								$file = mysql_result($globalcontents_res,$gres,"parsefile");
								$name = mysql_result($globalcontents_res,$gres,"name");
								include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file;
								$fieldvalue = unserializeBroken(mysql_result($globalcontents_res,$gres,"valuefield"));
								$fieldvaluestyle = "array";
								$clsInterpreter = new $interpreterClass;
								$clsInterpreter->dbCon = $wspvars['dbcon'];
								echo "<option value=\"".mysql_result($globalcontents_res,$gres,"id")."\">".mysql_result($globalcontents_res,$gres,"name");
								if (mysql_result($globalcontents_res,$gres,"content_lang")!=""):
									echo " [".mysql_result($globalcontents_res,$gres,"content_lang")."] ";
								endif;
								echo " - ".$clsInterpreter->getView($fieldvalue, $fieldvaluestyle);
								echo "</option>";
								$clsInterpreter->closeInterpreterDB();
							
							endfor;
							
							?>
						</select></li>
				<?php endif; ?>
					<li class="tablecell two"><?php echo returnIntLang('contentedit add new content desc'); ?></li>
					<li class="tablecell two"><input type="text" class="two" /></li>
				</ul>
				<input type="hidden" name="op" value="add" />
				<input type="hidden" id="mid" name="mid" value="<?php echo intval($_POST['mid']); ?>" />
				<input type="hidden" id="carea" name="carea" value="<?php echo intval($_POST['carea']) ?>" />
			<fieldset class="options">
				<p><a href="#" onclick="$('#newcontent').toggle('blind', 500); return false;" class="redfield">Abbrechen</a> <a href="#" onclick="checkNewData();" class="greenfield">Erstellen</a></p>
			</fieldset>
		<?php else: ?>
			<p><?php echo returnIntLang('contentstructure this menupoint has no template with contentvars defined'); ?></p>
		<?php endif; ?>
		<?php endif;
		endif;
		*/
		
	endif;
endif;
