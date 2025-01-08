<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.9
 * @lastchange 2019-10-16
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/dbaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/ftpaccess.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

$fsizes = array('Byte', 'KB', 'MB', 'GB', 'TB');
$folder = array();
$mysortlist = array();

if (isset($_POST) && isset($_POST['fkid'])): $fkid = intval($_POST['fkid']); else: $fkid = 0; endif;

$files_sql = "SELECT * FROM `wspmedia` WHERE `mediafolder` = '".$_SESSION['fullstructure'][$fkid]['folder']."' AND `filename` !='' ";
if (isset($_POST) && isset($_POST['sort']) && $_POST['sort']=='date'):
	$files_sql.= " ORDER BY `filedate`";
elseif (isset($_POST) && isset($_POST['sort']) && $_POST['sort']=='size'):
	$files_sql.= " ORDER BY `filesize`";
else: // name
	$files_sql.= " ORDER BY `filename`";
endif;

    $files_res = doSQL($files_sql);		
    $countdelete = 0;
    
    if (isset($_POST) && isset($_POST['display'])): $display = $_POST['display']; else: $display = 'list'; endif;
    if ($files_res['num']>0) {
        foreach ($files_res['set'] AS $fresk => $fresv) {
            if (isset($_POST['stat']) && trim($_POST['stat'])=='countdelete') {
                if(intval($fresv['embed'])>0) {
                    $countdelete++;
                }
            }
            else {
                echo "<li class=\"file ".$display."\" id=\"".trim($fresv['filekey'])."\"><ul class=\"filedata ".$display."\">";
                echo "<li class='filegrabber ".$display."'>&nbsp;</li>";
                echo "<li class='fileicon ".$display."'>";
                echo "</li>";
                echo "<li class='filename ".$display."'>";
                $d_sql = "SELECT `filedesc` FROM `mediadesc` WHERE `mediafile` = '".str_replace("//", "/", str_replace("//", "/", trim($fresv['mediafolder']."/".$fresv['filename'])))."'";
                $d_res = doSQL($d_sql);		
                if ($d_res['num']>0):
                    if (trim($d_res['set'][0]['filedesc'])!=''):
                        echo "<em>".trim($d_res['set'][0]['filedesc'])."</em>";
                    else:
                        echo trim($fresv['filename']);
                    endif;
                else:
                    echo trim($fresv['filename']);
                endif;
                echo "</li>";
                echo "<li class='filesize ".$display."'>"; //'
                $sf = 0;
                $calcsize = intval($fresv['filesize']); 
                while ($calcsize>1024):
                    $calcsize = ($calcsize/1024);
                    $sf++;
                endwhile;
                $facts = unserializeBroken($fresv['filedata']);
                if($facts['size']!=""):
                    echo $facts['size']." ".returnIntLang('str px', false)." , ";
                endif;
                echo round($calcsize,0)." ".$fsizes[$sf];
                echo "</li>";
                echo "<li class='filedate ".$display."'>".date("Y-m-d H:i:s", intval($fresv['filedate']))."</li>";
                echo "<li class='fileaction ".$display."'>";
                echo "<span id=\"btn_detailsfile\" class=\"bubblemessage green\" onClick=\"showDetails('".str_replace("//", "/", str_replace("//", "/", trim($fresv['mediafolder']."/".$fresv['filename'])))."');\">".returnIntLang('bubble view', false)."</span> ";
                if(intval($fresv['embed'])>0):
                    echo "<span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span></li>";
                else:
                    echo "<span class=\"bubblemessage red\" onClick=\"if(confirm('". returnIntLang('confirm delete file', false) ."')) {confirmDeleteFile('".trim($fresv['filekey'])."');};\">".returnIntLang('bubble delete', false)."</span></li>";
                endif;
                echo "</li>";
                echo "<li class='closefile ".$display."'>&nbsp;</li>";
                echo "</ul></li>";
            }
        }
    }
    else {
        if (isset($_POST['stat']) && trim($_POST['stat'])=='countdelete') {
            //
        }
        else {
            echo "<li class=\"file ".$display." ui-sortable-helper\" id=\"\">&nbsp;</li>";
        }
    }
    
    if (isset($_POST['stat']) && trim($_POST['stat'])=='countdelete') {
        echo intval($countdelete);
    }
    
}

// EOF ?>
