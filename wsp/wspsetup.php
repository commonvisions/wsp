<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version 5.0
 * @lastchange 2011-09-02
 */

session_start();

error_reporting(E_ALL); ini_set("display_errors", 1);
if (phpversion()>5): date_default_timezone_set('Europe/Berlin'); endif;
include('./data/include/funcs.inc.php');

// install vars

$wsplang = 'de';
$lang = array('de' =>
	array(
		'install wsp' => 'Installation von WebSitePreview',
		
		'step4 text to install' => 'Sie haben alle notwendigen Informationen eingegeben. Im nächsten Installationsschritt werden die aktuellen Systemdateien vom Update-Server geladen und auf Ihrem Account eingespielt. Bitte laden Sie in der Zwischenzeit diese Seite <strong>nicht</strong> neu, bis die Seite den Abschluss des Installationsvorganges meldet. Je nach Systemleistung kann dieser Vorgang zwischen 5 und 10 Minuten in Anspruch nehmen. Sollte dieser Zeitraum signifikant &uuml;berschritten werden, ohne das sich etwas ändert, kontaktieren Sie Ihren Anbieter f&uuml;r WSP.',
		
		)
	);

$wspvars['installuri'] = 'http://update.wsp-server.info/wsp';
$wspvars['installfiles'] = 'http://update.wsp-server.info/wsp';
$wspvars['installdatabase'] = 'http://update.wsp-server.info/updater';
$wspvars['installkey'] = 'ahfsa9r278rtSNDKJaou387zrfsdfchizqrw';

if (!(array_key_exists('wspvars', $_SESSION))): $_SESSION['wspvars'] = array(); endif;
if (is_array($_SESSION['wspvars']) && !(array_key_exists('resultmsg', $_SESSION['wspvars']))): $_SESSION['wspvars']['resultmsg'] = array(); endif;
if (!(array_key_exists('noticemsg', $_SESSION['wspvars']))): array_push($_SESSION['wspvars'], 'noticemsg'); endif;
if (!(array_key_exists('errormsg', $_SESSION['wspvars']))): array_push($_SESSION['wspvars'], 'errormsg'); endif;

// install functions

function FileUpdate($data) {
	if ($data!=trim($GLOBALS['tmpwspbasedir'].'/wspsetup.php')):
		$fh = fopen($GLOBALS['wspvars']['installuri']."/updater.php?key=".$GLOBALS['wspvars']['installkey']."&url=".$_SESSION['siteurl']."&file=".$data, 'r');
		$fileupdate = '';
		if (intval($fh)>0):
			while (!feof($fh)):
				$fileupdate .= fgets($fh, 4096);
			endwhile;
		endif;
		fclose($fh);
	
		$tmppfad = $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".basename($data);
		$tmpdat=fopen($tmppfad,'w');
		fwrite($tmpdat, $fileupdate);
		fclose($tmpdat);
	
		$ftpAttempt = 3;
		$aReturn = array(2);
		$aReturn[0] = false;
		$ftp = false;
	
		while (!$ftp && ($ftpAttempt > 0)):
			$ftp = ftp_connect($_SESSION['ftphost']);
			$ftpAttempt--;
		endwhile;
		
		if ($ftp === false):
			$aReturn[0] = true;
			$aReturn[1] = '';
		elseif (!ftp_login($ftp, $_SESSION['ftpuser'], $_SESSION['ftppass'])):
			$aReturn[0] = true;
			$aReturn[1] = '';
		else:
			// verzeichnisstruktur anlegen, wenn nicht vorhanden
			$dirstructure = explode("/", $data);
			$opendir = "";
			array_pop($dirstructure);
			foreach ($dirstructure AS $value):
				$opendir = $opendir.$value."/";
				@ftp_mkdir($ftp, $_SESSION['ftpbasedir'].$opendir);
			endforeach;
			if (!(@ftp_put($ftp, $_SESSION['ftpbasedir']."/".str_replace("wsp/",$GLOBALS['tmpwspbasedir']."/",$data), $tmppfad, FTP_BINARY))):
				$aReturn[0] = true;
				$aReturn[1] = '';
			endif;
		endif;
		@unlink($tmppfad);
	endif;
	}

function DatabaseUpdate($changes) {
	$tempdev_fh = fopen($GLOBALS['wspvars']['installdatabase'].'/media/xml/database.xml', 'r');
	$tempdev_xmlversion = '';
	if (intval($tempdev_fh)>0):
		while (!feof($tempdev_fh)):
			$tempdev_xmlversion .= fgets($tempdev_fh, 4096);
		endwhile;
	endif;
	fclose($tempdev_fh);
	$tempdev_xml = xml_parser_create();

	xml_parse_into_struct($tempdev_xml, $tempdev_xmlversion, $tempdev_values, $index);
	$tempdevtable = array();
	$tempdev_tablenametemp = '';
	
	foreach ($tempdev_values as $tags):
		if ($tags['tag']=='TABLENAME'):
			$tempdev_tablename[] = $tags['value'];
			foreach ($tempdev_values as $tags2):
				if ($tags2['tag']=='TABLENAME'):
					$tempdev_tablenametemp = $tags2['value'];
				endif;
				if ($tempdev_tablenametemp==$tags['value']):
					if ($tags2['tag']=='FIELD'):
						@$tempdevtable[$tags['value']]['field'][] = $tags2['value'];
					endif;
					if ($tags2['tag']=='TYPE'):
						@$tempdevtable[$tags['value']]['type'][] = $tags2['value'];
					endif;
					if ($tags2['tag']=='NULL'):
						@$tempdevtable[$tags['value']]['null'][] = $tags2['value'];
					endif;
					if ($tags2['tag']=='KEY'):
						@$tempdevtable[$tags['value']]['key'][] = $tags2['value'];
					endif;
					if ($tags2['tag']=='DEFAULT'):
						@$tempdevtable[$tags['value']]['default'][] = $tags2['value'];
					endif;
					if ($tags2['tag']=='EXTRAS'):
						@$tempdevtable[$tags['value']]['extras'][] = $tags2['value'];
					endif;
				endif;
			endforeach;
		endif;
	endforeach;
	// add tables
	for ($i=0;$i<$changes['tablecount'];$i++):
		$sqlstradnt = "";
		$keystrnt = "";
		if ($changes[$i]['tablename'] != "restrictions" && $changes[$i]['tablename']!="siteproperties"):
			$key = array_search($changes[$i]['tablename'],$tempdev_tablename);
			$sqlstradnt = "CREATE TABLE IF NOT EXISTS `".$tempdev_tablename[$key]."` (";
			for($j=0;$j<sizeof($tempdevtable[$tempdev_tablename[$key]]['field']);$j++):
				if($tempdevtable[$tempdev_tablename[$key]]['key'][$j]=="PRI"){
					$keystrnt.=", PRIMARY KEY (".$tempdevtable[$tempdev_tablename[$key]]['field'][$j].")";
				}elseif($tempdevtable[$tempdev_tablename[$key]]['key'][$j]=="MUL"){
					$keystrnt.=", INDEX (".$tempdevtable[$tempdev_tablename[$key]]['field'][$j].")";
				}else{
					$keystrnt.="";
				}
				if($tempdevtable[$tempdev_tablename[$key]]['null'][$j]=="YES"){
					$nullstr="NULL";
				}else{
					$nullstr="NOT NULL";
				}
				if($tempdevtable[$tempdev_tablename[$key]]['default'][$j]!=""){
					$defaultnt=" DEFAULT '".$tempdevtable[$tempdev_tablename[$key]]['default'][$j]."'";
				}else{
					$defaultnt="";
				}
				$sqlstradnt.= " `".$tempdevtable[$tempdev_tablename[$key]]['field'][$j]."` ".$tempdevtable[$tempdev_tablename[$key]]['type'][$j]." ".$nullstr." ".$defaultnt." ".$tempdevtable[$tempdev_tablename[$key]]['extras'][$j]."";
				
				if($j==(sizeof($tempdevtable[$tempdev_tablename[$key]]['field'])-1)):
					$sqlstradnt.=$keystrnt.")";
				else:
					$sqlstradnt.=", ";
				endif;
			endfor;
			mysql_query($sqlstradnt);
		endif; ?>
		<script type="text/javascript" language="javascript">
		<!--
		updateStat('db', <?php echo ceil($i/($changes['tablecount']/100)); ?>, '<?php echo ceil($i/($changes['tablecount']/100)); ?>%');
		//-->
		</script>
		<?php
		flush();flush();flush();
		ob_flush();ob_flush();ob_flush();
	endfor;
	}

function PackageUpdate($package) {
	if ($package!=''):
		$fh = fopen($GLOBALS['wspvars']['installuri'].'/updater.php?key='.$GLOBALS['wspvars']['installkey']."&url=".$_SESSION['siteurl'].'&file=updater/media/packages/'.$package.'.wsp3.tgz', 'r');
		$fileupdate = '';
		if (intval($fh)>0):
			while (!feof($fh)):
				$fileupdate .= fgets($fh, 4096);
			endwhile;
		endif;
		fclose($fh);

		$tmppfad = $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package.".wsp3.tgz";
		$tmpdat = fopen($tmppfad,'w');
		fwrite($tmpdat, $fileupdate);
		fclose($tmpdat);
		
		$ftphdl = ftp_connect($_SESSION['ftphost']);
		$login = ftp_login($ftphdl, $_SESSION['ftpuser'], $_SESSION['ftppass']);
		
		@mkdir($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package);
		
		if ($_SESSION['req_tarexec']===true):
			exec("cd ".$_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."; tar xzf ".$_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package.".wsp3.tgz");
		elseif ($_SESSION['req_tarpear']===true):
			require_once ($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/data/include/peararchive/tar.php");
			$tar_object = new Archive_Tar($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package);
			$tar_object-> extract($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/");
		endif;
		
		ftp_put($ftphdl, $_SESSION['ftpbasedir'].$GLOBALS['tmpwspbasedir']."/packages/".$package.".wsp3.xml", $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/package.xml", FTP_BINARY);
		
		$GLOBALS['copytree'] = array();
		GetCopyTree($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/");

//		echo "<pre>";
//		print_r($GLOBALS['copytree']);
//		echo "</pre>";

		
		foreach($GLOBALS['copytree'] AS $value):
			$copyvalue = str_replace($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$GLOBALS['tmpwspbasedir'],"",$value);
			if ($copyvalue!=""):
				if (is_dir(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$GLOBALS['tmpwspbasedir']."/".$copyvalue)))):
					@ftp_mkdir($ftphdl, str_replace("//", "/", str_replace("//", "/", $_SESSION['ftpbasedir'].$GLOBALS['tmpwspbasedir']."/".$copyvalue)));
				elseif (is_file($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$GLOBALS['tmpwspbasedir']."/".$copyvalue)):
					ftp_put($ftphdl, str_replace("//", "/", str_replace("//", "/", $_SESSION['ftpbasedir'].$GLOBALS['tmpwspbasedir']."/".$copyvalue)), str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$GLOBALS['tmpwspbasedir']."/".$copyvalue)), FTP_BINARY);
					// remove installed file from install folder
					unlink(str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$GLOBALS['tmpwspbasedir']."/".$copyvalue)));
				endif;
			endif;
		endforeach;
		// remove directories from install folder
		arsort($GLOBALS['copytree']);
		foreach($GLOBALS['copytree'] AS $value):
			$copyvalue = str_replace($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package,"",$value);
			if ($copyvalue!=""):
				if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$copyvalue)):
					@rmdir($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$copyvalue);
				endif;
			endif;
		endforeach;
		ftp_close($ftphdl);
	endif;
	}

