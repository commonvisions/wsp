<?php
/**
* @author stefan@covi.de
* @since 6.9
* @version GIT
*/

if (isset($_REQUEST['set_privacy'])) {
    setcookie('cookieAccepted', '0', 0, '/');
    $_SESSION['wsppage']['allowblock'] = [];
    header('location: ./');
}

// standard behaviour is blocking fonts & analytics 
$_SESSION['wsppage']['allowstats'] = $_SESSION['wsppage']['allowstats'] ?? false;
$_SESSION['wsppage']['allowfonts'] = $_SESSION['wsppage']['allowfonts'] ?? false;

// add allow stats
if (intval($_POST['allowstats'] ?? 0)==1) { $_SESSION['wsppage']['allowstats'] = true; }
// add allow fonts
if (intval($_POST['allowfonts'] ?? 0)==1) { $_SESSION['wsppage']['allowfonts'] = true; }

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