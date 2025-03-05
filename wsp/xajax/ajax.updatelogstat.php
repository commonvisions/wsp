<?php
/**
 * @author stefan@covi.de
 * @since 6.0
 * @version GIT
 */
if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=''):
session_start();
require("../data/include/globalvars.wsp7.php");
require("../data/include/errorhandler.wsp7.php");
require("../data/include/siteinfo.wsp7.php");

$showuser_sql = "SELECT s.`sid`, s.`timevar`, s.`logintime`, s.`userid`, s.`position`, r.`realname` FROM `security` s, `restrictions` r WHERE s.`userid` = r.`rid` AND s.`userid` != '".$_SESSION['wspvars']['userid']."' GROUP BY s.`userid` ORDER BY s.`timevar` DESC";
$showuser_res = doSQL($showuser_sql);

$userpositions = array(
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/cmindex.php")) => "Startseite CMS",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/index.php")) => "Startseite CMS",
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/usermanagement.php")) => "Benutzerverwaltung",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/useredit.php")) => "Benutzer bearbeiten",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/usernotice.php")) => ": ".returnIntLang('menu user messages', false),
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/usershow.php")) => returnIntLang('menu user login', false),
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/semanagement.php")) => "Suchmaschineneinstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/siteprefs.php")) => "Allgemeine Seiteneinstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/headerprefs.php")) => "Einstellung Weiterleitungen",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/googletools.php")) => "Google Tools Einstellungen",
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/screenmanagement.php")) => "Verwaltung Bilder Screendesign",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/designedit.php")) => "Verwaltung CSS",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/scriptedit.php")) => "Verwaltung JavaScript",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/menutemplate.php;id=0")) => "Verwaltung Men&uuml;templates",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/selfvarsedit.php")) => "Verwaltung CMS Variablen",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/templatesedit.php;id=0")) => "Verwaltung Templates",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/mediafile.php")) => "Bilddetails/Dateidetails",
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/menuedit.php")) => "Seitenstruktur",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/contentstructure.php")) => "Inhalte &Uuml;bersicht",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/languagetools.php")) => "Sprachlokalisierung",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/globalcontent.php")) => "Globale Inhalte &Uuml;bersicht",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/globalcontentedit.php")) => "Globale Inhalte bearbeiten",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/contentmove.php")) => "Inhalte verschieben &amp; kopieren",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/menueditdetails.php;mid=")) => "Seiteneinstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/trash.php")) => "Papierkorb",
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/rssedit.php")) => "RSS Editor",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/rssfeed.php")) => "RSS Feed erstellen",
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/imagemanagement.php")) => "Dateisystem Bilder",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/documentmanagement.php")) => "Dateisystem Dokumente",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/swfmanagement.php")) => "Dateisystem Flash & Video",
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/modinterpreter.php;mod=")) => "Modul",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/editorprefs.php")) => "Editor Einstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/cleanup.php")) => "Dateisystem bereinigen",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/editcon.php")) => "Verbindungseinstellungen bearbeiten",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/modules.php")) => "Modulverwaltung",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/system.php")) => "Systemverwaltung",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/setupbuilder.php")) => "Setup Builder",
	
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/preview.php")) => "Vorschau",
	str_replace("//", "/", str_replace("//", "/", "/".WSP_DIR."/publisher.php")) => "Publisher"
	);

for ($sres=0; $sres<$showuser_res['num']; $sres++):
	// output users real name
    echo "<tbody>";
    echo "<tr>";
    echo "<td>";
    if (mysql_result($showuser_res,$sres,"sid")!=$_SESSION['wspvars']['actusersid']):
		echo "<input name=\"setfree[]\" id=\"setfree_".intval($showuser_res['set'][$sres]['sid'])."\" type=\"checkbox\" value=\"".intval($showuser_res['set'][$sres]['userid'])."\" />&nbsp;&nbsp;";
        echo "<label for=\"setfree_".intval($showuser_res['set'][$sres]['sid'])."\">";
	endif;
	echo mysql_result($showuser_res,$sres,"realname");
    if (intval($showuser_res['set'][$sres]['sid'])!=intval($_SESSION['wspvars']['actusersid'])):
        echo "</label>";
    endif;
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>".date("Y-m-d H:i:s", mysql_result($showuser_res,$sres,"timevar"))."</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    if (array_key_exists(mysql_result($showuser_res,$sres,"position"), $userpositions)):
		echo $userpositions[(mysql_result($showuser_res,$sres,"position"))];
	elseif(substr(str_replace("/".WSP_DIR."/","/wsp/",mysql_result($showuser_res,$sres,"position")),5,15)=="contentedit.php"):
		echo "Inhalte bearbeiten";
		$cidpos = explode(";cid=", mysql_result($showuser_res,$sres,"position"));
		
		$cid_sql = "SELECT m.`description` FROM `content` AS `c`, `menu` AS `m` WHERE c.`cid` = ".$cidpos[1]." AND c.`mid` = m.`mid`";
		$cid_res = mysql_query($cid_sql);
		$cid_num = mysql_num_rows($cid_res);
		
		if ($cid_num>0):
			echo "<em>".mysql_result($cid_res,0)."</em>";
		endif;
	elseif(substr(str_replace("/".WSP_DIR."/","/wsp/",mysql_result($showuser_res,$sres,"position")),5,19)=="menueditdetails.php"):
		echo "Seiteneinstellungen bearbeiten";
		$cidpos = explode(";mid=", mysql_result($showuser_res,$sres,"position"));
		
		$cid_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".$cidpos[1];
		$cid_res = mysql_query($cid_sql);
		$cid_num = mysql_num_rows($cid_res);
		
		if ($cid_num>0):
			echo ": <em>".mysql_result($cid_res,0)."</em>";
		endif;
	else:
		echo mysql_result($showuser_res,$sres,"position");
	endif;
    echo "</td>";
    echo "</tr>";
    echo "</tbody>";
endfor;

endif; ?>