function MediaUpdate($package) {
	if ($package!=''):
		$fh = fopen($GLOBALS['wspvars']['installuri'].'/updater.php?key=install&file=updater/media/media/'.$package.'.wsp3.tgz', 'r');
		$fileupdate = '';
		if (intval($fh)>0):
			while (!feof($fh)):
				$fileupdate .= fgets($fh, 4096);
			endwhile;
		endif;
		fclose($fh);

		$tmppfad = $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package.".wsp3.tgz";
		$tmpdat = fopen($tmppfad,'w');
		fwrite($tmpdat, $fileupdate);
		fclose($tmpdat);
		
		$ftphdl = ftp_connect($_SESSION['ftphost']);
		$login = ftp_login($ftphdl, $_SESSION['ftpuser'], $_SESSION['ftppass']);
		
		@mkdir($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package);
		
		if ($_SESSION['req_tarexec']===true):
			exec("cd ".$_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."; tar xzf ".$_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package.".wsp3.tgz");
		elseif ($_SESSION['req_tarpear']===true):
			require_once ($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/data/include/peararchive/tar.php");
			$tar_object = new Archive_Tar($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package);
			$tar_object-> extract($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/");
		endif;
		
		$GLOBALS['copytree'] = array();
		GetCopyTree($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/");
		
		foreach($GLOBALS['copytree'] AS $value):
			$copyvalue = str_replace($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/wsp","",$value);
			$copyvalue = substr($copyvalue, 7);
			if ($copyvalue!=""):
				if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/wsp/media/".$copyvalue)):
					@ftp_mkdir($ftphdl, $_SESSION['ftpbasedir'].$GLOBALS['tmpwspbasedir']."/media/".$copyvalue);
				elseif (is_file($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/wsp/media/".$copyvalue)):
					ftp_put($ftphdl, $_SESSION['ftpbasedir'].$GLOBALS['tmpwspbasedir']."/media/".$copyvalue, $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/wsp/media/".$copyvalue, FTP_BINARY);
					unlink($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/wsp/media/".$copyvalue);
				endif;
			endif;
		endforeach;
		
		arsort($GLOBALS['copytree']);
		
		foreach($GLOBALS['copytree'] AS $value):
			$copyvalue = str_replace($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package,"",$value);
			if ($copyvalue!=""):
				if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$copyvalue)):
					@rmdir($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/".$package."/".$copyvalue);
				endif;
			endif;
		endforeach;
		
		ftp_close($ftphdl);
	endif;
	}

function GetCopyTree($startsysdir) {
	$dirread = dir($startsysdir);
	while (false !== ($entry = $dirread->read())):
		if (!($entry=='.') && !($entry=='..') && !($entry=='package.xml')):
			$GLOBALS['copytree'][] = str_replace("//","/",$startsysdir."/".$entry);
			if (is_dir($startsysdir."/".$entry)):
				GetCopyTree($startsysdir."/".$entry);
			endif;
		endif;
	endwhile;
	}

if (!(isset($_POST['step']))):
//	if (!(isset($_GET['logout'])) && !(isset($_POST['loginmail'])) && !(isset($_POST['loginfield']))):
	if (!(isset($_GET['cookie']))):
		$_SESSION['cookiecheck'] = "run";
		$_SESSION['loginmsg'] = "";
		header("location: wspsetup.php?cookie");
	endif;
	if ($_SESSION['cookiereload']=="run" && $_SESSION['cookiecheck']!="pass"):
		$_SESSION['cookiecheck'] = "run";
	endif;
	$_SESSION['cookiereload'] = "run";
//	endif;
endif;

$tmpwspbasedir = substr(str_replace("/wspsetup.php","",$_SERVER['SCRIPT_NAME']),1);

$_SESSION['step'] = 0;
$_SESSION['req_cookies'] = false;
$_SESSION['req_safemode'] = false;
$_SESSION['req_fopen'] = false;
$_SESSION['req_exec'] = false;
$_SESSION['req_tar'] = false;
$_SESSION['req_tarexec'] = false;
$_SESSION['req_tarpear'] = false;

$pear = false;

/* perform requirements checks */
if ($_SESSION['cookiecheck']=='run'): $_SESSION['req_cookies'] = true; endif;
if (ini_get('safe_mode')==0): $_SESSION['req_safemode'] = true; endif;
$fh = fopen('http://www.google.de', 'r'); 
if ($fh): $_SESSION['req_fopen'] = true; endif; 
fclose($fh);
@exec("ls ".$_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/", $execarray);
if(count($execarray)>0 && is_array($execarray)): $_SESSION['req_exec'] = true; endif;
unset($execarray); @exec("tar xfv wsp.tar", $execarray); 
if(count($execarray)>0 && is_array($execarray)): 
	$_SESSION['req_tar'] = true;
	$_SESSION['req_tarexec'] = true;
else:
	if (is_file($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/include/peararchive/tar.php")):
		require_once ($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/include/peararchive/tar.php");
		/* delete test directories */
		$tar_object = new Archive_Tar('wsp.tar');
		$tar_object-> extract($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/");
		$pear = true;
		$peartmp = false;
		$pearcheck = false;
		if (is_file($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/data/include/peararchive/tar.php")):
			$pearcheck = true;
			$peartmp = true;
			$_SESSION['req_tar'] = true;
			$_SESSION['req_tarpear'] = true;
		else:
			@ mkdir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/testdir");
			if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/testdir")):
				$peartmp = true;
				$pearcheck = false;
			else:
				$peartmp = false;
				$pearcheck = false;
			endif;
		endif;
	else:
		$pear = false;
	endif; 
	@ unlink($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/data/include/peararchive/PEAR.php");
	@ unlink($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/data/include/peararchive/PEAR5.php");
	@ unlink($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/data/include/peararchive/tar.php");
	@ rmdir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/data/include/peararchive");
	@ rmdir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/data/include");
	@ rmdir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/data");
	@ rmdir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/data/tmp/testdir");
endif;

if (!(isset($_POST['step']))):
	$_SESSION['step'] = 0;
endif;

if (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==1):
	$_SESSION['step'] = 1;
	unset($_SESSION['ftphost']);
	unset($_SESSION['ftpuser']);
	unset($_SESSION['ftppass']);
	unset($_SESSION['ftpbasedir']);
	unset($_SESSION['dbhost']);
	unset($_SESSION['dbuser']);
	unset($_SESSION['dbpass']);
	unset($_SESSION['dbname']);
endif;

if (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==2):
	// grab ftp-data
	$_SESSION['ftphost'] = $_POST['ftphost'];
	$_SESSION['ftpuser'] = $_POST['ftpuser'];
	$_SESSION['ftppass'] = $_POST['ftppass'];
	$_SESSION['ftpbasedir'] = $_POST['ftpbasedir'];
	$_SESSION['wspvars']['resultmsg'] = "";
	$_SESSION['wspvars']['noticemsg'] = "";
	$_SESSION['wspvars']['errormsg'] = "";
	// check ftp
	$ftpaccess = false;
	$ftpcon = @ftp_connect($_POST['ftphost']);
	if ($ftpcon === false):
		$_SESSION['wspvars']['errormsg'].= '<p>Der FTP-Host ist nicht erreichbar!</p>';
	elseif (@ftp_login($ftpcon, $_POST['ftpuser'], $_POST['ftppass']) === false):
		$_SESSION['wspvars']['errormsg'].= '<p>Die FTP-Verbindung konnte nicht aufgebaut werden! Bitte pr&uuml;fen Sie Username und Passwort!</p>';
	else:
		if (ftp_size($ftpcon , $_SESSION['ftpbasedir'].substr($_SERVER['PHP_SELF'],1))<=0):
			$_SESSION['wspvars']['resultmsg'].= '<p>Die FTP-Verbindung war erfolgreich.</p>';
			$_SESSION['wspvars']['noticemsg'].= '<p>Bitte &uuml;berpr&uuml;fen Sie die Einstellungen zum FTP-Basedir!</p>';
		else:
			$_SESSION['wspvars']['resultmsg'].= '<p>Die FTP-Verbindung war erfolgreich und alle Einstellungen scheinen korrekt zu sein.</p>';
			$ftpaccess = true;
		endif;
	endif;
	@ftp_close($ftpcon);
	// grab dbcon-data
	$_SESSION['dbhost'] = $_POST['dbhost'];
	$_SESSION['dbuser'] = $_POST['dbuser'];
	$_SESSION['dbpass'] = $_POST['dbpass'];
	$_SESSION['dbname'] = $_POST['dbname'];
	// check dbcon
	$db = @mysql_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpass']);
	if ($db === false):
		$_SESSION['wspvars']['errormsg'].= '<p>Der Login am MySQL-Datenbank-Server war nicht erfolgreich!</p>';
	elseif (!mysql_select_db($_POST['dbname'], $db)):
		$_SESSION['wspvars']['errormsg'].= '<p>Die angegebene MySQL-Datenbank konnte nicht angesprochen werden!</p>';
	else:
		$_SESSION['wspvars']['resultmsg'].= '<p>Der Login am MySQL-Datenbank-Server war erfolgreich!</p>';
		$dbaccess = true;
	endif;
	if (!($dbaccess && $ftpaccess)):
		$_POST['step'] = 1;
		$_SESSION['step'] = 1;
	elseif ($_SESSION['step']<intval($_POST['step'])):
		$_SESSION['step'] = intval($_POST['step']);
	endif;
	if ($_SESSION['step']>4):
		$_SESSION['step'] = 4;
	endif;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==3):
	// grab admin-data
	$_SESSION['adminuser'] = $_POST['adminuser'];
	$_SESSION['adminpass'] = $_POST['adminpass'];
	$_SESSION['adminrealname'] = $_POST['adminrealname'];
	$_SESSION['adminmail'] = $_POST['adminmail'];
	if ($_SESSION['step']<intval($_POST['step'])):
		$_SESSION['step'] = intval($_POST['step']);
	endif;
	if ($_SESSION['step']>4):
		$_SESSION['step'] = 4;
	endif;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==4):
	// grab data
	$_SESSION['siteurl'] = $_POST['siteurl'];
	$_SESSION['devurl'] = $_POST['devurl'];
	$_SESSION['sitetitle'] = $_POST['sitetitle'];
	$_SESSION['sitedesc'] = $_POST['sitedesc'];
	$_SESSION['sitekeys'] = $_POST['sitekeys'];
	if ($_SESSION['step']<intval($_POST['step'])):
		$_SESSION['step'] = intval($_POST['step']);
	endif;
	if ($_SESSION['step']>4):
		$_SESSION['step'] = 4;
	endif;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==5):
	$_SESSION['step'] = 5;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==6):
	$_SESSION['step'] = 6;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==7):
	$_SESSION['step'] = 7;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==8):
	$_SESSION['step'] = 8;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==9):
	$_SESSION['step'] = 9;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==10):
	$_SESSION['step'] = 10;
elseif (isset($_POST) && array_key_exists('step', $_POST) && $_POST['step']==11):
	$_SESSION['step'] = 11;
endif;

if ($_SESSION['step']==4):
	$_SESSION['wspvars']['errormsg'] = '';
	// create tmp-folder an check for safemode before jumping to next page
	$ftp = @ftp_connect($_SESSION['ftphost']);
	@ftp_login($ftp, $_SESSION['ftpuser'], $_SESSION['ftppass']);
	// setzen der schreibrechte des wsp-ordners
	@ftp_site($ftp, "chmod 777 ".$_SESSION['ftpbasedir'].$tmpwspbasedir);
	// anlegen des tmp-ordners und setzen der schreibrechte
	@ftp_mkdir($ftp, $_SESSION['ftpbasedir'].$tmpwspbasedir."/tmp");
	@ftp_site($ftp, "chmod 777 ".$_SESSION['ftpbasedir'].$tmpwspbasedir."/tmp");
	// zuruecksetzen der schreibrechte des wsp-ordners
	@ftp_site($ftp, "chmod 755 ".$_SESSION['ftpbasedir'].$tmpwspbasedir);
	@ftp_close($ftpcon);
	$safemode = true;
	$fp = fopen('tmp/install.log', 'w');
	if (!($fp)):
		$_SESSION['wspvars']['errormsg'].= '<p>Bitte &uuml;berpr&uuml;fen Sie vor dem weiteren Installationsvorgang, ob der "PHP-Safemode" auf "AUS" gesetzt ist, damit WSP die temporären Dateien schreiben kann.</p>';
		$safemode = false;
	endif;
	// check connection to update-server
	$doinstall = true;
	$fh = @fopen($GLOBALS['wspvars']['installuri']."/updater.php?key=install", 'r');
	if (!($fh)):
		$_SESSION['wspvars']['errormsg'].= "<p>Es gibt keine Verbindung zum Installationsserver. Prüfen Sie bitte, ob Ihr System den Befehl fopen() unterstützt.</p>";
		$doinstall = false;
	endif;
endif;

if ($_SESSION['step']==5):
	// create tmp dir, if not exists
	if (!(is_dir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/tmp/".session_id()))):
		mkdir($_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/tmp/".session_id());
	endif;
	// create base folder structure
	$folderstructure = array(
		'data',
		'data/include',
		'data/interpreter',
		'data/script',
		'data/lang',
		'data/modsetup',
		'data/modules',
		'data/parser',
		'data/script',
		'data/templates',
		'media',
		'media/layout',
		'media/screen',
		'media/skel',
		'media/skel/data',
		'media/skel/data/include',
		'media/skel/data/script',
		'media/skel/media',
		'media/skel/media/download',
		'media/skel/media/flash',
		'media/skel/media/images',
		'media/skel/media/video',
		'media/skel/media/fonts',
		'media/skel/media/layout',
		'media/skel/media/rss',
		'media/skel/media/screen',
		'media/skel/modules',
		'modules',
		'packages',
		'plugins'
		);
	$ftp = @ftp_connect($_SESSION['ftphost']);
	@ftp_login($ftp, $_SESSION['ftpuser'], $_SESSION['ftppass']);
	foreach ($folderstructure AS $foldervalue):
		@ftp_mkdir($ftp, $_SESSION['ftpbasedir'].$tmpwspbasedir."/".$foldervalue);
	endforeach;
	@ftp_close($ftpcon);
endif;

$startinstall = true;
/*
if (is_file("data/include/dbaccess.inc.php")):
	$startinstall_error .= "<p>Database connection file already configured.</p>";
	$startinstall = false;
endif;
if (is_file("data/include/ftpaccess.inc.php")):
	$startinstall_error .=  "<p>File transfer protocoll file already configured.</p>";
	$startinstall = false;
endif;
*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="de">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<meta name="author" content="http://www.covi.de">
<meta name="copyright" content="http://www.covi.de">
<meta name="publisher" content="http://www.covi.de">
<meta name="robots" content="nofollow">
<title>WSP3 Installer</title>
<script type="text/javascript"><!--//--><![CDATA[//><!--
startList = function() {
	if (document.all&&document.getElementById) {
		cssdropdownRoot = document.getElementById("cssdropdown");
		for (x=0; x<cssdropdownRoot.childNodes.length; x++) {
			node = cssdropdownRoot.childNodes[x];
			if (node.nodeName=="LI") {
				node.onmouseover=function() {
					this.className+=" over";
					}
				node.onmouseout=function() {
					this.className=this.className.replace(" over", "");
					}
				}
			}
		}
	}

if (window.attachEvent) { window.attachEvent("onload", startList) }
else { window.onload=startList; }
	
//--><!]]></script>
<style type="text/css">
<!--
* {
	margin: 0px;
	padding: 0px;
	}

body {
	font-family: 'Lucida Grande', lucida, verdana, sans-serif;
	font-size: 12px;
	}

#topholderback {
	position: fixed;
	top: 0px;
	left: 0px;
	width: 100%;
	height: 40px;
	background: #D3DDE3;
	border-bottom: 1px solid #fff;
	-moz-box-shadow: 3px 0px 10px #000;
	-webkit-box-shadow: 3px 0px 10px #000;
	box-shadow: 3px 0px 10px #000;
	/* For IE 8 */
	-ms-filter: "progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=135, Color='#000000')";
	/* For IE 5.5 - 7 */
	filter: progid:DXImageTransform.Microsoft.Shadow(Strength=4, Direction=135, Color='#000000');
	z-index: 4;
	}

#topholder {
	position: fixed;
	top: 0px;
	left: 0px;
	width: 100%;
	height: 20px;
	background: #C2CBD1; 
	line-height: 40px;
	z-index: 5;
	}

#topspacer {
	z-index: 3;
	position: relative;
	margin: 0px auto 10px auto;
	width: 980px;
	height: 45px;
	font-size: 11px;
	}

#errormsg {
	position: relative;
	margin: 0px auto 10px auto;
	background: #B60D00;
	color: #fff;
	border: none;
	}

#noticemsg {
	position: relative;
	margin: 0px auto 10px auto;
	background: #EBB88A;
	color: #fff;
	border: none;
	}

#resultmsg {
	position: relative;
	margin: 0px auto 10px auto;
	background: #12690F;
	color: #fff;
	border: none;
	}

#infoholder {
	position: relative;
	margin: 0px auto 0px auto;
	width: 980px;
	}

#contentholder, #contentblock, #contentarea {
	z-index: 1;
	position: relative;
	margin: 10px auto 20px auto;
	width: 980px;
	}
	
#footer {
	clear: both;
	width: 98%;
	margin: 20px 0% 20px 1%;
	border-top: 1px solid black;
	}

#footer p {
	padding: 15px 0px;
	font-size: 11px;
	text-align: center;
	}
	
#footer p.rightpos {
	float: right;
	text-align: right;
	}

#footer p.leftpos {
	float: left;
	text-align: left;
	}

