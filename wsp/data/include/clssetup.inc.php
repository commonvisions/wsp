<?php
/**
 * @description basic class for module setup routine
 * @author s.haendler@covi.de
 * @copyright (c) 2018, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.8.1
 * @lastchange 2018-09-18
 */
 
class clsSetup {
	/**
	* Gibt die Modulversion zurück
	*/
	function version() {
		return '';
	}	// version()
    
    function minWSP() {
        return false;
    }
    
    function maxWSP() {
        return false;
    }
    
	/**
	* Gibt die GUID des Moduls zurück
	*/
	function getGUID() {
		return '';
	}	// getGUID()

	/**
	* Gibt den Modulnamen zurück
	*/
	function name() {
		return '';
	}	// version

	/**
	* Gibt ein Array mit den Modulen zurück, die installiert sein müssen (GUID, mind. Version)
	*/
	function dependences() {
		return array();
	}	// dependences()

	/**
	* Gibt ein Array mit den Menüeinträgen zurück
	* Struktur der Rückgabe:
	* @return cmsMenu(Menu-GUID, Title, Link, Description, Parent)
	*/
	function cmsMenu() {
		$menu = array();
		return $menu;
	}	// cmsMenu()

	/**
	* mögliche Werte für das Zugriffsrecht (Menu-GUID, Titel, Werte-Array)
	*/
	function cmsRights() {
		$rights = array();
		return $rights;
	}	// cmsRightName()

	/**
	* Gibt Informationen zum Parser zurück
	* @return: getParser(Parsername, Classname, HTMLMode('yes', 'no'), Parserfile, Maxfields, GUID, Version)
	*/
	function getParser() {
		$parser = array();
		return $parser;
	}	// getParser()

	/**
	* Gibt Informationen zum Menüarser zurück
	* @return: getMenuParser(Parsername, Classname, HTMLMode('yes', 'no'), Parserfile, Maxfields, GUID, Version)
	*/
	function getMenuParser() {
		$menu = array();
		return $menu;
	}	// getMenuParser()
	
	function getPlugin() {
		$plugin = array();
		return $plugin;
	}	// getMenuParser()

	/**
	* Gibt die Typen des Modules zurück (Parser, CMS-Modul)
	* @return getType(Parser('1', '0'), CMS-Modul('1', '0'))
	*/
	function getType() {
		$type['isparser'] = 0;
		$type['iscmsmodul'] = 0;
		$type['ismenu'] = 0;
		$type['isglobal'] = 0;
		return $type;
	}	// getType()

	/**
	* Gibt an, ob für Das Modul eine Setup-Routine existiert
	* @return getHasSetup('1', '0')
	*/
	function getHasSetup() {
		return '0';
	}	// getType()

	/**
	* Gibt an, ob für Das Modul eine Setup-Routine existiert
	* @return getSetup('setupfile.php', '')
	*/
	function getSetup() {
		return '';
	}	// getSetup()

	/**
	* Gibt die Default-Einstellung für modsetup an
	* @return getSetup('serialissiertes array', '')
	*/
	function getSetupDefault() {
		return '';
	}	// getSetupDefault()

	/**
	 * Gibt Selfvars dieses Modules zurück
	 *
	 * @return unknown
	 */
	function getSelfVars() {
		$selfvars = array();
		return $selfvars;
	}	// getSelfVars()

	/**
	 * SQL-Statement zum erzeugen der benötigten Tabellen
	 *
	 * @return Array
	 */
	function getSQLCreate() {
		$sql = array();
		return $sql;
	}
	/* BEISPIEL ZU getSQLDescribe():
	****************************************************
	 function getSQLDescribe() {
		$sql = array();
		$sql[0]['tablename']="testdb";
		$sql[0]['fields']["id"]['field']="id";
		$sql[0]['fields']["id"]['type']="int(11)";
		$sql[0]['fields']["id"]['null']="";
		$sql[0]['fields']["id"]['default']="";
		$sql[0]['fields']["id"]['extra']="auto_increment";
		$sql[0]['fields']["b"]['field']="b";
		$sql[0]['fields']["b"]['type']="varchar(12)";
		$sql[0]['fields']["b"]['null']="";
		$sql[0]['fields']["b"]['default']="ba";
		$sql[0]['fields']["b"]['extra']="";
		$sql[0]['fields']["cneu"]['field']="cneu";
		$sql[0]['fields']["cneu"]['type']="int(11)";
		$sql[0]['fields']["cneu"]['null']="NULL";
		$sql[0]['fields']["cneu"]['default']="";
		$sql[0]['fields']["cneu"]['extra']="";
		$sql[0]['key'][0]['name']="PRIMARY";
		$sql[0]['key'][0]['value'][0]="id";	
		$sql[0]['key'][1]['name']="b";
		$sql[0]['key'][1]['value'][0]="b";
		$sql[0]['delete']=false;			
		return $sql;
	}
	****************************************************
	 */
	function getSQLDescribe() {
		$sql = array();
		return $sql;
	}
}

// EOF ?>