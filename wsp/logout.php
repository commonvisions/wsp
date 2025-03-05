<?php
/**
 * logout
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2021-09-16
 */

session_start();
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
if (isset($_SESSION['wspvars']['usevar']) && trim($_SESSION['wspvars']['usevar'])!=''):
	doSQL("DELETE FROM `security` WHERE `usevar` = '".escapeSQL($_SESSION['wspvars']['usevar'])."'");
endif;
// redirect to login page (where everything else goes on)
header("location: ./login.php?logout");