a {
	color: #354E65;
	text-decoration: none;
	}

fieldset {
	padding: 6px;
	margin: 5px 0px 3px 0px;
	border: 1px solid #D3DDE3;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	}

fieldset.options {
	text-align: center;
	background: #D3DDE3;
	}

fieldset h1 {
	font-size: 120%;
	font-weight: bold;
	}
	
fieldset legend {
	font-weight: bold;
	padding: 0px 2px;
	}

fieldset a {
	color: #354E65;
	text-decoration: none;
	}
	
fieldset a.greenfield {
	color: #354E65;
	text-decoration: none;
	border: 1px solid #354E65;
	background: #fff;
	padding: 3px;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	font-size: 11px;
	}

fieldset a.greenfield:hover {
	color: #fff;
	border: 1px solid #354E65;
	background: #354E65;
	}
	
a.orangefield {
	color: #5E7B95;
	text-decoration: none;
	border: 1px solid #5E7B95;
	background: #fff;
	padding: 3px;
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	font-size: 11px;
	}

a.orangefield:hover {
	color: #fff;
	border: 1px solid #5E7B95;
	background: #5E7B95;
	}

#menuholder {
	z-index: 6;
	position: fixed;
	margin: 0px auto 10px auto;
	width: 980px;
	height: 40px;
	font-size: 11px;
	}

#menuholder li {
	float: left;
	position: relative;
	line-height: 21px;
	border-left: 1px solid white;
	}

#menuholder li.mainlist {
	padding: 10px 10px;
	cursor: pointer;
	}
	
#menuholder li.mainlist.disabled {
	opacity: 0.3;
	cursor: auto;
	}

#menuholder ul {
	list-style-type: none;
	}

/* tabellengestaltung */

table.contenttable {
	width: 100%;
	border-collapse: collapse;
	}
	
table.contenttable.noborder {
	border: none;
	}

table.contenttable td {
	padding: 5px;
	border: 1px solid #354E65;
	}

table.contenttable td table td {
	border: none;
	padding: 0px;
	}
	
table.contenttable td table.contenttable td {
	padding: 5px;
	border: 1px solid #354E65;
	}
	
table.contenttable tr.tablehead {
	background: #354E65;
	color: #fff;
	}
	
table.contenttable tr.tablehead td a {
	color: #fff;
	}
	
table.contenttable tr.firstcol {
	background: #fff;
	}

table.contenttable tr.firstcol td.required {
	background: #F3DAC4;
	}

table.contenttable tr.secondcol {
	background: #D3DDE3;
	}

table.contenttable tr.secondcol td.required {
	background: #E2A09F;
	}
	
table.contenttable tr.firstcol.updatepossible {
	background: #F5E2D0;
	}
	
table.contenttable tr.secondcol.updatepossible {
	background: #EAB78A;
	}
	
table.contenttable.noborder td {
	border: none;
	}

#dhtmltooltip{
	position: absolute;
	width: 250px;
	border: none;
	padding: 5px;
	visibility: hidden;
	z-index: 100;
	opacity: 0.9;
	background: white;
	-moz-box-shadow: 0px 0px 10px #000;
	-webkit-box-shadow: 0px 0px 10px #000;
	box-shadow: 0px 0px 10px #000;
	}

accr {
	cursor: pointer;
	}

form {
	margin: 0px;
	padding: 0px;
	}

input, textarea, select {
	font-family: verdana, arial, sans-serif;
	font-size: 11px;
	line-height: 17px;
	}

input.one.full {
	width: 96%;
	}

select.one.full {
	width: 98%;
	}

input.two.half {
	width: 96%;
	}

input.three.full {
	width: 98.66%;
	}
	
textarea.three.full {
	width: 98.66%;
	}

-->
</style>
</head>


<body id="wspbody">

<div id="topholderback"></div>
<div id="topholder"></div>
<script type="text/javascript"><!--//--><![CDATA[//><!--

startList = function() {
	if (document.all&&document.getElementById) {
		cssdropdownRoot = document.getElementById("cssdropdown");
		for (x=0; x<cssdropdownRoot.childNodes.length; x++) {
			node = cssdropdownRoot.childNodes[x];
			if (node.nodeName=="LI") {
				node.onmouseover=function() {
					this.className+=" over";
					}
				node.onmouseout=function() {
					this.className=this.className.replace(" over", "");
					}
				}
			}
		}
	}

