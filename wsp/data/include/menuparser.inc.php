<?php
/**
 * parser-functions to create dynamic menus from menu-template
 * @author stefan@covi.de
 * @since 3.3
 * @version 7.0
 * @lastchange 2020-03-05
 */

if (!(function_exists('renameFile'))):
// finds all MENUVAR entries in template and replaces them with menu data
function renameFile($mid = 0, $lang = 'de') {
	$newlink = returnLinkedText("[%PAGE:".$mid."%]");
	$oldlink_sql = "SELECT `mid`, `filename` FROM `menu` WHERE `forwarding_id` = " .intval($mid)." AND `trash` = 1 ORDER BY `mid` desc";
	$oldlink_res = doSQL($oldlink_sql);
	if($oldlink_res['num']>0):
		$oldlink = returnLinkedText("[%PAGE:".intval($oldlink_res['set'][0]['mid'])."%]");
	endif;
	// grep parsedir information
	$parsedir = intval(getWSPProperties('parsedirectories'));
    if($lang != "de"):
		$rnlang = "/".$lang."/";
	else:
		$rnlang = "";
	endif;

    if(intval($mid)>0):
		$ftpAttempt = 3;
		$counterOld = $ftpAttempt;
		$ftp = false;
		
		while (!$ftp && ($ftpAttempt > 0)):
			if ($counterOld != $ftpAttempt):
				$counterOld = $ftpAttempt;
				sleep(1);
			endif;
			$ftp = ftp_connect($_SESSION['wspvars']['ftphost'], $_SESSION['wspvars']['ftpport']);
			$ftpAttempt--;
		endwhile;
		if ($ftp === false):
			$aReturn[0] = true;
			addWSPMsg('errormsg', returnIntLang('publisher cant upload menufile1', false)." ... ".returnIntLang('publisher cant upload menufile2 connect', false));
		elseif (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])):
			$aReturn[0] = true;
			addWSPMsg('errormsg', returnIntLang('publisher cant upload menufile1', false)." ... ".returnIntLang('publisher cant upload menufile2 login', false));
		else:
			if (!ftp_rename($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$oldlink), str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$newlink))):
				addWSPMsg('errormsg', str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$oldlink)."<br />".str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$newlink)."<br />");
				$returnstat = false;
			else:
				$returnstat = true;
			endif;
		endif;
	endif;
	}
endif; //renameFile();

