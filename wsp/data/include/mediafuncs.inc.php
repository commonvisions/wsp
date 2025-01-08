<?php
/**
 * functions, managing media folders
 * @author cms@covi.de
 * @copyright (c) 2022, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.11
 * @lastchange 2022-10-25
 */

if((isset($_GET['func']) && $_GET['func']=="") && (isset($_POST['func']) && $_POST['func']!="")):
	$_GET['func'] = $_POST['func'];
endif;

if (!(function_exists('DeleteFolder'))):
function DeleteFolder($path) {
	if($path!="" && substr($path,0,7)=="/media/"):
		if (is_dir($_SERVER['DOCUMENT_ROOT'].$path)):
			$dir = opendir ($_SERVER['DOCUMENT_ROOT'].$path);
		    while ($entry = readdir($dir)) {
		        if ($entry == '.' || $entry == '..') continue;
		        if (is_dir ("..".$path.'/'.$entry)) {
		           DeleteFolder ($path.'/'.$entry);
		
		        } else if (is_file ("..".$path.'/'.$entry) || is_link ("..".$path.'/'.$entry)) {
		            ftpDeleteFile($GLOBALS['wspvars']['ftpbasedir'].$path.'/'.$entry);
		            // Fehler?
					}
		    }
		    closedir ($dir);
		    ftpDeleteDir($GLOBALS['wspvars']['ftpbasedir'].$path);
		endif;
    endif;
	}
endif;

if (isset($opm) && $opm == 'deletefolder'):
	if($_GET['delpath']!="" && substr($_GET['delpath'],0,7)=="/media/"):
		DeleteFolder($_GET['delpath']);
	endif;
	if($_GET['delthumbpath']!="" && substr($_GET['delthumbpath'],0,7)=="/media/"):
		DeleteFolder($_GET['delthumbpath']);
	endif;
endif;

if(isset($opm) && $opm=="switchdir"):
	$basedir=$GLOBALS['wspvars']['ftpbasedir']."/media/".$_POST['mediafolder'].$_POST['switchpath'];
	$destdir=$GLOBALS['wspvars']['ftpbasedir']."/media/".$_POST['mediafolder'].$_POST['menu_switchdir'].substr($_POST['switchpath'],strrpos($_POST['switchpath'],"/"),strlen($_POST['switchpath']));
	$ftpswd = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
	$login = ftp_login($ftpswd, $wspvars['ftpuser'], $wspvars['ftppass']);
	ftp_rename($ftpswd, $basedir, $destdir);
	ftp_close($ftpswd);
endif;

if(isset($opm) && $opm=="switchfile"):
	$errormsg = "<p>Noch nicht implementiert.</p>";
endif;

if(isset($opm) && $opm=="changefile"):
	$basename = $_POST['renfilepath'].$_POST['oldfilename'];
	$destname = $_POST['renfilepath'].str_replace(" ","",$_POST['newfilename']).substr($_POST['oldfilename'],strrpos($_POST['oldfilename'],"."));
	/* ftp-functions */
	$ftpswd = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
	$login = ftp_login($ftpswd, $wspvars['ftpuser'], $wspvars['ftppass']);
	if ($login):
		@ftp_rename($ftpswd, $GLOBALS['wspvars']['ftpbasedir'].$basename, $GLOBALS['wspvars']['ftpbasedir'].$destname);
		ftp_close($ftpswd);
		$noticemsg = "<p>Die Datei wurde umbenannt.</p>";
	endif;
	// update mediadesc table
	$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '".str_replace("//", "/",str_replace("//", "/", $_POST['renfilepath']."/".$_POST['oldfilename']))."'";
	$desc_res = doSQL($desc_sql);
	if ($desc_res['num']>0):
		$sql = "UPDATE `mediadesc` SET `mediafile` = '".str_replace("//", "/", str_replace("//", "/", $_POST['renfilepath'].str_replace(" ","",$_POST['newfilename']).substr($_POST['oldfilename'],strrpos($_POST['oldfilename'],"."))))."' WHERE `mediafile` LIKE '".str_replace("//", "/", str_replace("//", "/", $_POST['renfilepath']."/".$_POST['oldfilename']))."'";
		doSQL($sql);
	endif;
endif;

if(isset($opm) && $opm=="setdesc"):
	$desc_sql = "SELECT `filedesc` FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", $_POST['renfilepath']."/".$_POST['oldfilename'])."%'";
	$desc_res = doSQL($desc_sql);
	if ($desc_res['num']>0):
		$olddesc = trim($desc_res['set'][0]['filedesc']);
		$sql = "UPDATE `mediadesc` SET `filedesc` = '".escapeSQL($_POST['newdesc'])."' WHERE `mediafile` LIKE '%".str_replace("//", "/", $_POST['renfilepath']."/".$_POST['oldfilename'])."%'";
	else:
		$sql = "INSERT INTO `mediadesc` SET `filedesc` = '".escapeSQL($_POST['newdesc'])."', `mediafile` = '".str_replace("//", "/", $_POST['renfilepath']."/".$_POST['oldfilename'])."'";
	endif;
	if (doSQL($sql)['res'] && $olddesc!=$_POST['newdesc']):
		$noticemsg .= "<p>Der Datei wurde eine Beschreibung zugewiesen.</p>";
	endif;
endif;

if (isset($opm) && $opm=='changedir'):
	function searchChild($parent){
		$sql = "SELECT * FROM `menu` WHERE `connected` = ".intval($parent);
		$res = doSQL($sql);
		if($res['num']>0):
			foreach ($res['set'] AS $rsk => $rsv):
				if(intval($rsv['templates_id'])==0):
					doSQL("UPDATE `menu` SET `contentchanged` = 1 WHERE `mid` = ".intval($rsv["mid"]));
					searchChild(intval($rsv["mid"]));
	 			endif;
	 		endforeach;
	 	endif;
	 	}

	if(trim(removeSpecialChar($_POST['newdirname']))!=""){
		$ftpnewd = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
		$login = ftp_login($ftpnewd, $GLOBALS['wspvars']['ftpuser'], $wspvars['ftppass']);
		ftp_rename($ftpnewd,$GLOBALS['wspvars']['ftpbasedir'].$_POST['renpath'], $GLOBALS['wspvars']['ftpbasedir'].substr($_POST['renpath'],0,strrpos($_POST['renpath'],"/"))."/".removeSpecialChar($_POST['newdirname']));
		ftp_close($ftpnewd);

		//menu
		$sqlmenu = doSQL("SELECT `mid`, `header_img`, `imageon`, `imageoff` FROM `menu`");
		foreach ($sqlmenu['set'] AS $smk => $smv) {
			if (strstr(trim($smv["header_img"]),$_POST['renpath'])) {
				$string = str_replace(basename($_POST['renpath']),removeSpecialChar($_POST['newdirname']),trim($smv["header_img"]));
				doSQL("UPDATE `menu` SET `header_img` = '".escapeSQL($string)."', `contentchanged` = 1 WHERE `mid` = ".intval($smv["mid"]));
                doSQL("UPDATE `end_menu` SET `header_img` = '".escapeSQL($string)."', `contentchanged` = 1 WHERE `mid` = ".intval($smv["mid"]));
			}
			if (strstr(trim($smv["imageon"]),$_POST['renpath'])) {
				$string = str_replace(basename($_POST['renpath']),removeSpecialChar($_POST['newdirname']),trim($smv["imageon"]));
				doSQL("UPDATE `menu` SET `imageon` = '".escapeSQL($string)."', `contentchanged` = 1 WHERE `mid` = ".intval($smv["mid"]));
                doSQL("UPDATE `end_menu` SET `imageon` = '".escapeSQL($string)."', `contentchanged` = 1 WHERE `mid` = ".intval($smv["mid"]));
			}
			if (strstr(trim($smv["imageoff"]),$_POST['renpath'])) {
				$string= str_replace(basename($_POST['renpath']),removeSpecialChar($_POST['newdirname']),trim($smv["imageoff"]));
				doSQL("UPDATE `menu` SET `imageoff` = '".escapeSQL($string)."', `contentchanged` = 1 WHERE `mid` = ".intval($smv["mid"]));
                doSQL("UPDATE `end_menu` SET `imageoff` = '".escapeSQL($string)."', `contentchanged` = 1 WHERE `mid` = ".intval($smv["mid"]));
			}
		}
		//content
		$sqlcontent = doSQL("SELECT `cid`, `mid`, `valuefields` FROM `content`");
		foreach ($sqlcontent['set'] AS $smk => $smv) {
			if (strstr(trim($smv["valuefields"]),$_POST['renpath'])) {
                $string= str_replace(basename($_POST['renpath']), removeSpecialChar($_POST['newdirname']),trim($smv["valuefields"]));
				doSQL("UPDATE `content` SET `valuefields` = '".escapeSQL($string)."' WHERE `cid` = ".intval($smv["cid"]));
				doSQL("UPDATE `menu` SET `contentchanged` = 1 WHERE `mid` = ".intval($smv["mid"]));
			}
		}
		//selfvars
		$sqlselvars = doSQL("SELECT `id`,`selfvar` FROM `selfvars`");
		foreach ($sqlselvars['set'] AS $smk => $smv){
			if(strstr(trim($smv["selfvar"]),$_POST['renpath'])){
                $string= str_replace(basename($_POST['renpath']), removeSpecialChar($_POST['newdirname']), trim($smv["selfvar"]));
				doSQL("UPDATE `selfvars` SET `selfvar` = '".escapeSQL($string)."' WHERE `id` = ".intval($smv["id"]));
            }
		}
		
		//Templates
		$sqltemplate = doSQL("SELECT `id`,`template`,`bodytag` FROM `templates`");
		foreach ($sqltemplate['set'] AS $smk => $smv) {
			if (strstr(trim($smv["template"]), $_POST['renpath'])):
				$string = str_replace(basename($_POST['renpath']), removeSpecialChar($_POST['newdirname']), trim($smv["template"]));
				doSQL("UPDATE `templates` SET `template` = '".escapeSQL($string)."' WHERE `id` = ".intval($smv["id"]));
				// find ALL menupoints using that template
                $tmid = getMIDusingTemplate(intval($smv["id"]));
                if (is_array($tmid) && count($tmid)>0) {
                    foreach ($tmid AS $mid) {
                        doSQL("UPDATE `menu` SET `contentchanged` = 1 WHERE `mid` = ".intval($mid));
                    }
                }
			endif;

			if (strstr(trim($smv["bodytag"]),$_POST['renpath'])){
				$string = str_replace(basename($_POST['renpath']), removeSpecialChar($_POST['newdirname']), trim($smv["bodytag"]));
				doSQL("UPDATE `templates` SET `bodytag` = '".escapeSQL($string)."' WHERE `id` = ".intval($smv["id"]));
				// find ALL menupoints using that template
                $tmid = getMIDusingTemplate(intval($smv["id"]));
                if (is_array($tmid) && count($tmid)>0) {
                    foreach ($tmid AS $mid) {
                        doSQL("UPDATE `menu` SET `contentchanged` = 1 WHERE `mid` = ".intval($mid));
                    }
                }
			}
		}
		
		//golbalcontent
		$sqlglobalcontent = doSQL("SELECT `id`, `valuefield` FROM `globalcontent`");
		foreach ($sqlglobalcontent['set'] AS $smk => $smv) {
			if (strstr(trim($smv["valuefield"]),$_POST['renpath'])) {
				$string = str_replace(basename($_POST['renpath']), removeSpecialChar($_POST['newdirname']), trim($smv["valuefield"]));
				doSQL("UPDATE `globalcontent` SET `valuefield` = '".escapeSQL($string)."' WHERE `id` = ".intval($smv["id"]));
			}
		}
		
	}