if (window.attachEvent) { window.attachEvent("onload", startList) }
else { window.onload=startList; }

function jumpTo(jumpValue) {
	if (jumpValue=='logout') {
		window.location.href = '/wsp/logout.php';
		}
	else {
		var menuLength = document.getElementById('cssdropdown').childNodes.length;
		for (var cm = 0; cm < menuLength; cm++) {
			if (document.getElementById('cssdropdown').childNodes[cm].nodeName=='DIV') {
				document.getElementById(document.getElementById('cssdropdown').childNodes[cm].getAttribute('id')).style.display = 'none';
				}
			}
		document.getElementById(jumpValue).style.display = 'block';
		}
	}

function showStep(stepID) {
	for (s=0; s<=<?php if(intval($_SESSION['step'])<5): echo intval($_SESSION['step']); else: echo 9; endif; ?>; s++) {
		document.getElementById('step' + s).style.display = 'none';
		}
	document.getElementById('step' + stepID).style.display = 'block';
	}

//--><!]]></script>
<div id="menuholder">
	<ul>
		<li class="mainlist <?php if($_SESSION['step']>4): echo "disabled"; endif; ?>" id="m_1"><?php if($_SESSION['req_cookies']===true && $_SESSION['req_safemode']===true && $_SESSION['req_fopen']===true && $_SESSION['req_tar']===true && $_SESSION['step']<5): ?><a onclick="showStep(1)"><?php endif; ?>Zugangsdaten<?php if($_SESSION['req_cookies']===true && $_SESSION['req_safemode']===true && $_SESSION['req_fopen']===true && $_SESSION['req_tar']===true && $_SESSION['step']<5): ?></a><?php endif; ?></li>
		<li class="mainlist <?php if($_SESSION['step']>4): echo "disabled"; endif; ?>" id="m_2"><?php if($_SESSION['step']>1 && $_SESSION['step']<5): ?><a onclick="showStep(2)"><?php endif; ?>Admin-Account<?php if($_SESSION['step']>1 && $_SESSION['step']<5): ?></a><?php endif; ?></li>
		<li class="mainlist <?php if($_SESSION['step']>4): echo "disabled"; endif; ?>" id="m_3"><?php if($_SESSION['step']>2 && $_SESSION['step']<5): ?><a onclick="showStep(3)"><?php endif; ?>Basisdaten<?php if($_SESSION['step']>2 && $_SESSION['step']<5): ?></a><?php endif; ?></li>
		<li class="mainlist <?php if($_SESSION['step']>4): echo "disabled"; endif; ?>" id="m_4"><?php if($_SESSION['step']>3 && $_SESSION['step']<5): ?><a onclick="showStep(4)"><?php endif; ?>Basisinstallation starten<?php if($_SESSION['step']>3 && $_SESSION['step']<5): ?></a><?php endif; ?></li>
		<!-- <li class="mainlist <?php if($_SESSION['step']<6 || $_SESSION['step']>10): echo "disabled"; endif; ?>" id="m_5"><?php if($_SESSION['step']>5 &&  $_SESSION['step']<11): ?><a onclick="showStep(5)"><?php endif; ?>Module & Interpreter installieren<?php if($_SESSION['step']>5 &&  $_SESSION['step']<11): ?></a><?php endif; ?></li>
		<li class="mainlist <?php if($_SESSION['step']<6 || $_SESSION['step']>10): echo "disabled"; endif; ?>" id="m_6"><?php if($_SESSION['step']>6 &&  $_SESSION['step']<11): ?><a onclick="showStep(6)"><?php endif; ?>Screendesign<?php if($_SESSION['step']>6 && $_SESSION['step']<11): ?></a><?php endif; ?></li>
		<li class="mainlist <?php if($_SESSION['step']<6 || $_SESSION['step']>10): echo "disabled"; endif; ?>" id="m_7"><?php if($_SESSION['step']>7 &&  $_SESSION['step']<11): ?><a onclick="showStep(7)"><?php endif; ?>CSS<?php if($_SESSION['step']>7 && $_SESSION['step']<11): ?></a><?php endif; ?></li>
		<li class="mainlist <?php if($_SESSION['step']<6 || $_SESSION['step']>10): echo "disabled"; endif; ?>" id="m_8"><?php if($_SESSION['step']>8 &&  $_SESSION['step']<11): ?><a onclick="showStep(8)"><?php endif; ?>Basistemplate<?php if($_SESSION['step']>8 && $_SESSION['step']<11): ?></a><?php endif; ?></li>
		<li class="mainlist <?php if($_SESSION['step']<6 || $_SESSION['step']>10): echo "disabled"; endif; ?>" id="m_9"><?php if($_SESSION['step']>9 &&  $_SESSION['step']<11): ?><a onclick="showStep(9)"><?php endif; ?>1. Seite anlegen<?php if($_SESSION['step']>9 && $_SESSION['step']<11): ?></a><?php endif; ?></li> -->
	</ul>
</div>

<fieldset id="dhtmltooltip"></fieldset>
<div id="topspacer"></div>
<div id="infoholder"><fieldset id="errormsg" <? if(!(isset($_SESSION['wspvars']['errormsg'])) || is_array($_SESSION['wspvars']['errormsg']) && count($_SESSION['wspvars']['errormsg'])==0): echo "style=\"display: none\""; endif; ?>><?php if(isset($_SESSION['wspvars']['errormsg']) && count($_SESSION['wspvars']['errormsg'])>0): echo implode(" ", $_SESSION['wspvars']['errormsg']); endif; unset($_SESSION['wspvars']['errormsg']); ?></fieldset>
<fieldset id="noticemsg" <? if(!(isset($_SESSION['wspvars']['noticemsg'])) || is_array($_SESSION['wspvars']['noticemsg']) && count($_SESSION['wspvars']['noticemsg'])==0): echo "style=\"display: none\""; endif; ?>><?php if(isset($_SESSION['wspvars']['noticemsg']) && count($_SESSION['wspvars']['noticemsg'])>0): echo implode(" ", $_SESSION['wspvars']['noticemsg']); endif; unset($_SESSION['wspvars']['noticemsg']); ?></fieldset>
<fieldset id="resultmsg" <? if(!(isset($_SESSION['wspvars']['resultmsg'])) || is_array($_SESSION['wspvars']['resultmsg']) && count($_SESSION['wspvars']['resultmsg'])==0): echo "style=\"display: none\""; endif; ?>><?php if(isset($_SESSION['wspvars']['resultmsg']) && count($_SESSION['wspvars']['resultmsg'])>0): echo implode(" ", $_SESSION['wspvars']['resultmsg']); endif; unset($_SESSION['wspvars']['resultmsg']); ?></fieldset></div>

<script language="JavaScript" type="text/javascript">

var offsetxpoint=-60 //Customize x offset of tooltip
var offsetypoint=20 //Customize y offset of tooltip
var ie=document.all
var ns6=document.getElementById && !document.all
var enabletip=false
if (ie||ns6)
var tipobj=document.all? document.all["dhtmltooltip"] : document.getElementById? document.getElementById("dhtmltooltip") : ""

function ietruebody() {
	return (document.compatMode && document.compatMode!="BackCompat")? document.documentElement : document.body
	}

function ddrivetip(thetext, thecolor, thewidth) {
	if (ns6||ie){
		if (typeof thewidth!="undefined") tipobj.style.width=thewidth+"px"
		if (typeof thecolor!="undefined" && thecolor!="") tipobj.style.backgroundColor=thecolor
		tipobj.innerHTML=thetext
		enabletip=true
		return false
		}
	}

function positiontip(e){
if (enabletip){
var curX=(ns6)?e.pageX : event.clientX+ietruebody().scrollLeft;
var curY=(ns6)?e.pageY : event.clientY+ietruebody().scrollTop;
//Find out how close the mouse is to the corner of the window
var rightedge=ie&&!window.opera? ietruebody().clientWidth-event.clientX-offsetxpoint : window.innerWidth-e.clientX-offsetxpoint-20
var bottomedge=ie&&!window.opera? ietruebody().clientHeight-event.clientY-offsetypoint : window.innerHeight-e.clientY-offsetypoint-20

var leftedge=(offsetxpoint<0)? offsetxpoint*(-1) : -1000

//if the horizontal distance isn't enough to accomodate the width of the context menu
if (rightedge<tipobj.offsetWidth)
//move the horizontal position of the menu to the left by it's width
tipobj.style.left=ie? ietruebody().scrollLeft+event.clientX-tipobj.offsetWidth+"px" : window.pageXOffset+e.clientX-tipobj.offsetWidth+"px"
else if (curX<leftedge)
tipobj.style.left="5px"
else
//position the horizontal position of the menu where the mouse is positioned
tipobj.style.left=curX+offsetxpoint+"px"

//same concept with the vertical position
if (bottomedge<tipobj.offsetHeight)
tipobj.style.top=ie? ietruebody().scrollTop+event.clientY-tipobj.offsetHeight-offsetypoint+"px" : window.pageYOffset+e.clientY-tipobj.offsetHeight-offsetypoint+"px"
else
tipobj.style.top=curY+offsetypoint+"px"
tipobj.style.visibility="visible"
}
}

function hideddrivetip(){
if (ns6||ie){
enabletip=false
tipobj.style.visibility="hidden"
tipobj.style.left="-1000px"
tipobj.style.backgroundColor=''
tipobj.style.width=''
}
}

document.onmousemove=positiontip;

function blendItem(objID, start, blenddir) {
	document.getElementById(objID).style.opacity = start/100;
	document.getElementById(objID).style.filter = 'alpha(opacity: ' + start + ')';
	if (blenddir=='hide') {
		if (start>=5) {
			setTimeout("blendItem('" + objID + "', " + (start-5) + ", 'hide')", 100);
			}
		else {
			document.getElementById(objID).style.display = 'none';
			}
		}
	else if (blenddir=='show') {
		document.getElementById(objID).style.display = 'block';
		if (start<=95) {
			setTimeout("blendItem('" + objID + "', " + (start+5) + ", 'show')", 100);
			}
		else {
			document.getElementById(objID).style.display = 'block';
			}
		}
	}

if (document.getElementById('errormsg').innerHTML == '') {
	document.getElementById('errormsg').style.display = 'none';
	}
else {
	setTimeout("blendItem('errormsg', " + 100 + ", 'hide')", 3000);
	}
if (document.getElementById('noticemsg').innerHTML == '') {
	document.getElementById('noticemsg').style.display = 'none';
	}
else {
	setTimeout("blendItem('noticemsg', " + 100 + ", 'hide')", 3000);
	}
if (document.getElementById('resultmsg').innerHTML == '') {
	document.getElementById('resultmsg').style.display = 'none';
	}
else {
	setTimeout("blendItem('resultmsg', " + 100 + ", 'hide')", 3000);
	}

