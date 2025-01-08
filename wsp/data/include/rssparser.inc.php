<?php
/**
 * rss parser-functions
 * @author s.haendler@covi.de
 * @copyright (c) 2019, Common Visions Media.Agentur (COVI)
 * @since 6.0
 * @version 6.8
 * @lastchange 2019-01-25
 */

if (!(function_exists('publishRSS'))):
function publishRSS($rssid, $ftp = false, $preview = false) {
	$returnstat = false;
	if ($ftp!==false || $preview):
		$rssdata_sql = "SELECT * FROM `rssdata` WHERE `rid` = ".intval($rssid);
		$rssdata_res = doSQL($rssdata_sql);
		if ($rssdata_res['num']>0 && trim($rssdata_res['set'][0]['rssfilename'])!=''):
			$rssentry_sql = "SELECT * FROM `rssentries` WHERE `rid` = ".intval($rssid)." ORDER BY `eupdate` DESC";
			$rssentry_res = doSQL($rssentry_sql);
			
			$tmprssfile = '<'.'?'.'xml version="1.0" encoding="UTF-8" '.'?'.'>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n\t".'<channel>'."\n";
			$tmprssfile.= "\t\t<title>".$rssdata_res['set'][0]['rsstitle']."</title>\n";
			$tmprssfile.= "\t\t<link>https://".$rssdata_res['set'][0]['rsshref']."</link>\n";
			if ($rssdata_res['set'][0]['rsssubtitle']!=$rssdata_res['set'][0]['rsstitle'] && $rssdata_res['set'][0]['rsssubtitle']!=""):
				$tmprssfile.= "\t\t<description>".trim($rssdata_res['set'][0]['rsssubtitle'])."</description>\n";
			else:
				$tmprssfile.= "\t\t<description>".$rssdata_res['set'][0]['rsshref']."</description>\n";
			endif;
			$tmprssfile.= "\t\t<copyright>".$rssdata_res['set'][0]['rssauthor']."</copyright>\n";
			$tmprssfile.= "\t\t<language>de</language>\n";
			$tmprssfile.= "\t\t<pubDate>".date("r")."</pubDate>\n";
			$tmprssfile.= "\t\t<docs>http://blogs.law.harvard.edu/tech/rss</docs>\n";
			$tmprssfile.= "\t\t<atom:link href=\"https://".$rssdata_res['set'][0]['rsshref']."/media/rss/".trim($rssdata_res['set'][0]['rssfilename']).".rss\" rel=\"self\" type=\"application/rss+xml\" />\n";
			
            foreach($rssentry_res['set'] AS $rsek => $rsev):

                $tmprssfile.= "\t\t<item>\n";
				$tmprssfile.= "\t\t\t<title>".trim($rsev['etitle'])."</title>\n";
				$tmprssfile.= "\t\t\t<description>".trim(setUTF8(html_entity_decode(strip_tags(trim($rsev['esummary'])))))."</description>\n";
				if ($rsev['econtype']=="url"):
					$tmprssfile.= "\t\t\t<link>".$rsev['econ']."</link>\n";
				elseif ($rsev['econtype']=="mid"):
					// verknuepfung mit dem cm-menue und bildung der
					// verweis-url aus der menue-tabelle
					$link = $rssdata_res['set'][0]['rsshref'].str_replace("//","/",returnInterpreterPath(intval($rsev['econ']), '/', 2));
					$tmprssfile.= "\t\t\t<link>https://".$link."</link>\n";
				endif;
				$tmprssfile.= "\t\t\t<author>".$rsev['eauthor']."</author>\n";
				if ($rsev['econtype']=="url"):
					$tmprssfile.= "\t\t\t<guid>https://".$rsev['econ']."/?rssid=".intval($rsev['eid'])."</guid>\n";
				elseif ($rsev['econtype']=="mid"):
					// verknuepfung mit dem cm-menue und bildung der
					// verweis-url aus der menue-tabelle
					$link = $rssdata_res['set'][0]['rsshref'].str_replace("//","/",returnInterpreterPath(intval($rsev['econ']), '/', 2));
					$tmprssfile.= "\t\t\t<guid>https://".$link."?rssid=".intval($rsev['eid'])."</guid>\n";
				endif;
				$entrydate = intval($rsev['eupdate']);
                if ($entrydate>time()): $entrydate = time(); endif;
				$tmprssfile.= "\t\t\t<pubDate>".date("r", $entrydate)."</pubDate>\n";
				$tmprssfile.= "\t\t</item>\n";
			
            endforeach;
				
			$tmprssfile.= "\t</channel>\n";
			$tmprssfile.= "</rss>";
			
            if (!($preview)):
                $tmppath = str_replace("//", "/", $_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/".$_SESSION['wspvars']['wspbasedir']."/tmp/".$_SESSION['wspvars']['usevar']."/");
                $tmpfile = tempnam($tmppath, '');

                if (!(is_dir($_SERVER['DOCUMENT_ROOT']."/media/rss"))):
                    ftp_mkdir($ftp, str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/media/rss/"));
                endif;

                $fh = fopen($tmpfile, "w+");
                fwrite ($fh, $tmprssfile);
                fclose ($fh);

                if (!ftp_put($ftp, str_replace("//", "/", $_SESSION['wspvars']['ftpbasedir']."/media/rss/".trim($rssdata_res['set'][0]['rssfilename']).'.rss'), $tmpfile, FTP_BINARY)):
                    $_SESSION['errormsg'] .= "<p>Kann erzeugte Datei nicht hochladen. (Put)</p>";
                else:
                    $rsspublish = true;
                    $returnstat = true;
                endif;
                unlink($tmpfile);

                if ($rsspublish===true):
                    foreach($rssentry_res['set'] AS $rsek => $rsev):
                        doSQL("UPDATE `rssentries` SET `epublished` = 1 WHERE `eid` = '".intval($rsev['eid'])."'");
                        $returnstat = true;
                    endforeach;
                endif;
            else:
                $returnstat = $tmprssfile;
            endif;
		endif;
	endif;
	return $returnstat;
	}
endif;
	
// EOF ?>