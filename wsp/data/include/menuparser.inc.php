<?php
/**
 * parser-functions to create dynamic menus from menu-template
 * @author stefan@covi.de
 * @since 3.3
 * @version 6.11
 * @lastchange 2023-01-04
 */

if (!(function_exists('showMenuDesign'))):
// creates code array from string based definition db entry
function showMenuDesign($code) {
	$coderows = explode("LEVEL", $code);
	$menucode = array();
	$level_buf = 0;
	foreach ($coderows AS $levelvalue):
		if (trim($levelvalue) != ""):			
			$levelrows = explode("\n", str_replace("[","", str_replace("]","", str_replace("{","", str_replace("}","", stripslashes(trim($levelvalue)))))));
			if (trim($levelrows[0]) != ""):
				$level_buf = trim($levelrows[0]);
			else:
				$level_buf++;				
			endif;
			$menucode[($level_buf-1)] = array();
			foreach ($levelrows AS $codevalue):
				if (trim($codevalue) != ""):
					$codeset = explode("=", trim($codevalue));
					if (isset($codeset[1])) $menucode[($level_buf-1)][(trim($codeset[0]))] = str_replace("'", "", trim($codeset[1])); // 7.5.2015
				endif;
			endforeach;
		endif;
	endforeach;
	return $menucode;
	} 
endif; //showMenuDesign();

if (!(function_exists('renameFile'))) {
	// finds all MENUVAR entries in template and replaces them with menu data
	function renameFile($mid = 0, $lang = 'de') {
		$newlink = returnLinkedText("[%PAGE:".$mid."%]");
		$oldlink_sql = "SELECT `mid`,`filename` FROM `menu` WHERE `forwarding_id` = ".intval($mid)." AND `trash` = 1 ORDER BY `mid` desc";
		$oldlink_res = doSQL($oldlink_sql);
		if($oldlink_res['num']>0):
			$oldlink = returnLinkedText("[%PAGE:".intval($oldlink_res['set'][0]['mid'])."%]");
		endif;
		
		// grep parsedir information
		$parsedir = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'parsedirectories'"));
		
		if($lang != "de"):
			$rnlang = "/".$lang."/";
		else:
			$rnlang = "";
		endif;

		if(intval($mid)>0) {
			
			$usedirect = false;
			if (isset($_SESSION['wspvars']['directwriting']) && $_SESSION['wspvars']['directwriting']===true) {
				$usedirect = true;
			} else {
				$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])));
				if ($ftp!==false) {
					if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; addWSPMsg('errormsg', returnIntLang('publisher cant upload menufile1', false)." ... ".returnIntLang('publisher cant upload menufile2 login')); }} else { addWSPMsg('errormsg', returnIntLang('publisher cant upload menufile1', false)." ... ".returnIntLang('publisher cant upload menufile2 connect')); } if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
			}

			if ($ftp) {
				if (!ftp_rename($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$oldlink), str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$newlink))):
					addWSPMsg('errormsg', str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$oldlink)."<br />".str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$rnlang.$newlink)."<br />");
					$returnstat = false;
				else:
					$returnstat = true;
				endif;
				ftp_close($ftp);
			}
		}
	}
}