function valiData(formID) {
	if (formID==1) {
		// FTP
		if (document.getElementById('ftphost').value == '') {
			alert('Bitte geben Sie den FTP-Host an.');
			document.getElementById('ftphost').focus();
			return false;
			}	// if
		if (document.getElementById('ftpbasedir').value == '') {
			alert('Bitte geben Sie das FTP-Basisverzeichnis an.');
			document.getElementById('ftpbasedir').focus();
			return false;
			}	// if
		if (document.getElementById('ftpuser').value == '') {
			alert('Bitte geben Sie den FTP-Usernamen an.');
			document.getElementById('ftpuser').focus();
			return false;
			}	// if
		if (document.getElementById('ftppass').value == '') {
			alert('Bitte geben Sie das FTP-Passwort an.');
			document.getElementById('ftppass').focus();
			return false;
			}	// if
		
		// MySQL
		if (document.getElementById('dbhost').value == '') {
			alert('Bitte geben Sie den MySQL-Host an.');
			document.getElementById('dbhost').focus();
			return false;
			}	// if
		if (document.getElementById('dbname').value == '') {
			alert('Bitte geben Sie die MySQL-Datenbank an.');
			document.getElementById('dbname').focus();
			return false;
			}	// if
		if (document.getElementById('dbuser').value == '') {
			alert('Bitte geben Sie den MySQL-Usernamen an.');
			document.getElementById('dbuser').focus();
			return false;
			}	// if
		if (document.getElementById('dbpass').value == '') {
			alert('Bitte geben Sie das MySQL-Passwort an.');
			document.getElementById('dbpass').focus();
			return false;
			}	// if
		}
	else if (formID==2) {
		if (document.getElementById('adminuser').value == '') {
			alert('Bitte geben Sie einen Benutzernamen an.');
			document.getElementById('adminuser').focus();
			return false;
			}	// if
		if (document.getElementById('adminpass').value == '') {
			alert('Bitte geben Sie ein Passwort mit mind. 5 Stellen an.');
			document.getElementById('adminpass').focus();
			return false;
			}	// if
		if (document.getElementById('adminrealname').value == '') {
			alert('Bitte geben Sie Ihren richtigen Namen oder eine Bezeichnung Ihres Accounts an.');
			document.getElementById('adminrealname').focus();
			return false;
			}	// if
		if (document.getElementById('adminmail').value == '') {
			alert('Bitte geben Sie einen gueltige eMail-Adresse an.');
			document.getElementById('adminmail').focus();
			return false;
			}	// if
		}
	submitForm(formID);
	}	// valiData()
		
	function submitForm(formID) {
		document.getElementById('formStep' + formID).submit();
		}	// submitForm()
	
// -->
</script>
<style type="text/css">
<!--

td#req_javascript, td#req_cookies, td#req_safemode, td#req_fopen, td#req_tar {
	background: #B60D00;
	color: #fff;
	}
td#req_exec, td#req_php {
	background: #E86F0C;
	color: #fff;
	}

<?php if (intval(phpversion())>=5): ?>
td#req_php {
	background: #12690F;
	color: #fff;
	}
<?php endif; ?>

<?php if ($_SESSION['req_cookies']===true): ?>
td#req_cookies {
	background: #12690F;
	color: #fff;
	}
<?php endif; ?>

<?php if ($_SESSION['req_safemode']===true): ?>
td#req_safemode {
	background: #12690F;
	color: #fff;
	}
<?php endif; ?>

<?php if ($_SESSION['req_fopen']===true): ?>
td#req_fopen {
	background: #12690F;
	color: #fff;
	}
<?php endif;  ?>

<?php if ($_SESSION['req_exec']===true): ?>
td#req_exec {
	background: #12690F;
	color: #fff;
	}
<?php endif; ?>

<?php if ($_SESSION['req_tar']===true): ?>
td#req_tar {
	background: #12690F;
	color: #fff;
	}
<?php endif; ?>

-->
</style>
<input type="hidden" id="cfc" value="false" />
<div id="contentholder">
	<?php if(intval($_SESSION['step'])<6): ?><fieldset><h1><?php echo $lang[$wsplang]['install wsp']; ?></h1></fieldset><?php endif; ?>
	<span id="step0" <?php if(intval($_SESSION['step'])!=0): ?>style="display: none;"<?php endif; ?>>
	<fieldset>
		<p>Vor der Installation von WebSitePreview möchten wir Sie bitten, zu &uuml;berpr&uuml;fen, ob die folgenden Services verf&uuml;gbar sind.</p>
	</fieldset>
	<fieldset><legend>Benötigte Services</legend>
	<table class="contenttable">
		<tr>
			<td id="req_php">PHP 5 empfohlen » Sie haben Version <?php echo phpversion(); ?></td>
		</tr>
		<tr>
			<td id="req_cookies">Cookies</td>
		</tr>
		<tr>
			<td id="req_javascript">JavaScript-Funktionen</td>
		</tr>
		<tr>
			<td id="req_safemode">Safemode <strong>OFF</strong></td>
		</tr>
		<tr>
			<td id="req_fopen">Öffnen von Dateien anderer Server</td>
		</tr>
		<tr>
			<td id="req_exec">Ausführung externer Programme - einige Funktionen benötigen diesen Zugriff, wie z. B. die Konvertierung von PDF zu Bild. Sie können aber auf diese Funktionalität verzichten, wenn Sie dies nicht benötigen.</td>
		</tr>
		<tr>
			<td id="req_tar">
				<?php if (!($pear)): ?>
					Entpacken von Archiven mit der EXEC + TAR Funktion. 
					<?php if(!(is_file('wsp.tar'))): ?> 
						Bitte laden Sie für den Funktionstest die Datei <strong>wsp.tar</strong> in den gleichen Ordner wie die Setup-Datei. 
					<?php elseif (!($_SESSION['req_tar']===true)): ?> 
						Sollten Sie hier eine Fehlermeldung sehen, entpacken Sie bitte das mitgelieferte TAR-Archiv auf Ihrem Rechner, laden Sie die Inhalte in den WSP-Ordner und führen Sie diesen Test erneut aus.
					<?php endif; ?>
				<?php elseif ($pear): ?>
					Entpacken von Archiven mit der PEAR + TAR Funktion.
					<?php if(!(is_file('wsp.tar'))): ?>
						Bitte entpacken Sie die Datei wsp.tar auf Ihrem Rechner. Laden Sie dann die entpackten Inhalte sowie die original Datei <strong>wsp.tar</strong> in den Ordner der Setup-Datei und vergeben Sie dem Unterordner 'tmp' im Ordner 'data' alle Schreibrechte (777).
					<?php else: ?>
						<?php if (!($peartmp)): ?>
						Bitte entpacken Sie die Datei wsp.tar auf Ihrem Rechner. Laden Sie dann die entpackten Inhalte in den Ordner der Setup-Datei und vergeben Sie dem Unterordner 'tmp' im Ordner 'data' freie Schreibrechte (777). Führen Sie dann den Test erneut aus. 
						<?php elseif (!($pearcheck)): ?>
						Ein Entpacken von Archiven ist leider nicht möglich. Bitte wenden Sie sich an Ihren Webhoster, damit er die Erweiterung PEAR aktiviert.
						<?php endif; ?>
					<?php endif; ?>
				<?php endif; ?> 
			</td>
		</tr>
	</table>
	</fieldset>
	<script language="JavaScript" type="text/javascript">
	<!--
	document.getElementById('req_javascript').style.background = '#12690F';
	document.getElementById('req_javascript').style.color = '#fff';
	// -->
	</script>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep0" style="margin: 0px;">
	<input type="hidden" name="step" value="1" />
	</form>
	<fieldset class="options">
		<?php if($_SESSION['req_cookies']===true && $_SESSION['req_safemode']===true && $_SESSION['req_fopen']===true && $_SESSION['req_tar']===true): ?><p><a href="#" onclick="valiData(0);" class="greenfield">Installation starten</a></p><?php else: ?><p>Für eine korrekte Installation und die nachfolgende Verwendung von WSP sind rot markierte Services zwingend zu aktivieren.</p><?php endif; ?>
	</fieldset>
	</span>
	<span id="step1" <?php if($_SESSION['step']!=1): ?>style="display: none;"<?php endif; ?>>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep1" style="margin: 0px;">
	<fieldset>
		<p>Dies ist der erste Schritt der Installation von WebSitePreview. Sie installieren WSP im Ordner "<strong><?php echo $tmpwspbasedir; ?></strong>".</p>
		<p>Um die Installation zu beginnen, geben Sie bitte die FTP- und MySQL-Zugangsdaten ein, die Websitepreview zum Installieren und im sp&auml;teren Betrieb nutzen soll.</p>
	</fieldset>
	<fieldset><legend>Zugangsdaten</legend>
	<script language="JavaScript1.2" type="text/javascript">
	<!--
	
	function checkExtern(checkbox, checktarget) {
		if (document.getElementById(checkbox).checked) {
			document.getElementById(checktarget).style.display = 'block';
			}
		else {
			document.getElementById(checktarget).style.display = 'none';
			}
		}
	
	// -->
	</script>
	<table class="contenttable">
		<tr class="tablehead">
			<td colspan="4">FTP-Einstellungen f&uuml;r den Betrieb von WSP</td>
		</tr>
		<tr class="firstcol">
			<td width="25%">FTP-Host <accr onMouseover="ddrivetip('Der &#x93;FTP-Host&#x94; ist die Internetadresse, die Sie zur FTP-Anmeldung ben&ouml;tigen. In vielen F&auml;llen ist diese Adresse gleich der, &uuml;ber die Ihre Internetseiten erreicht werden k&ouml;nnen. Bitte geben Sie <strong>kein</strong> Protokoll (z. B. http:// oder ftp://) an. Der von WebSitePreview ermittelte Host ist voreingetragen.');" onMouseout="hideddrivetip();">ⓘ</accr></td>
			<td width="25%"><input type="text" id="ftphost" name="ftphost" value="<?php 
			
			if (trim($_SESSION['ftphost'])==""):
				if (substr($_SERVER['HTTP_HOST'], 0, 4)=="www."):
					echo substr($_SERVER['HTTP_HOST'], 4);
				else:
					echo $_SERVER['HTTP_HOST']; 
				endif;
			else:
				echo $_SESSION['ftphost'];
			endif; 
			
			?>" class="one full" /></td>
			<td width="25%">FTP-Basisverzeichnis <accr onMouseover="ddrivetip('Das &#x93;Basisverzeichnis&#x94; (<strong>Basedir</strong>) ist das Verzeichnis unter dem Stammverzeichnis (<strong>Rootdir</strong>) Ihrer Domain, in dem Sie Dateien ablegen, damit diese von Besuchern Ihrer Internetadresse erreicht werden k&ouml;nnen. H&auml;ufig ist das Basisverzeichnis das Verzeichnis &#x93;html&#x94;, &#x93;httpdocs&#x94; oder auch &#x93;www&#x94;, bei vielen Providern entspricht das Basisverzeichnis auch dem Stammverzeichnis. Geben Sie hier den Verzeichnisbaum ausgehend vom Loginverzeichnis Ihres FTP-Benutzers an. Bitte schlie&szlig;en Sie mit einem &#x93;/&#x94; ab.');" onMouseout="hideddrivetip();">ⓘ</accr></td>
			<td width="25%"><input type="text" id="ftpbasedir" name="ftpbasedir" value="<?php
	
			$pathstructure = explode("/", $_SERVER['DOCUMENT_ROOT']);
			$lastdir = $pathstructure[count($pathstructure)-1];
			
			if (trim($_SESSION['ftpbasedir'])!=""):
				echo trim($_SESSION['ftpbasedir']);
			elseif ($lastdir=="html" || $lastdir=="httpdocs" || $lastdir=="www"):
				echo "/".$lastdir."/";
			endif;
			
			?>" class="one full" /></td>
		</tr>
		<tr class="secondcol">
			<td width="25%">FTP-Benutzername</td>
			<td width="25%"><input type="text" id="ftpuser" name="ftpuser" value="<?php if (trim($_SESSION['ftpuser'])!=""): echo $_SESSION['ftpuser']; endif; ?>" class="one full" /></td>
			<td width="25%">FTP-Passwort</td>
			<td width="25%"><input type="text" id="ftppass" name="ftppass" value="<?php if (trim($_SESSION['ftppass'])!=""): echo $_SESSION['ftppass']; endif; ?>" class="one full" /></td>
		</tr>
		<tr class="firstcol">
			<td width="25%">MySQL-Host</td>
			<td width="25%"><input type="text" id="dbhost" name="dbhost" value="<?php if (trim($_SESSION['dbhost'])==""): echo "localhost"; else: echo $_SESSION['dbhost']; endif; ?>" class="one full" /></td>
			<td width="25%">MySQL-DB</td>
			<td width="25%"><input type="text" id="dbname" name="dbname" value="<?php if (trim($_SESSION['dbname'])!=""): echo $_SESSION['dbname']; endif; ?>" class="one full" /></td>
		</tr>
		<tr class="secondcol">
			<td width="25%">MySQL-Benutzername</td>
			<td width="25%"><input type="text" id="dbuser" name="dbuser" value="<?php if (trim($_SESSION['dbuser'])!=""): echo $_SESSION['dbuser']; endif; ?>" class="one full" /></td>
			<td width="25%">MySQL-Passwort</td>
			<td width="25%"><input type="text" id="dbpass" name="dbpass" value="<?php if (trim($_SESSION['dbpass'])!=""): echo $_SESSION['dbpass']; endif; ?>" class="one full" /></td>
		</tr>
		<tr class="tablehead">
			<td colspan="4">Ich betreibe WSP auf einem anderen Server als die Live-Umgebung - Zugänge f&uuml;r das LIVE-System anlegen <input type="checkbox" name="checkexternlive" id="checkexternlive" onchange="checkExtern(this.id, 'externlive');" value="1" <?php if(intval($_SESSION['checkexternlive'])==1): echo "checked=\"checked\""; endif; ?> ></td>
		</tr>
	</table>
	<div id="externlive" <?php if(intval($_SESSION['checkexternlive'])==0): echo "style=\"display: none;\""; endif; ?>><table class="contenttable" style="margin-top: -1px;">
		<tr class="firstcol">
			<td width="25%">FTP-Host LIVE</td>
			<td width="25%"><input type="text" id="ftphostlive" name="ftphostlive" value="<?php 
			
			if (trim($_SESSION['ftphostlive'])!=""):
				echo $_SESSION['ftphostlive'];
			endif; 
			
			?>" class="one full" /></td>
			<td width="25%">FTP-Basisverzeichnis LIVE</td>
			<td width="25%"><input type="text" id="ftpbasedirlive" name="ftpbasedirlive" value="<?php
	
			if (trim($_SESSION['ftpbasedirlive'])!=""):
				echo trim($_SESSION['ftpbasedirlive']);
			endif;
			
			?>" class="one full" /></td>
		</tr>
		<tr class="secondcol">
			<td width="25%">FTP-Benutzername LIVE</td>
			<td width="25%"><input type="text" id="ftpuserlive" name="ftpuserlive" value="<?php if (trim($_SESSION['ftpuserlive'])!=""): echo $_SESSION['ftpuserlive']; endif; ?>" class="one full" /></td>
			<td width="25%">FTP-Passwort LIVE</td>
			<td width="25%"><input type="text" id="ftppasslive" name="ftppasslive" value="<?php if (trim($_SESSION['ftppasslive'])!=""): echo $_SESSION['ftppasslive']; endif; ?>" class="one full" /></td>
		</tr>
		<tr class="firstcol">
			<td width="25%">MySQL-Host LIVE</td>
			<td width="25%"><input type="text" id="dbhostlive" name="dbhostlive" value="<?php if (trim($_SESSION['dbhostlive'])!=""): echo $_SESSION['dbhostlive']; endif; ?>" class="one full" /></td>
			<td width="25%">MySQL-DB LIVE</td>
			<td width="25%"><input type="text" id="dbnamelive" name="dbnamelive" value="<?php if (trim($_SESSION['dbnamelive'])!=""): echo $_SESSION['dbnamelive']; endif; ?>" class="one full" /></td>
		</tr>
		<tr class="secondcol">
			<td width="25%">MySQL-Benutzername LIVE</td>
			<td width="25%"><input type="text" id="dbuserlive" name="dbuserlive" value="<?php if (trim($_SESSION['dbuserlive'])!=""): echo $_SESSION['dbuserlive']; endif; ?>" class="one full" /></td>
			<td width="25%">MySQL-Passwort LIVE</td>
			<td width="25%"><input type="text" id="dbpasslive" name="dbpasslive" value="<?php if (trim($_SESSION['dbpasslive'])!=""): echo $_SESSION['dbpasslive']; endif; ?>" class="one full" /></td>
		</tr>
	</table></div>
	</fieldset>
	<input type="hidden" name="step" value="2" />
	</form>
	<fieldset class="options">
		<p><a href="#" onclick="valiData(1);" class="greenfield"><?php if($_SESSION['step']==1): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
	</fieldset>
	<script type="text/javascript" language="javascript">
	<!--
	document.getElementById('ftphost').focus();
	//-->
	</script>
	</span>
	
