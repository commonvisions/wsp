<?php
/**
 * TINYMCE medialist.json for "link"-plugin
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-22
 */

session_start();

if (isset($_SESSION['wspvars']['wspbasedir'])):
    include_once str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/globalvars.inc.php'));
    include_once str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/funcs.inc.php'));
    include_once str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/dbaccess.inc.php'));
    include_once str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/checkuser.inc.php'));
    include_once str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/errorhandler.inc.php'));
    include_once str_replace("//", "/", str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'].'/'.$_SESSION['wspvars']['wspbasediradd'].'/'.$_SESSION['wspvars']['wspbasedir'].'/data/include/siteinfo.inc.php'));

    if (!(function_exists('getMediaDownloadTiny'))):
    function getMediaDownloadTiny($path = '/', $selected='', $toppath = '', $trimname = 40) {
        //
        // array $selected abfangen 
        //
        $selecteda = '';
        if (!(is_array($selected))):
            $selecteda = array($selected);
        endif;
        $mediafiles = '';
        $files = array();
        $dir = array();
        $hdlfa = array('thumbs','preview','download/thumbs','download/preview','download/originals','images/thumbs','images/preview','flash','screen','layout','rss','fonts');
        $hdlf = @doResultSQL("SELECT `varvalue` FROM `wspproperties` WHERE `varname` = 'hiddendownload'");
        if (trim($hdlf)!=''): $hdlfa = array_merge($hdlfa, explode(",", $hdlf)); endif;
        foreach ($hdlfa AS $k => $v): 
            $hdlfa[$k] = str_replace("//", "/", str_replace("//", "/", str_replace("//", "/", "/".trim($v)."/")));
        endforeach;
        $hdlfa = array_unique($hdlfa);
        if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media".$path) && !(in_array(trim($path), $hdlfa))):
            $d = dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media".$path);
            while (false !== ($entry = $d->read())):
                if (substr($entry, 0, 1)!='.'):
                    if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry)):
                        $files[] = $path.$entry;
                    elseif (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry) && !(in_array(trim($entry), $hdlfa))):
                        $dir[] = $path.$entry;
                    endif;
                endif;
            endwhile;
            $d->close();
            sort($files);
            sort($dir);
            foreach($files AS $value):
                $mediafiles .= "{title: '  "; //'
                $mediadesc = '';
                $desc_sql = "SELECT * FROM `mediadesc` WHERE `mediafile` LIKE '%".escapeSQL(str_replace("//", "/", str_replace("//", "/", $value)))."%'";
                $desc_res = doSQL($desc_sql);
                if ($desc_res['num']>0):
                    $mediadesc = trim($desc_res['set'][0]["filedesc"]);
                endif;
                if (trim($toppath)!="" && $toppath!="/"):
                    $value = str_replace($toppath, "", $value);
                endif;
                if (trim($mediadesc)!=""):
                    $mediafiles .= $mediadesc;
                else:
                    if (trim($path)!="" && $path!="/"):
                        $mediatmp = str_replace($path, ".../", $value);
                    endif;
                    if (strlen($mediatmp)>($trimname+3)):
                        $mediafiles .= substr($mediatmp,0,10)."...".substr($mediatmp,-($trimname-10));
                    else:
                        $mediafiles .= $mediatmp;
                    endif;
                endif;
                $mediafiles .= "', value: '".str_replace("//", "/", str_replace("//", "/", "/media/".$value))."'},\n"; //';
            endforeach;
            foreach($dir AS $value):
                $tmpfiles = getMediaDownloadTiny($value.'/', $selecteda, $toppath, $trimname);
                if (trim($tmpfiles)!=''):
                    $mediafiles .= "{title: 'Ordner - ".substr($value,1)."', value: 'Ordner - " . $value . "'},\n"; //'
                    $mediafiles .= $tmpfiles;
                endif;
            endforeach;
        endif;
        return $mediafiles;
        }	// getMediaDownloadTiny()
    endif;

    echo "[\n";
    echo getMediaDownloadTiny('/', '', '', $_SESSION['wspvars']['stripfilenames']);
    echo "]";
else:
    echo "[ERROR]";
endif;

// EOF ?>