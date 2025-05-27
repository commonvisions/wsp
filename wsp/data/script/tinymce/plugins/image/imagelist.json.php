<?php
/**
 * TINYMCE imagelist.json for "image"-plugin
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-25
 */

session_start();

if (isset($_SESSION['wspvars']['wspbasedir'])) {

	$includeDir = str_replace("//", "/", $_SERVER['DOCUMENT_ROOT'] . '/' . 
		$_SESSION['wspvars']['wspbasediradd'] . '/' . $_SESSION['wspvars']['wspbasedir']);

	include_once str_replace("//", "/", $includeDir . '/data/include/globalvars.inc.php');
	include_once str_replace("//", "/", $includeDir . '/data/include/funcs.inc.php');
	include_once str_replace("//", "/", $includeDir . '/data/include/dbaccess.inc.php');
	include_once str_replace("//", "/", $includeDir . '/data/include/checkuser.inc.php');
	include_once str_replace("//", "/", $includeDir . '/data/include/errorhandler.inc.php');
	include_once str_replace("//", "/", $includeDir . '/data/include/siteinfo.inc.php');

	function getMediaImageTiny($path = '/images/', $selected='', $toppath = '', $trimname = 40) {
		//	array $selected abfangen 
		$selected = is_array($selected) ? $selected : array($selected);
		$mediafiles = '';
		$files = array();
		$dir = array();
		$hdlfa = array('thumbs','preview','images/thumbs','images/preview','screen','layout','rss','fonts');

		if (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media".$path) && !(in_array(trim($path), $hdlfa))):
			$d = dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd']."/media".$path);
			while (false !== ($entry = $d->read())):
				if (substr($entry, 0, 1)!='.' && $entry != 'index.php'):
					if (is_file($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry)):
						$files[] = $path.$entry;
					elseif (is_dir($_SERVER['DOCUMENT_ROOT']."/".$_SESSION['wspvars']['wspbasediradd'].'/media'.$path.$entry) && str_replace("/","",trim($entry))!="thumbs" && str_replace("/","",trim($entry))!="flash" && str_replace("/","",trim($entry))!="screen"):
						$dir[] = $path.$entry;
					endif;
				endif;
			endwhile;
			$d->close();
			sort($files);
			sort($dir);
			foreach($files AS $value):
				$mediafiles .= "{title: '   "; //'
				$mediadesc = '';
				$desc_sql = "SELECT `filedesc` FROM `mediadesc` WHERE `mediafile` LIKE '%".str_replace("//", "/", str_replace("//", "/", $value))."%'";
				$desc_res = doResultSQL($desc_sql);
				if ($desc_res!==false && trim($desc_res)!=''):
					$mediadesc = trim($desc_res);
				endif;
				if (trim($toppath)!="" && $toppath!="/"):
					$value = str_replace($toppath, "", $value);
				endif;
				if (trim($mediadesc)!=""):
					$mediafiles .= $mediadesc;
				elseif (strlen($value)>$trimname):
					$mediafiles .= substr($value,0,5)."...".substr($value,-($trimname-5));
				else:
					$mediafiles .= $value;
				endif;
				$mediafiles .= str_replace("//", "/", str_replace("//", "/", "', value: '/media/" . $value . "'},\n"));
			endforeach;
			foreach($dir AS $value):
				if (isset($_SESSION['wspvars']['publisherdata']['hiddenmedia']) && trim($_SESSION['wspvars']['publisherdata']['hiddenmedia'])!=''):
					$hiddenmedia = explode(",",trim($_SESSION['wspvars']['publisherdata']['hiddenmedia']));
					if(is_string($hiddenmedia)):
						if (strpos($value, $hiddenmedia)==0):
							$mediafiles .= "{title: 'Ordner - ".substr($value,1)."', value: './'},\n"; //'
							$mediafiles .= getMediaImageTiny($value.'/', $selected, $toppath, $trimname);
						endif;
					else:
						$mediafiles .= "{title: 'Ordner - ".substr($value,1)."', value: './'},\n"; //'
						$mediafiles .= getMediaImageTiny($value.'/', $selected, $toppath, $trimname);
					endif;
				else:
					$mediafiles .= "{title: 'Ordner - ".substr($value,1)."', value: './'},\n"; //'
					$mediafiles .= getMediaImageTiny($value.'/', $selected, $toppath, $trimname);
				endif;
			endforeach;
		endif;
		return $mediafiles;
	}	// getMediaImageTiny()


	echo "[\n";
	echo getMediaImageTiny();
	echo "]";

} else {
	echo '[ERROR]';
}