if (!(function_exists('createMenu'))):
// finds all MENUVAR entries in template and replaces them with menu data
function createMenu($buf, $mid = 0, $lang = 'de', $preview = false) {
	$pos = 0;
	while (!($pos===false)):
		// find menuvar placeholder
		$pos = strpos($buf, '[%MENUVAR:');
		if ($pos===false):
			$tmp = $buf;
		else:
			$tmp = substr($buf, 0, $pos);
			$buf = substr($buf, $pos+10);
			$pos = strpos($buf, '%]');
			$guid = trim(substr($buf, 0, $pos));
            $guidtext = explode("|", $guid);
            if (is_array($guidtext)):
                if (count($guidtext)==1):
                    // no special text given - everything will stay as it is defined
                    $guidtext = '';
                elseif (count($guidtext)==2):
                    // special text is given
                    $guid = $guidtext[0];
                    $guidtext = trim($guidtext[1]);
                else:
                    $guid = $guidtext[0];
                    $guidtext = '';
                endif;
            endif;
			$mnutmp = '';
			// get some facts to given menu
			$mid_sql = "SELECT `level`, `connected`, `position` FROM `menu` WHERE `mid` = ".intval($mid);
			$mid_res = doSQL($mid_sql);
			// get max lvl info
			$lvl_sql = "SELECT MAX(`level`) FROM `menu` WHERE `visibility` = 1 AND `trash` = 0";
			$lvl_res = doResultSQL($lvl_sql);
			$lvl = 0; if ($lvl_res!==false): $lvl = intval($lvl_res); endif;
			// get facts to menutemplate
			$menu_sql = 'SELECT `parser`, `code`, `startlevel` FROM `templates_menu` WHERE `guid` LIKE "'.$guid.'"';
			$menu_res = doSQL($menu_sql);
			if ($menu_res['num']>0):
				$stl_sql = '';
				if (trim($menu_res['set'][0]['parser'])!=""):
					// interpreter usage
					$mnutmp .= "<?php\n";
					$mnutmp .= "@include DOCUMENT_ROOT.\"/data/menu/".trim($menu_res['set'][0]['parser'])."\";\n";
					$mnutmp .= "\$menuparser = new \$menuClass();\n";
					$mnutmp .= "echo \$menuparser->getMenu();\n";
					$mnutmp .= "?>";
				else:
					$mncd = showMenuDesign(trim($menu_res['set'][0]['code']));
					if (array_key_exists('MENU.SHOW',$mncd[0]) && trim($mncd[0]['MENU.SHOW'])!=''):
						// some menupoints were selected to be displayed ..
						$startmid = 0;
					else:
						$startmid = 0;
						$midarray = array_merge(returnIDTree($mid),returnIDRoot($mid));
						$stl_sql = "SELECT `mid` FROM `menu` WHERE `level` = ".(intval($menu_res['set'][0]['startlevel'])-1)." AND `mid` IN ('".implode("','", $midarray)."') AND `trash` = 0 ORDER BY `position` ASC";
						$stl_res = doResultSQL($stl_sql);
						if ($stl_res!==false): $startmid = intval($stl_res); endif;
					endif;
					$menuparser = buildMenu($mncd, intval($menu_res['set'][0]['startlevel']), $startmid, intval($mid), $lang, 0, count($mncd), false, $preview);			
					$mnutmp .= "\n<!-- MENUVAR:".strtoupper($guid).":START -->\n";
					// $mnutmp .= $menuparser['buildcss'];
					$mnutmp .= "<div id=\"menucontainer-".strtolower($guid)."\" class=\"menucontainer-".strtolower($guid)."\">";
					$mnutmp .= $menuparser['menucode'];
					$mnutmp .= "</div>";
					$mnutmp .= "\n<!-- MENUVAR:".strtoupper($guid).":END -->\n";
				endif;
			elseif ($guid=='FULLLIST'):
				$mnutmp.= "\n<!-- MENUVAR:FULLLIST:START -->\n";
				$tpl = array();
				for ($l=0;$l<$lvl;$l++):
					$tpl[] = array('TYPE' => 'LIST');
				endfor;
				$menuparser = buildMenu($tpl, 1, 0, $mid, $lang, 0, 0, true, $preview);
				// $mnutmp .= $menuparser['buildcss'];
				$mnutmp .= "<div class=\"menucontainer-fulllist menucontainer-fulldynamic\">";
				$mnutmp .= $menuparser['menucode'];
				$mnutmp .= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:FULLLIST:END -->\n";
			elseif ($guid=='FULLDIV'):
				$mnutmp.= "\n<!-- MENUVAR:FULLDIV:START -->\n";
				$tpl = array();
				for ($l=0;$l<$lvl;$l++):
					$tpl[] = array('TYPE' => 'LINK');
				endfor;
				$menuparser = buildMenu($tpl, 1, 0, $mid, $lang, 0, 0, true, $preview);
				// $mnutmp .= $menuparser['buildcss'];
				$mnutmp .= "<div class=\"menucontainer-fulldiv menucontainer-fulldynamic\">";
				$mnutmp .= $menuparser['menucode'];
				$mnutmp .= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:FULLDIV:END -->\n";
			elseif ($guid=='FULLSELECT'):
				$mnutmp.= "\n<!-- MENUVAR:FULLSELECT:START -->\n";
				$tpl = array();
				for ($l=0;$l<$lvl;$l++):
					$tpl[] = array('TYPE' => 'SELECT');
				endfor;
				$menuparser = buildMenu($tpl, 1, 0, $mid, $lang, 0, 0, true, $preview);
				// $mnutmp .= $menuparser['buildcss'];
				$mnutmp .= "<div class=\"menucontainer-fullselect menucontainer-fulldynamic\">";
				$mnutmp .= $menuparser['menucode'];
				$mnutmp .= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:FULLSELECT:END -->\n";
			elseif ($guid=='HORIZONTALLIST'):
				$mnutmp.= "\n<!-- MENUVAR:HORIZONTALLIST:START -->\n";
				$menuparser = buildMenu(array(array('TYPE' => 'LIST')), intval($mid_res['set'][0]['level']), intval($mid_res['set'][0]['connected']), $mid, $lang, 0, 1, false, $preview);
				// $mnutmp.= $menuparser['buildcss'];
				$mnutmp.= "<div class=\"menucontainer-horizontallist\">";
				$mnutmp.= $menuparser['menucode'];
				$mnutmp.= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:HORIZONTALLIST:END -->\n";
			elseif ($guid=='HORIZONTALDIV'):
				$mnutmp.= "\n<!-- MENUVAR:HORIZONTALDIV:START -->\n";
				$menuparser = buildMenu(array(array('TYPE' => 'LINK')), intval($mid_res['set'][0]['level']), intval($mid_res['set'][0]['connected']), $mid, $lang, 0, 1, false, $preview);
				// $mnutmp.= $menuparser['buildcss'];
				$mnutmp.= "<div class=\"menucontainer-horizontaldiv\">";
				$mnutmp.= $menuparser['menucode'];
				$mnutmp.= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:HORIZONTALDIV:END -->\n";
			elseif ($guid=='HORIZONTALSELECT'):
				$mnutmp.= "\n<!-- MENUVAR:HORIZONTALSELECT:START -->\n";
				$menuparser = buildMenu(array(array('TYPE' => 'SELECT')), intval($mid_res['set'][0]['level']), intval($mid_res['set'][0]['connected']), $mid, $lang, 0, 1, false, $preview);
				// $mnutmp .= $menuparser['buildcss'];
				$mnutmp .= "<div class=\"menucontainer-horizontalselect\">";
				$mnutmp .= $menuparser['menucode'];
				$mnutmp .= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:HORIZONTALSELECT:END -->\n";
			elseif ($guid=='SUBLIST'):
    
                if (isset($guidtext) && intval($guidtext)>0) {
                    $mnutmp.= "\n<!-- MENUVAR:SUBLIST:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'LIST')), 0, intval($guidtext), $mid, $lang, 0, 1, false, $preview);
                    // $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-sublist\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:SUBLIST:END -->\n";
                    $guid = $guid.".".intval($guidtext);
                }
                else {
                    $mnutmp.= "\n<!-- MENUVAR:SUBLIST:START -->\n";
                    $menuparser = buildMenu(array(array('TYPE' => 'LIST')), intval($mid_res['set'][0]['level']), $mid, $mid, $lang, 0, 1, false, $preview);
                    // $mnutmp .= $menuparser['buildcss'];
                    $mnutmp .= "<div class=\"menucontainer-sublist\">";
                    $mnutmp .= $menuparser['menucode'];
                    $mnutmp .= "</div>";
                    $mnutmp.= "\n<!-- MENUVAR:SUBLIST:END -->\n";
                }
			elseif ($guid=='SUBDIV'):
				$mnutmp.= "\n<!-- MENUVAR:SUBDIV:START -->\n";
				$menuparser = buildMenu(array(array('TYPE' => 'LINK')), intval($mid_res['set'][0]['level']), $mid, $mid, $lang, 0, 1, false, $preview);
				// $mnutmp .= $menuparser['buildcss'];
				$mnutmp .= "<div class=\"menucontainer-subdiv\">";
				$mnutmp .= $menuparser['menucode'];
				$mnutmp .= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:SUBDIV:END -->\n";
			elseif ($guid=='SUBSELECT'):
				$mnutmp.= "\n<!-- MENUVAR:SUBSELECT:START -->\n";
				$menuparser = buildMenu(array(array('TYPE' => 'SELECT')), intval($mid_res['set'][0]['level']), $mid, $mid, $lang, 0, 1, false, $preview);
				// $mnutmp .= $menuparser['buildcss'];
				$mnutmp .= "<div class=\"menucontainer-subselect\">";
				$mnutmp .= $menuparser['menucode'];
				$mnutmp .= "</div>";
				$mnutmp.= "\n<!-- MENUVAR:SUBSELECT:END -->\n";
			elseif ($guid=='LINKLAST'):
				$mnutmp.= "\n<!-- MENUVAR:LINKLAST:START -->\n";
				if ($mid_res['num']>0):
					$link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `position` < ".intval($mid_res['set'][0]['position'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` DESC LIMIT 0,1";
					$link_res = doSQL($link_sql);
					if ($link_res['num']>0):
						if ($preview):
							$lnk = "?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
						else:
							if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
								if (intval($link_res['set'][0]['isindex'])==1):
									if (intval($link_res['set'][0]['level'])==1):
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
									else:
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
									endif;
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
								endif;
							else:
								if (intval($link_res['set'][0]['isindex'])==1 && intval($link_res['set'][0]['level'])==1):
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 2, '', $lang)));
								endif;
							endif;
						endif;
						$mnutmp.= "<a class=\"linkup\" href=\"".$lnk."\">";
						$dsc = trim($link_res['set'][0]['description']);
						$lng = unserializeBroken(trim($link_res['set'][0]['langdescription']));
						if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
						if(isset($guidtext) && trim($guidtext)!=''):
                            $mnutmp.= trim($guidtext);
                        else:
                            $mnutmp.= $dsc;
						endif;
                        $mnutmp.= "</a>";
					endif;
				endif;
				$mnutmp.= "\n<!-- MENUVAR:LINKLAST:END -->\n";
            elseif ($guid=='LINKLASTALL'):
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
							$lnk = "?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
						else:
							if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
								if (intval($link_res['set'][0]['isindex'])==1):
									if (intval($link_res['set'][0]['level'])==1):
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
									else:
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
									endif;
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
								endif;
							else:
								if (intval($link_res['set'][0]['isindex'])==1 && intval($link_res['set'][0]['level'])==1):
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 2, '', $lang)));
								endif;
							endif;
						endif;
						$mnutmp.= "<a class=\"linkup\" href=\"".$lnk."\">";
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
			elseif ($guid=='LINKNEXT'):
				$mnutmp.= "\n<!-- MENUVAR:LINKNEXT:START -->\n";
				if ($mid_res['num']>0):
					$link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `connected`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `position` > ".intval($mid_res['set'][0]['position'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` ASC LIMIT 0,1";
					$link_res = doSQL($link_sql);
					if ($link_res['num']>0):
						if ($preview):
							$lnk = "?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
						else:
							if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
								if (intval($link_res['set'][0]['isindex'])==1):
									if (intval($link_res['set'][0]['connected'])==0):
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
									else:
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 2, '', $lang)."/"));
									endif;
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
								endif;
							else:
								if (intval($link_res['set'][0]['isindex'])==1 && intval($link_res['set'][0]['connected'])==0):
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 2, '', $lang)));
								endif;
							endif;
						endif;
						$mnutmp.= "<a class=\"linkup\" href=\"".$lnk."\">";
						$dsc = setUTF8(trim($link_res['set'][0]['description']));
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
				$mnutmp.= "\n<!-- MENUVAR:LINKNEXT:END -->\n";
            elseif ($guid=='LINKNEXTALL'):
				$mnutmp.= "\n<!-- MENUVAR:LINKNEXTALL:START -->\n";
				if ($mid_res['num']>0):
					$link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `connected`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `position` > ".intval($mid_res['set'][0]['position'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` ASC LIMIT 0,1";
					$link_res = doSQL($link_sql);
                    if ($link_res['num']==0):
                        // find LOWEST position to return first entry in list as "next" link even if there is no next page 
                        $link_sql = "SELECT `mid`, `description`, `langdescription`, `level`, `isindex` FROM `menu` WHERE `mid` != ".intval($mid)." AND `connected` = ".intval($mid_res['set'][0]['connected'])." AND `visibility` = 1 AND `trash` = 0 ORDER BY `position` ASC LIMIT 0,1";
                        $link_res = doSQL($link_sql);
                    endif;
					if ($link_res['num']>0):
						if ($preview):
							$lnk = "?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
						else:
							if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
								if (intval($link_res['set'][0]['isindex'])==1):
									if (intval($link_res['set'][0]['connected'])==0):
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
									else:
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 2, '', $lang)."/"));
									endif;
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1, '', $lang)."/"));
								endif;
							else:
								if (intval($link_res['set'][0]['isindex'])==1 && intval($link_res['set'][0]['connected'])==0):
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 2, '', $lang)));
								endif;
							endif;
						endif;
						$mnutmp.= "<a class=\"linkup\" href=\"".$lnk."\">";
						$dsc = setUTF8(trim($link_res['set'][0]['description']));
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
				$mnutmp.= "\n<!-- MENUVAR:LINKNEXTALL:END -->\n";
			elseif ($guid=='LINKUP'):
				$mnutmp.= "\n<!-- MENUVAR:LINKUP:START -->\n";
				if ($mid_res['num']>0):
					$link_sql = "SELECT `mid`, `description`, `langdescription` FROM `menu` WHERE `mid` = ".intval($mid_res['set'][0]['connected'])." AND `visibility` = 1 AND `trash` = 0 LIMIT 0,1";
					$link_res = doSQL($link_sql);
                    if ($link_res['num']>0):
						if ($preview):
							$lnk = "?previewid=".intval($link_res['set'][0]['mid'])."&previewlang=".$lang;
						else:
							if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
								if (intval($link_res['set'][0]['isindex'])==1):
									if (intval($link_res['set'][0]['level'])==1):
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1)."/"));
									else:
										$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1)."/"));
									endif;
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 1)."/"));
								endif;
							else:
								if (intval($link_res['set'][0]['isindex'])==1 && intval($link_res['set'][0]['level'])==1):
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 0)."/index.php"));
								else:
									$lnk = str_replace("//", "/", str_replace("//", "/", returnPath(intval($link_res['set'][0]['mid']), 2)));
								endif;
							endif;
						endif;
						$mnutmp.= "<a class=\"linkup\" href=\"".$lnk."\">";
						$dsc = trim($link_res['set'][0]['description']);
						$lng = unserializeBroken(trim($link_res['set'][0]['langdescription']));
						if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;
						$mnutmp.= $dsc;
						$mnutmp.= "</a>";
					endif;
				endif;
				$mnutmp.= "\n<!-- MENUVAR:LINKUP:END -->\n";
			else:
				addWSPMsg('errormsg', returnIntLang('publisher menutemplate not found1').$guid.returnIntLang('publisher menutemplate not found2'));
				$mnutmp .= "<?php /* no menu found to parse width guid ".$guid." */ ?>";
			endif;
			
			// hier wird das gebaute menü in den code eingefügt und $buf wieder komplett zurückgegeben
			
			// write parsed menu to /data/menu/
			$returnstat = false;
			if (!($preview)) {
				if ($guid=='FULLLIST' || $guid=='FULLDIV' || $guid=='FULLSELECT') {
					$menufile = '';
				} else {
					$menufile = $mid.".";
				}
				if ($lang!='') {
					$menufile.= $lang.".";
				}
				$menufile.= strtolower($guid).".menu.inc";
				// write temp file to user dir
				$tmpmenu = str_replace("//","/",str_replace("//","/",$_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/".$menufile));
				$fh = fopen($tmpmenu, "w");
				fwrite($fh, trim($mnutmp));
				fclose($fh);
				// copy tmp file from user dir to final menu destination
				$ftpmenu = "/data/menu/".$menufile.".php";
				
				if (isset($_SESSION['wspvars']['directwriting']) && $_SESSION['wspvars']['directwriting']===true) {
					$usedirect = true;
				} else {
					$usedirect = false;
					$ftp = ((isset($_SESSION['wspvars']['ftpssl']) && $_SESSION['wspvars']['ftpssl']===true)?ftp_ssl_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport'])):ftp_connect($_SESSION['wspvars']['ftphost'], intval($_SESSION['wspvars']['ftpport']))); if ($ftp!==false) {if (!ftp_login($ftp, $_SESSION['wspvars']['ftpuser'], $_SESSION['wspvars']['ftppass'])) { $ftp = false; addWSPMsg('errormsg', returnIntLang('publisher cant upload menufile1', false)." ... ".returnIntLang('publisher cant upload menufile2 login')); }} else { addWSPMsg('errormsg', returnIntLang('publisher cant upload menufile1', false)." ... ".returnIntLang('publisher cant upload menufile2 connect')); } if (isset($_SESSION['wspvars']['ftppasv']) && $ftp!==false) { ftp_pasv($ftp, $_SESSION['wspvars']['ftppasv']); }
				}
				
				if (!(is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/data/menu/")))) && !empty($ftp)) {
					createDirFTP('/data/menu');
				} else if (!(is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/data/menu/"))))) {
					mkdir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/data/menu/");
				}

				if (!empty($ftp) && ftp_put($ftp, str_replace("//","/",$_SESSION['wspvars']['ftpbasedir'].$ftpmenu), $tmpmenu, FTP_BINARY)) {
					$returnstat = true;
					@ftp_close($ftp);
				} else {
					$returnstat = false;
				}

				if (!$returnstat) {
					if (copy($tmpmenu, $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$ftpmenu)) {
						$returnstat = true;
					} else {
						addWSPMsg('errormsg', returnIntLang('publisher cant create menufile1', false)." \"".$ftpmenu."\" ".returnIntLang('publisher cant create menufile2', false)."<br />");
						$returnstat = false;
					}
				} 
			}
			
			if ($returnstat) {
				// return include if ftp copy was done
				$mnutmp = "<"."?"."php @include(DOCUMENT_ROOT.\"/".$ftpmenu."\"); ?>\n";
			}
			
			$tmp.= $mnutmp.substr($buf, $pos+2); $buf = $tmp;
			
		 endif;
	endwhile;
	return $tmp;
	}
endif; // createMenu()

if (!(function_exists('getShowtimeLink'))):
// return display times for menupoint links
function getShowtimeLink($mid) {	
	if ($mid>0):
		$st_sql = "SELECT m.`weekday`, m.`showtime` FROM `menu` m WHERE (m.`weekday` > 0 OR m.`showtime` != '') AND m.`mid` = ".intval($mid);
		$st_res = doSQL($st_sql);
		if($st_res['num']>0):
			$cDay = intval($st_res['set'][0]["weekday"]);
			$cTime = unserializeBroken(trim($st_res['set'][0]["showtime"]));
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
endif;

if (!(function_exists('getLoginLink'))):
// return display times for menupoint links
function getLoginLink($mid) {
	$loginLink = "";
	$li_sql = "SELECT m.`login`, m.`logincontrol` FROM `menu` m WHERE m.`login` > 0 AND m.`mid` = ".intval($mid);
	$li_res = doSQL($li_sql);
	if($li_res['num']>0):
		$logincontrol = unserializeBroken(trim($li_res['set'][0]['logincontrol']));
		if (is_array($logincontrol) && count($logincontrol)>0):
			$loginLink = "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true ";
			$UID = array();
			foreach($logincontrol AS $lk => $lv):
				$UID[] = "\$_SESSION['wsppage']['uservalue']==".$lv;
			endforeach;
			$loginLink.= " AND (".implode(" OR ", $UID).")";
			$loginLink.= "): "."?".">";
		else:
			$loginLink = "<"."?"."php if(array_key_exists('wsppage', \$_SESSION) && array_key_exists('userlogin', \$_SESSION['wsppage']) && \$_SESSION['wsppage']['userlogin']===true): "."?".">";
		endif;
	endif;
	return $loginLink;
}	// getLoginLink()
endif;

if (!(function_exists('buildMenu'))):
// translated menucode as array(), $startlevel, $basemid => start on this base, actual mid, lang, actlevel, maxlevel, preview
function buildMenu($code=array(), $startlevel=1, $basemid=0, $actmid=0, $lang='de', $actl=0, $maxl=0, $generic=false, $preview=false) {
	if ($maxl==0) $maxl = count($code);
	// grep parsedir information
	$parsedir = intval(doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'parsedirectories'"));
	// return midTree to set "active" menupoints
	$actmidtree = returnIDTree($actmid);
	$str_num = 0;
	$str_sql = '';
	$not_sql = '';
	if (array_key_exists('MENU.HIDE',$code[0]) && trim($code[0]['MENU.HIDE'])!=''):
		$tmpdenymidlist = explode(";", $code[0]['MENU.HIDE']);
		if (is_array($tmpdenymidlist) && count($tmpdenymidlist)>0):
			$not_sql = " AND `mid` NOT IN ('".implode("','", $tmpdenymidlist)."') ";
		endif;
	endif;
	if (array_key_exists('MENU.SHOW',$code[0]) && trim($code[0]['MENU.SHOW'])!=''):
		$str_sql = "SELECT `mid` FROM `menu` WHERE `mid` IN ('".implode("','", explode(";", $code[0]['MENU.SHOW']))."') ".$not_sql." AND `trash` = 0 AND `denylang` NOT LIKE '%2:\"".$lang."\"%'";
		$str_res = doSQL($str_sql);
		$actl = 1;
		$maxl = 1;
	elseif ($actl<$maxl):
        /*
        // some errors occured - 20190620
        // if basemid == 0 because related start is a sub from active menupoint
        if ($basemid==0) {
            $basemid = $actmid;
        }
        */
		$str_sql = "SELECT `mid`, `level` FROM `menu` WHERE `connected` = ".intval($basemid)." ".$not_sql." AND `level` >= ".intval($startlevel)." AND `trash` = 0 AND `visibility` = 1 AND `denylang` NOT LIKE '%2:\"".$lang."\"%' ORDER BY `position` ASC";
		$str_res = doSQL($str_sql);
    endif;
	$buf = array();
	$buf['data'] = array(
		'code' => $code,
		'sql' => $str_sql,
		'sql_num' => intval(isset($str_res['num'])?$str_res['num']:0),
		'startlevel' => $startlevel,
		'basemid' => $basemid,
		'actmid' => $actmid,
		'lang' => $lang,
		'actlevel' => $actl,
		'maxlevel' => $maxl,
		'preview' => $preview
		);
	// if ($preview):
	// 	// some preview output to source
	// 	$buf['buildcss'] = '';
	// else:
	// 	$buf['buildcss'] = '';
	// endif;
	$buf['menucode'] = '';
	// if menupoints were found to display
	if (isset($str_res['num']) && $str_res['num']>0):
		// building mid list
		$midlist = array();
		$tmpmidlist = array();
		if (array_key_exists('MENU.SHOW',$code[0]) && trim($code[0]['MENU.SHOW'])!=''):
			$tmpmidlist = explode(";", $code[0]['MENU.SHOW']);
			for($smres=0; $smres<$str_res['num']; $smres++):
				if (in_array(intval($str_res['set'][$smres]['mid']),$tmpmidlist)):
					$midlist[array_search(intval($str_res['set'][$smres]['mid']),$tmpmidlist)] = intval($str_res['set'][$smres]['mid']);
				endif;
			endfor;
			ksort($midlist);
			if ($code[0]['TYPE']=='LINK'):
				$buf['menucode'].= '<div class="menudiv ';
				if (isset($code[0]['CONTAINER.CLASS'])):
					$buf['menucode'].= $code[0]['CONTAINER.CLASS'];
				endif;
				$buf['menucode'].= ' contains-'.count($midlist).' ">';
			elseif (($code[0]['TYPE']=='SELECT' && $actl==0) || ($code[0]['TYPE']=='SELECT' && $actl==$maxl)):
				$buf['menucode'].= '<select onchange="self.location.href=this.value" class=" contains-'.count($midlist).' ">';
			elseif ($code[0]['TYPE']=='SHORTCUT'):
				$buf['menucode'].= '';
			elseif ($code[0]['TYPE']!='SELECT'):	
				$buf['menucode'].= '<ul class="';
				if (isset($code[0]['CONTAINER.CLASS'])):
					$buf['menucode'].= $code[0]['CONTAINER.CLASS'];
				endif;
				$buf['menucode'].= ' contains-'.count($midlist).' ">'."\n";
			endif;
		else:
			for($smres=0; $smres<$str_res['num']; $smres++):
				$midlist[] = intval($str_res['set'][$smres]['mid']);
			endfor;
			if ($code[0]['TYPE']=='LINK'):
				$buf['menucode'].= '<div class="menudiv level'.intval($str_res['set'][0]['level']).' contains-'.count($midlist).' ';
				if (isset($code[0]['CONTAINER.CLASS'])):
					$buf['menucode'].= $code[0]['CONTAINER.CLASS'];
				endif;
				$buf['menucode'].= '">';
			elseif (($code[0]['TYPE']=='SELECT' && $actl==0) || ($code[0]['TYPE']=='SELECT' && $actl==$maxl)):
				$buf['menucode'].= '<select class="level'.intval($str_res['set'][0]['level']).' contains-'.count($midlist).' " onchange="self.location.href=this.value">';
			elseif ($code[0]['TYPE']=='SHORTCUT'):
				$buf['menucode'].= '';
			elseif ($code[0]['TYPE']!='SELECT'):	
				$buf['menucode'].= '<ul class="level'.intval($str_res['set'][0]['level']).' ';
				if (isset($code[0]['CONTAINER.CLASS'])):
					$buf['menucode'].= $code[0]['CONTAINER.CLASS'];
				endif;
				$buf['menucode'].= ' contains-'.count($midlist).' ">'."\n";
			endif;
		endif;
		
        $mposnum = 0;
		foreach ($midlist AS $midkey => $midvalue):
			$mposnum++;
            $midfacts_sql = "SELECT * FROM `menu` WHERE `mid` = ".intval($midvalue);
			$midfacts_res = doSQL($midfacts_sql);
            
            $submid_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($midvalue)." AND `visibility` = 1 AND `trash` = 0";
			$submid_res = doSQL($submid_sql);
    
			if ($midfacts_res['num']>0):
				// build showtime <if>
				if (!($preview)):
					$buf['menucode'].= getShowtimeLink(intval($midvalue));
				endif;
				if (!($preview)):
					$buf['menucode'].= getLoginLink(intval($midvalue));
				endif;
				if ($code[0]['TYPE']=='LINK'):
					$buf['menucode'].= "<div class=\"level".intval($midfacts_res['set'][0]['level'])." m".intval($midfacts_res['set'][0]['mid'])." s".($smres+1);
					if ($generic===true && $preview===false) {
						$buf['menucode'].= " <"."?"."php echo (in_array(".intval($midfacts_res['set'][0]['mid']).", \$_SESSION['wsppage']['midtree'])?'active':'inactive'); ?> ";
					}
					else {
						if (is_array($actmidtree) && in_array(intval($midfacts_res['set'][0]['mid']), $actmidtree)) { 
							$buf['menucode'].= " active";
						}
						else { 
							$buf['menucode'].= " inactive";
						}
					}
					if (trim($midfacts_res['set'][0]['addclass'])!=''): $buf['menucode'].= " ".trim($midfacts_res['set'][0]['addclass']); endif;
					$buf['menucode'].= "\">";
				elseif ($code[0]['TYPE']=='SELECT'):
					$buf['menucode'].= "<option value=\"";
					// link
					if ($preview):
						$buf['menucode'].= "?previewid=".intval($midfacts_res['set'][0]['mid'])."&previewlang=".$lang;
					else:
						if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
							if (intval($midfacts_res['set'][0]['isindex'])==1):
								if (intval($midfacts_res['set'][0]['level'])==1):
									$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 0, '', $lang)."/"));
								else:
									$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 1, '', $lang)."/"));
								endif;
							else:
								$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 1, '', $lang)."/"));
							endif;
						else:
							if (intval($midfacts_res['set'][0]['isindex'])==1 && intval($midfacts_res['set'][0]['level'])==1):
								$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
							else:
								$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 2, '', $lang)));
							endif;
						endif;
					endif;
					$buf['menucode'].= "\"";
					if (intval($midfacts_res['set'][0]['mid'])==$actmid): $buf['menucode'].= " selected=\"selected\" "; endif;
					$buf['menucode'].= ">";
				elseif ($code[0]['TYPE']=='SHORTCUT'):
					$buf['menucode'].= '';
				else:
					$buf['menucode'].= "<li class=\"level".intval($midfacts_res['set'][0]['level'])." m".intval($midfacts_res['set'][0]['mid'])." s".($smres+1);
					if ($generic===true && $preview===false) {
						$buf['menucode'].= " <"."?"."php echo (in_array(".intval($midfacts_res['set'][0]['mid']).", \$_SESSION['wsppage']['midtree'])?'active':'inactive'); ?> ";
					}
					else {
						if (is_array($actmidtree) && in_array(intval($midfacts_res['set'][0]['mid']), $actmidtree)) { 
							$buf['menucode'].= " active";
						}
						else { 
							$buf['menucode'].= " inactive";
						}
					}
				    $buf['menucode'].= " mpos-".$mposnum." ";
                    if ($submid_res['num']>0): $buf['menucode'].= " sub "; else: $buf['menucode'].= " nosub "; endif;
                    
                    if (trim($midfacts_res['set'][0]['addclass'])!=''): $buf['menucode'].= " ".trim($midfacts_res['set'][0]['addclass']); endif;
					$buf['menucode'].= "\">";
				endif;
				
				if ($code[0]['TYPE']=='SELECT'):
					for($a=0;$a<$actl;$a++):
						$buf['menucode'].= ". . ";
					endfor;
				else:
					// real link in structure
					$buf['menucode'].= "<a ";
					if (trim($midfacts_res['set'][0]['jsmouseover'])!=''):
						$buf['menucode'].= " onmouseover=\"".trim($midfacts_res['set'][0]['jsmouseover'])."\" ";
					endif;
					if (trim($midfacts_res['set'][0]['jsclick'])!=''):
						$buf['menucode'].= " onclick=\"".trim($midfacts_res['set'][0]['jsclick'])."\" ";
					else:
						$buf['menucode'].= "href=\"";
						if (trim($midfacts_res['set'][0]['offlink'])!=''):
							$buf['menucode'].= trim($midfacts_res['set'][0]['offlink']);
						elseif ($preview):
							$buf['menucode'].= "?previewid=".intval($midfacts_res['set'][0]['mid'])."&previewlang=".$lang;
						else:
							// create links
							if (intval($_SESSION['wspvars']['publisherdata']['parsedirectories'])==1 || $parsedir==1):
								if (intval($midfacts_res['set'][0]['isindex'])==1):
									if (intval($midfacts_res['set'][0]['level'])==1):
										$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 0, '', $lang)."/"));
									else:
										$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 1, '', $lang)."/"));
									endif;
								else:
									$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 1, '', $lang)."/"));
								endif;
							else:
								if (intval($midfacts_res['set'][0]['isindex'])==1 && intval($midfacts_res['set'][0]['level'])==1):
									$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 0, '', $lang)."/index.php"));
								else:
									$buf['menucode'].= str_replace("//", "/", str_replace("//", "/", returnPath(intval($midfacts_res['set'][0]['mid']), 2, '', $lang)));
								endif;
							endif;
						endif;
						$buf['menucode'].= "\" ";
						if (trim($midfacts_res['set'][0]['offlink'])!=''):
							if (trim($midfacts_res['set'][0]['externtarget'])!='' && trim($midfacts_res['set'][0]['externtarget'])!='_none'):
								$buf['menucode'].= " target=\"".trim($midfacts_res['set'][0]['externtarget'])."\" ";	
							endif;
						elseif (trim($midfacts_res['set'][0]['interntarget'])!='' && trim($midfacts_res['set'][0]['interntarget'])!='_none'):
							$buf['menucode'].= " target=\"".trim($midfacts_res['set'][0]['interntarget'])."\" ";
						endif;
					endif;
					$buf['menucode'].= " class=\"level".intval($midfacts_res['set'][0]['level'])." m".intval($midfacts_res['set'][0]['mid'])." s".($smres+1);
					if ($generic===true && $preview===false) {
						$buf['menucode'].= " <"."?"."php echo (in_array(".intval($midfacts_res['set'][0]['mid']).", \$_SESSION['wsppage']['midtree'])?'active':'inactive'); ?> ";
					}
					else {
						if (is_array($actmidtree) && in_array(intval($midfacts_res['set'][0]['mid']), $actmidtree)) { 
							$buf['menucode'].= " active";
						}
						else { 
							$buf['menucode'].= " inactive";
						}
					}
					if (trim($midfacts_res['set'][0]['addclass'])!=''): $buf['menucode'].= " ".trim($midfacts_res['set'][0]['addclass']); endif;
					$buf['menucode'].= "\"";
					// javascript mouseout
					if (trim($midfacts_res['set'][0]['jsmouseout'])!=''):
						$buf['menucode'].= " onmouseout=\"".trim($midfacts_res['set'][0]['jsmouseout'])."\" ";
					endif;
					$buf['menucode'].= ">";
				endif;
				// show that timesetup is active
				if(trim(getLoginLink(intval($midvalue)))!='' && $preview):
					$buf['menucode'].= "#";
				endif;
				if(trim(getShowtimeLink(intval($midvalue)))!='' && $preview):
					$buf['menucode'].= "[";
				endif;
				$dsc = trim($midfacts_res['set'][0]['description']);
				$lng = unserializeBroken(trim($midfacts_res['set'][0]['langdescription']));
				if(count($_SESSION['wspvars']['lang'])>1): if (is_array($lng) && array_key_exists($lang, $lng)): $dsc = $lng[$lang]; endif; endif;

				$menuimage = '';
				if(($code[0]['TYPE']=='LINKIMAGE') || ($code[0]['TYPE']=='LISTIMAGE')):
					if (is_array($actmidtree) && in_array(intval($midfacts_res['set'][0]['mid']), $actmidtree)): 
						if(trim($midfacts_res['set'][0]['imageakt'])!=""):
							$menuimage = trim($midfacts_res['set'][0]['imageakt']);
						elseif(trim($midfacts_res['set'][0]['imageon'])!=""):
							$menuimage = trim($midfacts_res['set'][0]['imageon']);
						endif;
						$overout = "onmouseover=\"this.src='".trim($midfacts_res['set'][0]['imageon'])."';\" onmouseout=\"this.src='".$menuimage."';\"";
					else:
						if(trim($midfacts_res['set'][0]['imageon'])!=""):
							$menuimage = trim($midfacts_res['set'][0]['imageoff']);
							$overout = "onmouseover=\"this.src='" .trim($midfacts_res['set'][0]['imageon']). "';\" onmouseout=\"this.src='" . $menuimage . "';\"";
						endif;
					endif;
					if(!empty($menuimage)):			
						$buf['menucode'].= "<img src=\"" . $menuimage . "\" alt=\"" . $dsc . "\" id=\"a".intval($midvalue)."\" title=\"" . $dsc . "\" border=\"0\" ". $overout ."  />";
					else:
						$buf['menucode'].= $dsc;
					endif;
				else:
					$buf['menucode'].= $dsc;
				endif;
				unset($menuimage);

				// end show that timesetup is active
				if(trim(getShowtimeLink(intval($midvalue)))!='' && $preview):
					$buf['menucode'].= "]";
				endif;
				
				if ($code[0]['TYPE']!='SELECT'):
					$buf['menucode'].= "</a>";	
				endif;
	
				if ($code[0]['TYPE']=='LINK'):
					$buf['menucode'].= "</div>";
					if ((isset($code[0]['SPACER']) && $code[0]['SPACER']!='') && $midkey<(count($midlist)-1)):
						$buf['menucode'].= "<div class=\"spacer\">".$code[0]['SPACER']."</div>"."\n";
					endif;
					if ($actl<$maxl):
						$sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($midfacts_res['set'][0]['mid'])." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
						$sub_res = doSQL($sub_sql);
						if ($sub_res['num']>0):
							$subcode = buildMenu($code, $startlevel, intval($midfacts_res['set'][0]['mid']), $actmid, $lang, $actl+1, $maxl, $generic, $preview);
							$buf['menucode'].= $subcode['menucode'];
						endif;
					endif;
				elseif ($code[0]['TYPE']=='SELECT'):
					$buf['menucode'].= '</option>'."\n";
					if ($actl<$maxl):
						$sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($midfacts_res['set'][0]['mid'])." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
						$sub_res = doSQL($sub_sql);
						if ($sub_res['num']>0):
							$subcode = buildMenu($code, $startlevel, intval($midfacts_res['set'][0]['mid']), $actmid, $lang, $actl+1, $maxl, $generic, $preview);
							$buf['menucode'].= $subcode['menucode'];
						endif;
					endif;
				elseif ($code[0]['TYPE']=='SHORTCUT'):
					$buf['menucode'].= '';
				else:	
					if ($actl<$maxl):
						$sub_sql = "SELECT `mid` FROM `menu` WHERE `connected` = ".intval($midfacts_res['set'][0]['mid'])." AND `trash` = 0 AND `visibility` = 1 ORDER BY `position` ASC";
						$sub_res = doSQL($sub_sql);
						if ($sub_res['num']>0):
							$subcode = buildMenu($code, $startlevel, intval($midfacts_res['set'][0]['mid']), $actmid, $lang, $actl+1, $maxl, $generic, $preview);
							$buf['menucode'].= $subcode['menucode'];
						endif;
					endif;
					$buf['menucode'].= "</li>\n";
				endif;
				
				// close showtime <if>
				if(trim(getShowtimeLink(intval($midvalue)))!='' && !($preview)):
					$buf['menucode'].= "<"."?"."php endif; "."?".">";	
				endif;
				// close login <if>
				if(trim(getLoginLink(intval($midvalue)))!='' && !($preview)):
					$buf['menucode'].= "<"."?"."php endif; "."?".">";
				endif;
				
			endif;
		endforeach;
		// close container
		if ($code[0]['TYPE']=='LINK'):
			$buf['menucode'].= '</div>'."\n";
		elseif (($code[0]['TYPE']=='SELECT' && $actl==0) || ($code[0]['TYPE']=='SELECT' && $actl==$maxl)):
			$buf['menucode'].= '</select>'."\n";
		elseif ($code[0]['TYPE']=='SHORTCUT'):
			$buf['menucode'].= '';
		else:	
			$buf['menucode'].= '</ul>'."\n";
		endif;
	endif;
	return $buf;
	}
endif; // buildMenu();

// EOF ?>