<?php if($_SESSION['step']>1): ?>
	<span id="step2" <?php if($_SESSION['step']!=2): ?>style="display: none;"<?php endif; ?>>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep2" style="margin: 0px;">
	<fieldset>
		<p>Legen Sie hier einen ersten Administrator-Account an. Nach der Installation von WSP können Sie weitere Administratoren oder Benutzer mit eingeschränkten Rechten anlegen.</p>
	</fieldset>
	<fieldset>
		<legend>Admin-Account</legend>
		<table class="contenttable">
		<tr>
			<td width="25%">Benutzername</td>
			<td width="25%"><input name="adminuser" id="adminuser" type="text" value="<?php if (array_key_exists('adminuser', $_SESSION) && trim($_SESSION['adminuser'])!=""): echo $_SESSION['adminuser']; endif; ?>" class="one full" /></td>
			<td width="25%">Passwort</td>
			<td width="25%"><input name="adminpass" id="adminpass" type="text" value="<?php if (array_key_exists('adminpass', $_SESSION) && trim($_SESSION['adminpass'])!=""): echo $_SESSION['adminpass']; endif; ?>" class="one full" /></td>
		</tr>
		<tr>
			<td width="25%">Realer Name</td>
			<td width="25%"><input name="adminrealname" id="adminrealname" type="text" value="<?php if (array_key_exists('adminrealname', $_SESSION) && trim($_SESSION['adminrealname'])!=""): echo $_SESSION['adminrealname']; endif; ?>" class="one full" /></td>
			<td width="25%">eMail-Adresse</td>
			<td width="25%"><input name="adminmail" id="adminmail" type="text" value="<?php if (array_key_exists('adminmail', $_SESSION) && trim($_SESSION['adminmail'])!=""): echo $_SESSION['adminmail']; endif; ?>" class="one full" /></td>
		</tr>
		</table>
	</fieldset>
	<fieldset class="options">
		<p><input type="hidden" name="step" value="3" /><a href="#" onclick="valiData(2);" class="greenfield"><?php if($_SESSION['step']==2): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
	</fieldset>
	</form>
	<script type="text/javascript" language="javascript">
	<!--
	document.getElementById('adminuser').focus();
	//-->
	</script>
	</span>
<?php endif; ?>

<?php if($_SESSION['step']>2): ?>
	<span id="step3" <?php if($_SESSION['step']!=3): ?>style="display: none;"<?php endif; ?>>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep3" style="margin: 0px;">
	<fieldset>
		<p>Definieren Sie hier die Basiseinstellungen f&uuml;r den Betrieb der Seite und von WSP.</p>
	</fieldset>
	<fieldset>
		<legend>Basisdaten</legend>
		<table class="contenttable">
		<tr>
			<td width="25%">Basis-URL/Identifier <accr onMouseover="ddrivetip('Einige Suchmaschinen speichern im Identifier die Basis-URL der Seite und fassen so Ergebnislisten zusammen.');" onMouseout="hideddrivetip();">ⓘ</accr></td>
			<td width="25%"><table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td>http://&nbsp;</td>
					<td width="100%"><input type="text" name="siteurl" id="siteurl" value="<?php 
			
					if (!(array_key_exists('siteurl', $_SESSION)) || (array_key_exists('siteurl', $_SESSION) && trim($_SESSION['siteurl'])=="")):
						echo $_SERVER['HTTP_HOST']; 
					else:
						echo $_SESSION['siteurl'];
					endif; 
					
					?>" class="one full" /></td>
				</tr>
			</table></td>
			<td width="25%">Development-URL <accr onMouseover="ddrivetip('Sollten Sie diese Pr&auml;senz unter einer anderen URL entwickeln, weil Sie zum Beispiel erst nach Freigabe des Projektes die Adresse Ihrer Website auf diesen Server umleiten, k&ouml;nnen Sie hier die URL der Entwicklungsumgebung angeben, damit Sie beim Klick auf &#x93;Website&#x94; direkt auf der Entwicklungsumgebung landen.');" onMouseout="hideddrivetip();">ⓘ</accr></td>
			<td width="25%"><table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td>http://&nbsp;</td>
					<td width="100%"><input type="text" name="devurl" id="devurl" value="<?php 
			
					if (!(array_key_exists('devurl', $_SESSION)) || (array_key_exists('devurl', $_SESSION) && trim($_SESSION['devurl'])=="")):
						echo $_SERVER['HTTP_HOST']; 
					else:
						echo $_SESSION['devurl'];
					endif; 
					
					?>" class="one full" /></td>
				</tr>
			</table></td>
		</tr>
		
		<tr class="secondcol">
			<td width="25%">Seitentitel <accr onMouseover="ddrivetip('Der Seitentitel sollte pr&auml;gnant sein und maximal 200 Zeichen enthalten. Eine gute L&auml;nge f&uuml;r Suchmaschinen sind circa 80 Zeichen, in denen nach M&ouml;glichkeit auch ein oder mehrere Keywords enthalten sein sollten.');" onMouseout="hideddrivetip();">ⓘ</accr></td>
			<td colspan="3"><input name="sitetitle" id="sitetitle" type="text" value="<?php if (array_key_exists('sitetitle', $_SESSION) && trim($_SESSION['sitetitle'])!=""): echo $_SESSION['sitetitle']; endif; ?>" maxlength="250" class="three full" /></td>
		</tr>
		<tr>
			<td width="25%">Kurzbeschreibung</td>
			<td colspan="3"><textarea name="sitedesc" id="sitedesc" cols="20" rows="5" class="three full"><?php if (array_key_exists('sitedesc', $_SESSION) && trim($_SESSION['sitedesc'])!=""): echo $_SESSION['sitedesc']; endif; ?></textarea></td>
		</tr>
		<tr class="secondcol">
			<td width="25%">Suchbegriffe</td>
			<td colspan="3"><textarea name="sitekeys" id="sitekeys" cols="20" rows="7" class="three full"><?php if (array_key_exists('sitekeys', $_SESSION) && trim($_SESSION['sitekeys'])!=""): echo $_SESSION['sitekeys']; endif; ?></textarea></td>
		</tr>
	</table>
	</span>
	</fieldset>
	<fieldset class="options">
		<p><input type="hidden" name="step" value="4" /><a href="#" onclick="submitForm(3);" class="greenfield"><?php if($_SESSION['step']==3): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
	</fieldset>
	</form>
	</span>