endif;

// $path
// $mediafolder
// $extern
// $style => 0 = fieldset | 2 = dropdown | 1 = hidden
// $search => 0 = fieldset | 1 = hidden
// $filelist => 0 = fieldset | 2 = dropdown | 1 = hidden

if (!(function_exists("listDir"))):
function listDir($path = '', $mediafolder = '', $extern = 0, $style = 0, $forbidden = array('thumbs')) {
	if ($mediafolder==""):
		$showdir = @dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/media/".$path)));
	else:
		$showdir = @dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/media/".$mediafolder."/".$path)));
	endif;
	// security function to prevent full directory requests 
	$path = str_replace("//","/",str_replace("//","/",str_replace("//","/",str_replace(".","",$path))));
	
	if ($showdir==false):
		echo "<fieldset class=\"errormsg\">Das von Ihnen gew&auml;hlte Verzeichnis ist nicht vorhanden.</fieldset>";
	else:
		$directory = array();
		while (false !== ($folder = $showdir->read())):
			if (substr($folder, 0, 1) != '.'):
				if (is_dir($_SERVER['DOCUMENT_ROOT']."/media/".$mediafolder."/".$path."/".$folder) && (!(in_array($folder, $forbidden)))):
					if ($GLOBALS['mediafolders']!="" && ($path=="" || $path=="/")):
						if (in_array($folder, $GLOBALS['mediafolders'])):
							$directory[] = $folder;
						endif;
					else:
						$directory[] = $folder;
					endif;
				endif;
			endif;
		endwhile;
		sort($directory);
		
		if ($style==2):

			echo "<fieldset id=\"fieldset_listdir\" class=\"text\">\n";
			echo "<legend>Verzeichnisse im Pfad ";
			$pathinfo = "/media/".$mediafolder;
			if ($path=="" && $mediafolder!=""):
				$pathinfo.= "/";
			else:
				$pathinfo.= $path;
			endif;
			echo str_replace("//","/",$pathinfo);
			echo "</legend>\n";
			echo "<select style=\"width: 98%;\" name=\"path\" onchange=\"document.getElementById('listdir_changedir_form_path_value').value = this.value; document.getElementById('listdir_changedir_form').submit();\">\n";
			echo "<option>Bitte w&auml;hlen</option>\n";
			if ($path!="" && $path!="/"):
				echo "<option value=\"/\">Nach oben</option>\n";
				echo "<option value=\"".(substr($path, 0, strrpos(substr($path, 0, -1), "/")))."/\">Eine Ebene nach oben</option>\n";
			endif;
			foreach ($directory AS $key => $value):
				echo "<option value=\"/".str_replace("//", "/", $path.$value)."/\">".$value."</option>\n";
			endforeach;
			echo "</select>\n";
			echo "</fieldset>\n";
		else:
			if (count($directory)>0 || ($path!="" && $path!="/")):
				echo  "<script language=\"javascript\" type=\"text/javascript\">";
				echo "function fillswitch(id,media){
					  while (document.getElementById('menu_switchdir').childNodes.length>1) {
						  document.getElementById('menu_switchdir').removeChild(document.getElementById('menu_switchdir').childNodes[1]);
				      }
		 			  xajax_filllist(id,media);	
		 			  }";
				echo  "</script>";
				
				echo "<fieldset id=\"fieldset_listdir\" class=\"text\">\n";
				if ($path!="" || $mediafolder==""):
					$viewstatus = "open";
				endif;
				echo "<legend>Verzeichnisse im Pfad ";
				$pathinfo = "/media/".$mediafolder;
				if ($path=="" && $mediafolder!=""):
					$pathinfo.= "/";
				else:
					$pathinfo.= $path;
				endif;
				
				echo str_replace("//","/",$pathinfo);
				
				echo " ".showOpenerCloser('dirinfo_content',$viewstatus)."</legend>";
				echo "<span id=\"dirinfo_content\">";
				echo "<p>";
				
				if (intval($extern)>0):
					$linkExtern = '&extern='.intval($extern);
				else:
					$linkExtern = '';
				endif;
				
				echo "<ul style=\"margin: 0px; padding: 0px; list-style-type: none;\">";
				if ($path!=""):
					echo "<li class=\"showlist\" style=\"list-style-type: none; clear: both; width: 99%; padding-bottom: 3px; margin-bottom: 3px; border-bottom: 1px dotted #000000;\">";
					
					echo "<a href=\"".$_SERVER['PHP_SELF'];
					if ($extern>0):
						echo "?extern=".intval($extern);
					endif;
					echo "\" title=\"Top\">";
					echo "<img src=\"/".$GLOBALS['wspvars']['wspbasedir']."/media/screen/folder.png\" title=\"Top\" alt=\"Top\" height=\"15\" border=\"0\" align=\"absmiddle\" style=\"margin-right: 20px;\" />/</a></li>";
					$temppath = explode("/",$path);
					for ($t=0;$t<(count($temppath)-1);$t++):
						$upperdir[$t] = $temppath[$t];
					endfor;
					@$upperdir = implode("/",$upperdir);
					echo "<li class=\"showlist\" style=\"list-style-type: none; clear: both; width: 99%;";
					if (count($directory)>0):
						echo " padding-bottom: 3px; margin-bottom: 3px; border-bottom: 1px dotted #000000;";
					endif;
					echo "\">";
					
					echo "<form name=\"form_levelup\" id=\"form_levelup\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
					echo "<input type=\"hidden\" name=\"func\" id=\"\" value=\"".$_GET['func']."\" />";
					echo "<input type=\"hidden\" name=\"op\" id=\"\" value=\"chdir\" />";
					echo "<input type=\"hidden\" name=\"element\" id=\"\" value=\"".$_GET['element']."\" />";
					echo "<input type=\"hidden\" name=\"path\" id=\"\" value=\"".$upperdir."\" />";
					if (intval($extern)>0):
						echo "<input type=\"hidden\" name=\"extern\" id=\"\" value=\"".intval($extern)."\" />";
					endif;
					echo "</form>";
					echo "<a href=\"#\" title=\"Vorherige Ebene\" onClick=\"document.getElementById('form_levelup').submit();\"><img src=\"/".$GLOBALS['wspvars']['wspbasedir']."/media/screen/folder.png\" height=\"15\" border=\"0\" align=\"absmiddle\" style=\"margin-right: 20px;\" />..</a>";
					echo "</li>";
				endif;
				
				for ($d=0;$d<count($directory);$d++):
					echo "<li class=\"showlist\" style=\"list-style-type: none; clear: both; width: 99%; margin: 0px; padding: 0px;";
					if ($d<count($directory)-1):
						echo " padding-bottom: 3px; margin-bottom: 3px; border-bottom: 1px dotted #000000;";
					endif;
					echo "\">";
					if ($extern==0):
						// delete
						echo "<span class=\"listdelete\" style=\"float: right; margin-left: 5px; line-height: 15px;\"><a class=\"red\" href=\"".$_SERVER['PHP_SELF']."?usevar=".$GLOBALS['wspvars']['usevar']."&func=".$_GET['func']."&op=deletefolder&delpath=/media/".$mediafolder. "/" .$path."/".$directory[$d]."&delthumbpath=/media/".$mediafolder. "/thumbs/" .$path."/".$directory[$d]."$linkExtern\" onclick=\"if (confirm(unescape('Wollen Sie diesen Ordner wirklich l%F6schen?'+String.fromCharCode(10)+'Alle untergeordneten Ordner und Dateien werden dann ebenfalls gel%F6scht'))) { return true; } else { return false; }\" title=\"l&ouml;schen\"><img src=\"/".$GLOBALS['wspvars']['wspbasedir']."/media/screen/delete_x.png\" border=\"0\" /></a></span>";
						// move
						// echo "<span class=\"listdelete\" style=\"float: right; margin-left: 20px; line-height: 15px;\"><a href=\"#\" class=\"orange\" onclick=\"fillswitch('".$path."/".$directory[$d].$linkExtern."','$mediafolder');document.getElementById('switchdir').style.display='block'; document.getElementById('changedir').style.display='none'; document.getElementById('switchpath').value='".$path."/".$directory[$d].$linkExtern."';\">Verschieben</a></span>";
						// rename
						echo "<span class=\"listdelete\" style=\"float: right; margin-left: 5px; line-height: 15px;\"><a href=\"#\" onclick=\"document.getElementById('changedir').style.display='block'; document.getElementById('switchdir').style.display='none'; document.getElementById('newdirname').value = '".$directory[$d]."'; document.getElementById('showrenamedir').innerHTML = '".$directory[$d]."'; document.getElementById('renpath').value='/media/".$mediafolder.$path."/".$directory[$d].$linkExtern."'; \"><img src=\"/".$GLOBALS['wspvars']['wspbasedir']."/media/screen/rename_x.png\" border=\"0\" /></a></span>";
					endif;
					// link
					
					echo "<form name=\"chdir_form_".$d."\" id=\"chdir_form_".$d."\" action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
					echo "<input type=\"hidden\" name=\"func\" id=\"\" value=\"".$_GET['func']."\" />";
					echo "<input type=\"hidden\" name=\"op\" id=\"\" value=\"chdir\" />";
					echo "<input type=\"hidden\" name=\"element\" id=\"\" value=\"".$_GET['element']."\" />";
					echo "<input type=\"hidden\" name=\"path\" id=\"\" value=\"".$path."/".$directory[$d]."\" />";
					if (intval($extern)>0):
						echo "<input type=\"hidden\" name=\"extern\" id=\"\" value=\"".intval($extern)."\" />";
					endif;
					echo "</form>";
					echo "<span class=\"listicon\" style=\"line-height: 15px; float: left; width: 70px;\"><a href=\"#\" title=\"".$directory[$d]."\" onClick=\"document.getElementById('chdir_form_".$d."').submit();\"><img src=\"/".$GLOBALS['wspvars']['wspbasedir']."/media/screen/folder.png\" height=\"15\" align=\"absmiddle\" border=\"0\" /></a></span>";
					echo "<span class=\"listdirfilenum\" style=\"line-height: 15px; float: left; width: 70px;\">";
					
					$checkdirforfilenum = $_SERVER['DOCUMENT_ROOT']."/media/".$mediafolder. "/" .$path."/".$directory[$d];
					$cdffn = dir($checkdirforfilenum);
					$filesnum = 0;
					
					while (false !== ($entries = $cdffn->read())):
						if (substr($entries, 0, 1) != '.'):
							if (!is_dir($_SERVER['DOCUMENT_ROOT']."/media/".$GLOBALS['mediafolder']."/".$path."/".$entries)):
								$filesnum++;
							endif;
						endif;
					endwhile;
					echo $filesnum;
					
					echo " Datei";
					if ($filesnum!=1): echo "en"; endif;
					echo "</span>";
					
					unset($filesnum);
					
					echo "<span class=\"listdirname\" style=\"margin-left: 10px; line-height: 15px;\"><a href=\"#\" title=\"".$directory[$d]."\" onClick=\"document.getElementById('chdir_form_".$d."').submit();\">".$directory[$d]."</a></span>";
					echo "</li>";
				endfor;
				echo "</ul>";
				
				echo "</p>";
				echo "<p style=\"clear: both; line-height: 1px; font-size: 1px; margin: 0px; padding: 0px; height: 1px;\">&nbsp;</p></span>";
				echo "</span>";
				echo "</fieldset>\n";
				
				echo "<fieldset id=\"changedir\" style=\"display: none;\">\n";
				echo "<legend>Verzeichnis '<span id=\"showrenamedir\">name</span>' umbenennen</legend>";
				echo "<form action=\"".$_SERVER['PHP_SELF']."\" id=\"form_changedir\" method=\"post\">\n";
				echo "Neuer Verzeichnisname:&nbsp;<input type=\"text\" id=\"newdirname\" name=\"newdirname\" />\n<br /><br />";
				echo "<a class=\"redfield\" href=\"#\" onClick=\"document.getElementById('changedir').style.display = 'none';\">Abbrechen</a>&nbsp;&nbsp;&nbsp;<a class=\"greenfield\" href=\"#\" onclick=\"if (confirm(unescape('Wollen sie das Verzeichnis wirklich umbenennen?'))) {document.getElementById('form_changedir').submit(); return false; } else { return false; }\" title=\"umbenennen\">Umbenennen</a><br /><br />\n";
				echo "<input type=\"hidden\" id=\"renpath\" name=\"renpath\" value=\"\" />\n";
				echo "<input type=\"hidden\" id=\"mediafolder\" name=\"mediafolder\" value=\"$mediafolder\" />\n";
				echo "<input type=\"hidden\" id=\"op\" name=\"op\" value=\"changedir\" />\n";
				echo "</form>\n";
				echo "</fieldset>\n";
				
				echo "<fieldset id=\"switchdir\" style=\"display: none;\">\n";
				echo "<legend>Verzeichnis '<span id=\"showmovedir\">name</span>' verschieben</legend>";
				echo "<form action=\"".$_SERVER['PHP_SELF']."\" id=\"form_switchdir\" method=\"post\">\n";
				echo "Zielverzeichnis <select id=\"menu_switchdir\" name=\"menu_switchdir\">\n";
				echo "</select>";
				echo "<br /><br />";
				echo "<a class=\"redfield\" href=\"".$_SERVER['PHP_SELF']."?usevar=".$GLOBALS['wspvars']['usevar']."\">Abbrechen</a>&nbsp;&nbsp;&nbsp;<a class=\"greenfield\" href=\"#\" onclick=\"if (confirm(unescape('Wollen sie das Verzeichnis wirklich verschieben??'))) {document.getElementById('form_switchdir').submit(); return false; } else { return false; }\" title=\"hierher verschieben\">Verschieben</a><br /><br />\n";
				echo "<input type=\"hidden\" id=\"switchpath\" name=\"switchpath\" value=\"\" />\n";
				echo "<input type=\"hidden\" id=\"op\" name=\"op\" value=\"switchdir\" />\n";
				echo "<input type=\"hidden\" id=\"mediafolder\" name=\"mediafolder\" value=\"$mediafolder\" />\n";
				echo "</form>\n";
				echo "</fieldset>\n";
			endif;
		endif;
	endif;
	}
