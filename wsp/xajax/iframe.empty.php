<?php
/**
 * empty iframe
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-03-07
 */

if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
    echo "<!-- this is an empty iframe called by document to get some content the DOM returns ready. -->";
} else {
    die('error calling');
}

// EOF