<?php
/**
 * ajax publisherinfo
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2021-06-02
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    session_start();
    if (isset($_SESSION) && isset($_SESSION['wspvars']) && isset($_SESSION['wspvars']['lockscreen']) && $_SESSION['wspvars']['lockscreen']===false) {
        // if ANYTHING is in queue, we have to lower the time limit
        echo '600000' ;
    } else {
        echo '-1';
    }
} else {
    echo '-1';
}

// EOF