endif; // listDir()

/**
* prepare media-contents to showcase
*
* @param String $path ; relative path to media-folder
* @param boolean $extern => true, wenn die Anzeige für ein Modul aufbereitet werden soll (nur Auswahl möglich)
*
*/
if (!(function_exists("listFiles"))):
function listFiles($path = '', $extern = 0) {
	// unset error and notice messages
	unset($_SESSION['errormsg']);
	unset($_SESSION['noticemsg']);
	// set extern to integer value
	// extern = 1 => call from wysiwyg
	$extern = intval($extern);
	// security function to prevent full directory requests 
	$path = str_replace("//","/",str_replace("//","/",str_replace(".","",$path)));
	
	$wspvars['medialistlength'] = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'medialistlength'"));
	if (intval($wspvars['medialistlength'])<10):
		$wspvars['medialistlength'] = 10;
	endif;
	
	if ($GLOBALS['mediafolder']!=""):
		$mediafolderpath = $GLOBALS['mediafolder']."/";
	endif;
	
	if (!(is_array($GLOBALS['mediafolders']))):
		$GLOBALS['mediafolders'] = array();
	endif;
	
	$showdir = @dir(str_replace("//","/", str_replace("//","/", $_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath.$path)));
	if ($showdir==false):
		echo "<fieldset class=\"errormsg\">Das von Ihnen gew&auml;hlte Verzeichnis ist nicht vorhanden.</fieldset>";
	else:
		if ($extern==0):
			echo  "<script language=\"javascript\" type=\"text/javascript\">";
			echo "function fillswitchfiles(id,media){
				  while (document.getElementById('menu_switchfile').childNodes.length>1) {
					  document.getElementById('menu_switchfile').removeChild(document.getElementById('menu_switchfile').childNodes[1]);
			      }
	 			  xajax_filllist(id,media);	
	 			  }";
			echo  "</script>";
			// function to delete media files
			if (isset($_POST['delfile'])):
				if ($_POST['delfile']!=""):
					ftpDeleteFile($GLOBALS['wspvars']['ftpbasedir'].$_POST['delfile']);
				endif;
				if ($_POST['delthumbfile']!=""):
					ftpDeleteFile($GLOBALS['wspvars']['ftpbasedir'].$_POST['delthumbfile']);
				endif;
			endif;
		endif;
		$files = array();
		while (false !== ($entries = $showdir->read())):
			if (substr($entries, 0, 1) != '.'):
				if (!is_dir($_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath.$path."/".$entries)):
					if (!isset($_REQUEST['search']) || preg_match("/".$_REQUEST['search']."/i", $entries)):
						$files[] = $entries;
					endif;
				endif;
			endif;
		endwhile;
		sort($files);
		if (count($files)>0):
			if ($extern==0):
				// javascript function to delete media files
				echo "\n<script type=\"text/javascript\" language=\"javascript\">";
				echo "\n<!--";
				echo "\nfunction delConfirmFile(filename) {";
				echo "\n	if (confirm(unescape('Soll die Datei \'' + filename + '\' wirklich gel%f6scht werden?'))) {";
				echo "\n		document.getElementById('delmedia').submit();";
				echo "\n		}	// if";
				echo "\n	}	// delConfirm()";
				echo "\n//-->";
				echo "\n</script>";
				// display search
				echo "<fieldset class=\"text\" id=\"searchfile\">";
				echo "<legend>Suchen im Pfad ";
				$pathinfo = "/media/".$GLOBALS['mediafolder'];
				if ($path==""): $pathinfo.= "/"; else: $pathinfo.= $path; endif;
				echo str_replace("//","/",str_replace("//","/",$pathinfo));	
				echo " ".showOpenerCloser('searchcontents_content','open')."</legend>";
				echo "<span id=\"searchcontents_content\"><form action=\"".$_SERVER['PHP_SELF']."\" id=\"form_search\" name=\"form_search\" method=\"post\">";
				echo "<input type=\"hidden\" id=\"search_page\" name=\"page\" value=\"1\" />";
				echo "<input type=\"hidden\" id=\"func\" name=\"func\" value=\"".@$_REQUEST['func']."\" />";
				echo "<input type=\"hidden\" id=\"op\" name=\"op\" value=\"".@$_REQUEST['op']."\" />";
				echo "<input type=\"hidden\" id=\"element\" name=\"element\" value=\"".@$_REQUEST['element']."\" />";
				echo "<input type=\"hidden\" id=\"path\" name=\"path\" value=\"".@$_REQUEST['path']."\" />";
