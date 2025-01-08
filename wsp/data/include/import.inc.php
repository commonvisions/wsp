<?php
/**
 * class to import wsp-data
 * @author COVI
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8
 * @lastchange 2018-09-18
 */

// SH 2018-09-18
// as in export - stay in view. concept interesting

/*
// If the result will be an object, this container class is used.

class SimpleXMLObject{
	function attributes(){
		$container = get_object_vars($this);
		return (object) $container["@attributes"];
	}
	function content(){
		$container = get_object_vars($this);
		return (object) $container["@content"];
	}
}

// The Main XML Parser Class

class simplexml{
	var $result = array();
	var $ignore_level = 0;
	var $skip_empty_values = false;
	var $php_errormsg;
	var $evalCode = "";

//	 * Adds Items to Array
//	 * @param int $level
//	 * @param array $tags
//	 * @param $value
//	 * @param string $type

	function array_insert($level,$tags,$value,$type){
		$temp = '';
		for($c = $this->ignore_level+1; $c < $level+1; $c++):
			if(isset($tags[$c]) && (is_numeric(trim($tags[$c])) || trim($tags[$c]))):
				if(is_numeric($tags[$c])) $temp.= '[' .$tags[$c].']';
				else $temp.= '["'.$tags[$c].'"]';
			endif;
		endfor;
		$this->evalCode.= '$this->result'.$temp."=\"".addslashes($value)."\";//(".$type.")\n";
		//echo $code. "\n";
	}

//	 * Define the repeated tags in XML file so we can set an index
//	 * @param array $array
//	 * @return array

	function xml_tags($array){
	$repeats_temp = array();
	$repeats_count = array();
	$repeats = array();

	if(is_array($array)):
		$n = count($array)-1;
		for($i = 0; $i < $n; $i++):
			$idn = $array[$i]['tag'].$array[$i]['level'];
			if(in_array($idn,$repeats_temp)):
				$repeats_count[array_search($idn,$repeats_temp)]+=1;
			else:
				array_push($repeats_temp,$idn);
				$repeats_count[array_search($idn,$repeats_temp)]=1;
			endif;
		endfor;
	endif;
	$n = count($repeats_count);
	for($i=0;$i<$n;$i++):
		if($repeats_count[$i]>1) array_push($repeats,$repeats_temp[$i]);
	endfor;
	unset($repeats_temp);
	unset($repeats_count);
	return array_unique($repeats);
	}

//	 * Converts Array Variable to Object Variable
//	 * @param array $arg_array
//	 * @return $tmp

	function array2object ($arg_array){
		if(is_array($arg_array)):
			$keys = array_keys($arg_array);
			if(!is_numeric($keys[0])) $tmp = new SimpleXMLObject;
			foreach($keys as $key):
				if(is_numeric($key)) $has_number = true;
				if(is_string($key)) $has_string = true;
			endforeach;
			if(isset($has_number) and !isset($has_string)):
				foreach ($arg_array as $key => $value):
					$tmp[] = $this->array2object($value);
				endforeach;
			elseif(isset($has_string)):
				foreach ($arg_array as $key => $value):
					if(is_string($key)) $tmp->$key = $this->array2object($value);
				endforeach;
			endif;
		elseif(is_object($arg_array)):
			foreach($arg_array as $key => $value):
				if(is_array($value) or is_object($value)) $tmp->$key = $this->array2object($value);
				else $tmp->$key = $value;
			endforeach;
		else:
			$tmp = $arg_array;
		endif;
		return $tmp; //return the object
	}

//	 * Reindexes the whole array with ascending numbers
//	 * @param array $array
//	 * @return array

	function array_reindex($array){
		if(is_array($array)):
			if(count($array) == 1 && $array[0]):
				return $this->array_reindex($array[0]);
			else:
				foreach($array as $keys => $items):
					if (is_array($items)):
						if (is_numeric($keys)) $array[$keys] = $this->array_reindex($items);
						else $array[$keys] = $this->array_reindex(array_merge(array(), $items));
					endif;
				endforeach;
			endif;
		endif;
		return $array;
	}

//	 * Parse the XML generation to array object
//	 * @param array $array
//	 * @return array

    function xml_reorganize($array){
		$count = count($array);
		$repeat = $this->xml_tags($array);
		$repeatedone = false;
		$tags = array();
		$k = 0;
		for($i = 0; $i < $count; $i++):
			switch($array[$i]['type']){
				case 'open':
					array_push($tags,$array[$i]['tag']);
					if($i > 0 && ($array[$i]['tag'] == $array[$i-1]['tag']) && ($array[$i-1]['type'] == 'close')) $k++;
					if(isset($array[$i]['value']) && ($array[$i]['value'] || !$this->skip_empty_values)):
						array_push($tags,'@content');
						$this->array_insert(count($tags),$tags,$array[$i]['value'],"open");
						array_pop($tags);
					endif;
					if(in_array($array[$i]['tag'].$array[$i]['level'],$repeat)):
						if(($repeatedone == $array[$i]['tag'].$array[$i]['level']) && ($repeatedone)):
							array_push($tags,strval($k++));
						else:
							$repeatedone = $array[$i]['tag'].$array[$i]['level'];
							array_push($tags,strval($k));
						endif;
					endif;
					if(isset($array[$i]['attributes']) && $array[$i]['attributes'] && $array[$i]['level'] != $this->ignore_level):
						array_push($tags,'@attributes');
						foreach($array[$i]['attributes'] as $attrkey => $attr):
							array_push($tags,$attrkey);
							$this->array_insert(count($tags),$tags,$attr,"open");
							array_pop($tags);
						endforeach;
						array_pop($tags);
					endif;
					break;
				case 'close':
					array_pop($tags);
					if(in_array($array[$i]['tag'].$array[$i]['level'],$repeat)):
						if($repeatedone == $array[$i]['tag'].$array[$i]['level']):
							array_pop($tags);
						else:
							$repeatedone = $array[$i+1]['tag'].$array[$i+1]['level'];
							array_pop($tags);
						endif;
					endif;
					break;
				case 'complete':
					array_push($tags,$array[$i]['tag']);
					if (in_array($array[$i]['tag'].$array[$i]['level'],$repeat)):
						if ($repeatedone == $array[$i]['tag'].$array[$i]['level'] && $repeatedone):
							array_push($tags,strval($k));
						else:
							$repeatedone = $array[$i]['tag'].$array[$i]['level'];
							array_push($tags,strval($k));
						endif;
					endif;
					if(isset($array[$i]['value']) && ($array[$i]['value'] || !$this->skip_empty_values)):
						if (isset($array[$i]['attributes']) && $array[$i]['attributes']):
							array_push($tags,'@content');
							$this->array_insert(count($tags),$tags,$array[$i]['value'],"complete");
							array_pop($tags);
						else:
							$this->array_insert(count($tags),$tags,$array[$i]['value'],"complete");
						endif;
					endif;
					if(isset($array[$i]['attributes']) && $array[$i]['attributes']):
						array_push($tags,'@attributes');
						foreach($array[$i]['attributes'] as $attrkey => $attr):
							array_push($tags,$attrkey);
							$this->array_insert(count($tags),$tags,$attr,"complete");
							array_pop($tags);
						endforeach;
						array_pop($tags);
					endif;
					if(in_array($array[$i]['tag'].$array[$i]['level'],$repeat)):
						array_pop($tags);
						$k++;
					endif;
					array_pop($tags);
					break;
			}
		endfor;
		eval($this->evalCode);
		$last = $this->array_reindex($this->result);
		return $last;
	}

// Get the XML contents and parse like SimpleXML
// @param string $file
// @param string $resulttypew
// @param string $encoding
// @return array/object

    function xml_load_file($file,$resulttype='object',$encoding='ISO-8859-1'){
		$php_errormsg = "";
		$this->result = "";
		$this->evalCode = "";
		$values = "";
		$data = file_get_contents($file);
		if(!$data) return 'Cannot open xml document: '.(isset($php_errormsg) ? $php_errormsg : $file);

		$parser = xml_parser_create($encoding);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
		$ok = xml_parse_into_struct($parser,$data,$values);
		if(!$ok):
			$errmsg = sprintf("XML parse error %d '%s' at line %d, column %d (byte index %d)",
			xml_get_error_code($parser),
			xml_error_string(xml_get_error_code($parser)),
			xml_get_current_line_number($parser),
			xml_get_current_column_number($parser),
			xml_get_current_byte_index($parser));
		endif;

		xml_parser_free($parser);
		if(!$ok) return $errmsg;
		if($resulttype == 'array') return $this->xml_reorganize($values);
		// default $resulttype is 'object'
		return $this->array2object($this->xml_reorganize($values));
	}
}
function import(){
	$sxml = new simplexml;
	$xml = $sxml->xml_load_file($_FILES['contentFile']['tmp_name']);
	if($xml && $_FILES['contentFile']['type']  == ('text/xml' OR 'application/xml')):
		$items = array(); //Elemente Kommplett eingelesen
		$menus = array(); //Neue ID vergeben
		$import = array(); //Fertige SQL-Anfragen
		$menus_start = mysql_fetch_row(mysql_query("SELECT `mid` FROM `menu` ORDER BY `mid` DESC LIMIT 1"));
		$menus_start = $menus_start[0]; //ID des letzten Menuelementes ermitteln
		$menus_position = mysql_fetch_row(mysql_query("SELECT `position` FROM `menu` WHERE `level` = 1 ORDER BY `position` DESC LIMIT 1"));
		$menus_position = $menus_position[0];
		require ("data/parser/parserfuncs.inc.php"); // file-path
		
//		Filenamen falls bereits vorhanden durch neuen Namen ersetzten zb. test -> test_1
//		Alternativ: Menupunkte gleichen Dateinamens einander ergänzen.
//		
//		Positions überprüfen und menupunkt hinten anhängen
//		in menu und template_id überprüfen
//			-> Ansatz: beim exportieren nicht die template_id exportieren sondern den Namen/Titel und dannach abgleichen, wenn nicht mit 1 bzw ersten template importieren
		
		//Menupunkte einlesen	
		if (($count = count($xml->menu->item)) == 1):
			$temp = $xml->menu->item;
			$xml->menu->item = array();
			$xml->menu->item[0] = $temp;
		endif;
		if ($count > 0):
			for($i = 0; $i < $count; $i++):
				foreach($xml->menu->item[$i] as $itemkey => $item): //XML-Object einlesen
					if (is_array($item)): //Prüfen ob XML Atribut ein Array ist
						$item = $item[0]; // Fals Array ersten Eintrag in eine normale Variable umwandeln
					endif;
					if ($itemkey == 'mid'):
					
						$path = createPath($item) . '/';
					
						$menus_start++;
						$menus[$item] = $menus_start;
						$items[$i][$itemkey] = $menus_start;
						$mid = $items[$i][$itemkey];
					elseif ($itemkey == 'connected'): //Hirachie pruefen
						$foo = false;
						foreach ($menus as $mk => $m)
							if ($item == $mk):
								$items[$i][$itemkey] = "$m";
								$foo = true;								
							endif;
						foreachend;
						if (!$foo):
							$items[$i][$itemkey] = "0";
						endif;
						$level[$mid][$itemkey] = $items[$i][$itemkey];
					elseif ($itemkey == 'level'):
						$items[$i][$itemkey] = $item;
						$level[$mid][$itemkey] = $items[$i][$itemkey];
					elseif ($itemkey == 'filename'):
						$doexists = false;
						$ccount = 1;
						while( !$doexists ):
							if ( file_exists($_SERVER['DOCUMENT_ROOT'] . $path . $item . '.php') ):
								$doexists = false;
								$item.= "_" . $ccount;
							else:
								$doexists = true;
							endif;
						$ccount++;
						endwhile;
						$items[$i][$itemkey] = $item;
					else:
						$items[$i][$itemkey] = $item;
					endif;
				endforeach;
			endfor;
			for($i = 0; $i < $count; $i++):
				$cc = 0; $var = ''; $val = '';
				foreach($xml->menu->item[$i] as $item):
					$cc++;
				endforeach;
				foreach($items[$i] as $itemskey => $itemsvalue): //XML-Object aus Array auslesen und aufbereiten
					$var.= $itemskey;
					if($itemskey == 'level'): //Level pruefen
						if($level[$items[$i]['mid']]['connected'] == 0):
							$val.= "'1'";
							$levels = 1;
							$mid = $items[$i]['mid']; 
						else:
							if($mid == $items[$i]['connected']):
								$levels++;
								$mid = $items[$i]['mid']; 
							else:
								foreach($level as $levelkey =>$levelvalue):
									if($levelkey == $items[$i]['connected']):
										$levels = $level[$levelkey]['level']+1;
									endif;
								endforeach;
								$mid = $items[$i]['mid']; 
							endif;
							$val.= "'" . $levels . "'" ;
						endif;
					elseif($itemskey == 'contentchanged'): //Auf bearbeitet setzten
						$val.="'1'";
					elseif($itemskey == 'position' && $items[$i]['level'] == 1): //Auf bearbeitet setzten
						$val.="'>" . ++$menus_position . "<'";
					else:
						$val.="'" . $itemsvalue . "'";
					endif;
					if($cc > 1):
						$var.= ", ";
						$val.= ", ";
					endif;
					$cc--;
				endforeach;
				$import[] = "INSERT INTO menu ($var) VALUES ($val);"; //SQL-Statement erstellen
			endfor;
		endif;
		//Inhalte einlesen	
		if (($count = count($xml->content->item)) == 1):
			$temp = $xml->content->item;
			$xml->content->item = array();
			$xml->content->item[0] = $temp;
		endif;
		if ($count > 0):
			$contents_start = mysql_fetch_row(mysql_query("SELECT cid FROM `content` ORDER BY cid DESC LIMIT 1"));
			$contents_start = $contents_start[0]; //ID des letzten Menuelementes ermitteln
			for($i = 0; $i < $count; $i++):
				$cc = 0; $var = ''; $val = '';
				foreach($xml->content->item[$i] as $item):
					$cc++;
				endforeach;
				foreach($xml->content->item[$i] as $itemkey => $item): //XML-Object einlesen
					if(is_array($item)): //Prüfen ob XML Atribut ein Array ist
						$item = $item[0]; // Fals Array ersten Eintrag in eine normale Variable umwandeln
					endif;
					$var.= $itemkey;
					if($itemkey == 'cid'):
						$contents_start++;
						$val.= "'" . $contents_start . "'";
					elseif($itemkey == 'mid'):
						foreach($menus as $mk => $m)
							if($item == $mk):
								$val.= "'$m'";
							endif;
						foreachend;	
					else:
						$val.= "'" . $item . "'";
					endif;
					if($cc > 1):
						$var.= ", ";
						$val.= ", ";
					endif;
					$cc--;
				endforeach;
				$import[] = "INSERT INTO content ($var) VALUES ($val);"; //SQL-Statement erstellen
			endfor;
		endif;
		foreach($import as $im):
			echo "<pre>$im</pre><hr />\n";
//			$err = mysql_query($im); //SQL-Statements ausführen
//			if(!$err): //auf SQL-Fehler ueberpruefen
//				echo "Fehler im SQL-Statement<br />\n";
//			endif;					
		endforeach;
		$msg['msg'] = "<p>Der Import war erfolgreich. (".date('d.m.Y H:i:s').")</p>";
		$msg['stat'] = "noticemsg";
	else:
		$msg['msg'] = "<p>Fehler, es k&ouml;nnen nur XML-Dateinen importiert werden!</p>";
		$msg['stat'] = "errormsg";
	endif;
	return $msg;
}
*/
// EOF ?>