<?php endif; ?>

<?php if($_SESSION['step']>3): ?>
	<span id="step4" <?php if($_SESSION['step']!=4): ?>style="display: none;"<?php endif; ?>>
	<?php if ($safemode && $doinstall && $startinstall): ?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep4" style="margin: 0px;">
		<fieldset>
			<p><?php echo $lang[$wsplang]['step4 text to install']; ?></p></fieldset>
		<fieldset class="options">
			<p><input type="hidden" name="step" value="5" /><a href="#" onclick="submitForm(4);" class="greenfield">Installation starten</a></p>
		</fieldset>
		</form>
	<?php else : ?>
		<fieldset>
			<p>Die Installation kann derzeit nicht durchgef&uuml;hrt werden.</p>
		</fieldset>
	<?php endif; ?>
	</span>
<?php endif; ?>

<?php if($_SESSION['step']==5): ?>
	<fieldset>
		<legend>Systemdateien kopieren</legend>
		<p id="showfilestat" style="width: 0px; background: green; color: #000;">0%</p>
	</fieldset>
	<fieldset>
		<legend>Datenbank anlegen</legend>
		<p id="showdbstat" style="width: 0px; background: green; color: #000;">0%</p>
	</fieldset>
	<fieldset>
		<legend>Ergänzende Pakete kopieren</legend>
		<p id="showpackagestat" style="width: 0px; background: green; color: #000;">0%</p>
	</fieldset>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep5" style="margin: 0px;"><input type="hidden" name="step" value="6" /></form>
	<fieldset id="nextstep" class="options" style="display: none;">
		<p><a href="index.php" class="greenfield">Zum Login</a> <!-- <a href="#" onclick="submitForm(5);" class="greenfield">Fortfahren mit Ersteinrichtung</a> --></p>
	</fieldset>
<?php endif; ?>

<?php if($_SESSION['step']>5): ?>
	<span id="step5" <?php if($_SESSION['step']!=6): ?>style="display: none;"<?php endif; ?>>
		<fieldset><h1>Module & Interpreter installieren</h1></fieldset>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep6" style="margin: 0px;"><input type="hidden" name="step" value="7" /></form>
		<fieldset class="options">
			<p><a href="index.php" class="greenfield">Zum Login</a> <a href="#" onclick="submitForm(6);" class="greenfield"><?php if($_SESSION['step']==6): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
		</fieldset>
	</span>
	<span id="step6" <?php if($_SESSION['step']!=7): ?>style="display: none;"<?php endif; ?>>
		<fieldset><h1>Screendesign</h1></fieldset>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep7" style="margin: 0px;"><input type="hidden" name="step" value="8" /></form>
		<fieldset class="options">
			<p><a href="index.php" class="greenfield">Zum Login</a> <a href="#" onclick="submitForm(7);" class="greenfield"><?php if($_SESSION['step']==7): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
		</fieldset>
	</span>
	<span id="step7" <?php if($_SESSION['step']!=8): ?>style="display: none;"<?php endif; ?>>
		<fieldset><h1>CSS</h1></fieldset>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep8" style="margin: 0px;"><input type="hidden" name="step" value="9" /></form>
		<fieldset class="options">
			<p><a href="index.php" class="greenfield">Zum Login</a> <a href="#" onclick="submitForm(8);" class="greenfield"><?php if($_SESSION['step']==8): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
		</fieldset>
	</span>
	<span id="step8" <?php if($_SESSION['step']!=9): ?>style="display: none;"<?php endif; ?>>
		<fieldset><h1>Basistemplate</h1></fieldset>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep9" style="margin: 0px;"><input type="hidden" name="step" value="10" /></form>
		<fieldset class="options">
			<p><a href="index.php" class="greenfield">Zum Login</a> <a href="#" onclick="submitForm(9);" class="greenfield"><?php if($_SESSION['step']==9): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
		</fieldset>
	</span>
	<span id="step9" <?php if($_SESSION['step']!=10): ?>style="display: none;"<?php endif; ?>>
		<fieldset><h1>1. Seite anlegen</h1></fieldset>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" id="formStep10" style="margin: 0px;"><input type="hidden" name="step" value="11" /></form>
		<fieldset class="options">
			<p><a href="index.php" class="greenfield">Zum Login</a> <a href="#" onclick="submitForm(10);" class="greenfield"><?php if($_SESSION['step']==10): ?>Fortfahren<?php else: ?>&Auml;nderungen speichern<?php endif; ?></a></p>
		</fieldset>
	</span>
	<span id="step10" <?php if($_SESSION['step']!=11): ?>style="display: none;"<?php endif; ?>>
		<fieldset><h1>Zum WSP Login</h1></fieldset>
		<fieldset><p>Sie haben die Einrichtung von WSP abgeschlossen. Melden Sie sich an und legen Sie los.</p></fieldset>
		<fieldset class="options">
			<p><a href="index.php" class="greenfield">Zum Login</a></p>
		</fieldset>
	</span>
<?php endif; ?>
</div>
<div id="footer">
	<p class="rightpos">WSP funktioniert am besten mit folgenden Browsern:<br />IE 9+, FF, Safari, Chrome</p>
	<p class="leftpos">Helfen Sie, WebSitePreview weiter zu entwickeln!</p>
	<p><strong><a href="http://www.websitepreview.de" target="_blank">WebSitePreview</a> Installer 2.3</strong> &copy; <a href="http://www.covi.de" target="_blank">Common Visions Media.Agentur</a> 2001 - <?php echo date("Y"); ?> &middot; <a href="http://www.covi.de" target="_blank">www.covi.de</a></p>
</div>

<script language="JavaScript" type="text/javascript">
<!--

function updateStat(field, width, text) {
	document.getElementById('show' + field + 'stat').style.width = (width*1) + '%';
	document.getElementById('show' + field + 'stat').style.color = '#fff';
	document.getElementById('show' + field + 'stat').innerHTML = text;
	}

// -->
</script>

