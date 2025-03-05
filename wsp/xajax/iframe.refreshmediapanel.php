<?php
/**
 * ...
 * @author stefan@covi.de
 * @since 6.0
 * @version 7.0
 * @lastchange 2020-03-13
 */
// if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!='') {
session_start();

require ("../data/include/globalvars.inc.php");
require ("../data/include/checkuser.inc.php");
require ("../data/include/errorhandler.inc.php");
require ("../data/include/siteinfo.inc.php");
require ("../data/include/headertiny.inc.php");

// type = array, xml, json, txt
// lang = true (all languages), iso-shortcut
// order = structure, lastchange, priority
function getSitemap($type = 'array', $lang = true, $order = 'structure') {
    $structure = returnIDRoot(0);
    $parsedir = getWSPProperties('parsedirectories');
    $sitemapdata = array();
    // max priority is high changefreq PLUS less level PLUS is index file » 
    // max calc value depends on max levels » e.g. with 4 levels in structure max calc value = 6 (1 (high change freq)+4 (levels)+1 (index page))
    $maxlevel = intval(doResultSQL("SELECT MAX(`level`) FROM `menu` WHERE trash = 0"));
    $maxprio = $maxlevel+2;
    if (count($structure)>0) {
        $r = 0; foreach ($structure AS $sk => $sv) {
            $r++;
            $mpointdata = doSQL("SELECT `level`, `editable`, `visibility`, `description`, `offlink`, `forwarding_id`, `contentchanged`, `structurechanged`, `lastchange`, `isindex` FROM `menu` WHERE `mid` = ".intval($sv));
            $filename = fileNamePath($sv, 0, 0);
            $description = $mpointdata['set'][0]['description'];
            $queued = intval(getNumSQL("SELECT `id` FROM `wspqueue`")); if ($queued<1) { $queued = 1; }
            $changes = intval(getNumSQL("SELECT `id` FROM `wspqueue` WHERE 
                (`action` = 'publishitem' OR `action` = 'publishstructure' OR `action` = 'publishcontent') AND 
                `param` = ".intval($sv)));
            $changeval = ($changes>0) ? $changes/$queued : 1/$queued;
            $priority = round(($maxlevel/intval($mpointdata['set'][0]['level']) + intval($mpointdata['set'][0]['isindex']) + $changeval)/$maxprio,2);
            $changefreq = intval(round($changeval*10,1));
            if ($parsedir==1) { $filename = cleanPath($filename."/index.php"); }
            $sitemapdata[intval($sv)] = array(
                'level' => intval($mpointdata['set'][0]['level']),
                'desc' => $description,
                'lastchange' => intval($mpointdata['set'][0]['lastchange']),
                'filename' => $filename,
                'changefreq' => $changefreq,
                'priority' => $priority,
            );
        }   
    }
    if ($type=='array') {
        return ($sitemapdata);
    } 
}

echo "<div style='position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; text-align: center;'><i class='fa fa-circle-o-notch fa-spin fa-3x fa-fw' style='position: absolute; top: 50%; margin-top: -1em; font-size: 2em;'></i></div>";
flush();
flush();
flush();

// get all active pages and active structure
$pagelist = getSitemap();
// foreach pagelist-item get sourcecode and check for use mediafiles 
$rex = '/((("|\')\/media\/)(.*)(\.)(\w{3,4})("|\'))/m';
$usedmedia = array();
foreach ($pagelist AS $plk => $plv) {
    $source = $_SERVER['REQUEST_SCHEME']."://".cleanPath($_SERVER['HTTP_HOST']."/".$plv['filename']);
    $local = cleanPath(DOCUMENT_ROOT."/".$plv['filename']);
    if (file_exists($local)) {
        $filesource = file_get_contents($source);
        preg_match_all($rex, $filesource, $matches, PREG_SET_ORDER, 0);
        if (isset($matches) && is_array($matches)) {
            foreach ($matches AS $mk => $mv) {
                $usedmedia[] = str_replace("'", "", str_replace("\"", "", $mv[0]));
            }
        }
    }
}
$db_sql = 'SELECT `valuefields` FROM `content` WHERE `trash` = 0';
$db_res = doSQL($db_sql);
foreach ($db_res['set'] AS $dbk => $dbv) {
    if (trim($dbv['valuefields'])!='') {
        preg_match_all($rex, $dbv['valuefields'], $matches, PREG_SET_ORDER, 0);
        if (isset($matches) && is_array($matches)) {
            foreach ($matches AS $mk => $mv) {
                $usedmedia[] = str_replace("'", "", str_replace("\"", "", $mv[0]));
            }
        }
    }
}
$db_sql = 'SELECT `valuefields` FROM `content_global` WHERE `trash` = 0';
$db_res = doSQL($db_sql);
foreach ($db_res['set'] AS $dbk => $dbv) {
    if (trim($dbv['valuefields'])!='') {
        preg_match_all($rex, $dbv['valuefields'], $matches, PREG_SET_ORDER, 0);
        if (isset($matches) && is_array($matches)) {
            foreach ($matches AS $mk => $mv) {
                $usedmedia[] = str_replace("'", "", str_replace("\"", "", $mv[0]));
            }
        }
    }
}
$usedmedia = array_values(array_unique($usedmedia));

// 1. run already in database stored media
// run mediadb and cleanup non-existing files as also doubles
$media_sql = "SELECT `mid`, `filepath`, `filename` FROM `wspmedia` GROUP BY `filepath` ORDER BY `mid` DESC";
$media_res = doSQL($media_sql);
foreach ($media_res['set'] AS $mrk => $mrv) {
    $del = 0;
    if (!(is_file(cleanPath(DOCUMENT_ROOT."/".$mrv['filepath'])))) {
        // clean non-existing files
        $del+= getAffSQL("DELETE FROM `wspmedia` WHERE `filepath` = '".escapeSQL($mrv['filepath'])."'");
    } 
    else {
        // clean doubles
        $del+= getAffSQL("DELETE FROM `wspmedia` WHERE `filepath` = '".escapeSQL($mrv['filepath'])."' AND `mid` != ".intval($mrv['mid']));
    }
    if (trim($mrv['filename'])=='') {
        doSQL("UPDATE `wspmedia` SET `filename` = '".escapeSQL(basename($mrv['filepath']))."' WHERE `filepath` = '".escapeSQL($mrv['filepath'])."' AND `mid` = ".intval($mrv['mid']));
    }
    // cleanup mediadesc-table - 2020-03-12
    // this is preparing a later version where mediadesc 
    // should not be used anymore and can be removed
    $mediadesc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` = '".escapeSQL($mrv['filepath'])."' LIMIT 0,1";
    $mediadesc_res = doSQL($mediadesc_sql);
    if ($mediadesc_res['num']==1) {
        doSQL("UPDATE `wspmedia` SET `filedesc` = '".escapeSQL(trim($mediadesc_res['set'][0]['filedesc']))."', `filekeys` = '".escapeSQL(trim($mediadesc_res['set'][0]['filekeys']))."' WHERE `filepath` = '".escapeSQL($mrv['filepath'])."' AND `mid` = ".intval($mrv['mid']));
        doSQL("DELETE FROM `mediadesc` WHERE `id` = ".intval($mediadesc_res['set'][0]['id']));
    }
}
// run mediadb with usedmedia and setup these mediafiles as embed
foreach ($usedmedia AS $umk => $umv) {
    doSQL("UPDATE `wspmedia` SET `embed` = 1 WHERE `filepath` = '".escapeSQL($umv)."'");
}

// 2. run files from system
// get ALL media files
$dirlist = simpledirlist("/media/", true);
$ins = 0;
foreach ($dirlist AS $dlk => $dlv) {
    // get all files from directory if directory is not a preview or thumb directory
    $checks = array(
        '/media\/[a-z]*\/preview/m',
        '/media\/[a-z]*\/thumbs/m',
        '/media\/[a-z]*\/originals/m',
        '/media\/layout/m',
        '/media\/flash/m',
        '/media\/preview/m',
        '/media\/originals/m',
        '/media\/thumbs/m',
        '/media\/rss/m'
    );
    $df = 0;
    foreach ($checks AS $ck => $cv) {
        preg_match_all($cv, $dlv, $match, PREG_SET_ORDER, 0);
        $df+= count($match);
    }
    // only if folder is not in forbidden list from $checks
    if ($df==0) {
        $filelist = scanfiles($dlv);
        foreach($filelist AS $flk => $flv) {
            // get information about that file in db
            $media_sql = "SELECT `mid` FROM `wspmedia` WHERE `filepath` = '".escapeSQL(cleanPath(DIRECTORY_SEPARATOR.$dlv.DIRECTORY_SEPARATOR.$flv))."'";
            $media_res = doSQL($media_sql);
            if ($media_res['num']==0) {
                // if file does not exist in database
                // get some more information about that file to store it in table
                $stat = @stat(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$dlv.DIRECTORY_SEPARATOR.$flv);
                $type = @mime_content_type(DOCUMENT_ROOT.DIRECTORY_SEPARATOR.$dlv.DIRECTORY_SEPARATOR.$flv);
                // exploding the folder string
                $mfdr = explode(DIRECTORY_SEPARATOR, $dlv);
                $base = array($mfdr[2],cleanPath(DIRECTORY_SEPARATOR.$dlv.DIRECTORY_SEPARATOR.$flv));
                // prepare SQL statement
                $file_sql = "INSERT INTO `wspmedia` SET `filepath` = '".escapeSQL(cleanPath(DIRECTORY_SEPARATOR.$dlv.DIRECTORY_SEPARATOR.$flv))."', `filename` = '".escapeSQL(basename(DIRECTORY_SEPARATOR.$dlv.DIRECTORY_SEPARATOR.$flv))."', `filetype` = '".escapeSQL($type)."', `filekey` = '".escapeSQL(base64_encode(serialize($base)))."', `filesize` = '".(isset($stat['size'])?intval($stat['size']):0)."', `filedate` = '".(isset($stat['mtime'])?intval($stat['mtime']):0)."', `lastchange` = ".time();
                $file_res = doSQL($file_sql);
                if ($file_res['inf']>0) {
                    $ins++; 
                }
            }
        }
    }
}

//  } else {
//      echo "<pre>No direct access allowed</pre>";
//  }

echo "<p>".returnIntLang('refreshmediapanel removed files1', true)." ".$del." ".returnIntLang('refreshmediapanel removed files2', true)."</p>";
echo "<p>".returnIntLang('refreshmediapanel inserted files1', true)." ".$ins." ".returnIntLang('refreshmediapanel inserted files2', true)."</p>";
flush();
flush();
flush();
echo "<script>alert('done');</script>";

echo "</body></html>";
// EOF