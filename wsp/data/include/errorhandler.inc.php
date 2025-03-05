<?php
/**
 * deprecated since WSP 7.0
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-08
 */

if (defined('WSP_DEV') && WSP_DEV) {
    error_reporting(E_ALL);
    ini_set('display_errors' , true);
    if (isset($_POST) && count($_POST)>0) {
        addWSPMsg('noticemsg', "<div style='display: block; font-family: monospace; white-space: pre; padding: 9.5px; margin: 0 0 10px 30px; font-size: 10px; line-height: 1.1; color: #333; word-break: break-all; word-wrap: break-word; border: 1px solid rgba(0,0,0,0.15); border-radius: 4px;'>POST: ".var_export($_POST, true)."</div>", false);
    }
    if (isset($_GET) && count($_GET)>0) {
        addWSPMsg('noticemsg', "<div style='display: block; font-family: monospace; white-space: pre; padding: 9.5px; margin: 0 0 10px 30px; font-size: 10px; line-height: 1.1; color: #333; word-break: break-all; word-wrap: break-word; border: 1px solid rgba(0,0,0,0.15); border-radius: 4px;'>GET: ".var_export($_GET, true)."</div>", false);
    }
}
