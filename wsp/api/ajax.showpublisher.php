<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */

if (!empty($_SERVER['HTTP_REFERER'] ?? null)):
	session_start();
	$wspdir = str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".($_SESSION['wspvars']['wspbasediradd'] ?? "")."/".($_SESSION['wspvars']['wspbasedir'] ?? "")));

	require $wspdir.'/data/include/globalvars.inc.php';
    require $wspdir.'/data/include/usestat.inc.php';
	require $wspdir.'/data/include/wsplang.inc.php';
	require $wspdir."/data/include/dbaccess.inc.php";
	require $wspdir."/data/include/funcs.inc.php";
	require $wspdir."/data/include/errorhandler.inc.php";
	require $wspdir."/data/include/siteinfo.inc.php";

	if (!(isset($_REQUEST['showpublish'])) || trim($_REQUEST['showpublish'])==''): $_REQUEST['showpublish'] = 'all'; endif;
	if (!(isset($_REQUEST['searchpublish']))): $_REQUEST['searchpublish'] = ''; endif;
	
	$starttime = microtime(true);
	
	echo "<table class='publishinglist tablelist' id='contentpublisher'>";
	$linecount = 0;
	if ($_SESSION['wspvars']['rights']['publisher']>1):
		$showmidpath = $_SESSION['wspvars']['rights']['publisher_array'];
	else:
		$showmidpath = array();
	endif;
	$publish = getPublisherStructure(0, array(), $showmidpath, $_SESSION['wspvars']['workspacelang'], trim($_REQUEST['showpublish']), trim($_REQUEST['searchpublish']));
	$scriptsize = strlen(serialize($publish));
	$sc = array('b','kb','mb','gb');
	$scpos = 0;
	while($scriptsize>1024):
		$scriptsize = ceil($scriptsize/1024);
		$scpos++;
	endwhile;
	$memsize = memory_get_usage();
	$mc = array('b','kb','mb','gb');
	$mcpos = 0;
	while($memsize>1024):
		$memsize = ceil($memsize/1024);
		$mcpos++;
	endwhile;
	if ($publish!=''):
		echo $publish;
	else:
		echo '<tr><td class="tablecell eight" style="text-align: center;">'.returnIntLang('publisher no data matching', false).'</td></tr>';
	endif;
	echo "</table>";

endif;
// EOF ?>