//				if ($_REQUEST['extern'] == '1'):
//					echo "<input type=\"hidden\" id=\"extern\" name=\"extern\" value=\"1\" />";
//				endif;
				echo "<input type=\"text\" id=\"search_input\" name=\"search\" value=\"".@$_REQUEST['search']."\" /> <a onclick=\"document.getElementById('form_search').submit();\" style=\"cursor: pointer;\"><img src=\"/".$GLOBALS['wspvars']['wspbasedir']."/media/screen/look_x.png\" alt=\"Suchen\" border=\"0\"></a>";
				echo "<input type=\"submit\" value=\"\" style=\"display:none\" />";
				echo "</form></span>";
				echo "</fieldset>\n";
			endif;
			// display files
			echo "<fieldset  id=\"fieldset_listfiles\" style=\"height: auto;\" class=\"text\">\n";
			echo "<legend>".$GLOBALS['mediadesc']." im Pfad ";
			$pathinfo = "/media/".$GLOBALS['mediafolder'];
			if ($path==""):
				$pathinfo.= "/";
			else:
				$pathinfo.= $path;
			endif;
			echo str_replace("//","/",$pathinfo);
			
			
			echo " ".showOpenerCloser('dircontents_content','open'); // musste raus, irgend etwas in der Funktion stimmt nicht
			echo "</legend>";
			echo "<span id=\"dircontents_content\">";
			echo "<p>";
			// page selection
			if (empty($_REQUEST['page']))  $_REQUEST['page'] = '1';
			if ($extern==1):
				$wspvars['medialistlength'] = 6;
			endif;
			$start_page = ( $_REQUEST['page'] -1 ) * $wspvars['medialistlength'];
			if($start_page + $wspvars['medialistlength'] > count($files)):
				$end_page = count($files);
			else:
				$end_page = $start_page + $wspvars['medialistlength'];
			endif;
			if (count($files) > $wspvars['medialistlength']):
				$num_page = ceil(count($files) / $wspvars['medialistlength']);
				echo "<p style=\"border-bottom: 1px solid black;\">";
				echo "<span style=\"border: 1px solid #295487; border-bottom: none; margin: 1px; margin-left: 0px; padding: 2px 5px; background: #fff; line-height: 18px;\">Seite</span>";
				for ($i = 1; $i <= $num_page; $i++):
					if ($i==1 || $i==2 || $i==3 || $i==($_REQUEST['page']-1) || $i==($_REQUEST['page']) || $i==($_REQUEST['page']+1) || $i==($num_page-2) || $i==($num_page-1) || $i==($num_page)):
						if ($i == $_REQUEST['page']):
							echo "<span style=\"border: 1px solid #295487; border-bottom: none; margin: 1px; padding: 2px 5px; background: #C5D1DF; line-height: 18px;\">".$i."</span>";
						else:
							echo "<a href=\"#\" onclick=\"document.getElementById('page_page').value = '".$i."'; document.getElementById('form_page').submit(); return false;\" style=\"border: 1px solid #295487; border-bottom: none; margin: 1px;  padding: 2px 5px; line-height: 18px; font-weight: normal; color: #000;\">".$i."</a>";
						endif;
						$displayspan = true;
					elseif ($displayspan):
						echo "<span style=\"border: 1px solid #295487; border-bottom: none; margin: 1px; padding: 2px 5px; line-height: 18px; font-weight: normal; color: #000;\">..</span>";
						$displayspan = false;
					endif;
				endfor;
				echo "</p>\n";
			endif;
			
			if ($GLOBALS['wspvars']['showmedia']=="liste"):
				// darstellung als liste
				echo "<ul style=\"margin: 0px; padding: 0px; list-style-type: none;\">";
				for ($f=$start_page;$f<$end_page;$f++):
					echo "<li class=\"showlist\" style=\"list-style-type: none; clear: both; width: 99%; margin: 0px; padding: 0px;";
					if ($f < $end_page - 1):
						echo " padding-bottom: 5px; margin-bottom: 3px; border-bottom: 1px dotted #000000;";
					endif;
					echo "\">";
					$title = $files[$f]."\r\nLetzte &Auml;nderung: ".date('Y-m-d H:i:s', filemtime($_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath.$path."/".$files[$f]));
					
					if ($GLOBALS['mediafolder']=="images" || $GLOBALS['mediafolder']=="screen" || in_array("screen", $GLOBALS['mediafolders']) || in_array("images", $GLOBALS['mediafolders'])):
						// show images from both mediafolders
						$imageinfo = @getimagesize(str_replace("//","/", str_replace("//","/",$_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath."/".$path."/".$files[$f])));
						// check mediadesc
						$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//","/","/media/".$mediafolderpath.$path."/".$files[$f])."%'";
						$desc_res = mysql_query($desc_sql);
						// empty mediadetails
						$mediadesc = '';
						$mediakeys = '';
						if ($desc_res):
							$desc_num = mysql_num_rows($desc_res);
							if ($desc_num>0):
								// set mediadetails if avaiable
								$mediadesc = mysql_result($desc_res, 0, "filedesc");
								$mediakeys = mysql_result($desc_res, 0, "filekeys");
							endif;
						endif;
						if ($extern==0):
							$image = str_replace('//', '/', str_replace('//', '/', '/media/' . $mediafolderpath.$path.'/'.$files[$f]));
							// check usage in content
							$sql = "SELECT `cid` FROM `content` WHERE `valuefields` LIKE '%" . $image . "%' LIMIT 1";
							$res = mysql_query($sql);
							$num = mysql_num_rows($res);
							if ($num == 0):
								// check usage in globalcontent
								$sql = "SELECT `id` FROM `globalcontent` WHERE `valuefield` LIKE '%" . $image . "%' LIMIT 1";
								$res = mysql_query($sql);
								$num+= mysql_num_rows($res);
								if ($num == 0):
									// check usage in stylesheets
									$sql = "SELECT `id` FROM `stylesheets` WHERE `stylesheet` LIKE '%" . $image . "%' LIMIT 1";
									$res = mysql_query($sql);
									$num+= mysql_num_rows($res);
									if ($num == 0):
										// check usage in menuimages
										$sql = "SELECT `mid` FROM `menu` WHERE `imageon`='" . $image . "' OR `imageoff`='" . $image . "' OR `imageakt`='" . $image . "' OR `imageclick`='" . $image . "' LIMIT 1";
										$res = mysql_query($sql);
										$num+= mysql_num_rows($res);
									endif;
								endif;
							endif;
							// display delete button
							echo "<span title=\"".$image."\" style=\"float: right; margin-left: 5px; line-height: 15px;";
							if ($num < 1):
								echo "\"><a class=\"red\" href=\"#\" onclick=\"document.getElementById('delfile').value = '/media/" . $mediafolderpath . $path . "/" . $files[$f] . "';document.getElementById('delthumbfile').value = '/media/" . $mediafolderpath . "thumbs/" . $path . "/" . $files[$f]."'; delConfirmFile('".$files[$f]."');\"><img src=\"/wsp/media/screen/delete_x.png\" border=\"0\" alt=\"L&ouml;schen\" title=\"L&ouml;schen\" /></a>";
							else:
								echo " font-weight:bold; font-color:grey;\"><img src=\"/wsp/media/screen/delete_x.png\" border=\"0\" alt=\"L&ouml;schen\" title=\"L&ouml;schen\" style=\"opacity: 0.3;\" />";
							endif;
							echo "</span>";
							// display move button
							// echo "<span style=\"float: right; margin-left: 20px; line-height: 15px;\">";
							// echo "<a class=\"orange\" href=\"#\" onclick=\"document.getElementById('switchfile').style.display='block'; fillswitchfiles('".$path."/".$directory[$d].$linkExtern."','".$mediafolder."'); document.getElementById('showswitchfile').innerHTML = '".$files[$f]."'; document.getElementById('changefile').style.display='none';\">";
							// echo "Verschieben</a></span>";
							if ($imageinfo['mime']!=""):
								// display rename button
								echo "<span title=\"" . $image . "\" style=\"float: right; margin-left: 5px; line-height: 15px;";
								if ($num==0):
									echo "\"><a class=\"orange\" href=\"#\" onclick=\"document.getElementById('changefile').style.display='block'; document.getElementById('switchfile').style.display='none'; document.getElementById('newfilename').value = '".substr($files[$f],0,strrpos($files[$f],"."))."'; document.getElementById('showrenamefile').innerHTML = '".$files[$f]."'; document.getElementById('showrenamefileend').innerHTML = '".substr($files[$f],strrpos($files[$f],"."))."'; document.getElementById('changerenfilepath').value='/media/".$mediafolderpath.$path."/'; document.getElementById('changeoldfilename').value='".$files[$f]."'; \"><img src=\"/wsp/media/screen/rename_x.png\" border=\"0\" alt=\"Umbenennen\" title=\"Umbenennen\" /></a>";
								else:
									echo " font-weight:bold; font-color:grey;\"><img src=\"/wsp/media/screen/rename_x.png\" border=\"0\" alt=\"Umbenennen\" title=\"Umbenennen\" style=\"opacity: 0.3;\" />";
								endif;
								echo "</span>";
								// display details button
								echo "<span style=\"float: right; margin-left: 5px; line-height: 15px;\"><a href=\"#\" onclick=\"document.getElementById('details_showpath').value = '".str_replace("//", "/", "/media/".$mediafolderpath)."'; document.getElementById('details_showfile').value = '".$path.'/'.$files[$f]."'; document.getElementById('mediadetails').submit();\" title=\"Details\" ><img src=\"/wsp/media/screen/look_x.png\" border=\"0\" alt=\"Details\" title=\"Details\" /></a></span>";
							endif;
						endif;
						echo "<span class=\"showfile\" style=\"float: left; margin-right: 5px; width: 65px; height: 25px; overflow: hidden;\">";
						
						if (file_exists(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath."thumbs/".$path."/".$files[$f])))):
							// return existing thumbnail
							$thumb = givebackThumb($imageinfo[0],$imageinfo[1],50,15);
							if ($extern>0):
								if ($_REQUEST['func']=="insertImageText"):
									echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"insertImageText('".str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f])."')"."; return false;\" style=\"color: #000000;\">";
								elseif ($_REQUEST['func']=="insertImageTextDetails"):
									echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"insertImageTextDetails('".str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f])."','".addslashes($mediadesc)."','".addslashes($mediadesc)."')"."; return false;\" style=\"color: #000000;\">";
								elseif ($_REQUEST['func']!=""):
									echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"".$_REQUEST['func']."('".str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f])."')"."; return false;\" style=\"color: #000000;\">";
								endif;
							else:
								echo "\n\t<a href=\"#\" onclick=\"document.getElementById('details_showpath').value = '".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath))."'; document.getElementById('details_showfile').value = '".$path.'/'.$files[$f]."';"." document.getElementById('mediadetails').submit();\" title=\"Details\" style=\"color: #000\">";
							endif;
							echo "<img src=\"".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath."thumbs/".$path."/".$files[$f]))."\" height=\"".$thumb[1]."\" border=\"1\" style=\"margin-top: "; 
							if ($thumb[1]<15):
								echo ceil((15-$thumb[1])/2);
							else:
								echo "0";
							endif;
							echo "px;\" />";
							echo "</a>";
						elseif ($imageinfo['mime']!=""):
							// create thumbnail
							if ($_SESSION['wspvars']['createthumbfromimage']=="checked"):
								$orig = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath."/".$path."/".$files[$f]));
								$thumb = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['wspvars']['wspbasedir']."/tmp/".$GLOBALS['wspvars']['usevar']."/media/".$mediafolderpath."/thumbs/".$path."/".$files[$f]));
								//create dir if needed
								createDir(str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath."/thumbs/".$path."/")));
								// create thumb
								resizeGDimage(str_replace("//", "/", $orig), str_replace("//", "/", $thumb), 0, 100, 75, 1);
								// ftp-access
								require_once ("data/include/ftpaccess.inc.php");
								$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
								$login = ftp_login($ftp, $GLOBALS['wspvars']['ftpuser'], $GLOBALS['wspvars']['ftppass']);
								$ftppath = str_replace("//", "/", $GLOBALS['wspvars']['ftpbasedir']."/media/".$mediafolderpath."/thumbs/".$path."/");
								// create dir if needed
								createDirFTP(str_replace("//", "/", $ftppath));
								// copy file to ftp
								@ftp_put($ftp, $ftppath."/".$files[$f], $thumb, FTP_BINARY);
								@unlink($thumb);
								ftp_close($ftp);
							endif;
							$thumb = givebackThumb($imageinfo[0],$imageinfo[1],50,15);
							$xtrapath = "";
							if (is_file(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath."thumbs/".$path."/".$files[$f])))):
								$xtrapath = "/thumbs/";
							endif;
							// return resized original image or - if thumb was created - thumbnail
							if ($extern>0):
								if ($_REQUEST['func']=="insertImageText"):
									echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"insertImageText('".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f]))."')"."; return false;\" style=\"color: #000000;\">";
								elseif ($_REQUEST['func']=="insertImageTextDetails"):
									echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"insertImageTextDetails('".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f]))."','".addslashes($mediadesc)."','".addslashes($mediadesc)."')"."; return false;\" style=\"color: #000000;\">";
								elseif ($_REQUEST['func']!=""):
									echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"".$_REQUEST['func']."('".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f]))."')"."; return false;\" style=\"color: #000000;\">";
								endif;
							else:
								echo "\n\t<a href=\"#\" onclick=\"document.getElementById('details_showpath').value = '".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath))."'; document.getElementById('details_showfile').value = '".$path.'/'.$files[$f]."';"." document.getElementById('mediadetails').submit();\" title=\"Details\" style=\"color: #000\">";
							endif;
							echo "<img src=\"/media/".str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", $mediafolderpath."/".$xtrapath."/".$path."/".$files[$f])))."\" border=\"1\" width=\"".ceil($thumb[0])."\" height=\"".ceil($thumb[1])."\" align=\"absmiddle\" ";
							if (ceil($thumb[1])<15):
								echo "style=\"margin-top: ".ceil((15-ceil($thumb[1]))/2)."px;\" ";
							endif;
							echo "/>";
							echo "</a>\n";
						else:
							echo "<em style=\"line-height: 18px;\">Kein Bild</em>";
						endif;
						echo "</span>";
				
						// show last change info
						echo "<span class=\"dateinfo\" style=\"float: left; width: 130px; line-height: 15px; \">".gmdate("Y-m-d H:i:s", filemtime($_SERVER['DOCUMENT_ROOT'].$image))."</span>";

						// show image size info
						if (intval($imageinfo[0])>0 && intval($imageinfo[1])>0 && $extern==0):
							echo "<span class=\"sizeinfo\" style=\"float: left; width: 80px; line-height: 15px; \">".$imageinfo[0]." x ".$imageinfo[1]."</span>";
						else:
							echo "<span class=\"sizeinfo\" style=\"float: left; width: 80px; line-height: 15px; \">&nbsp;</span>";
						endif;
						// link image title if extern connection
						if ($extern>0):
							if ($_REQUEST['func']=="insertImageText"):
								echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"insertImageText('".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f]))."')"."; return false;\" style=\"color: #000000;\">";
							elseif ($_REQUEST['func']=="insertImageTextDetails"):
								echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"insertImageTextDetails('".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f]))."','".addslashes($mediadesc)."','".addslashes($mediadesc)."')"."; return false;\" style=\"color: #000000;\">";
							elseif ($_REQUEST['func']!=""):
								echo "\n\t<a href=\"#\" title=\"".$title."\" onclick=\"".$_REQUEST['func']."('".str_replace("//", "/", str_replace("//", "/", "/media/".$mediafolderpath.$path."/".$files[$f]))."')"."; return false;\" style=\"color: #000000;\">";
							endif;
						endif;
						
						echo "<span class=\"fileinfo\" style=\"line-height: 15px; \">";
						if ($extern==0):
							echo "<span id=\"img_".$f."\" style=\"cursor: pointer;\" onclick=\"document.getElementById('img_".$f."').style.display = 'none'; document.getElementById('rename_img_".$f."').style.display = 'block'; document.getElementById('rename_field_".$f."').focus(); \">";
						endif;
						if ($mediadesc!=""):
							echo "<em title=\"".$files[$f]."\">".$mediadesc."</em>";
						elseif (strlen($files[$f]) > 80):
							echo substr($files[$f], 0, 65).'...'.substr($files[$f], -5);
						else:
							echo $files[$f];
						endif;
						if ($extern==0):
							echo "</span>";
							$filetype = substr($files[$f],strrpos($files[$f],"."));
							echo "<span id=\"rename_img_".$f."\" style=\"display: none;\"><form id=\"rename_".$f."\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\"><input type=\"text\" name=\"newdesc\" id=\"rename_field_".$f."\" value=\"".$mediadesc."\" style=\"width: 50%;\" onBlur=\"document.getElementById('rename_".$f."').submit();\">";
							$pathinfo = "/media/".$mediafolderpath.$path."/";
							echo "<input type=\"hidden\" name=\"renfilepath\" value=\"".str_replace("//", "/", $pathinfo)."\" />\n";
							echo "<input type=\"hidden\" name=\"oldfilename\" value=\"".$files[$f]."\" />\n";
							echo "<input type=\"hidden\" name=\"op\" value=\"setdesc\" />\n";
							echo "<input type=\"hidden\" name=\"page\" value=\"".@$_REQUEST['page']."\" />";
							echo "<input type=\"hidden\" name=\"func\" value=\"".@$_REQUEST['func']."\" />";
							echo "<input type=\"hidden\" name=\"element\" value=\"".@$_REQUEST['element']."\" />";
							echo "<input type=\"hidden\" name=\"path\" value=\"".@$_REQUEST['path']."\" />";
							echo "</form></span>";
						endif;
						echo "</span>";
						if ($extern>0):
							echo "</a>";
						endif;
					else: // show not image document types in other folders 
						$desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//","/","/media/".$mediafolderpath.$path."/".$files[$f])."%'"; // check mediadesc
						$desc_res = mysql_query($desc_sql);
						$mediadesc = ''; // empty mediadetails
						$mediakeys = ''; // empty mediadetails
						if ($desc_res):
							$desc_num = mysql_num_rows($desc_res);
							if ($desc_num>0):
								$mediadesc = mysql_result($desc_res, 0, "filedesc");
								$mediakeys = mysql_result($desc_res, 0, "filekeys");
							endif;
						endif;
						
						if ($extern==0):
							$sql = "SELECT c.`cid` FROM `content` c WHERE c.`valuefields` LIKE '%".$path."/".$files[$f]."%'  AND (SELECT m.`mid` FROM `menu` m WHERE m.`mid`=c.`mid`)>0 LIMIT 1";
							$res = mysql_query($sql);
							$num = mysql_num_rows($res);
							if ($num == 0):
								$sql = "SELECT `id` FROM `globalcontent` WHERE `valuefield` LIKE '%".$path."/".$files[$f]."%' LIMIT 1";
								$res = mysql_query($sql);
								$num+= mysql_num_rows($res);
							endif;
							echo "<span title=\"".$files[$f]."\" style=\"float: right; margin-left: 5px; line-height: 15px;";
							if ($num < 1):
								echo "\"><a class=\"red\" href=\"#\" onclick=\"document.getElementById('delfile').value = '/media/" . $mediafolderpath . $path . "/" . $files[$f] . "';document.getElementById('delthumbfile').value = '/media/" . $mediafolderpath . "thumbs/" . $path . "/" . $files[$f]."'; delConfirmFile('".$files[$f]."');\"><img src=\"/wsp/media/screen/delete_x.png\" border=\"0\" alt=\"L&ouml;schen\" title=\"L&ouml;schen\" /></a>";
							else:
								echo " font-weight: bold; font-color: grey;\"><img src=\"/wsp/media/screen/delete_x.png\" border=\"0\" alt=\"L&ouml;schen\" title=\"L&ouml;schen\" style=\"opacity: 0.3;\" />";
							endif;
							echo "</span>";
							
							// echo "<span style=\"float: right; margin-left: 20px; line-height: 15px;\">";
							// echo "<a class=\"orange\" href=\"#\" onclick=\"document.getElementById('switchfile').style.display='block'; fillswitchfiles('".$path."/".$directory[$d].$linkExtern."','".$mediafolder."'); document.getElementById('showswitchfile').innerHTML = '".$files[$f]."'; document.getElementById('changefile').style.display='none';\">";
							// echo "Verschieben</a></span>";
							
							echo "<span title=\"" . $files[$f] . "\" style=\"float: right; margin-left: 5px; line-height: 15px;";
							if ($num < 1):
								echo "\"><a class=\"orange\" href=\"#\" onclick=\"document.getElementById('changefile').style.display='block'; document.getElementById('switchfile').style.display='none'; document.getElementById('newfilename').value = '".substr($files[$f],0,strrpos($files[$f],"."))."'; document.getElementById('showrenamefile').innerHTML = '".$files[$f]."'; document.getElementById('showrenamefileend').innerHTML = '".substr($files[$f],strrpos($files[$f],"."))."'; document.getElementById('changerenfilepath').value='/media/".$mediafolderpath.$path."/'; document.getElementById('changeoldfilename').value='".$files[$f]."'; \"><img src=\"/wsp/media/screen/rename_x.png\" border=\"0\" alt=\"Umbenennen\" title=\"Umbenennen\" /></a>";
							else:
								echo " font-weight:bold; font-color:grey;\"><img src=\"/wsp/media/screen/rename_x.png\" border=\"0\" alt=\"Umbenennen\" title=\"Umbenennen\" style=\"opacity: 0.3;\" />";
							endif;
							echo "</span>";
							echo "<span style=\"float: right; margin-left: 5px; line-height: 15px;\"><a href=\"#\" onclick=\"document.getElementById('details_showpath').value = '".str_replace("//", "/", "/media/".$mediafolderpath)."'; document.getElementById('details_showfile').value = '".$path."/".$files[$f]."'; document.getElementById('mediadetails').submit();\" title=\"Details\"><img src=\"/wsp/media/screen/look_x.png\" border=\"0\" alt=\"Details\" title=\"Details\" /></a></span>";
						endif;
						
						// get documents info, output document icon, etc ...
						echo "<span class=\"showfile\" style=\"float: left; width: 70px; height: 25px;\">";
						if ($extern==1):
							echo "<a href=\"#\" title=\"".$title."\" onclick=\"parent.insertFile('".str_replace('//', '/', "/media/".$path."/".$files[$f])."')"."; return false;\">";
						endif;
						echo "<img src=\"/".$GLOBALS['wspvars']['wspbasedir']."/media/screen/";
						$filetype = substr($files[$f],strrpos($files[$f],"."));
						if ($filetype==".gif"):
							echo "gificon.png";
						elseif ($filetype==".jpg" || $filetype==".jpeg"):
							echo "jpgicon.png";
						elseif ($filetype==".png"):
							echo "pngicon.png";
						elseif ($filetype==".pdf"):
							echo "pdficon.png";
						else:
							echo "icon.png";
						endif;
						echo "\" border=\"0\" height=\"15\" align=\"absmiddle\" />";
						if ($extern==1):
							echo "</a>";
						endif;
						echo "\n</span>";

						echo "<span class=\"dateinfo\" style=\"float: left; width: 130px; line-height: 15px; \">".gmdate("Y-m-d H:i:s", filemtime($_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath.$path."/".$files[$f]))."</span>";

						
						echo "<span class=\"sizeinfo\" style=\"float: left; width: 80px; line-height: 15px; \">".ceil(filesize($_SERVER['DOCUMENT_ROOT']."/media/".$mediafolderpath.$path."/".$files[$f])/1024)." KB</span>";
						
						echo "<span class=\"fileinfo\" style=\"line-height: 15px; \">";
						if ($extern==1):
							echo "<a href=\"#\" onclick=\"parent.insertFile('".str_replace('//', '/', "/media/".$mediafolderpath.$path."/".$files[$f])."')"."; return false;\">";
						else:
							echo "<span id=\"img_".$f."\" style=\"cursor: pointer;\" onclick=\"document.getElementById('img_".$f."').style.display = 'none'; document.getElementById('rename_img_".$f."').style.display = 'block'; document.getElementById('rename_field_".$f."').focus(); \">";
						endif;
						
						if ($mediadesc!=""):
							echo "<em title=\"".$files[$f]."\">".$mediadesc."</em>";
						elseif (strlen($files[$f]) > 80):
							echo substr($files[$f], 0, 65).'...'.substr($files[$f], -5);
						else:
							echo $files[$f];
						endif;
						if ($extern==1):
							echo "</a>";
						else:
							echo "</span>";
							$filetype = substr($files[$f],strrpos($files[$f],"."));
							echo "<span id=\"rename_img_".$f."\" style=\"display: none;\"><form id=\"rename_".$f."\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\"><input type=\"text\" name=\"newdesc\" id=\"rename_field_".$f."\" value=\"".$mediadesc."\" style=\"width: 50%;\" onBlur=\"document.getElementById('rename_".$f."').submit();\">";
							$pathinfo = "/media/".$mediafolderpath.$path."/";
							echo "<input type=\"hidden\" name=\"renfilepath\" value=\"".str_replace("//", "/", str_replace("//", "/", $pathinfo))."\" />\n";
							echo "<input type=\"hidden\" name=\"oldfilename\" value=\"".$files[$f]."\" />\n";
							echo "<input type=\"hidden\" name=\"op\" value=\"setdesc\" />\n";
							echo "<input type=\"hidden\" name=\"page\" value=\"".@$_REQUEST['page']."\" />";
							echo "<input type=\"hidden\" name=\"func\" value=\"".@$_REQUEST['func']."\" />";
							echo "<input type=\"hidden\" name=\"element\" value=\"".@$_REQUEST['element']."\" />";
							echo "<input type=\"hidden\" name=\"path\" value=\"".@$_REQUEST['path']."\" />";
							echo "</form></span>";
						endif;
						echo "</span>";
					endif;
					echo "</li>";
				endfor;
				echo "</ul>\n";
				if ($extern==0):
					//
					echo "\n\n<form action=\"mediadetails.php\" id=\"mediadetails\" name=\"mediadetails\" method=\"post\">\n";
					echo "<input type=\"hidden\" id=\"details_media\" name=\"medialoc\" value=\"".$_SERVER['PHP_SELF']."\" />\n";
					echo "<input type=\"hidden\" id=\"details_showpath\" name=\"showpath\" value=\"\" />\n";
					echo "<input type=\"hidden\" id=\"details_showfile\" name=\"showfile\" value=\"\" />\n";
					echo "<input type=\"hidden\" id=\"details_page\" name=\"page\" value=\"".@$_REQUEST['page']."\" />\n";
					echo "<input type=\"hidden\" id=\"details_func\" name=\"func\" value=\"".@$_REQUEST['func']."\" />\n";
					echo "<input type=\"hidden\" id=\"details_element\" name=\"element\" value=\"".@$_REQUEST['element']."\" />\n";
					echo "<input type=\"hidden\" id=\"details_path\" name=\"path\" value=\"".@$_REQUEST['path']."\" />\n";
					echo "<input type=\"hidden\" id=\"details_search\" name=\"search\" value=\"".@$_REQUEST['search']."\" />\n";
					echo "</form>\n";
					//
					echo "\n\n<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" name=\"delmedia\" id=\"delmedia\">";
					echo "\n<input name=\"delfile\" id=\"delfile\" type=\"hidden\" value=\"\" />";
					echo "\n<input name=\"delthumbfile\" id=\"delthumbfile\" type=\"hidden\" value=\"\" />";
					echo "\n<input name=\"path\" type=\"hidden\" value=\"".$path."\" />";
					echo "\n</form>\n";
				endif;
			endif;
			
			echo "</p>";
			
			if (count($files) > $wspvars['medialistlength']):
				echo "<p style=\"clear: both; border-top: 1px solid black;\">";
				echo "<span style=\"border: 1px solid #295487; border-top: none; margin: 1px; margin-left: 0px; padding: 2px 5px; background: #fff; line-height: 18px;\">Seite</span>";
				for ($i = 1; $i <= $num_page; $i++):
					if ($i==1 || $i==2 || $i==3 || $i==($_REQUEST['page']-1) || $i==($_REQUEST['page']) || $i==($_REQUEST['page']+1) || $i==($num_page-2) || $i==($num_page-1) || $i==($num_page)):
						if ($i == $_REQUEST['page']):
							echo "<span style=\"border: 1px solid #295487; border-top: none; margin: 0px 1px; padding: 2px 5px; background: #C5D1DF; line-height: 18px;\">".$i."</span>";
						else:
							echo "<a href=\"#\" onclick=\"document.getElementById('page_page').value = '".$i."'; document.getElementById('form_page').submit(); return false;\" style=\"border: 1px solid #295487; border-top: none; margin: 0px 1px;  padding: 2px 5px; line-height: 18px; font-weight: normal; color: #000;\">".$i."</a>";
						endif;
						$displayspan = true;
					elseif ($displayspan):
						echo "<span style=\"border: 1px solid #295487; border-top: none; margin: 0px 1px;  padding: 2px 5px; line-height: 18px; font-weight: normal; color: #000;\">..</span>";
						$displayspan = false;
					endif;
				endfor;
				
				
				echo "</p>\n";
				if (intval($extern)!=1):
					echo "<form action=\"".$_SERVER['PHP_SELF']."\" id=\"form_page\" name=\"form_page\" method=\"post\">\n";
					echo "<input type=\"hidden\" id=\"page_page\" name=\"page\" value=\"".@$_REQUEST['page']."\" />\n";
					echo "<input type=\"hidden\" id=\"func\" name=\"func\" value=\"".@$_REQUEST['func']."\" />\n";
					echo "<input type=\"hidden\" id=\"op\" name=\"op\" value=\"\" />\n";
					echo "<input type=\"hidden\" id=\"element\" name=\"element\" value=\"".@$_REQUEST['element']."\" />\n";
					echo "<input type=\"hidden\" id=\"path\" name=\"path\" value=\"".@$_REQUEST['path']."\" />\n";
					if (intval($extern)>0):
						echo "<input type=\"hidden\" id=\"extern\" name=\"extern\" value=\"".intval($extern)."\" />\n";
					endif;
					echo "<input type=\"hidden\" id=\"search\" name=\"search\" value=\"".@$_REQUEST['search']."\" />\n";
					echo "</form>";
				endif;
			endif;
			if ($extern==0):
				echo "<p style=\"clear: both; line-height: 1px; font-size: 1px; margin: 0px; padding: 0px; height: 1px;\">&nbsp;</p></span>";
			endif;
			echo "</fieldset>";
			if ($extern==0):
				echo "<fieldset id=\"switchfile\" style=\"display: none;\">\n";
				echo "<legend>Datei '<span id=\"showswitchfile\">name</span>' verschieben</legend>";
				echo "<form action=\"".$_SERVER['PHP_SELF']."\" id=\"form_switchfile\" name=\"form_switchfile\" method=\"post\">\n";
				echo "Zielverzeichnis <select id=\"menu_switchfile\" name=\"menu_switchfile\">\n";
				echo "</select>";
				echo "<br /><br />";
				echo "<p><a class=\"orangefield\" href=\"#\" onClick=\"document.getElementById('switchfile').style.display = 'none';\" title=\"Abbrechen\">Abbrechen</a>&nbsp;&nbsp;&nbsp;<a class=\"greenfield\" href=\"#\" onclick=\"if (confirm(unescape('wollen sie die Datei wirklich umbenennen?'))) {document.getelementbyid('form_changefile').submit(); return false; } else { return false; }\" title=\"Umbenennen\">Umbenennen</a></p>\n";
				echo "<input type=\"hidden\" id=\"switchrenfilepath\" name=\"renfilepath\" value=\"\" />\n";
				echo "<input type=\"hidden\" id=\"switcholdfilename\" name=\"oldfilename\" value=\"\" />\n";
				echo "<input type=\"hidden\" id=\"switchop\" name=\"op\" value=\"changefile\" />\n";
				echo "</form>\n";
				echo "</fieldset>\n";
				
				echo "<fieldset id=\"changefile\" style=\"display: none;\">\n";
				echo "<legend>DIESE Datei '<span id=\"showrenamefile\">name</span>' umbenennen</legend>";
				echo "<form action=\"".$_SERVER['PHP_SELF']."\" name=\"form_changefile\" id=\"form_changefile\" method=\"post\" onsubmit=\"if (confirm(unescape('Wollen sie die Datei wirklich umbenennen?'))) {document.getElementById('form_changefile').submit(); return false; } else { return false; }\">\n";
				echo "Neuer Dateiname:&nbsp;<input type=\"text\" name=\"newfilename\" id=\"newfilename\" size=\"50\" value=\"\" /> <span id=\"showrenamefileend\">.gif</span>\n<br /><br />";
				echo "<input type=\"hidden\" id=\"changerenfilepath\" name=\"renfilepath\" value=\"\" />\n";
				echo "<input type=\"hidden\" id=\"changeoldfilename\" name=\"oldfilename\" value=\"\" />\n";
				echo "<input type=\"hidden\" id=\"changeop\" name=\"op\" value=\"changefile\" />\n";
				echo "<input type=\"hidden\" id=\"change_page\" name=\"page\" value=\"1\" />";
				echo "<input type=\"hidden\" id=\"func\" name=\"func\" value=\"".@$_REQUEST['func']."\" />";
				echo "<input type=\"hidden\" id=\"element\" name=\"element\" value=\"".@$_REQUEST['element']."\" />";
				echo "<input type=\"hidden\" id=\"path\" name=\"path\" value=\"".@$_REQUEST['path']."\" />";
				if (intval($extern)>0):
					echo "<input type=\"hidden\" id=\"extern\" name=\"extern\" value=\"".intval($extern)."\" />";
				endif;
				echo "<a class=\"redfield\" href=\"#\" onclick=\"document.getElementById('changefile').style.display = 'none';\" title=\"Abbrechen\">Abbrechen</a>&nbsp;&nbsp;&nbsp;<a class=\"greenfield\" href=\"javascript:;\" onclick=\"if (confirm(unescape('Wollen sie die Datei wirklich umbenennen?'))) {document.getElementById('form_changefile').submit(); return false; } else { return false; }\" title=\"Umbenennen\">Umbenennen</a><br /><br />\n";
				echo "</form>\n";
				echo "</fieldset>\n";
			endif;
		else:
			echo "<fieldset  id=\"fieldset_listfiles\" style=\"height: auto;\" class=\"text\">\n";
			echo "<legend>".$GLOBALS['mediadesc']." im Pfad ";
			$pathinfo = "/media/".$GLOBALS['mediafolder'];
			if ($path==""):
				$pathinfo.= "/";
			else:
				$pathinfo.= $path;
			endif;
			echo str_replace("//","/",$pathinfo);
			echo "</legend>";
			echo "Keine Bilder im Verzeichnis vorhanden.";
			echo "</fieldset>";
		endif;
	endif;
	}