<?php if($_SESSION['step']==5): 
	// get all files from install server
	$fh = fopen($GLOBALS['wspvars']['installuri'].'/versions.php?key=install&url='.$_SESSION['siteurl'].'&type=readfile', 'r');
	$xmldata = '';
	if (intval($fh)>0):
		while (!feof($fh)):
			$xmldata .= fgets($fh, 4096);
		endwhile;
	endif;
	fclose($fh);
	$xml = xml_parser_create();
	$xmlparse = xml_parse_into_struct($xml, $xmldata, $values, $index);
	// fill array with installing files
	$newfile = array();
	foreach ($values as $file):
		if ($file['tag']=='FILENAME'):
			$newfile[] = str_replace("[wsp]",$tmpwspbasedir,$file['value']);
		endif;
	endforeach;
	// create installing instruction file
	$installpath = $_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/tmp/".session_id()."/updatedateien.php";
	$writeinstallfiles = fopen ($installpath,'w');
	fwrite($writeinstallfiles, sizeof($newfile)."\r\n");
	for ($i=0;$i<sizeof($newfile);$i++):
		fwrite($writeinstallfiles, $newfile[$i]."\r\n");
	endfor;
	fclose($writeinstallfiles);
	// install files
	$installpath = $_SERVER['DOCUMENT_ROOT']."/".$tmpwspbasedir."/tmp/".session_id()."/updatedateien.php";
	$install = file($installpath);
	for($i=1;$i<=$install[0];$i++):
		FileUpdate(trim($install[$i]));
		?>
		<script type="text/javascript" language="javascript">
		<!--
		updateStat('file', <?php echo ceil($i/($install[0]/100)); ?>, '<?php echo ceil($i/($install[0]/100)); ?>%');
		//-->
		</script>
		<?php
		flush();flush();flush();
		ob_flush();ob_flush();ob_flush();
	endfor;
	// install definite required tables to login and process first actions to go to system configuration
	$setupdbcon = mysql_connect($_SESSION['dbhost'],$_SESSION['dbuser'],$_SESSION['dbpass']);
	mysql_select_db($_SESSION['dbname'], $setupdbcon);
	// setup restriction table and admin rights
	$sql = "DROP TABLE IF EXISTS `restrictions`";
	mysql_query($sql);
	$sql = "CREATE TABLE `restrictions` (
		`rid` int(11) NOT NULL auto_increment,
		`usertype` enum('admin','user') NOT NULL default 'user',
		`user` varchar(16) NOT NULL default '',
		`pass` varchar(50) NOT NULL default '',
		`realname` varchar(200) NOT NULL default '',
		`realmail` varchar(200) NOT NULL default '',
		`realposition` varchar(100) NOT NULL default '',
		`rights` text NOT NULL,
		`idrights` text NOT NULL,
		PRIMARY KEY  (`rid`),
		UNIQUE KEY `user` (`user`)
		) TYPE=MyISAM COMMENT='Benutzerverwaltung' ;";
	mysql_query($sql);
	$sql = "INSERT INTO `restrictions` SET 
		`usertype` = 'admin',
		`user` = '".escapeSQL($_SESSION['adminuser'])."',
		`pass` = '".md5($_SESSION['adminpass'])."',
		`realname` = '".escapeSQL($_SESSION['adminrealname'])."',
		`realmail` = '".escapeSQL($_SESSION['adminmail'])."',
		`realposition` = 'admin',
		`rights` = '',
		`idrights` = ''";
	mysql_query($sql);
	// setup site property table and admin rights
	$sql = "DROP TABLE IF EXISTS `siteproperties`";
	mysql_query($sql);
	$sql = "CREATE TABLE `siteproperties` (
		`siteurl` varchar(100) NOT NULL default '',
		`sitetitle` varchar(250) NOT NULL default 'made with websitepreview - powered by covi.de',
		`sitekeys` text NOT NULL,
		`sitedesc` text NOT NULL,
		`sitecopy` varchar(150) NOT NULL default 'www.wsp3.de',
		`google-verify-v1` varchar(50) NOT NULL default '',
		`menutype` enum('steps','replace','divopen','undefined') NOT NULL default 'undefined',
		`nonames` text NOT NULL,
		`languages` varchar(150) NOT NULL default 'de',
		`options` text NOT NULL,
		`templates_id` int(11) NOT NULL default '0',
		`revisit` varchar(50) NOT NULL default '2 weeks',
		`use_session` char(1) NOT NULL default '1',
		`show_media` enum('galerie','liste') NOT NULL default 'liste'
		) TYPE=MyISAM;";
	mysql_query($sql);
	$sql = "INSERT INTO `siteproperties` SET `siteurl` = '".@escapeSQL($_SESSION['basisurl'])."',
		`sitetitle` = '".@escapeSQL($_SESSION['sitetitle'])."',
		`sitekeys` = '".@escapeSQL($_SESSION['sitekeys'])."',
		`sitedesc` = '".@escapeSQL($_SESSION['sitedesc'])."',
		`nonames` = 'index;parser;shop;login;logout;cms;data;media;images;screen;layout;wsp'";
	mysql_query($sql);
	// setup security table
	$sql = "DROP TABLE IF EXISTS `security`";
	mysql_query($sql);
	$sql = "CREATE TABLE `security` (
		`sid` int(11) NOT NULL auto_increment,
		`referrer` varchar(20) NOT NULL,
		`userid` int(11) NOT NULL,
		`timevar` int(11) NOT NULL,
		`viewer_mode` enum('no','yes') NOT NULL default 'no',
		`usevar` varchar(100) NOT NULL,
		`logintime` int(11) NOT NULL,
		`position` varchar(200) NOT NULL,
		`useragent` varchar(150) NOT NULL,
		PRIMARY KEY  (`sid`)
		) TYPE=MyISAM;";
	mysql_query($sql);
	/* properties table */
	$sql = "CREATE TABLE `wspproperties` (
		`id` int(11) NOT NULL auto_increment,
		`varname` varchar(255) NOT NULL,
		`varvalue` text NOT NULL,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `varname` (`varname`)
		) TYPE=MyISAM COMMENT='WSP Properties' ;";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'sitetitle', `varvalue` = '".escapeSQL($_SESSION['sitetitle'])."'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'sitekeys', `varvalue` = '".escapeSQL($_SESSION['sitekeys'])."'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'sitedesc', `varvalue` = '".escapeSQL($_SESSION['sitedesc'])."'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'sitecopy', `varvalue` = 'www.websitepreview.de'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'siteurl', `varvalue` = '".escapeSQL($_SESSION['siteurl'])."'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'devurl', `varvalue` = '".escapeSQL($_SESSION['devurl'])."'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'show_media', `varvalue` = 'liste'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'medialistlength', `varvalue` = '20'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'filereplacer', `varvalue` = '-'";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'websitethumb', `varvalue` = '0''";
	mysql_query($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'stripslashes', `varvalue` = '0'";
	doSQL($sql);
	doSQL("INSERT INTO `wspproperties` SET `varname` = 'backupsteps', `varvalue` = '3'");
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'autoscalepreselect', `varvalue` = '800x600'";
	doSQL($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'thumbsize', `varvalue` = '50'";
	doSQL($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'squarethumb', `varvalue` = '0'";
	doSQL($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'use_css', `varvalue` = 'wsp'";
	doSQL($sql);
	$sql = "INSERT INTO `wspproperties` SET `varname` = 'autologout', `varvalue` = '60'";
	doSQL($sql);
	
	$fh = fopen($GLOBALS['wspvars']['installdatabase'].'/media/xml/database.xml', 'r');
	$xmldata = '';
	if (intval($fh)>0):
		while (!feof($fh)):
			$xmldata .= fgets($fh, 4096);
		endwhile;
	endif;
	fclose($fh);
	
	$xml = xml_parser_create();
	xml_parse_into_struct($xml, $xmldata, $values, $index);
	
	$devtable = array();
	$dev_tablenametemp = '';
	
	foreach ($values as $tags):
		if ($tags['tag']=='TABLENAME'):
			$dev_tablename[]=$tags['value'];
			foreach ($values as $tags2):
				if ($tags2['tag']=='TABLENAME'):
					$dev_tablenametemp = $tags2['value'];
				endif;
				if ($dev_tablenametemp==$tags['value']):
					if ($tags2['tag']=='FIELD'):
						@$devtable[$tags['value']]['field'][] = $tags2['value'];
					endif;
					if ($tags2['tag']=='TYPE'):
						@$devtable[$tags['value']]['type'][]=$tags2['value'];
					endif;
					if ($tags2['tag']=='NULL'):
						@$devtable[$tags['value']]['null'][]=$tags2['value'];
					endif;
					if ($tags2['tag']=='KEY'):
						@$devtable[$tags['value']]['key'][]=$tags2['value'];
					endif;
					if ($tags2['tag']=='DEFAULT'):
						@$devtable[$tags['value']]['default'][]=$tags2['value'];
					endif;
					if ($tags2['tag']=='EXTRAS'):
						@$devtable[$tags['value']]['extras'][]=$tags2['value'];
					endif;
				endif;
			endforeach;
		endif;
	endforeach;
	
	$devid = 0;
	foreach ($dev_tablename as $table):
		$newversion=true;
		$updDBStruc = true;
		$temarray[$devid]['tableaction']="addnewtable";
		$temarray[$devid]['tablename']=$table;
		$devid++;
	endforeach;
	
	$temarray['tablecount'] = $devid;
	$datbasepfad = $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/updatedatabase.php";
	$datbasedat=fopen($datbasepfad,'w');
	fwrite($datbasedat, serialize($temarray));
	fclose($datbasedat);

	$installdbpath = $_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/".session_id()."/updatedatabase.php";
	$installdb = file($installdbpath);
	$selectChanges = unserialize(trim($installdb[0]));
	DatabaseUpdate($selectChanges);
	?>
	<script type="text/javascript" language="javascript">
	<!--
	updateStat('db', 100, '100%');
	//-->
	</script>
	<?php
	flush();flush();flush();
	ob_flush();ob_flush();ob_flush();	
	// get all required packages from install server		
	$fh = fopen($wspvars['installfiles'].'/versionspackage.php?key=install', 'r');
	$xmldata = '';
	if (intval($fh)>0):
		while (!feof($fh)):
			$xmldata .= fgets($fh, 4096);
		endwhile;
	endif;
	fclose($fh);
	$xml = xml_parser_create();
	xml_parse_into_struct($xml, $xmldata, $values, $index);
	$packages = array();
	foreach ($values as $file):
		if ($file['tag']=='PACKAGENAME'):
			$packages[] = $file['value'];
		endif;
	endforeach;
	// get all required media files from install server
	$fh = fopen($wspvars['installfiles'].'/versionsmedia.php?key=install', 'r');
	$xmldata = '';
	if (intval($fh)>0):
		while (!feof($fh)):
			$xmldata .= fgets($fh, 4096);
		endwhile;
	endif;
	fclose($fh);
	$xml = xml_parser_create();
	xml_parse_into_struct($xml, $xmldata, $values, $index);
	$media = array();
	foreach ($values as $file):
		if ($file['tag']=='PACKAGENAME'):
			$media[] = $file['value'];
		endif;
	endforeach;
	$alladdinstall = intval(count($packages))+intval(count($media));
	// install packages
	for($i=0; $i<count($packages); $i++):
		PackageUpdate($packages[$i]);
		?>
		<script type="text/javascript" language="javascript">
		<!--
		updateStat('package', <?php echo ceil($i/($alladdinstall/100)); ?>, '<?php echo ceil($i/($alladdinstall/100)); ?>%');
		//-->
		</script>
		<?php
		flush();flush();flush();
		ob_flush();ob_flush();ob_flush();
	endfor;
	// install media
	for($i=0; $i<count($media); $i++):
		MediaUpdate($media[$i]);
		?>
		<script type="text/javascript" language="javascript">
		<!--
		updateStat('package', <?php echo ceil(($i+count($packages))/($alladdinstall/100)); ?>, '<?php echo ceil(($i+count($packages))/($alladdinstall/100)); ?>%');
		//-->
		</script>
		<?php
		flush();flush();flush();
		ob_flush();ob_flush();ob_flush();
	endfor;
	?>
	<script type="text/javascript" language="javascript">
	<!--
	updateStat('package', 100, '100%');
	//-->
	</script>
	<?php
	// write db and ftp config files
	$ftp = @ftp_connect($_SESSION['ftphost']);
	$ftpcon = ftp_login($ftp, $_SESSION['ftpuser'], $_SESSION['ftppass']);
	if ($ftpcon):
		$fh = fopen($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/ftpaccess.inc.php", "w+");
		fwrite($fh, "<?php
/**
 * ftp-zugangsdaten
 * @author system
 * @version 2.0
 * @lastchange ".date("Y-m-d")."
 */
\$_SESSION['wspvars']['ftphost'] = '".$_SESSION['ftphost']."';
\$_SESSION['wspvars']['ftpbasedir'] = '".$_SESSION['ftpbasedir']."';
\$_SESSION['wspvars']['ftpuser'] = '".$_SESSION['ftpuser']."';
\$_SESSION['wspvars']['ftppass'] = '".$_SESSION['ftppass']."';

DEFINE('WSP_FTPHOST', '".$_SESSION['ftphost']."');
DEFINE('WSP_FTPBASEDIR', '".$_SESSION['ftpbasedir']."');
DEFINE('WSP_FTPUSER', '".$_SESSION['ftpuser']."');
DEFINE('WSP_FTPPASS', '".$_SESSION['ftppass']."');
?>");
		fclose($fh);
		// copy file to structure
		$ftpput = @ftp_put($ftp, $_SESSION['ftpbasedir'].'/'.$GLOBALS['tmpwspbasedir'].'/data/include/ftpaccess.inc.php', $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS['tmpwspbasedir'].'/tmp/ftpaccess.inc.php', FTP_BINARY);
		
		$fh = fopen($_SERVER['DOCUMENT_ROOT']."/".$GLOBALS['tmpwspbasedir']."/tmp/dbaccess.inc.php", "w+");
		$falsemail = $_SESSION['adminmail'];
						
		fwrite($fh, "<?php
/**
 * db-zugangsdaten
 * @author system
 * @since 3.1
 * @version 2.0
 * @lastchange ".date("Y-m-d")."
 */
\$_SESSION['wspvars']['dbcon'] = @mysql_connect('".$_SESSION['dbhost']."','".$_SESSION['dbuser']."','".$_SESSION['dbpass']."');
\$_SESSION['wspvars']['dbaccess'] = @mysql_select_db('".$_SESSION['dbname']."', \$_SESSION['wspvars']['dbcon']);

DEFINE('WSP_DBHOST', '".$_SESSION['dbhost']."');
DEFINE('WSP_DBNAME', '".$_SESSION['dbname']."');
DEFINE('WSP_DBUSER', '".$_SESSION['dbuser']."');
DEFINE('WSP_DBPASS', '".$_SESSION['dbpass']."');
?>");
		fclose($fh);
		// copy file to structure
		$ftpput = @ftp_put($ftp, $_SESSION['ftpbasedir'].'/'.$GLOBALS['tmpwspbasedir'].'/data/include/dbaccess.inc.php', $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS['tmpwspbasedir'].'/tmp/dbaccess.inc.php', FTP_BINARY);
		$ftpput = @ftp_put($ftp, $_SESSION['ftpbasedir'].'/data/include/dbaccess.inc.php', $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBALS['tmpwspbasedir'].'/tmp/dbaccess.inc.php', FTP_BINARY);
	endif;
	@ftp_close($ftpcon);
	
	?>
	<script type="text/javascript" language="javascript">
	<!--
	document.getElementById('nextstep').style.display = 'block';
	//-->
	</script>
<?php endif; ?>

</body>
</html>