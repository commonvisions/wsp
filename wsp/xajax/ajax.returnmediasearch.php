<?php
/**
 * ...
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2018-09-18
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/wsplang.inc.php';
require $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php';
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/errorhandler.inc.php";
include $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/data/include/siteinfo.inc.php";

$fullmediastructure = $_SESSION['xajaxmediastructure'];
ksort($fullmediastructure);
$fsizes = array('Byte', 'KB', 'MB', 'GB', 'TB');
$folder = array();
$mysortlist = array();

if (isset($_POST) && isset($_POST['search']) && strlen(trim($_POST['search']))>2):
	$resultset = array();
	foreach ($fullmediastructure AS $fk => $fv):
		foreach ($fv AS $mk => $mv):
			if (stristr($mv['filename'], trim($_POST['search']))>=0 && !(stristr($mv['filename'], trim($_POST['search'])))===false):
				$resultset[$mk] = $mv;
			else:
				if (stristr($mv['description'], trim($_POST['search']))>=0 && !(stristr($mv['description'], trim($_POST['search'])))===false):
					$resultset[$mk] = $mv;
				else:
					if (stristr($mv['keywords'], trim($_POST['search']))>=0 && !(stristr($mv['keywords'], trim($_POST['search'])))===false):
						$resultset[$mk] = $mv;
					endif;
				endif;
			endif;
		endforeach;
	endforeach;
	if (count($resultset)>0):
		if (isset($_POST) && isset($_POST['sort']) && $_POST['sort']=='date'):
			foreach ($resultset AS $mfk => $mfv):
				$sortlist[$mfk] = $mfv['lastchange'];
			endforeach;
		elseif (isset($_POST) && isset($_POST['sort']) && $_POST['sort']=='size'):
			foreach ($resultset AS $mfk => $mfv):
				$sortlist[$mfk] = $mfv['filesize'];
			endforeach;
		else: // name
			foreach ($resultset AS $mfk => $mfv):
				$sortlist[$mfk] = $mfk;
			endforeach;
		endif;
		asort($sortlist);
		if (isset($_POST) && isset($_POST['display'])): $display = $_POST['display']; else: $display = 'list'; endif;

		echo "<legend>".returnIntLang('media searched filestructure', true)."</legend>";		
		echo "<ul id=\"foundfiles\">";
		foreach ($sortlist AS $sk => $sv):
			echo "<li class=\"file ".$display."\"><ul class=\"filedata ".$display."\">";
			echo "<li class='filegrabber ".$display."'>&nbsp;</li>";
			echo "<li class='fileicon ".$display."'>";
			if ($resultset[$sk]['thumbnail']!=''):
				echo "<img src=\"".$resultset[$sk]['thumbnail']."\" class=\"thumb\" onClick=\"showDetails('');\">";
			endif;
			echo "</li>";
			echo "<li class='filename ".$display."'>"; //'
			if ($resultset[$sk]['description']!=''):
				echo "<em>".$resultset[$sk]['description']."</em>";
			else:
				echo $sk;
			endif;
			echo "</li>";
			echo "<li class='filesize ".$display."'>"; //'
			$sf = 0;
			$calcsize = $resultset[$sk]['filesize']; 
			while ($calcsize>1024):
				$calcsize = ($calcsize/1024);
				$sf++;
			endwhile;
			echo $resultset[$sk]['size']." ".returnIntLang('str px', false)." , ".round($calcsize,0)." ".$fsizes[$sf];
			echo "</li>";
			echo "<li class='filedate ".$display."'>".date("Y-m-d H:i:s", $resultset[$sk]['lastchange'])."</li>";
			echo "<li class='fileaction ".$display."'>";
			echo "<span id=\"btn_detailsfile\" class=\"bubblemessage green\" onClick=\"showDetails('');\">".returnIntLang('bubble view', false)."</span> ";
			if(($resultset[$sk]['inuse'])):
				echo "<span class=\"bubblemessage orange disabled\">".returnIntLang('bubble rename', false)."</span> <span class=\"bubblemessage red disabled\">".returnIntLang('bubble delete', false)."</span></li>";
			else:
				echo "<span class=\"bubblemessage orange\" onclick=\"changeFileName(''); \">".returnIntLang('bubble rename', false)."</span> <span class=\"bubblemessage red\" onClick=\"delFile('');\">".returnIntLang('bubble delete', false)."</span></li>";
			endif;
			echo "</li>";
			echo "<li class='closefile ".$display."'>&nbsp;</li>";
			echo "</ul></li>";
		endforeach;
		echo "</ul>";
	else:
		echo "<legend>".returnIntLang('media searched filestructure', true)."</legend>";
		echo "<p>".returnIntLang('mediasearch no results').".</p>";
	endif;
endif;
endif;
// EOF ?>
