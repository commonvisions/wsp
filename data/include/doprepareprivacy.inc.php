<?php
/**
* @author s.haendler@covi.de
* @copyright (c) 2022, Common Visions Media.Agentur (COVI)
* @since 6.9
* @version 6.11.1
* @lastchange 2022-11-10
*/

if (isset($_REQUEST['set_privacy'])) {
    setcookie('cookieAccepted', '0', 0, '/');
    $_SESSION['wsppage']['allowblock'] = [];
    header('location: ./');
}

// standard behaviour is blocking analytics
$_SESSION['wsppage']['allowstats'] = isset($_SESSION['wsppage']['allowstats']) ? $_SESSION['wsppage']['allowstats'] : false;
$_SESSION['wsppage']['allowfonts'] = isset($_SESSION['wsppage']['allowfonts']) ? $_SESSION['wsppage']['allowfonts'] : false;

// add allow stats
if (isset($_POST['allowstats']) && intval($_POST['allowstats'])==1) { $_SESSION['wsppage']['allowstats'] = true; }
// add allow fonts
if (isset($_POST['allowfonts']) && intval($_POST['allowfonts'])==1) { $_SESSION['wsppage']['allowfonts'] = true; }

// add the posted blocks to session based allowblock param
if (isset($_POST['allow_block']) && !empty($_POST['allow_block'])) {
    foreach ($_POST['allow_block'] AS $abKey => $abValue) {
        $multiAbValue = explode(",", $abValue);
        foreach ($multiAbValue AS $multiValue) {
            $_SESSION['wsppage']['allowblock'][] = trim($multiValue);
        }
    }
						
}

// EOF