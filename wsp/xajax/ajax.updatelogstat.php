<?php
/**
 * udpatelogstat for usershow
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-22
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
?>

<li class="tablecell head two"><?php echo returnIntLang('loginstat user'); ?></li>
<li class="tablecell head two"><?php echo returnIntLang('loginstat last'); ?></li>
<li class="tablecell head four"><?php echo returnIntLang('loginstat pos'); ?></li>
<?php
$showuser_sql = "SELECT s.`sid`, s.`timevar`, s.`logintime`, s.`position`, r.`realname` FROM `security` s, `restrictions` r WHERE s.`userid` = r.`rid` ORDER BY s.`timevar`";
$showuser_res = doSQL($showuser_sql);

$userpositions = array(
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/cmindex.php")) => "Startseite CMS",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/index.php")) => "Startseite CMS",
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/usermanagement.php")) => "Benutzerverwaltung",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/useredit.php")) => "Benutzer bearbeiten",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/usernotice.php")) => ": ".returnIntLang('menu user messages', false),
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/usershow.php")) => returnIntLang('menu user login', false),
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/semanagement.php")) => "Suchmaschineneinstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/siteprefs.php")) => "Allgemeine Seiteneinstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/headerprefs.php")) => "Einstellung Weiterleitungen",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/googletools.php")) => "Google Tools Einstellungen",
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/screenmanagement.php")) => "Verwaltung Bilder Screendesign",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/designedit.php")) => "Verwaltung CSS",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/scriptedit.php")) => "Verwaltung JavaScript",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/menutemplate.php;id=0")) => "Verwaltung Men&uuml;templates",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/selfvarsedit.php")) => "Verwaltung CMS Variablen",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/templatesedit.php;id=0")) => "Verwaltung Templates",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/mediafile.php")) => "Bilddetails/Dateidetails",
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/menuedit.php")) => "Seitenstruktur",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/contentstructure.php")) => "Inhalte &Uuml;bersicht",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/languagetools.php")) => "Sprachlokalisierung",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/globalcontent.php")) => "Globale Inhalte &Uuml;bersicht",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/globalcontentedit.php")) => "Globale Inhalte bearbeiten",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/contentmove.php")) => "Inhalte verschieben &amp; kopieren",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/menueditdetails.php;mid=")) => "Seiteneinstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/trash.php")) => "Papierkorb",
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/rssedit.php")) => "RSS Editor",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/rssfeed.php")) => "RSS Feed erstellen",
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/imagemanagement.php")) => "Dateisystem Bilder",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/documentmanagement.php")) => "Dateisystem Dokumente",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/swfmanagement.php")) => "Dateisystem Flash & Video",
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/modinterpreter.php;mod=")) => "Modul",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/editorprefs.php")) => "Editor Einstellungen",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/cleanup.php")) => "Dateisystem bereinigen",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/editcon.php")) => "Verbindungseinstellungen bearbeiten",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/modules.php")) => "Modulverwaltung",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/system.php")) => "Systemverwaltung",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/setupbuilder.php")) => "Setup Builder",
	
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/preview.php")) => "Vorschau",
	str_replace("//", "/", str_replace("//", "/", "/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/publisher.php")) => "Publisher"
	);

foreach ($showuser_res['set'] AS $surk => $surv) {
	// output users real name
	echo "<li class=\"tablecell two\">";
	if (intval($surv["sid"])!=intval($_SESSION['wspvars']['actusersid'])):
		echo "<input name=\"setfree[]\" id=\"setfree_".intval($surv["sid"])."\" type=\"checkbox\" value=\"".intval($surv["sid"])."\" />&nbsp;<label for=\"setfree_".intval($surv["sid"])."\">";
	endif;
	echo trim($surv["realname"])."</label></li>";
	// output last action date time
	echo "<li class=\"tablecell two\">".date("Y-m-d H:i:s", intval($surv["timevar"]))."</li>";
	echo "<li class=\"tablecell four\">";
	if (array_key_exists($surv["position"], $userpositions)):
		echo $userpositions[$surv["position"]];
	elseif(substr(str_replace("/".$_SESSION['wspvars']['wspbasedir']."/","/wsp/",trim($surv["position"])),5,15)=="contentedit.php"):
		echo "Inhalte bearbeiten";
		$cidpos = explode(";cid=", trim($surv["position"]));
		$cid_sql = "SELECT m.`description` FROM `content` AS `c`, `menu` AS `m` WHERE c.`cid` = ".intval($cidpos[1])." AND c.`mid` = m.`mid`";
		$cid_res = doResultSQL($cid_sql);
		if ($cid_res!==false):
			echo "<em>".trim($cid_res)."</em>";
		endif;
	elseif(substr(str_replace("/".$_SESSION['wspvars']['wspbasedir']."/","/wsp/",trim($surv["position"])),5,19)=="menueditdetails.php"):
		echo "Seiteneinstellungen bearbeiten";
		$cidpos = explode(";mid=", trim($surv["position"]));
		$cid_sql = "SELECT `description` FROM `menu` WHERE `mid` = ".intval($cidpos[1]);
		$cid_res = doResultSQL($cid_sql);
		if ($cid_res!==false):
			echo "<em>".trim($cid_res)."</em>";
		endif;
	else:
		echo trim($surv["position"]);
	endif;
	echo "</li>";
}
endif;
// EOF ?>