endif; // listFiles()

if (!(function_exists("givebackThumb"))):
function givebackThumb($imagewidth,$imageheight,$thumbwidth,$thumbheight) {
	if ($imagewidth!="" && $imageheight!=""):
		if ($imagewidth>=$imageheight):
			// breite ist groesser/gleich der hoehe => querformat
			if ($imagewidth<=$thumbwidth && $imageheight<=$thumbheight):
				// bild ist kleiner als maximale thumbnail-abmessung
				$thumb[0] = $imagewidth;
				$thumb[1] = $imageheight;
			elseif ($imagewidth>$thumbwidth):
				// bild ist breiter als maximale thumbnail-breite
				$scalefactor = $imagewidth/$thumbwidth;
				$thumb[0] = $thumbwidth;
				$thumb[1] = ceil($imageheight/$scalefactor);
			elseif ($imageheight>$thumbheight):
				// bild ist hoeher als maximale thumbnail-hoehe
				$scalefactor = $imageheight/$thumbheight;
				$thumb[0] = ceil($imagewidth/$scalefactor);
				$thumb[1] = $thumbheight;
			else:
				$thumb[0] = $thumbwidth;
				$thumb[1] = $thumbheight;
			endif;
			if ($thumb[1]>$thumbheight):
				$thumb = givebackThumb($thumb[0],$thumb[1],$thumbwidth,$thumbheight);
			endif;
		else:
			if ($imagewidth<=$thumbwidth && $imageheight<=$thumbheight):
				// bild ist kleiner als maximale thumbnail-abmessung
				$thumb[0] = $imagewidth;
				$thumb[1] = $imageheight;
			elseif ($imageheight>$thumbheight):
				// bild ist hoeher als maximale thumbnail-hoehe
				$scalefactor = $imageheight/$thumbheight;
				$thumb[0] = ceil($imagewidth/$scalefactor);
				$thumb[1] = $thumbheight;
			elseif ($imagewidth>$thumbwidth):
				// bild ist breiter als maximale thumbnail-breite
				$scalefactor = $imagewidth/$thumbwidth;
				$thumb[0] = $thumbwidth;
				$thumb[1] = ceil($imageheight/$scalefactor);
			else:
				$thumb[0] = $thumbwidth;
				$thumb[1] = $thumbheight;
			endif;
			if ($thumb[0]>$thumbwidth):
				$thumb = givebackThumb($thumb[0],$thumb[1],$thumbwidth,$thumbheight);
			endif;
		endif;
	else:
		$thumb[0] = 100;
		$thumb[1] = 75;
	endif;
	return $thumb;
	}
