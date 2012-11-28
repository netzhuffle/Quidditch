<?php

// Funktionen nur für index.php
// $Id: functions.php-index.php,v 1.21 2012/10/17 06:15:29 student Exp $

require_once("functions.php-func-verlasse_chat.php");
require_once("functions.php-func-nachricht.php");


function erzeuge_sequence ($db,$id) {
	//  Funktion erzeugt einen Datensatz in der Tabelle squence mit der nächsten freien ID

	global $dbase,$conn;

	$query="select se_nextid from sequence where se_name='$db'";
	$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if (!($result && mysql_num_rows($result)==1)):

		// Tabelle neu anlegen
		$query="CREATE TABLE sequence (".
			"se_name varchar(127) NOT NULL default '',".
			"se_nextid int(10) unsigned NOT NULL default '0',".
			"PRIMARY KEY (se_name));";
		$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);

		// Sperren
		$query="LOCK  TABLES $db,sequence WRITE";
		$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);

		// Höchste ID lesen
		$query="select max($id) from $db";
		$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result && mysql_num_rows($result)==1) $temp=mysql_result($result,0,0)+1;
		if ($temp=="NULL" || !$temp) $temp=0;

		// ID eintragen
		$query="INSERT INTO sequence (se_name,se_nextid) VALUES ('$db','$temp')";
		$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if (!$result) echo mysql_errno() . " - " . mysql_error();

		// Sperre aufheben
		$query="UNLOCK TABLES";
		$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);

	endif;
	@mysql_free_result($result);
}


function show_who_is_online($result) {
	// Funktion gibt Liste der Räume mit Usern aus
	// $result ist gültiges Ergebnis einer Query, die o_userdata* und r_name enthalten muss

	global $ft0,$ft1,$t,$whotext;

	$text="";
	$r_name_alt="";
	$zeigen_alt=TRUE;
	if ($result){
		while ($row=mysql_fetch_object($result)){
			// $userdata=unserialize($row->o_userdata.$row->o_userdata2.$row->o_userdata3.$row->o_userdata4);
			// if ($userdata[u_away]=="") {
			// 	$nick=$userdata[u_nick]." ";
			// } else {
			// 	$nick="(".$userdata[u_nick].") ";
			// }
			if (($row->o_level=="S") || ($row->o_level=="C")) {
				$nick="<b>$row->o_name</b>";
			} else {
				$nick=$row->o_name;
			}
			
			// Unterscheidung Raum oder Community-Modul
			if (!$row->r_name || $row->r_name=="NULL") {
				$r_name=$t['default10'].$whotext[$row->o_who];
				$zeigen=TRUE;
			} else {
				// Nur offene, permanente Räume zeigen
				if (($row->r_status1=='O' || $row->r_status1=='m') && $row->r_status2=='P') {
					$zeigen=TRUE;
				} else {
					$zeigen=FALSE;
				}
				$r_name=$t['default9'].$row->r_name;
			}

			// Textwechsel
			if ($r_name_alt!=$r_name){
				if (strlen($text)==0){
					$text="$nick ";
				} else {
					// Nur offene, permanente Räume zeigen
					if ($zeigen_alt) {
						show_box2(str_replace("%raum%",$r_name_alt,$ft0.$t['default4'].$ft1), $text,"100%",false);
						echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";
					}
					$text="$nick ";
				};
				$r_name_alt=$r_name;
				$zeigen_alt=$zeigen;
			} else {
				$text.="$nick ";
			};
//			$i++;
		};
		if ($zeigen_alt) {
			show_box2(str_replace("%raum%",$r_name_alt,$ft0.$t['default4'].$ft1), $text,"100%",false);
			echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";
		}
		mysql_free_result($result);
	};
}