if (!(function_exists('createMenu'))) {
    // finds all MENUVAR entries in template and replaces them with menu data
    function createMenu($buf, $mid = 0, $lang = 'de', $preview = false) {
        $pos = 0; $m = 0; $tgt = ($preview?' target="_top" ':'');
        while (!($pos===false)) {
            // keep counting menus on page to use same menuvar with different params maybe
            $m++;
            // find menuvar placeholder
            $pos = strpos($buf, '[%MENUVAR:');
            if ($pos===false) {
                $tmp = $buf;
            }
            else {
                $tmp = substr($buf, 0, $pos);
                $buf = substr($buf, $pos+10);
                $pos = strpos($buf, '%]');
                $guid = trim(substr($buf, 0, $pos));
                $mnutmp = '';
                // get some facts to given menu
                $mid_sql = "SELECT `level`, `connected`, `position` FROM `menu` WHERE `mid` = ".intval($mid);
                $mid_res = doSQL($mid_sql);
                $mid_num = $mid_res['num'];
                // get max lvl info
                $lvl_res = doResultSQL("SELECT MAX(`level`) FROM `menu` WHERE `visibility` = 1 AND `trash` = 0");
                $lvl = 0; if ($lvl_res!==false): $lvl = intval($lvl_res); endif;
                // get facts to menutemplate
                $menu_sql = 'SELECT `parser`, `code`, `startlevel` FROM `templates_menu` WHERE `guid` LIKE "'.escapeSQL($guid).'"';
                $menu_res = doSQL($menu_sql);
                if ($menu_res['num']>0) {
                    $stl_sql = '';
                    if (trim($menu_res['set'][0]['parser'])!="") {
                        // interpreter usage
                        $mnutmp .= "<?php\n";
                        $mnutmp .= "@include DOCUMENT_ROOT.\"/data/menu/".trim($menu_res['set'][0]['parser'])."\";\n";
                        $mnutmp .= "\$menuparser = new \$menuClass();\n";
                        $mnutmp .= "echo \$menuparser->getMenu();\n";
                        $mnutmp .= "?>";
                    }
                    else {
                        $mncd = showMenuDesign(trim($menu_res['set'][0]['code']));
                        $startmid = 0;
                        if (array_key_exists('MENU.SHOW',$mncd[0]) && trim($mncd[0]['MENU.SHOW'])!='') {
                            // some menupoints were selected to be displayed ..
                        }
                        else {
                            $midarray = array_merge(returnIDTree($mid),returnIDRoot($mid));
                            $stl_sql = "SELECT `mid` FROM `menu` WHERE `level` = ".(intval($menu_res['set'][0]['startlevel'])-1)." AND `mid` IN (".implode(",", $midarray).") AND `trash` = 0 ORDER BY `position` ASC LIMIT 0,1";
                            $stl_res = doResultSQL($stl_sql);
                            if ($stl_res!==false): $startmid = intval($stl_res); endif;
                        }					
                        $menuparser = buildMenu($mncd, intval($menu_res['set'][0]['startlevel']), $startmid, intval($mid), $lang, 0, count($mncd), $preview);
                        $mnutmp .= "\n<!-- MENUVAR:".strtoupper($guid).":START -->\n";
                        $mnutmp .= $menuparser['buildcss'];
                        $mnutmp .= "<div id=\"menucontainer-".strtolower($guid)."\" class=\"menucontainer-".strtolower($guid)."\">";
                        $mnutmp .= $menuparser['menucode'];
                        $mnutmp .= "</div>";
                        $mnutmp .= "\n<!-- MENUVAR:".strtoupper($guid).":END -->\n";
                    }
                }
                else if ($guid=='FULLLIST') {
                    $mnutmp.= "\n<!-- MENUVAR:FULLLIST:START -->\n";
                    $tpl = array();
                    for ($l=0;$l<$lvl;$l++):
                        $tpl[] = array('TYPE' => 'LIST');
                    endfor;
                    $menuparser = buildMenu($tpl, 1, 0, $mid, $lang, 0, 0, $preview);
                    $mnutmp.= $menuparser['buildcss'];
                    $mnutmp.= "<div class=\"menucontainer-fulllist\">";
                    $mnutmp.= $menuparser['menucode'];
                    $mnutmp.= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:FULLLIST:END -->\n";
                }
                else if ($guid=='FULLDIV') {
                    $mnutmp.= "\n<!-- MENUVAR:FULLDIV:START -->\n";
                    $tpl = array();
                    for ($l=0;$l<$lvl;$l++):
                        $tpl[] = array('TYPE' => 'LINK');
                    endfor;
                    $menuparser = buildMenu($tpl, 1, 0, $mid, $lang, 0, 0, $preview);
                    $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-fulldiv\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:FULLDIV:END -->\n";
                }
                else if ($guid=='FULLSELECT') {
                    $mnutmp.= "\n<!-- MENUVAR:FULLSELECT:START -->\n";
                    $tpl = array();
                    for ($l=0;$l<$lvl;$l++):
                        $tpl[] = array('TYPE' => 'SELECT');
                    endfor;
                    $menuparser = buildMenu($tpl, 1, 0, $mid, $lang, 0, 0, $preview);
                    $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-fullselect\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:FULLSELECT:END -->\n";
                }
                else if ($guid=='HORIZONTALLIST') {
                    $mnutmp.= "\n<!-- MENUVAR:HORIZONTALLIST:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'LIST')), intval($mid_res['set'][0]['level']), intval($mid_res['set'][0]['connected']), $mid, $lang, 0, 1, $preview);
                    $mnutmp.= $menuparser['buildcss'];
                    $mnutmp.= "<div class=\"menucontainer-horizontallist\">";
                    $mnutmp.= $menuparser['menucode'];
                    $mnutmp.= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:HORIZONTALLIST:END -->\n";
                }
                else if ($guid=='HORIZONTALDIV') {
                    $mnutmp.= "\n<!-- MENUVAR:HORIZONTALDIV:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'LINK')), intval($mid_res['set'][0]['level']), intval($mid_res['set'][0]['connected']), $mid, $lang, 0, 1, $preview);
                    $mnutmp.= $menuparser['buildcss'];
                    $mnutmp.= "<div class=\"menucontainer-horizontaldiv\">";
                    $mnutmp.= $menuparser['menucode'];
                    $mnutmp.= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:HORIZONTALDIV:END -->\n";
                }
                else if ($guid=='HORIZONTALSELECT') {
                    $mnutmp.= "\n<!-- MENUVAR:HORIZONTALSELECT:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'SELECT')), intval($mid_res['set'][0]['level']), intval($mid_res['set'][0]['connected']), $mid, $lang, 0, 1, $preview);
                    $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-horizontalselect\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:HORIZONTALSELECT:END -->\n";
                }
                else if ($guid=='SUBLIST') {
                    $mnutmp.= "\n<!-- MENUVAR:SUBLIST:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'LIST')), intval($mid_res['set'][0]['level']), $mid, $mid, $lang, 0, 1, $preview);
                    $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-sublist\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:SUBLIST:END -->\n";
                }
                else if ($guid=='SUBDIV') {
                    $mnutmp.= "\n<!-- MENUVAR:SUBDIV:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'LINK')), intval($mid_res['set'][0]['level']), $mid, $mid, $lang, 0, 1, $preview);
                    $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-subdiv\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:SUBDIV:END -->\n";
                }
                else if ($guid=='SUBSELECT') {
                    $mnutmp.= "\n<!-- MENUVAR:SUBSELECT:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'SELECT')), intval($mid_res['set'][0]['level']), $mid, $mid, $lang, 0, 1, $preview);
                    $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-subselect\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:SUBSELECT:END -->\n";
                }
                else if ($guid=='LINKLAST') {
                    $mnutmp.= "\n<!-- MENUVAR:LINKLAST:START -->\n";
                    if ($mid_num>0):
                        $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `position` < ".intval($mid_res['set'][0]['position'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` DESC LIMIT 0,1";
                        $link_res = doSQL($link_sql);
                        if ($link_res['num']>0):
                            if ($preview):
                                $lnk = "/".WSP_DIR."/showpreview.php?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
                            else:
                                $pathdata = fileNamePath(intval($link_res['set'][0]['mid']), 0, 0, 0);
                                if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['path']);
                                }
                                else {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['file']);
                                }
                            endif;
                            $mnutmp.= "<a class=\"linklast\" ".$tgt." href=\"".$lnk."\">";
                            $dsc = trim($link_res['set'][0]['description']);
                            $lng = unserializeBroken($link_res['set'][0]['langdescription']);
                            if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
                            $mnutmp.= $dsc;
                            $mnutmp.= "</a>";
                        endif;
                    endif;
                    $mnutmp.= "\n<!-- MENUVAR:LINKLAST:END -->\n";
                }
                else if ($guid=='LINKLASTALL') {
                    // goes ROUND even to last entry
                    $mnutmp.= "\n<!-- MENUVAR:LINKLASTALL:START -->\n";
                    if ($mid_res['num']>0):
                    $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `position` < ".intval($mid_res['set'][0]['position'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` DESC LIMIT 0,1";
					$link_res = doSQL($link_sql);
					if ($link_res['num']==0):
                        // find LARGEST position to return last entry in list as "before" link
                        $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` DESC LIMIT 0,1";
                        $link_res = doSQL($link_sql);
                    endif;
					if ($link_res['num']>0):
						if ($preview):
							$lnk = "/".WSP_DIR."/showpreview.php?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
						else:
							$pathdata = fileNamePath(intval($link_res['set'][0]['mid']), 0, 0, 0);
                            if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                                $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['path']);
                            }
                            else {
                                $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['file']);
                            }
						endif;
						$mnutmp.= "<a class=\"linklast linklast-all\" ".$tgt." href=\"".$lnk."\">";
						$dsc = trim($link_res['set'][0]['description']);
						$lng = unserializeBroken($link_res['set'][0]['langdescription']);
						if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
						if(isset($guidtext) && trim($guidtext)!=''):
                            $mnutmp.= trim($guidtext);
                        else:
                            $mnutmp.= $dsc;
						endif;
                        $mnutmp.= "</a>";
                    endif;
                    endif;
                    $mnutmp.= "\n<!-- MENUVAR:LINKLASTALL:END -->\n";
                }
                else if ($guid=='LINKNEXT') {
                    $mnutmp.= "\n<!-- MENUVAR:LINKNEXT:START -->\n";
                    if ($mid_num>0):
                        $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `position` > ".intval($mid_res['set'][0]['position'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` ASC LIMIT 0,1";
                        $link_res = doSQL($link_sql);
                        $link_num = intval($link_res['num']);
                        if ($link_num>0) {
                            if ($preview) {
                                $lnk = "/".WSP_DIR."/showpreview.php?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
                            }
                            else {
                                $pathdata = fileNamePath(intval($link_res['set'][0]['mid']), 0, 0, 0);
                                if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['path']);
                                }
                                else {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['file']);
                                }
                            }
                            $mnutmp.= "<a class=\"linkup\" ".$tgt." href=\"".$lnk."\">";
                            $dsc = trim($link_res['set'][0]['description']);
                            $lng = unserializeBroken(trim($link_res['set'][0]['langdescription']));
                            if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
                            $mnutmp.= $dsc;
                            $mnutmp.= "</a>";
                        };
                    endif;
                    $mnutmp.= "\n<!-- MENUVAR:LINKNEXT:END -->\n";
                }
                else if ($guid=='LINKNEXTALL') {
                    $mnutmp.= "\n<!-- MENUVAR:LINKNEXTALL:START -->\n";
                    if ($mid_res['num']>0) {
                        $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `connected`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `position` > ".intval($mid_res['set'][0]['position'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` ASC LIMIT 0,1";
                        $link_res = doSQL($link_sql);
                        if ($link_res['num']==0) {
                            // find LOWEST position to return first entry in list as "next" link even if there is no next page 
                            $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex`, `connected` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` ASC LIMIT 0,1";
                            $link_res = doSQL($link_sql);
                        }
                        if ($link_res['num']>0) {
                            if ($preview) {
                                $lnk = "/".WSP_DIR."/showpreview.php?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
                            }
                            else {
                                $pathdata = fileNamePath(intval($link_res['set'][0]['mid']), 0, 0, 0);
                                if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['path']);
                                }
                                else {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['file']);
                                }
                            }
                            $mnutmp.= "<a class=\"linkup\" ".$tgt." href=\"".$lnk."\">";
                            $dsc = setUTF8(trim($link_res['set'][0]['description']));
                            $lng = unserializeBroken($link_res['set'][0]['langdescription']);
                            if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
                            if(isset($guidtext) && trim($guidtext)!=''):
                                $mnutmp.= trim($guidtext);
                            else:
                                $mnutmp.= $dsc;
                            endif;
                            $mnutmp.= "</a>";
                        }
                    }
                    $mnutmp.= "\n<!-- MENUVAR:LINKNEXTALL:END -->\n";
                }
                else if ($guid=='LINKUP') {
                    $mnutmp.= "\n<!-- MENUVAR:LINKUP:START -->\n";
                    if ($mid_num>0):
                        $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex` FROM `menu` WHERE `mid` = ".intval($mid_res['set'][0]['connected'])." AND `visibility` = 1 AND `trash` = 0 LIMIT 0,1";
                        $link_res = doSQL($link_sql);
                        $link_num = intval($link_res['num']);
                        if ($link_num>0) {
                            if ($preview) {
                                $lnk = "/".WSP_DIR."/showpreview.php?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
                            }
                            else {
                                $pathdata = fileNamePath(intval($link_res['set'][0]['mid']), 0, 0, 0);
                                if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['path']);
                                }
                                else {
                                    $lnk = cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['file']);
                                }
                            }
                            $mnutmp.= "<a class=\"linkup\" ".$tgt." href=\"".$lnk."\">";
                            $dsc = trim($link_res['set'][0]['description']);
                            $lng = unserializeBroken(trim($link_res['set'][0]['langdescription']));
                            if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
                            $mnutmp.= $dsc;
                            $mnutmp.= "</a>";
                        };
                    endif;
                    $mnutmp.= "\n<!-- MENUVAR:LINKUP:END -->\n";
                }
                // no menutemplate was found
                else {
                    addWSPMsg('errormsg', returnIntLang('publisher menutemplate not found1').$guid.returnIntLang('publisher menutemplate not found2'));
                    $mnutmp.= "<?php /* no menu found to parse width guid ".$guid." */ ?>";
                }

                // hier wird das gebaute menü in den code eingefügt und $buf wieder komplett zurückgegeben
                // try to write parsed menu to /data/menu/
                $returnstat = false; 
                if (!($preview)) {
                    // create the file name
                    $menufile = $mid.".".$m.".";
                    if ($lang!='') {
                        $menufile.= $lang.".";
                    }
                    $menufile.= strtolower($guid).".menu.inc";
                    // write temp file to user dir
                    $tmppath = cleanPath(DOCUMENT_ROOT.'/'.WSP_DIR.'/tmp/'.$_SESSION['wspvars']['usevar'].'/');
                    $tmpfile = tempnam($tmppath, 'mnu');
					$fh = fopen($tmpfile, "r+");
					fwrite($fh, trim($mnutmp));
					fclose($fh);
                    // do the final copy thing
                    $con = false;
                    if (isset($_SESSION['wspvars']['ftp']) && $_SESSION['wspvars']['ftp']!==false) {
                        $con = true;
                    } else if (isset($_SESSION['wspvars']['srv']) && $_SESSION['wspvars']['srv']!==false) {
                        $con = true;
                    }
                    if ($con===true) {
                        // create /data/menu/ directory if not avaiable
                        if (createFolder('/data/menu/')) {
                            // copy tmp file from user dir to final menu destination
                            if (!copyFile(str_replace(DOCUMENT_ROOT, '', $tmpfile), '/data/menu/'.$menufile.'.php')) {
                                addWSPMsg('errormsg', returnIntLang('publisher could not copy menu file to final destination', false));
                            }
                        } else {
                            addWSPMsg('errormsg', returnIntLang('publisher could not create menu directory', false));
                        }
                    } else {
                        addWSPMsg('errormsg', returnIntLang('publisher cant upload menufile1', false)." \"".$menufile."\" ".returnIntLang('publisher cant upload menufile2 no connect', false));
                    }
                    // unlinking is only nessessary, if the file was copied by ftp
                    // otherwise it was already moved bei srv part of copy function
                    @unlink($tmpfile);
                }

                // replace menutmp with include code if copying of menu file was done
                $tmp.= $mnutmp.substr($buf, $pos+2);
                $buf = $tmp;
            }
        }
        return $tmp;
    }
}

// return display times for menupoint links
if (!(function_exists('getShowtimeLink'))) {
    function getShowtimeLink($mid) {	
        if ($mid>0):
            $st_sql = "SELECT m.`weekday`, m.`showtime` FROM `menu` m WHERE (m.`weekday` > 0 OR m.`showtime` != '') AND m.`mid` = ".intval($mid);
            $st_res = doSQL($st_sql);
            if($st_res['num']>0):
                $cDay = intval($st_res['set'][0]['weekday']);
                $cTime = unserializeBroken($st_res['set'][0]['showtime']);
                if($cDay>0 && (is_array($cTime) && count($cTime)>0)):
                    // weekday and time are set
                    // create timeset
                    $datesArray = array();
                    foreach($cTime AS $ck => $cv):
                        $datesArray[] = "(time()>=".$cv[0]." AND time()<=".$cv[1].")";
                    endforeach;
                    $timesString = "(".implode(" OR ", $datesArray).")";
                    // create dayset
                    $wda = array(1,2,3,4,5,6,0);
                    for ($sd=6;$sd>=0;$sd--):
                        if ($cDay-pow(2,$sd)>=0):
                            $weekday[] = $wda[$sd];
                            $cDay = $cDay-(pow(2,$sd));
                        endif;
                    endfor;
                    foreach($weekday AS $key => $value):
                        $daysArray[] = "(date(\"w\")==".$value.")";
                    endforeach;
                    $daysString = "(".implode(" OR ", $daysArray).")";
                    $showtimeLink = "<"."?"."php if(" . $timesString . " AND " . $daysString . "): "."?".">";
                elseif($cDay>0 && !(is_array($cTime) && count($cTime)>1)):
                    // only weekday set
                    $wda = array(1,2,3,4,5,6,0);
                    for ($sd=6;$sd>=0;$sd--):
                        if ($cDay-pow(2,$sd)>=0):
                            $weekday[] = $wda[$sd];
                            $cDay = $cDay-(pow(2,$sd));
                        endif;
                    endfor;
                    foreach($weekday AS $key => $value): $daysArray[] = "(date(\"w\")==".$value.")"; endforeach;
                    $daysString = implode(" OR ", $daysArray);
                    $showtimeLink = "<"."?"."php if(".$daysString."): "."?".">";
                elseif($cDay==0 && (is_array($cTime) && count($cTime)>0)):
                    // only time set
                    $datesArray = array();
                    foreach ($cTime AS $ck => $cv):
                        $datesArray[] = "(time()>=".$cv[0]." AND time()<=".$cv[1].")";
                    endforeach;
                    $showtimeLink = "<"."?"."php if(".implode(" OR ", $datesArray)."): "."?".">";
                else:
                    // nothing set - why ever
                    $showtimeLink = "";
                endif;
            else:
                // no result » nothing set
                $showtimeLink = "";
            endif;
        else:
            $showtimeLink = "";
        endif;
        return $showtimeLink;
    }	// getshowtimeLink()
}

// return login facts for menupoint links
if (!(function_exists('getLoginLink'))) {
    function getLoginLink($mid) {
        $loginLink = "";
        $li_sql = "SELECT m.`login`, m.`logincontrol` FROM `menu` m WHERE m.`login` > 0 AND m.`mid` = ".intval($mid);
        $li_res = doSQL($li_sql);
        if($li_res['num']>0) {
            $logincontrol = unserializeBroken($li_res['set'][0]['logincontrol']);
            if (is_array($logincontrol) && count($logincontrol)>0) {
                $loginLink = "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true ";
                $UID = array();
                foreach($logincontrol AS $lk => $lv) {
                    $UID[] = "\$_SESSION['wsppage']['uservalue']==".$lv;
                }
                $loginLink.= " AND (".implode(" OR ", $UID).")";
                $loginLink.= "): "."?".">";
            }
            else {
                $loginLink = "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true): "."?".">";
            }
        }
        return $loginLink;
    }
} // getLoginLink()

if (!(function_exists('buildMenu'))) {
    // translated menucode as array(), $startlevel, $basemid => start on this base, actual mid, lang, actlevel, maxlevel, preview
    function buildMenu($code=array(), $startlevel=1, $basemid=0, $actmid=0, $lang = 'de', $actl=0, $maxl=0, $preview=false) {
        if ($maxl==0) { $maxl = ( (is_array($code)) ? count($code) : 0 ); }
        // grep parsedir information
        $parsedir = intval(getWSPProperties('parsedirectories'));
        // return midTree to set "active" menupoints
        $actmidtree = returnIDTree($actmid);
        $str_num = 0;
        $str_sql = '';
        $not_sql = '';
        $tgt = ($preview?' target="_top" ':'');
        if (isset($code[0]) && is_array($code[0]) && array_key_exists('MENU.HIDE',$code[0]) && trim($code[0]['MENU.HIDE'])!='') {
            $tmpdenymidlist = explode(";", $code[0]['MENU.HIDE']);
            if (is_array($tmpdenymidlist) && count($tmpdenymidlist)>0) {
                $not_sql = " AND `mid` NOT IN ('".implode("','", $tmpdenymidlist)."') ";
            }
        }
        if (isset($code[0]) && is_array($code[0]) && array_key_exists('MENU.SHOW',$code[0]) && trim($code[0]['MENU.SHOW'])!='') {
            $str_res = doSQL("SELECT `mid` FROM `menu` WHERE `mid` IN ('".implode("','", explode(";", $code[0]['MENU.SHOW']))."') ".$not_sql." AND `trash` = 0 AND (`denylang` NOT LIKE '%2:\"".$lang."\"%' OR `denylang` = NULL)");
            $str_num = intval($str_res['num']);
            $actl = 1;
            $maxl = 1;
        } else if ($actl<$maxl) {
            $str_res = doSQL("SELECT `mid`, `level` FROM `menu` WHERE `connected` = ".intval($basemid)." ".$not_sql." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC");
            $str_num = intval($str_res['num']);
        }
        $buf = array();
        $buf['data'] = array(
            'code' => $code,
            'sql' => $str_sql,
            'sql_num' => $str_num,
            'startlevel' => $startlevel,
            'basemid' => $basemid,
            'actmid' => $actmid,
            'lang' => $lang,
            'actlevel' => $actl,
            'maxlevel' => $maxl,
            'preview' => $preview
            );
        if ($preview):
            // some preview output to source
            $buf['buildcss'] = '<style>li.active { background: red; } </style>';
    //		$buf['buildcss'] = "<!-- ".$str_sql." -->\n";	// rausgenommen 7.5.15
        else:
            $buf['buildcss'] = '';
        endif;
        $buf['menucode'] = '';
        // if menupoints were found to display
        if ($str_num>0):
            // building mid list
            $midlist = array();
            $tmpmidlist = array();
            if (array_key_exists('MENU.SHOW',$code[0]) && trim($code[0]['MENU.SHOW'])!='') {
                $tmpmidlist = explode(";", $code[0]['MENU.SHOW']);
                foreach ($str_res AS $smresk => $smresv)  {
                    if (isset($smresv['mid']) && in_array(intval($smresv['mid']),$tmpmidlist)) {
                        $midlist[array_search(intval($smresv['mid']),$tmpmidlist)] = intval($smresv['mid']);
                    }
                }
                ksort($midlist);
                if ($code[0]['TYPE']=='LINK') {
                    $buf['menucode'].= '<div class="menudiv ';
                    if (isset($code[0]['CONTAINER.CLASS'])) {
                        $buf['menucode'].= $code[0]['CONTAINER.CLASS'];
                    }
                    $buf['menucode'].= ' contains-'.count($midlist).' ">';
                }
                else if (($code[0]['TYPE']=='SELECT' && $actl==0) || ($code[0]['TYPE']=='SELECT' && $actl==$maxl)) {
                    $buf['menucode'].= '<select onchange="top.location.href=this.value" class="contains-'.count($midlist).'">';
                }
                else if ($code[0]['TYPE']=='SHORTCUT') {
                    $buf['menucode'].= '';
                }
                else if ($code[0]['TYPE']!='SELECT') {
                    $buf['menucode'].= '<ul class="';
                    if (isset($code[0]['CONTAINER.CLASS'])) {
                        $buf['menucode'].= $code[0]['CONTAINER.CLASS'];
                    }
                    $buf['menucode'].= ' contains-'.count($midlist).' ">';
                }
            }	
            else {
                foreach ($str_res['set'] AS $smresk => $smresv)  {
                    $midlist[] = intval($smresv['mid']);
                }
                if ($code[0]['TYPE']=='LINK') {
                    $buf['menucode'].= '<div class="menudiv level'.intval($str_res['set'][0]['level']).' contains-'.count($midlist).' ';
                    if (isset($code[0]['CONTAINER.CLASS'])) {
                        $buf['menucode'].= $code[0]['CONTAINER.CLASS'];
                    }
                    $buf['menucode'].= '">';
                }
                else if (($code[0]['TYPE']=='SELECT' && $actl==0) || ($code[0]['TYPE']=='SELECT' && $actl==$maxl)) {
                    $buf['menucode'].= '<select class="level'.intval($str_res['set'][0]['level']).' contains-'.count($midlist).' " onchange="top.location.href=this.value">';
                }
                else if ($code[0]['TYPE']=='SHORTCUT') {
                    $buf['menucode'].= '';
                }
                else if ($code[0]['TYPE']!='SELECT') {
                    $buf['menucode'].= '<ul class="level'.intval($str_res['set'][0]['level']).' ';
                    if (isset($code[0]['CONTAINER.CLASS'])) {
                        $buf['menucode'].= $code[0]['CONTAINER.CLASS'];
                    }
                    $buf['menucode'].= ' contains-'.count($midlist).' ">';
                }
            }
            // run all menupoints
            foreach ($midlist AS $midkey => $midvalue):
                $midfacts_sql = "SELECT * FROM `menu` WHERE `mid` = ".intval($midvalue);
                $midfacts_res = doSQL($midfacts_sql);
                if ($midfacts_res['num']>0):
                    // build showtime <if>
                    if (!($preview)) {
                        $buf['menucode'].= getShowtimeLink(intval($midvalue));
                        $buf['menucode'].= getLoginLink(intval($midvalue));
                    }

                    $smres = 0;

                    if ($code[0]['TYPE']=='LINK'):
                        $buf['menucode'].= "<div class=\"level".intval($midfacts_res['set'][0]['level'])." m".intval($midfacts_res['set'][0]['mid'])." s".($smres++);
                        if (is_array($actmidtree) && in_array(intval($midfacts_res['set'][0]['mid']), $actmidtree)): $buf['menucode'].= " active"; else: $buf['menucode'].= " inactive"; endif;
                        if (trim($midfacts_res['set'][0]['addclass'])!=''): $buf['menucode'].= " ".trim($midfacts_res['set'][0]['addclass']); endif;
                        $buf['menucode'].= "\">";
                    elseif ($code[0]['TYPE']=='SELECT'):
                        $buf['menucode'].= "<option value=\"";
                        // link
                        if ($preview):
                            $buf['menucode'].= "/".WSP_DIR."/showpreview.php?previewid=".intval($midfacts_res['set'][0]['mid'])."&previewlang=".$lang;
                        else:
                            $pathdata = fileNamePath(intval($midfacts_res['set'][0]['mid']), 0, 0, 0);
                            if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1) {
                                $buf['menucode'].= cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['path']);
                            }
                            else {
                                $buf['menucode'].= cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['file']);
                            }
                        endif;
                        $buf['menucode'].= "\"";
                        if (intval($midfacts_res['set'][0]['mid'])==$actmid): $buf['menucode'].= " selected=\"selected\" "; endif;
                        $buf['menucode'].= ">";
                    elseif ($code[0]['TYPE']=='SHORTCUT'):
                        $buf['menucode'].= '';
                    else:
                        $buf['menucode'].= "<li class=\"level".intval($midfacts_res['set'][0]['level'])." m".intval($midfacts_res['set'][0]['mid'])." s".($midkey+1);
                        if (is_array($actmidtree) && in_array(intval($midfacts_res['set'][0]['mid']), $actmidtree)): $buf['menucode'].= " active"; else: $buf['menucode'].= " inactive"; endif;
                        if (trim($midfacts_res['set'][0]['addclass'])!=''): $buf['menucode'].= " ".trim($midfacts_res['set'][0]['addclass']); endif;
                        $buf['menucode'].= "\">";
                    endif;

                    if ($code[0]['TYPE']=='SELECT') {
                        for($a=0;$a<$actl;$a++) {
                            $buf['menucode'].= ". . ";
                        }
                    }
                    else {
                        // real link in structure
                        $buf['menucode'].= "<a ".$tgt." ";
                        if (trim($midfacts_res['set'][0]['jsmouseover'])!='') {
                            $buf['menucode'].= " onmouseover=\"".trim($midfacts_res['set'][0]['jsmouseover'])."\" ";
                        }
                        if (trim($midfacts_res['set'][0]['jsclick'])!=''):
                            $buf['menucode'].= " onclick=\"".trim($midfacts_res['set'][0]['jsclick'])."\" ";
                        else:
                            $buf['menucode'].= "href=\"";
                            if (trim($midfacts_res['set'][0]['offlink'])!='') {
                                $buf['menucode'].= trim($midfacts_res['set'][0]['offlink']);
                            }
                            else if ($preview) {
                                $buf['menucode'].= "/".WSP_DIR."/showpreview.php?previewid=".intval($midfacts_res['set'][0]['mid'])."&previewlang=".$lang;
                            }
                            // create internal links
                            else {
                                $pathdata = fileNamePath(intval($midfacts_res['set'][0]['mid']), 0, 0, 0);
                                if ((isset($_SESSION['wspvars']['publisherdata']['parsedirectories']) && intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1) || $parsedir==1) {
                                    $buf['menucode'].= cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['path']);
                                }
                                else {
                                    $buf['menucode'].= cleanPath('/'.(($lang!=WSP_LANG)?$lang:'').'/'.$pathdata['file']);
                                }
                            }
                            $buf['menucode'].= "\" ";
                            if (trim($midfacts_res['set'][0]['offlink'])!='') {
                                if (trim($midfacts_res['set'][0]['externtarget'])!='' && trim($midfacts_res['set'][0]['externtarget'])!='_none') {
                                    $buf['menucode'].= " target=\"".trim($midfacts_res['set'][0]['externtarget'])."\" ";	
                                }
                            }
                            else if (trim($midfacts_res['set'][0]['interntarget'])!='' && trim($midfacts_res['set'][0]['interntarget'])!='_none') {
                                $buf['menucode'].= " target=\"".trim($midfacts_res['set'][0]['interntarget'])."\" ";
                            }
                        endif;
                        // system based class informations
                        $buf['menucode'].= " class=\"level".intval($midfacts_res['set'][0]['level'])." m".intval($midfacts_res['set'][0]['mid'])." s".($midkey+1);
                        // active / inactive class of menupoint
                        if (is_array($actmidtree) && in_array($midfacts_res['set'][0]['mid'], $actmidtree)) {
                            $buf['menucode'].= " active"; 
                        }
                        else {
                            $buf['menucode'].= " inactive";
                        }
                        // own class
                        if (trim($midfacts_res['set'][0]['addclass'])!='') {
                            $buf['menucode'].= " ".trim($midfacts_res['set'][0]['addclass']);
                        }
                        // closing class attribute
                        $buf['menucode'].= " \" ";
                        // javascript mouseout
                        if (trim($midfacts_res['set'][0]['jsmouseout'])!='') {
                            $buf['menucode'].= " onmouseout=\"".trim($midfacts_res['set'][0]['jsmouseout'])."\" ";
                        }
                        $buf['menucode'].= ">";
                    }

                    // show that timesetup is active
                    if(trim(getLoginLink(intval($midvalue)))!='' && $preview) {
                        $buf['menucode'].= "#";
                    }
                    if(trim(getShowtimeLink(intval($midvalue)))!='' && $preview) {
                        $buf['menucode'].= "[";
                    }

                    $dsc = trim($midfacts_res['set'][0]['description']);
                    $lng = unserializeBroken($midfacts_res['set'][0]['langdescription']);
                    if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
                    $menuimage = '';
                    if(($code[0]['TYPE']!='SELECT')) {
                        if (is_array($actmidtree) && in_array($midvalue, $actmidtree)) {
                            if(trim($midfacts_res['set'][0]['imageakt'])!=""):
                                $menuimage = trim($midfacts_res['set'][0]['imageakt']);
                            elseif(trim($midfacts_res['set'][0]['imageon'])!=""):
                                $menuimage = trim($midfacts_res['set'][0]['imageon']);
                            endif;
                            $overout = "onmouseover=\"this.src='".trim($midfacts_res['set'][0]['imageon'])."';\" onmouseout=\"this.src='" .$menuimage."';\"";
                        }
                        else {
                            if (trim($midfacts_res['set'][0]['imageon'])!="") {
                                $menuimage = trim($midfacts_res['set'][0]['imageoff']);
                                $overout = "onmouseover=\"this.src='" .trim($midfacts_res['set'][0]['imageon']). "';\" onmouseout=\"this.src='" . $menuimage . "';\"";
                            }
                        }
                        if(!empty($menuimage)):			
                            $buf['menucode'].= "<img src=\"" . $menuimage . "\" alt=\"" . $dsc . "\" id=\"a".intval($midvalue)."\" title=\"" . $dsc . "\" border=\"0\" ". $overout ."  />";
                        else:
                            $buf['menucode'].= $dsc;
                        endif;
                    }
                    else {
                        $buf['menucode'].= $dsc;
                    }
                    unset($menuimage);

                    // end show that timesetup is active
                    if(trim(getShowtimeLink(intval($midvalue)))!='' && $preview):
                        $buf['menucode'].= "]";
                    endif;

                    if ($code[0]['TYPE']!='SELECT') { $buf['menucode'].= "</a>"; }

                    if ($code[0]['TYPE']=='LINK') {
                        $buf['menucode'].= "</div>";
                        if ((isset($code[0]['SPACER']) && $code[0]['SPACER']!='') && $midkey<(count($midlist)-1)):
                            $buf['menucode'].= "<div class=\"spacer\">".$code[0]['SPACER']."</div>";
                        endif;
                        if ($actl<$maxl) {
                            $sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($midvalue)." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
                            $sub_res = doSQL($sub_sql);
                            if ($sub_res['num']>0) {
                                $subcode = buildMenu($code, $startlevel, $midvalue, $actmid, $lang, $actl+1, $maxl, $preview);
                                $buf['menucode'].= $subcode['menucode'];
                            }
                        }
                    }
                    else if ($code[0]['TYPE']=='SELECT') {
                        $buf['menucode'].= '</option>';
                        if ($actl<$maxl) {
                            $sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($midvalue)." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
                            $sub_res = doSQL($sub_sql);
                            if ($sub_res['num']>0) {
                                $subcode = buildMenu($code, $startlevel, $midvalue, $actmid, $lang, $actl+1, $maxl, $preview);
                                $buf['menucode'].= $subcode['menucode'];
                            }
                        }
                    }
                    else if ($code[0]['TYPE']=='SHORTCUT') {
                        $buf['menucode'].= '';
                    }
                    else {	
                        if ($actl<$maxl) {
                            $sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($midvalue)." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
                            $sub_res = doSQL($sub_sql);
                            if ($sub_res['num']>0) {
                                $subcode = buildMenu($code, $startlevel, $midvalue, $actmid, $lang, $actl+1, $maxl, $preview);
                                $buf['menucode'].= $subcode['menucode'];
                            }
                        }
                        $buf['menucode'].= '</li>';
                    }

                    // close showtime <if>
                    if(trim(getShowtimeLink(intval($midvalue)))!='' && !($preview)) {
                        $buf['menucode'].= "<"."?"."php endif; "."?".">";	
                    }
                    // close login <if>
                    if(trim(getLoginLink(intval($midvalue)))!='' && !($preview)) {
                        $buf['menucode'].= "<"."?"."php endif; "."?".">";
                    }

                endif;
            endforeach;
            // close container
            if ($code[0]['TYPE']=='LINK'):
                $buf['menucode'].= '</div>';
            elseif (($code[0]['TYPE']=='SELECT' && $actl==0) || ($code[0]['TYPE']=='SELECT' && $actl==$maxl)):
                $buf['menucode'].= '</select>';
            elseif ($code[0]['TYPE']=='SHORTCUT'):
                $buf['menucode'].= '';
            else:	
                $buf['menucode'].= '</ul>';
            endif;
        endif;
        return $buf;
        }
} // buildMenu();

?>