endif; // givebackThumb
	
if (!(function_exists("fileinuse"))):
function fileinuse($path, $file) {
	unset($used);
	$checkfile = str_replace("/media/", "/", $file);
	$checkfile = str_replace("/images/", "/", $checkfile);
	$checkfile = str_replace("/screen/", "/", $checkfile);
	$checkfile = str_replace("/download/", "/", $checkfile);
	$checkfile = str_replace("//", "/", str_replace("//", "/", $checkfile));

	// check contents and menupoints
	$sql = "SELECT `mid`, `cid` FROM `content` WHERE `valuefields` LIKE '%".$file."%'";
	$res = mysql_query($sql);
	$cnum = mysql_num_rows($res);
	while ($data = mysql_fetch_row($res)):
		$used[$data[0]] = $data[0];
		$content[$data[0]][] = $data[1];
	endwhile;

	$sql = "SELECT c.`mid`, g.`id` FROM `globalcontent` AS g, `content` AS c WHERE g.`id` = c.`globalcontent_id` AND (g.`valuefield` LIKE '%" . $file . "%')";
	$res = mysql_query($sql);
	$cnum+= mysql_num_rows($res);
	while ($data = mysql_fetch_row($res)):
		$used[$data[0]] = $data[0];
		$globalcontent[$data[0]][] = $data[1];
	endwhile;
	$sql = "SELECT `mid` FROM `menu` WHERE `imageon`='" . $file . "' OR `imageoff`='" . $file . "' OR `imageakt`='" . $file . "' OR `imageclick`='" . $file . "'";
	$res = mysql_query($sql);
	$cnum+= mysql_num_rows($res);
	while ($data = mysql_fetch_row($res)):
		$used[$data[0]] = $data[0];
		$menuimg[$data[0]][] = $data[0];
	endwhile;
	
	// stylesheets pruefen
	$sql = "SELECT `id` FROM `stylesheets` WHERE `stylesheet` LIKE '%" . $file . "%'";
	$res = mysql_query($sql);
	$cnum+= mysql_num_rows($res);
	while ($data = mysql_fetch_row($res)):
		$used[$data[0]] = $data[0];
		$style[$data[0]] = $data[0];
	endwhile;
	
	if(isset($used)):
		return true;
	else:
		return false;
	endif;
	}