function login($u_id,$u_name,$u_level,$hash_id,$javascript,$ip_historie,$u_agb,
	$u_punkte_monat,$u_punkte_jahr,$u_punkte_datum_monat,$u_punkte_datum_jahr,
	$u_punkte_gesamt) {
// In das System einloggen
// $o_id wird zurückgeliefert
// u_id=User-ID, u_name ist Nickname, u_level ist Level, hash_id ist Session-ID
// javascript=JS WAHR/FALSCH, ip_historie ist Array mit IPs alter Logins, u_agb ist AGB gelesen Y/N

global $dbase, $conn, $http_host, $HTTP_SERVER_VARS, $punkte_gruppe, $communityfeatures,$logout_logging;


// IP/Browser Adresse des User setzen
$ip = $_SERVER["REMOTE_ADDR"];
$browser = $_SERVER["HTTP_USER_AGENT"];

$browser = str_replace("MSIE 8.0", "MSIE 7.0", $browser);

// Alle wichtigen HTTP-Header merken
$http_stuff=$_SERVER;

// ausblenden vom uninteressanten Sachen...
unset($http_stuff['DOCUMENT_ROOT']);
unset($http_stuff['HTTP_ACCEPT']);
unset($http_stuff['HTTP_ACCEPT_ENCODING']);
unset($http_stuff['HTTP_ACCEPT_CHARSET']);
unset($http_stuff['HTTP_ACCEPT_LANGUAGE']);
unset($http_stuff['HTTP_CACHE_CONTROL']);
unset($http_stuff['HTTP_CONNECTION']);
unset($http_stuff['HTTP_PRAGMA']);
unset($http_stuff['PATH']);
unset($http_stuff['SCRIPT_FILENAME']);
unset($http_stuff['SERVER_ADMIN']);
unset($http_stuff['SERVER_NAME']);
unset($http_stuff['SERVER_PORT']);
unset($http_stuff['SERVER_SIGNATURE']);
unset($http_stuff['SERVER_SOFTWARE']);
unset($http_stuff['GATEWAY_INTERFACE']);
unset($http_stuff['SERVER_PROTOCOL']);
unset($http_stuff['REQUEST_METHOD']);
unset($http_stuff['REQUEST_URI']);
unset($http_stuff['SCRIPT_NAME']);
unset($http_stuff['PATH_TRANSLATED']);
unset($http_stuff['PHP_SELF']);
unset($http_stuff['SERVER_ADDR']);
unset($http_stuff['argv']);
unset($http_stuff['argc']);
unset($http_stuff['CONTENT_TYPE']);
unset($http_stuff['CONTENT_LENGTH']);
unset($http_stuff['HTTP_REFERER']);
unset($http_stuff['HTTP_KEEP_ALIVE']);
unset($http_stuff['QUERY_STRING']);
unset($http_stuff['REMOTE_PORT']);
unset($http_stuff['PHP_AUTH_PW']);
                

// Aktionen initialisieren, nicht für Gäste
if ($u_level!="G" && $communityfeatures) {
	$query="select a_id FROM aktion WHERE a_user=$u_id ";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result && (mysql_num_rows($result)==0 || mysql_num_rows($result)>20)) {
		mysql_query("INSERT INTO aktion set a_user=$u_id, a_text='$u_name', a_wann='Sofort/Online',	a_was='Freunde',	a_wie='OLM'", $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		mysql_query("INSERT INTO aktion set a_user=$u_id, a_text='$u_name', a_wann='Login',		a_was='Freunde',	a_wie='OLM'", $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		mysql_query("INSERT INTO aktion set a_user=$u_id, a_text='$u_name', a_wann='Sofort/Online',	a_was='Neue Mail',	a_wie='OLM'", $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		mysql_query("INSERT INTO aktion set a_user=$u_id, a_text='$u_name', a_wann='Login',		a_was='Neue Mail',	a_wie='OLM'", $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		mysql_query("INSERT INTO aktion set a_user=$u_id, a_text='$u_name', a_wann='Alle 5 Minuten',	a_was='Neue Mail',	a_wie='OLM'", $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	};
	mysql_free_result($result);
};

// Prüfen, ob User noch online ist und ggf. ausloggen
$alteloginzeit="";
$query="select o_id, o_login FROM online WHERE o_user=$u_id ";
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
if ($result  && mysql_num_rows($result)!=0) 
{
	$alteloginzeit=mysql_result($result,0,1);
	logout(mysql_result($result,0,0),$u_id,"login");
}
@mysql_free_result($result);


// Userdaten ändern

// Login als letzten Login merken, dabei away und loginfehler zurücksetzen.
$query="UPDATE user SET u_login=NOW(),u_away='',u_loginfehler='' WHERE u_id=$u_id";
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
if (!$result):
	echo "Fehler beim Login: $query<BR>";
	exit;
endif;

// Punkte des Vormonats/Vorjahres löschen und Usergruppe ermitteln, falls nicht Gast
if ($u_level!="G" && $communityfeatures) {

	// TODO
	// Ist u_sms_anzahl_monat vom aktuellen Monat?
	// if ($u_sms_anzahl_monat!=date("n",time())){
	// 	$f[u_sms_anzahl_monat]=date("n",time());
	//	$f[u_sms_anzahl_monat]=0;
	//}

	// Ist u_punkte_monat vom aktuellen Monat?
	if ($u_punkte_datum_monat!=date("n",time())){
		$f['u_punkte_datum_monat']=date("n",time());
		$f['u_punkte_monat']=0;
	}

	// Ist u_punkte_jahr vom aktuellen Jahr?
	if($u_punkte_datum_jahr!=date("Y",time())){
		$f['u_punkte_datum_jahr']=date("Y",time());
		$f['u_punkte_jahr']=0;
	}

	// Aus der Zahl der Gesamtpunkten die Usergruppe ableiten und in u_punkte_gruppe
	// speichern
	$f['u_punkte_gruppe']=0;
	foreach ($punkte_gruppe as $key => $value) {
		if ($u_punkte_gesamt<$value) {
			break;
		} else {
			$f['u_punkte_gruppe']=dechex($key);
		}
	};

};



// Aktuelle IP/Datum zu ip_historie hinzufügen
$datum=time();
$ip_historie_neu[$datum]=$ip;
if (is_array($ip_historie)):
	$i=0;
	while(($i<3) AND list($datum,$ip_adr)=each($ip_historie)):
		$ip_historie_neu[$datum]=$ip_adr;
		$i++;
	endwhile;
endif;

// u_agb, ip_historie und Temp-Admin schreiben
if ($u_agb=="Y") $f['u_agb']=$u_agb;
$f['u_ip_historie']=serialize($ip_historie_neu);
if ($u_level=="A"):
	// falls Status bisher Temp-Admin -> zurücksetzen.
	$f['u_level']="U";
	$u_level="U";
endif;
$u_id=schreibe_db("user",$f,$u_id,"u_id");
if (!$u_id):
	echo "Fataler Fehler beim Login:<PRE>";
	print_r($f);
	echo "</PRE>";
	exit;
endif;

// Aktuelle Daten des Users aus Tabelle iignore lesen
// Query muss mit Code in ignore übereinstimmen
$query="SELECT i_user_passiv FROM iignore WHERE i_user_aktiv=$u_id";
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
if (!$result):
	echo "Fehler beim Login (iignore): $query<BR>";
	exit;
else:
	if (mysql_num_rows($result)==0):
		$ignore[0]=FALSE;
	else:
		while ($iignore=mysql_fetch_array($result)):
			$ignore[$iignore[i_user_passiv]]=TRUE;
		endwhile;
	endif;
endif;

$knebelzeit = NULL;
// Aktuelle Userdaten aus Tabelle user lesen
// Query muss mit Code in schreibe_db übereinstimmen
$query="SELECT u_id,u_name,u_nick,u_level,u_farbe,u_zeilen,u_backup,u_farbe_bg,".
	"u_farbe_alle,u_farbe_priv,u_farbe_noise,u_farbe_sys,u_clearedit, ".
	"u_away,u_email,u_adminemail,u_smilie,u_punkte_gesamt,u_punkte_gruppe,".
	"u_chathomepage,u_systemmeldungen,u_punkte_anzeigen ".
	"FROM user WHERE u_id=$u_id";
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
if (!$result):
	echo "Fehler beim Login: $query<BR>";
	exit;
else:
	$userdata=mysql_fetch_array($result,MYSQL_ASSOC);

	// Slashes in jedem Eintrag des Array ergänzen
	reset($userdata);
	while (list($ukey,$udata)=each($userdata)) {
		$udata=addslashes($udata);              
	}
	
	$userdata_array=zerlege(serialize($userdata));
	$http_stuff_array=zerlege(serialize($http_stuff));
	
	if (!isset($http_stuff_array[0])) $http_stuff_array[0] = "";
	if (!isset($http_stuff_array[1])) $http_stuff_array[1] = "";
	if (!isset($userdata_array[0])) $userdata_array[0] = "";
	if (!isset($userdata_array[1])) $userdata_array[1] = "";
	if (!isset($userdata_array[2])) $userdata_array[2] = "";
	if (!isset($userdata_array[3])) $userdata_array[3] = "";
	
	// Hole Knebelzeit aus Usertabelle
	$query="SELECT u_knebel FROM user WHERE u_id=$u_id";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result && mysql_num_rows($result)==1) 
	{
		$row=mysql_fetch_object($result);
		$knebelzeit = $row->u_knebel;
        }
        @mysql_free_result($result);
        	
endif;

// Vorbereitung für Login ist abgeschlossen. Jetzt nochmals Prüfen ob User online ist, 
// ggf. Session löschen und neue Session schreiben

// Tabellen online+user exklusiv locken
$query="LOCK   TABLES online WRITE, user WRITE";
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
$query="DELETE FROM online WHERE o_user=$u_id";
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

// User in in Tabelle online merken -> User ist online
unset($f);
$f['o_user']=$u_id;
$f['o_raum']=0;
$f['o_hash']=$hash_id;
$f['o_ip']=$ip;
$f['o_who']="1";
$f['o_browser']=$browser;
$f['o_name']=$userdata['u_nick'];
$f['o_vhost']=$http_host;
$f['o_js']=$javascript;
$f['o_level']=$userdata['u_level'];
$f['o_http_stuff']=$http_stuff_array[0];
$f['o_http_stuff2']=$http_stuff_array[1];
$f['o_userdata']=$userdata_array[0];
$f['o_userdata2']=$userdata_array[1];
$f['o_userdata3']=$userdata_array[2];
$f['o_userdata4']=$userdata_array[3];
$f['o_ignore']=serialize($ignore);
$f['o_punkte']=0; // Zähler mit 0 Punkten neu initialisieren
$f['o_aktion']=time(); // 5-min Aktionen initialisieren

$o_id=schreibe_db("online",$f,"","o_id");
if (!$o_id):
	echo "Fataler Fehler beim Login:<PRE>";
	print_r($f);
	echo "</PRE>";
	exit;
endif;


// Timestamps im Datensatz aktualisieren -> User gilt als eingeloggt
if ($alteloginzeit!="")
{ $query="UPDATE online SET o_aktiv=NULL, o_login='$alteloginzeit', o_knebel='$knebelzeit', o_timeout_zeit=DATE_FORMAT(NOW(),\"%Y%m%d%H%i%s\"), o_timeout_warnung='N' WHERE o_user=$u_id "; }
else
{ $query="UPDATE online SET o_aktiv=NULL, o_login=NULL, o_knebel='$knebelzeit', o_timeout_zeit=DATE_FORMAT(NOW(),\"%Y%m%d%H%i%s\"), o_timeout_warnung='N' WHERE o_user=$u_id "; }
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

// Lock freigeben
$query="UNLOCK TABLES";
$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);


// Bei Admins Cookie setzen zur Überprüfung der Session
if ($userdata['u_level']=="C" || $userdata['u_level']=="S") {
	setcookie("MAINCHAT".$userdata['u_nick'],md5($o_id.$hash_id."42"),0,"/");
};


if ($logout_logging) logout_debug($o_id,"login");

return($o_id);

};



function betrete_chat($o_id,$u_id,$u_name,$u_level,$raum,$javascript,$u_backup) {
// User $u_id betritt Raum $raum (r_id)
// Nachricht in Raum $raum wird erzeugt
// Zeiger auf letzte Zeile wird zurückgeliefert

global $dbase,$chat,$conn,$lobby,$eintrittsraum,$t,$hash_id,$communityfeatures,$beichtstuhl,$system_farbe,$u_punkte_gesamt;
global $HTTP_SERVER_VARS;
global $raum_eintrittsnachricht_kurzform, $raum_eintrittsnachricht_anzeige_deaktivieren;

// Falls eintrittsraum nicht definiert, lobby voreinstellen
if (strlen($eintrittsraum)==0):
	$eintrittsraum=$lobby;
endif;

// Ist $raum geschlossen oder User ausgesperrt?
// ausnahme: geschlossener Raum ist Eingangsraum -> für e-rotic-räume
// die sind geschlossen, aber bei manchen kostenlosen chats default :-(
// Ausnahme ist Beichtstuhl-Modus, hier darf beim Login auch ein
// geschlossener Raum betreten werden
if (strlen($raum)>0) {
	$query4711="SELECT r_id,r_status1,r_besitzer,r_name,r_min_punkte FROM raum WHERE r_id=$raum";
	$result=mysql_query($query4711, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result && mysql_num_rows($result)>0){
		$rows=mysql_fetch_object($result);
		$r_min_punkte = $rows->r_min_punkte;
		if ($rows->r_name!=$eintrittsraum) {
			switch ($rows->r_status1) {
			case "G":
			case "M":
				// Grundsätzlich nicht in geschlossene oder moderierte räume
				$raumeintritt = false;

				// es sei denn, man ist dorthin eingeladen
				$query2911="SELECT inv_user FROM invite WHERE inv_raum=$rows->r_id AND inv_user=$u_id";
				$result2911=mysql_query($query2911, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
				if ($result2911>0)
				{
					if (mysql_num_rows($result2911)>0) $raumeintritt=true;
					mysql_free_result($result);
				}
                                                                                                                
				// oder man ist Raumbesiter dort
				if ($rows->r_besitzer==$u_id) $raumeintritt=true;
				
				// oder der Beichtstuhlmodus
				if ($beichtstuhl) $raumeintritt=true;
				
				// abweisen wenn $raumeintritt = false
				if (!$raumeintritt) 
				{
					system_msg("",0,$u_id,$system_farbe,str_replace("%r_name_neu%",$rows->r_name,$t['raum_gehe4']));
					unset($raum);
				};
				break;
			default: 	
				break;
			}
		}
	}
	@mysql_free_result($result);
};

if (strlen($raum)>0):

	// Prüfung ob User aus Raum ausgesperrt ist

	$query4711="SELECT s_id FROM sperre WHERE s_raum=$raum AND s_user=$u_id";
	$result=mysql_query($query4711, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result>0) 
	{

		$rows=mysql_Num_Rows($result);
		if (($rows!=0) || ($r_min_punkte > $u_punkte_gesamt))
		{
			// Aktueller Raum ist gesperrt, oder zu wenige Punkte

			// Ist User aus Raum ausgesperrt, dann nicht einfach den Eintrittsraum oder die Lobby nehmen,
			// da kann der User auch ausgesperrt sein.

			unset($raum);

			if (!$beichtstuhl)
			{
				$query1222b="SELECT r_id FROM raum left join sperre on r_id = s_raum and s_user = '$u_id' ".
				            "WHERE r_status1 = 'O' and r_status2 = 'P' and r_min_punkte <= $u_punkte_gesamt ".
					    "and s_id is NULL ".
					    "ORDER BY r_id ";
				$result1222b=mysql_query($query1222b, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
				if (($result1222b > 0) && (mysql_num_rows($result1222b) > 0))
				{
					// Es gibt Räume, für die man noch nicht gesperrt ist.
					// hiervon den ersten nehmen
					$raum = mysql_result($result1222b, 0, 0);
				}
				mysql_free_result($result1222b);
			}

		}
		mysql_free_result($result);
	}
endif;

// print $raum;
// Welchen Raum betreten?
if (strlen($raum)==0){

	// Id des Eintrittsraums als Voreinstellung ermitteln
	$query4711="SELECT r_id,r_name,r_eintritt,r_topic ".
			"FROM raum WHERE r_name='$eintrittsraum' ";
	$result=mysql_query($query4711, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result) $rows=mysql_Num_Rows($result);

	// eintrittsraum nicht gefunden? -> lobby probieren.
	if ($rows==0) {
		$query4711="SELECT r_id,r_name,r_eintritt,r_topic ".
				"FROM raum WHERE r_name='$lobby' ";
		$result=mysql_query($query4711, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result) $rows=mysql_Num_Rows($result);
	}
	// lobby  nicht gefunden? --> lobby anlegen.
	if ($rows==0) {
		// lobby neu anlegen.
		$query4711="INSERT INTO raum ".
			"(r_id,r_name,r_eintritt,r_austritt,r_status1,r_besitzer,r_topic,r_status2,r_smilie) ".
			"VALUES (0,'$lobby','Willkommen','','O',1,'Eingangshalle','P','')";
		$result=mysql_query($query4711, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		// neu lesen.
		$query4711="SELECT r_id,r_name,r_eintritt,r_topic ".
				"FROM raum WHERE r_name='$lobby' ";
		$result=mysql_query($query4711, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result) $rows=mysql_Num_Rows($result);
	}

} else {

	// Gewählten Raum ermitteln
	$query4711="SELECT r_id,r_name,r_eintritt,r_topic ".
			"FROM raum WHERE r_id=$raum ";
	$result=mysql_query($query4711, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result) $rows=mysql_Num_Rows($result);

}

if ($result && $rows==1):
	$r_id=mysql_result($result,0,"r_id");
	$r_eintritt=mysql_result($result,0,"r_eintritt");
	$r_topic=mysql_result($result,0,"r_topic");
	$r_name=mysql_result($result,0,"r_name");
	mysql_free_result($result);
else:
	echo "<BODY><P>Fehler: Ungültige Raum-ID $raum beim Login!</P></BODY></HTML>\n";
	exit;
endif;

// Aktuellen Raum merken, o_who auf chat setzen
$f['o_raum']=$r_id;
$f['o_who']="0";


// Nachricht im Chat ausgeben
$back=nachricht_betrete($u_id,$r_id,$u_name,$r_name);


// ID der Eintrittsnachricht merken und online-Datensatz schreiben
$f['o_chat_id']=$back;
schreibe_db("online",$f,$o_id,"o_id");


// Topic vorhanden? ausgeben
if (strlen($r_topic)>0) {
	system_msg("",0,$u_id,"","<BR><B>$t[betrete_chat3] $r_name:</B> $r_topic");
}


// Eintrittsnachricht
if ($t['betrete_chat1']) {
	$txt=$t['betrete_chat1']." ".$r_name.":";
} else {
	unset($txt);
}

if ($raum_eintrittsnachricht_kurzform == "1") unset($txt);
                                                
if ($raum_eintrittsnachricht_anzeige_deaktivieren == "1")
{
}
else if (strlen($r_eintritt)>0){
	system_msg("",0,$u_id,"","<BR><B>$txt $r_eintritt, $u_name!</B><BR>");
} else {
	system_msg("",0,$u_id,"","<BR><B>$txt</B> $t[betrete_chat2], $u_name!</B><BR>");
}

// Wer ist alles im Raum?
raum_user($r_id,$u_id,$hash_id);


// Hat der User sein Profil ausgefüllt?
if ($communityfeatures && $u_level!="G") 
	profil_neu($u_id,$u_name,$hash_id);

$http_te = "";
if (isset($_SERVER['HTTP_TE'])) $http_te = $_SERVER['HTTP_TE']; 
 

// Optionale Warnungen ausgeben
$browser = $_SERVER["HTTP_USER_AGENT"];
if (!$javascript){
	warnung($u_id,$u_nick,"ohne_js");
} elseif ($u_backup || 
        preg_match("/(.*)mozilla\/[234](.*)mac(.*)/i",$browser) || 
        preg_match("/(.*)msie(.*)mac(.*)/i",$browser) ||
        preg_match("/(.*)Opera 3(.*)/i",$browser) ||    
        preg_match("/(.*)Opera\/9(.*)/i",$browser) ||    
        preg_match("/(.*)AppleWebKit\/53(.*)/i",$browser) ||    
        preg_match("/(.*)Konqueror(.*)/i",$browser) ||  
        preg_match("/(.*)mozilla\/5(.*)Netscape6(.*)/i",$browser) ||
        preg_match("/(.*)mozilla\/[23](.*)/i",$browser) ||
        preg_match("/(.*)AOL [123](.*)/i",$browser) ||   
        $http_te=='chunked') {
        $u_nick = "";
	warnung($u_id,$u_nick,"sicherer_modus");
};


// Hat der User Aktionen für den Login eingestellt, wie Nachricht bei neuer Mail oder Freunden an sich selbst?

if ($communityfeatures && $u_level!="G") 
	aktion("Login",$u_id,$u_name,$hash_id);



// Nachrichten an Freude verschicken
if ($communityfeatures) {
	$query="SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_userid=$u_id AND f_status = 'bestaetigt' ".
	       "UNION ".
	       "SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_freundid=$u_id AND f_status = 'bestaetigt' ".
	       "ORDER BY f_zeit desc "; 

        $result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

        if ($result && mysql_num_rows($result)>0) {

		while($row=mysql_fetch_object($result)) {
				unset($f);
				$f['raum']=$r_name;
				$f['aktion']="Login";
				$f['f_text']=$row->f_text;
				if ($row->f_userid==$u_id) {
					if (ist_online($row->f_freundid)) {
						$wann="Sofort/Online";
						$an_u_id=$row->f_freundid;
					} else {
						$wann="Sofort/Offline";
						$an_u_id=$row->f_freundid;
					}
				} else {
					if (ist_online($row->f_userid)) {
						$wann="Sofort/Online";
						$an_u_id=$row->f_userid;
					} else {
						$wann="Sofort/Offline";
						$an_u_id=$row->f_userid;
					}
				};
				// Aktion ausführen
				aktion($wann,$an_u_id,$u_name,"","Freunde",$f);
		};
        };
	@mysql_free_result($result);
};

return($back);

};




function id_erzeuge($u_id) {
// Erzeugt eindeutige ID für jeden User

$id=md5(uniqid(mt_rand()));
return $id;

};




function betrete_forum($o_id,$u_id,$u_name,$u_level) {
	// User betritt beim Login das Forum

	global $dbase,$conn,$chat,$lobby,$eintrittsraum,$t,$hash_id,$communityfeatures,$beichtstuhl,$system_farbe;

	//Daten in onlinetabelle schreiben
	$f['o_raum']=-1;
	$f['o_who']="2";

	//user betritt nicht chat, sondern direkt forum --> $back ist die aktuellste Zeile in chat
	$f['o_chat_id']=system_msg("",0,$u_id,"",str_replace("%u_nick%",$u_name,$t['betrete_forum1']));
	schreibe_db("online",$f,$o_id,"o_id");

	// Hat der User Aktionen für den Login eingestellt, wie Nachricht bei neuer Mail oder Freunden an sich selbst?
	if ($communityfeatures && $u_level!="G")
        	aktion("Login",$u_id,$u_name,$hash_id);


	// Nachrichten an Freude verschicken
	if ($communityfeatures) {
	    $query="SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_userid=$u_id AND f_status = 'bestaetigt' ".
	           "UNION ".
	           "SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_freundid=$u_id AND f_status = 'bestaetigt' ".
	           "ORDER BY f_zeit desc "; 
            $result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

            if ($result && mysql_num_rows($result)>0) {

                while($row=mysql_fetch_object($result)) {
                                unset($f);
                                $f['raum']="";
                                $f['aktion']="Login";
                                $f['f_text']=$row->f_text;
                                if ($row->f_userid==$u_id) {
                                        if (ist_online($row->f_freundid)) {
                                                $wann="Sofort/Online";
                                                $an_u_id=$row->f_freundid;
                                        } else {
                                                $wann="Sofort/Offline";
                                                $an_u_id=$row->f_freundid;
                                        }
                                } else {
                                        if (ist_online($row->f_userid)) {
                                                $wann="Sofort/Online";
                                                $an_u_id=$row->f_userid;
                                        } else {
                                                $wann="Sofort/Offline";
                                                $an_u_id=$row->f_userid;
                                        }
                                };
                                // Aktion ausführen
                                aktion($wann,$an_u_id,$u_name,"","Freunde",$f);
		    }
                };
        };
        @mysql_free_result($result);

}




function zeige_kopf() {
        // Gibt den HTML-Kopf auf der Eingangsseite aus
	global $body_tag,$layout_kopf,$layout_include,$layout_parse,
		$voreinstellung_body_tag,$layout_bodytag;

	if ($voreinstellung_body_tag):
		echo $voreinstellung_body_tag."\n";
	elseif ($layout_bodytag):
		echo $body_tag;
	endif;

        if (strlen($layout_kopf)>0 && !$layout_parse):
		// Direkt einlesen
                if ($layout_include==1):
                        include($layout_kopf);
                else:
                        readfile($layout_kopf);
                endif;
	elseif (strlen($layout_kopf)>0 && $layout_parse):
		// Lesen und ersetzen
		$fd = fopen ($layout_kopf, "r");
		$out=@fread($fd, 100000);
		@fclose($fd);
		$out=preg_replace("/src=\"\//i","src=\"".$layout_parse,$out);
		$out=preg_replace("/href=\"\//i","href=\"".$layout_parse,$out);
		$out=preg_replace("/action=\"\//i","action=\"".$layout_parse,$out);
		$out=preg_replace("/background=\"\//i","background=\"".$layout_parse,$out);
		$out=preg_replace("/".$layout_parse."\//i",$layout_parse,$out);
		echo $out;
        endif;
};    


function zeige_fuss() {
	// Gibt den HTML-Fuss auf der Eingangsseite aus
	global $body_tag,$layout_fuss,$layout_include,$layout_parse,$ivw,$layout_bodytag;
	global $f3,$f4,$mainchat_version;

        echo "<DIV align=center>".$f3.$mainchat_version.$f4."</DIV>\n<BR CLEAR=ALL>\n".
		"<BR CLEAR=ALL>\n";

	if (isset($banner) && strlen($banner)>0)
	{   
                readfile($banner);
        }
        if (strlen($layout_fuss)>0 && !$layout_parse):
                if ($layout_include==1):
                        include($layout_fuss);
                else:
                        readfile($layout_fuss);
                endif;
	elseif (strlen($layout_fuss)>0 && $layout_parse):
		// Lesen und ersetzen
		$fd = fopen ($layout_fuss, "r");
		$out=@fread($fd, 100000);
		@fclose($fd);
		$out=preg_replace("/src=\"\//i","src=\"".$layout_parse,$out);
		$out=preg_replace("/href=\"\//i","href=\"".$layout_parse,$out);
		$out=preg_replace("/action=\"\//i","action=\"".$layout_parse,$out);
		$out=preg_replace("/".$layout_parse."\//i",$layout_parse,$out);
		echo $out;
        endif;

        if ($ivw){
		// In IVW Pixel <IMG SRC=> einfügen (opt)
		if (!strpos($ivw,"<IMG")) {
			$ivw="<IMG SRC=\"$ivw\" ALT=\"\">\n";
		};
                echo $ivw."\n";
        };

	if ($layout_bodytag) echo "</BODY></HTML>\n";
};

function RaumNameToRaumID($eintrittsraum) {

	global $conn;

        // Holt anhand der Globalen Lobby Raumbezeichnung die Passende Raum ID

	$lobby_id = False;
	$eintrittsraum=addslashes($eintrittsraum);
	$query="SELECT r_id FROM raum WHERE r_name = '$eintrittsraum' ";
	$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result && mysql_num_rows($result)==1) {
		$lobby_id=mysql_result($result,0,"r_id");
	}
	mysql_free_result($result);
	return ($lobby_id);
}

function getsalt($feldname, $login) {
	
	// Versucht den Salt und die Verschlüsselung des Users zu erkennen
	// $login muss "sicher" kommen
	global $dbase, $conn;
	global $upgrade_password;

	$salt = "-9";
	$query="SELECT u_passwort FROM user WHERE $feldname = '$login' ";
	$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);

	if ($result && mysql_num_rows($result)==1)
	{
		// User vorhanden, u_passwort untersuchen
		$pass=mysql_result($result,0,"u_passwort");
		        
		if (preg_match('#(^\$6\$rounds\=([0-9]{4,9})\$(.{1,16})\$)#i', $pass, $treffer))
        	{
        		// SHA 512 erkannt
        		$salt = $treffer[1];
        		
			if (CRYPT_SHA512 == 0) 
			{
				// wenn SHA 512 nicht im System bekannt ist, Fehlermeldung
				// Kann nur bei Systemänderung vorkommen
				echo "<b>ERROR: CRYPT SHA512 used, but not supported; SALT $salt</b><br>";
				$salt = "-8";
			}
			
        	}
		else if ((preg_match('#(^\$5\$rounds\=([0-9]{4,9})\$(.{1,16})\$)#i', $pass, $treffer)) ||
		         (preg_match('#(^\$5\$(.{1,16})\$)#i', $pass, $treffer)))
        	{
        		// SHA 256 erkannt
        		$salt = $treffer[1];
        		
			if (CRYPT_SHA256 == 0) 
			{
				// wenn SHA 256 nicht im System bekannt ist, Fehlermeldung
				// Kann nur bei Systemänderung vorkommen
				echo "<b>ERROR: CRYPT SHA256 used, but not supported; SALT $salt</b><br>";
				$salt = "-7";
			}
			
        	}
		else if (preg_match('#(^\$2(a|x|y)\$([0-9]{1,2})\$(.{21}))#i', $pass, $treffer))
        	{
        		// Blowfish erkannt
        		$salt = $treffer[1].'$';
        		
			if (CRYPT_BLOWFISH == 0) 
			{
				// wenn Blowfish nicht im System bekannt ist, Fehlermeldung
				// Kann nur bei Systemänderung vorkommen
				echo "<b>ERROR: CRYPT BLOWFISH used, but not supported; SALT $salt</b><br>";
				$salt = "-6";
			}
			
        	}
		else if (preg_match('#(^\$1\$(.{0,8})\$)#i', $pass, $treffer))
        	{
        		// CRYPT MD5 erkannt
        		$salt = $treffer[1];
        		
			if (CRYPT_MD5 == 0) 
			{
				// wenn Crypt MD5 nicht im System bekannt ist, Fehlermeldung
				// Kann nur bei Systemänderung vorkommen
				echo "<b>ERROR: CRYPT MD5 used, but not supported; SALT $salt</b><br>";
				$salt = "-5";
			}
			
        	}
        	else if ((strlen($pass) == 20) && substr($pass,0,1) == '_')
        	{
        		// Extended DES erkannt
        		$salt = substr($pass, 0, 9);
        		
			if (CRYPT_EXT_DES == 0) 
			{
				// wenn Ext. DES nicht im System bekannt ist, Fehlermeldung
				// Kann nur bei Systemänderung vorkommen
				echo "<b>ERROR: Ext. DES used, but not supported; SALT $salt</b><br>";
				$salt = "-4";
			}
			else
			{
				if ((CRYPT_SHA256 == 1) || (CRYPT_MD5 == 1)) 
				{
					$upgrade_password = 1;
				}
			}        	}
		else if ((strlen($pass) == 32) && preg_match('#(^[a-f0-9]{32}$)#i', $pass, $treffer))
        	{
        		// HASH MD5 erkannt
        		$salt = 'MD5';
        		
			if (!function_exists('md5')) 
			{
				// wenn HASH MD5 nicht im System bekannt ist, Fehlermeldung
				// Kann nur bei Systemänderung vorkommen
				echo "<b>ERROR: HASH MD5 used, but not supported; SALT $salt</b><br>";
				$salt = "-3";
			}
			else
			{
				if ((CRYPT_SHA256 == 1) || (CRYPT_MD5 == 1)) 
				{
					$upgrade_password = 1;
				}
			}
        	}
        	else if ((strlen($pass) == 13) && substr($pass,0,1) != '$')
        	{
        		// Standard DES erkannt
        		$salt = substr($pass, 0, 2);
        		
			if (!function_exists('crypt')) 
			{
				// wenn Std. DES nicht im System bekannt ist, Fehlermeldung
				// Kann nur bei Systemänderung vorkommen
				echo "<b>ERROR: Std. DES used, but not supported; SALT $salt</b><br>";
				$salt = "-2";
			}
			else
			{
				if (defined("CRYPT_SHA256") || defined("CRYPT_MD5")) 
				{
					$upgrade_password = 1;
				}
			}
        	}
        	else
        	{
			echo "<b>ERROR: Verschlüsselung nicht erkannt <!-- $pass --></b><br>";
			$salt = "-1";
        	}
        	
	}        		
		
	return $salt;
}

function auth_user($feldname,$login,$passwort) {

	// Passwort prüfen und Userdaten lesen
	// Funktion liefert das mysql_result zurück, wenn auf EINEN User das login/passwort passt
	// $login muss "sicher" kommen
	// feldname = uc_nick oder u_name
	// passwort = Passwort

	global $dbase, $conn;
	global $crypted_password_extern, $upgrade_password;

	// $crypt_login & $md5login ist veraltet und wird nicht mehr unterstützt
	
	$v_salt = getsalt($feldname, $login);

	if ($v_salt == -9)
	{
		// User nicht gefunden
		return(0);
	}
	else if (($v_salt > -9) && ($v_salt < 0))
	{
		echo "<b>ERROR: Passwortverschlüsselung ungültig</b><br>";
		return (0);
	}
	else
	{
		// Nachdem die Verschlüsselung nun bekannt ist
		// Übergebenes PW verschlüsseln und gegen DB Prüfen
		
		if ($crypted_password_extern == 1) 
		{
			// Nichts tun, da das $passwort von einem Externen System bereits
			// verschlüsselt übergeben wurde, daher auch nicht "upgraden"
			$v_passwort = $passwort;
			$upgrade_password = 0;
		}
		else if ($v_salt == 'MD5') 
		{
			$v_passwort = md5($passwort);
		}
		else
		{
			$v_passwort = crypt($passwort, $v_salt);
		}
				  	
                $query = "SELECT * ".
                         "FROM user WHERE $feldname = '$login' ".
                         "AND u_passwort='".addslashes($v_passwort)."'";
        	$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
        	if ($result && mysql_num_rows($result)==1)
        	{
        		$usergefunden = mysql_result($result,0,"u_id");
        		mysql_free_result($result);

        		if ($upgrade_password == 1)
        		{
        			// PW war richtig => PW Verschlüsselung verbessern
				// indem neu gespeichert wird, Verschlüsselung wird in schreibe_db bestimmt
       				unset($f);
      				$f['u_passwort'] = $passwort;
      				$f['u_salt'] = $v_salt; // Dummy, wird nicht gespeichert, nur übermittelt und seperat ausgewertet
       				$f['u_id'] = $usergefunden;	
       				schreibe_db("user",$f,$f['u_id'],"u_id");
        		}

			// Neues PW ist nicht bekannt aber lt. oben richtig, daher neues $result erzeugen
	                $query = "SELECT * FROM user WHERE u_id = $usergefunden ";
	        	$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
        		return($result);
		} 	
		else 
		{
			return(0);
		}
	}
}
?>
