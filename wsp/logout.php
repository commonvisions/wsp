<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 */

session_start();
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
if (isset($_SESSION['wspvars']['wspbasedir']) && isset($_SESSION['wspvars']['usevar']) && trim($_SESSION['wspvars']['usevar'])!=''):
	if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
	require ("./data/include/dbaccess.inc.php");
	require ("./data/include/funcs.inc.php");
	require ("./data/include/filesystemfuncs.inc.php");
	// make logout from database
	$sql = "DELETE FROM `security` WHERE `usevar` = '".escapeSQL($_SESSION['wspvars']['usevar'])."'";
	doSQL($sql);
    require ("./data/include/wsplang.inc.php");
	// try to remove temporary data
	CleanupFolder(str_replace("//","/",str_replace("//","/",$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/")));
	// remove user from login-table
endif;
// destroy session and redirect
session_regenerate_id(FALSE);
session_destroy();
header("location: ./index.php?logout");
// EOF ?>