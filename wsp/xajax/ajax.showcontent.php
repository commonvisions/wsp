<?php
/**
 * calling contentlist
 * @author s.haendler@covi.de
 * @copyright (c) 2021, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.10
 * @lastchange 2021-04-14
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/funcs.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/filesystemfuncs.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/clsinterpreter.inc.php";

$edit_sql = "SELECT `editable` FROM `menu` WHERE `mid` = ".intval($_POST['mid']);
$edit_res = doSQL($edit_sql);
$editable = 1; if ($edit_res['num']>0): $editable = intval($edit_res['set'][0]['editable']); endif;

if ($edit_res['num']>0 && ($editable==1 || $editable==9 || $editable==7)) {
	$realtemp = getTemplateID(intval($_POST['mid']));
	$templatevars = getTemplateVars($realtemp);
	$siteinfo_num = 0;
	$siteinfo_sql = "SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'contentvardesc'";
	$siteinfo_res = doSQL($siteinfo_sql);
	if ($siteinfo_res['num']>0):
		$contentvardesc = unserializeBroken($siteinfo_res['set'][0]['varvalue']);
	endif;
	
	foreach ($templatevars['contentareas'] AS $tk => $tv) {
        echo "<li style=\"margin: 0px; padding: 0px;\">";
        echo "<table class=\"tablelist nodrag\">";
        echo "<tr>";
        echo "<td class=\"tablecell two head nodrag\">";
        echo "<span class=\"bubblemessage hidden\">".returnIntLang('bubble addcontent', false)."</span> <span class=\"bubblemessage hidden\">".returnIntLang('bubble addcontent', false)."</span> <a onclick=\"addContent(".$_POST['mid'].", ".$tv.");\"><span class=\"bubblemessage orange\">".returnIntLang('bubble addcontent', false)."</span></a>";
        echo "</td>";
        echo "<td class=\"tablecell four head nodrag\">";
        if (isset($contentvardesc) && is_array($contentvardesc)) {
            if (array_key_exists(($tv-1), $contentvardesc) && trim($contentvardesc[($tv-1)])!=''):
                echo $contentvardesc[($tv-1)];
            else:
                echo returnIntLang('contentstructure contentarea', false)." ".$tv;
            endif;
        }
        else {
            echo returnIntLang('contentstructure contentarea', false)." ".$tv;
        }
        echo "</td>";
        echo "<td class='tablecell two head nodrag'>&nbsp;</td>";
        echo "</tr>";
        echo "</table>";
        echo "</li>";
        echo "</ul>";
        echo "<ul class=\"carea_".$tv." careaholder\" rel=\"".$tv."\" style=\"margin: 0px; padding: 0px; list-style-type: none;\">";
        echo "<li style=\"margin: 0px; padding: 0px;\" class=\"droplist droponempty carea_".$tv."\">";
        // select contents by menupoint
        $consel_sql = "SELECT * FROM `content` WHERE `mid` = ".intval($_POST['mid'])." AND `content_area` = ".intval($tv)." AND `trash` = 0 AND (`content_lang` = '".escapeSQL(trim($_SESSION['wspvars']['workspacelang']))."' OR `content_lang` = '') ORDER BY `position`";
        $consel_res = doSQL($consel_sql);
        if ($consel_res['num']>0) {
            foreach ($consel_res['set'] AS $csresk => $csresv) {
                $cid = 0;
                $interpreter_sql = "SELECT * FROM `interpreter` WHERE `guid` = '".trim($csresv['interpreter_guid'])."'";
                $interpreter_res = doSQL($interpreter_sql);
                $intname = returnIntLang('unknown interpreter', false);
                if (isset($interpreter_res['set'][0]['name']) && trim($interpreter_res['set'][0]['name'])!=''):
                    $intname = trim($interpreter_res['set'][0]['name']);
                endif;
                $contentinfo = $intname;
                if (intval($csresv['globalcontent_id'])>0):
                    $globconsel_sql = "SELECT `valuefield` FROM `globalcontent` WHERE `id` = ".intval($csresv['globalcontent_id']);
                    $globconsel_res = doSQL($globconsel_sql);
                    if ($globconsel_res['num']>0):
                        $contentvalue = unserializeBroken($globconsel_res['set'][0]['valuefield']);
                    endif;
                    $cid = intval($csresv['globalcontent_id']);
                else:
                    $contentvalue = unserializeBroken($csresv['valuefields']);
                    $cid = intval($csresv['cid']);
                endif;
                $contentdesc = '';
                if ($interpreter_res['num']>0) {
                    $contentdesc = 'interpreter desc';
                    $file = trim($interpreter_res['set'][0]['parsefile']);
                    if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file)) {
                        include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/interpreter/".$file;
                        $clsInterpreter = new $interpreterClass;
                        $clsInterpreter->dbCon = $_SESSION['wspvars']['dbcon'];
                        if (method_exists($interpreterClass, 'getView')) {
                            $contentdesc = $clsInterpreter->getView($contentvalue, intval($csresv['mid']), $cid);
                        }
                        if (method_exists($interpreterClass, 'closeInterpreterDB')) {
                            $clsInterpreter->closeInterpreterDB();
                        }
                    }
                }
                if (trim($contentdesc)=='') {
                    if (is_array($contentvalue) && array_key_exists('desc', $contentvalue)):
                        $contentdesc = trim($contentvalue['desc']);
                    else:
                        $contentdesc = '';
                    endif;
                }
                // append description to interpreter info
                if ($contentdesc!="") {
                    $contentinfo.= " - ".$contentdesc;
                }
                // append information about global content
                if (intval($csresv['globalcontent_id'])>0) {
                    $contentinfo.= " <span class=\"bubblemessage green\" onclick=\"document.getElementById('editglobalid').value = '".intval($csresv['globalcontent_id'])."'; document.getElementById('editglobal').submit();\">GLOBAL</span>";
                }
                // append information about displayclass
                if (intval($csresv['displayclass'])==1) {
                    $contentinfo.= " <span class=\"bubblemessage orange\">D</span>";
                }
                else if (intval($csresv['displayclass'])==2) {
                    $contentinfo.= " <span class=\"bubblemessage orange\">M</span>";
                }
				else if (intval($csresv['displayclass'])==3) {
                    $contentinfo.= " <span class=\"bubblemessage orange\">P</span>";
				}
                // create output
                echo "<ul class=\"\" id=\"cli_".intval($csresv['cid'])."\" style=\"list-style-type: none;\">";
                echo "<li id=\"li_".intval($csresv['cid'])."\" class=\"droplistitem\">";
                echo "<ul id=\"ul_".intval($csresv['cid'])."\" class=\"tablelist id".intval($csresv['cid'])."\">";
                echo "<li class=\"tablecell ";
                // visibility information
                if (intval($csresv['visibility'])==0) {
                    echo " hiddencontent ";
                }
                echo " two id".intval($csresv['cid'])."\">";
                // move
                echo " <span class=\"bubblemessage orange movehandle\">".returnIntLang('bubble move', false)."</span>";
                // show or hide
                if (intval($csresv['visibility'])==0):
                    echo " <a id=\"acsh".intval($csresv['cid'])."\" onclick=\"contentShowHide('".intval($csresv['cid'])."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble show', false)."</span></a>";
                elseif (intval($csresv['visibility'])==1):
                    echo " <a id=\"acsh".intval($csresv['cid'])."\" onclick=\"contentShowHide('".intval($csresv['cid'])."');\"><span class=\"bubblemessage green\">".returnIntLang('bubble hide', false)."</span></a>";
                else:
                    echo " <a id=\"acsh".intval($csresv['cid'])."\" onclick=\"contentShowHide('".intval($csresv['cid'])."');\"><span class=\"bubblemessage\">".returnIntLang('bubble hide', false)."</span></a>";
                endif;
                // duplicate
                echo " <a onclick=\"contentClone('".intval($csresv['cid'])."','".str_replace("\"", "”", str_replace("'", "\'", $contentinfo))."');\"><span class=\"bubblemessage orange\">".returnIntLang('bubble clonex2', false)."</span></a>";
				// delete
                echo " <a onclick=\"contentRemove('".intval($csresv['cid'])."','".strip_tags(str_replace("\"", "”", str_replace("'", "\'", $contentinfo)))."');\"><span class=\"bubblemessage red\">".returnIntLang('bubble delete', false)."</span></a>";
                // checkforedit
                $cfe_sql = "SELECT `sid` FROM `security` WHERE `position` = '".escapeSQL("/".$_SESSION['wspvars']['wspbasedir']."/contentedit.php;cid=".intval($csresv['cid']))."'";
                $cfe_res = doSQL($cfe_sql);
                echo " <span class=\"bubblemessage ";
                if ($cfe_res['num']>0): echo " orange "; else: echo " green "; endif;
                echo "\" onclick=\"document.getElementById('editcontentid').value = '".intval($csresv['cid'])."'; document.getElementById('editcontents').submit();\">".returnIntLang('bubble edit', false)."</span>";
                echo "</li>";
                echo "<li class=\"tablecell ";
                if (intval($csresv['visibility'])==0):
                    echo " hiddencontent ";
                endif;
                echo " four id".intval($csresv['cid'])."\">".$contentinfo."</li>";
                echo "<li class='tablecell ";
                if (intval($csresv['visibility'])==0):
                    echo " hiddencontent ";
                endif;
                echo " two id".intval($csresv['cid'])."'>";
                echo date(returnIntLang('format date time',false), intval($csresv['lastchange']));
                echo " ".returnUserData(' | shortcut', intval($csresv['uid']));
                echo "</li>";
                echo "</ul>";
                echo "</li>";
                echo "</ul>";
            }
        }
        echo "</li>";
    }
}

endif;
// EOF ?>