endif; // fileinuse

// 2017-11-10
if (!(function_exists("fileUsage"))):
function fileUsage($path, $file) {
	$used = array();
	$checkfile = str_replace("/media/", "/", $file);
	$checkfile = str_replace("/images/", "/", $checkfile);
	$checkfile = str_replace("/screen/", "/", $checkfile);
	$checkfile = str_replace("/download/", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
	$checkfile = str_replace("//", "/", $checkfile);
    $file = strtolower($checkfile);
    if (trim($file)!=""):
        // check contents and menupoints
        $sql = "SELECT c.`mid` FROM `content` AS c, `menu` AS m WHERE c.`valuefields` LIKE '%".mysql_real_escape_string(trim($file))."%' AND c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` > 0 AND c.`mid` = m.`mid`";
        $res = doSQL($sql);
        if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $used[] = $data['mid'];
                $usetype[] = 'content';
            endforeach;
        endif;

        $sql = "SELECT c.`mid` FROM `globalcontent` AS g, `content` AS c, `menu` AS m WHERE g.`id` = c.`globalcontent_id` AND (g.`valuefield` LIKE '%" .mysql_real_escape_string(trim($file)). "%') AND g.`trash` = 0 AND c.`trash` = 0 AND m.`trash` = 0 AND c.`mid` > 0 AND c.`mid` = m.`mid`";
        $res = doSQL($sql);
        if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $used[] = $data['mid'];
                $usetype[] = 'global';
            endforeach;
        endif;

        $sql = "SELECT `mid` FROM `menu` WHERE (`imageon`='" .mysql_real_escape_string(trim($file)). "' OR `imageoff`='" .mysql_real_escape_string(trim($file)). "' OR `imageakt`='" .mysql_real_escape_string(trim($file)). "' OR `imageclick`='" .mysql_real_escape_string(trim($file)). "') AND `trash` = 0";
        $res = doSQL($sql);
        if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $used[] = $data['mid'];
                $usetype[] = 'menu';
            endforeach;
        endif;

        // stylesheets pruefen
        $sql = "SELECT `id` FROM `stylesheets` WHERE `stylesheet` LIKE '%" .mysql_real_escape_string(trim($file)). "%'";
        $res = doSQL($sql);
        if ($res['num']>0):
            foreach ($res['set'] AS $rsk => $data):
                $used[] = $data['id'];
                $usetype[] = 'style';
            endforeach;
        endif;
        
        $mod_sql = "SELECT `affectedcontent` FROM `modules` WHERE `affectedcontent` != ''";
		$mod_res = mysql_query($mod_sql);
		$mod_num = 0; if ($mod_res): $mod_num = mysql_num_rows($mod_res); endif;
		if ($mod_num>0):
			for ($m=0; $m<$mod_num; $m++):
				$affected = unserializeBroken(mysql_result($mod_res,$m,'affectedcontent'));
				if (is_array($affected) && count($affected)>0):
					foreach($affected AS $table => $cells):
						foreach ($cells AS $cellselect):
							$sql = "SELECT * FROM `".$table."` WHERE `".$cellselect."` LIKE '%".mysql_real_escape_string(trim($checkfile))."%'";
							$res = doSQL($sql);
							if ($res['num']>0): $used[] = $sql; endif;
						endforeach;
					endforeach;
				endif;
			endfor;
		endif;    
    
        if(count($used)>0):
            return true;
        else:
            return false;
        endif;
    else:
        return false;
    endif;
	}
endif; // fileUsage

// 2016-03-30
if (!(function_exists("returnJScreateUploader"))):
function returnJScreateUploader($uploaderid = '', $mediafolder = 'images', $targetfolder = '', $autoscale = 0, $thumbsize = 300) {
	// create upload area div	
	echo "<div id='".$uploaderid."'></div>";
	// load system fileupload script
	echo "<script src='/".$_SESSION['wspvars']['wspbasedir']."/data/script/filemanagement/fileuploader.js' type='text/javascript'></script>\n";
	// init script to create uploader
	echo "<script type='text/javascript'>\n\n";
	echo "function createUploader() {\n";
		echo "var uploader = new qq.FileUploader({\n";
			echo "element: document.getElementById('".$uploaderid."'),\n";
			echo "listElement: document.getElementById('".$uploaderid."-items'),\n";
			echo "action: '/".$_SESSION['wspvars']['wspbasedir']."/uploadmedia.php',\n";
			echo "template: '<div class=\"qq-uploader\">' + \n";
				echo "'<div class=\"qq-upload-drop-area\"><span>".returnIntLang('media upload drop files here', false)."</span></div>' + \n"; //'
				echo "'<div class=\"qq-upload-button\">".returnIntLang('media upload drop or select files here', false)."</div>' + \n"; //'
				echo "'<ul class=\"qq-upload-list\"></ul>' + \n";
				echo "'</div>',\n";
			echo "fileTemplate: '<li>' +\n";
				echo "'<div class=\"filegrabber\">".returnIntLang("media uploader upload", false)."</div>' + \n";
				echo "'<div class=\"qq-upload-spinner\"></div>' + \n";
				echo "'<div class=\"qq-upload-file\"></div>' + \n";
				echo "'<div class=\"qq-upload-size\"></div>' + \n";
				echo "'<a class=\"qq-upload-cancel\" href=\"#\">".returnIntLang("media cancel upload", false)."</a>' + \n";
				echo "'</li>',\n";
			echo "classes: {\n";
				echo "button: 'qq-upload-button',\n";
				echo "drop: 'qq-upload-drop-area',\n";
				echo "dropActive: 'qq-upload-drop-area-active',\n";
				echo "list: 'qq-upload-list',\n";
				echo "file: 'qq-upload-file',\n";
				echo "spinner: 'qq-upload-spinner',\n";
				echo "size: 'qq-upload-size',\n";
				echo "cancel: 'qq-upload-cancel',\n";
				echo "success: 'qq-upload-success',\n";
				echo "fail: 'qq-upload-fail'\n";
				echo "},\n";
			echo "params: {\n";
				echo "prescale: '".$autoscale."',\n";
				echo "thumbsize: '".$thumbsize."',\n";
				echo "targetfolder: '/media/".$mediafolder."/".$targetfolder."/',\n";
				echo "mediafolder: '".$mediafolder."',\n";
				echo "},\n";
			echo "minSizeLimit: 0,\n";
			echo "debug: false\n";
			echo "});\n";
		echo "}\n\n";
	echo "window.onload = createUploader;\n\n";
	echo "</script>";
	
	}
endif; // returnJScreateUploader

// EOF ?>