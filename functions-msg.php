<?php

// $Id: functions-msg.php,v 1.54 2012/10/17 06:16:53 student Exp $
// Funktionen, die NUR von interaktiv.php verwendet werden.

require_once("functions.php-func-verlasse_chat.php");
require_once("functions.php-func-nachricht.php");
require_once("functions.php-func-html_parse.php");
require_once("functions.php-func-raum_gehe.php");
if ($communityfeatures) {
	require_once("functions.php-func-sms.php");
	require_once("functions.php-freunde.php");
};

function chat_msg($o_id,$u_id,$u_name,$u_farbe,$admin,$r_id,$text,$typ) {

// Schreibt Text in Raum r_id
// User, u_id, Farbe, Raum und Typ der Nachricht werden übergeben
// Vor Schreiben wird der Text geparsed und die Aktionen ausgeführt
// Art:           N: Normal
//	          S: Systemnachricht
//                P: Privatnachticht
//                H: Versteckte Nachricht
// $raum_einstellungen und $ist_moderiert wurde von raum_ist_moderiert() gesetzt

global $mainchat_version,$akdbase,$conn,$dbase,$user_farbe,$hilfstext,$system_farbe,$moderationsmodul;
global $chat,$timeout,$chat_url,$datei_spruchliste,$t,$id,$ak,$check_name,$f1,$f2,$raumstatus1;
global $u_farbe_alle,$u_farbe_sys,$u_farbe_priv,$u_farbe_noise,$u_farbe_bg,$u_clearedit,$raum_max;
global $u_nick,$id,$http_host,$lobby,$o_raum,$o_js,$o_knebel,$r_status1,$u_level,$leveltext,$max_user_liste;
global $communityfeatures,$o_punkte,$beichtstuhl,$raum_einstellungen,$ist_moderiert,$userdata,$lustigefeatures;
global $punkte_ab_user,$punktefeatures,$whotext,$knebelzeit,$nickwechsel,$raumanlegenpunkte;
global $einstellungen_aendern, $single_room_verhalten, $eingabe_light_farbe, $eingabe_light_hilfe, $grep;

// Text $text parsen, Befehle ausführen, Texte im Chat ausgeben

// Voreinstellung für Nachrichtenfilter
$privat=FALSE;

// N als Voreinstellung setzen
$typ OR $typ="N";

// Farbe voreinstellen
$u_farbe OR $u_farbe=$user_farbe;

// Verbotene Zeichen filtern
$text=preg_replace("/[\\\\".chr(1)."-".chr(31)."]/","",$text);
$text=str_replace(chr(173), "", $text);

// autoknebel - ist text unerwünscht? Falls ja, User automatisch knebeln
$text=auto_knebel($text);

// Eingabe parsen
$chatzeile=preg_split("/ /",$text,4);
if (!isset($chatzeile[1])) $chatzeile[1] = "";
if (!isset($chatzeile[2])) $chatzeile[2] = "";
if (!isset($chatzeile[3])) $chatzeile[3] = "";
                                                                        
switch(strtolower($chatzeile[0])) {

case "/besitze":
case "/besitzeraum":
	// Übernimmt Besitzrechte eines Raums
	if (!$admin):
		system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
	else:
		$query="SELECT r_id,o_name,r_name FROM raum,online ".
			"WHERE r_besitzer=o_user AND ".
                        ($chatzeile[1] ? "r_name LIKE '$chatzeile[1]'" : "r_id=$r_id");
		#print $query;

		$result=mysql_query($query,$conn);
		$rows=mysql_num_rows($result);

		if ($rows==0){
			// nix gefunden. Besitzer evtl. nicht mehr existent?
			// neue query ohne r_besitzer
			$keinbesitzer=1;
			$query="SELECT r_id,r_name FROM raum WHERE ".
				($chatzeile[1] ? "r_name LIKE '$chatzeile[1]'" : "r_id=$r_id");
			$result=mysql_query($query,$conn);
			$rows=mysql_num_rows($result);
		}
                
		if ($rows==1):
			if (!$keinbesitzer) {
				$uu_name=mysql_result($result,0,"o_name");
			} else {
				$uu_name="Nobody";
			}
			$rr_id=mysql_result($result,0,"r_id");
			$rr_name=mysql_result($result,0,"r_name");

			// Raum ändern
			$f['r_besitzer']=$u_id;
			schreibe_db("raum",$f,$r_id,"r_id");
			system_msg("",0,$u_id,$system_farbe,str_replace("%rr_name%",$rr_name,str_replace("%uu_name%",$uu_name,str_replace("%u_name%",$u_name,$t['chat_msg2']))));
		else:
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg3']);
		endif;
	endif;
	break;

case "/develop-ids":
		if (!file_exists($grep))
		{
			system_msg("",0,$u_id,$system_farbe,"<b>config anpassen \$grep</b>");		
		}
		system_msg("",0,$u_id,$system_farbe,"daten werden aufbereitet");

		$txt="<small><pre>$mainchat_version\n\n";
		exec("$grep Id:.*,v *php*", $retval, $ll);
		for($i=0; $i < count($retval); $i++)
		{	
			if (substr($retval[$i], strpos($retval[$i], ":"), 3) == "://") $txt.=$retval[$i]."<br>";
      		}

		$txt.="</pre></small>";

		system_msg("",0,$u_id,$system_farbe,$txt);
		
	break;

case "/oplist":
	// Admins auflisten
	if ($admin || $u_level=="A") {
		$query="SELECT o_userdata,o_userdata2,o_userdata3,o_userdata4,o_level,r_name, ".
			"UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_login) AS online ".
			"FROM online left join raum on r_id=o_raum ".
			"WHERE (o_level='S' OR o_level='C' OR o_level='A') ".
			"ORDER BY r_name";
		$result=mysql_query($query,$conn);
		if ($result AND mysql_num_rows($result)>0) {
			while ($row=mysql_fetch_object($result)) {
				$userdaten=unserialize($row->o_userdata.$row->o_userdata2.$row->o_userdata3.$row->o_userdata4);
				$txt="";
				if ($row->r_name && $row->r_name!="NULL") {
					$raumname=$row->r_name;
				} else {
					$raumname="[".$whotext[2]."]";
				}
				if (!$userdaten[u_away]) {
					$txt.="<B>".$raumname.":</B> ".user($userdaten[u_id],$userdaten,TRUE,TRUE,"&nbsp;",$row->online,"",FALSE);
				} else {
					$txt.=$raumname.": ".user($userdaten[u_id],$userdaten,TRUE,FALSE,"&nbsp;","","",FALSE)." -> ".$userdaten[u_away];
				};
				system_msg("",0,$u_id,$system_farbe,$txt);
			}
			mysql_free_result($result);
		}
	}
break;

case "/op":
	// Nachrichten zwischen den Admins austauschen
case "/int":
	// Admin um Hilfe rufen.
	if ($u_level=="G") {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	} else {

		if ( $o_knebel>0 ) {
			// user ist geknebelt...
			$zeit=gmdate("H:i:s",$o_knebel);
			$txt=str_replace("%zeit%",$zeit,$t['knebel6']);
			system_msg("",0,$u_id,$system_farbe,$txt);
	                break;
		}
		// Smilies parsen
		$privat=TRUE;

		$query="SELECT r_name FROM raum WHERE r_id=$o_raum";
		$result=mysql_query($query,$conn);
		$r_name=mysql_result($result,0,"r_name");
		$tempadm="";
		if (strtolower($chatzeile[0]!="/int")) $tempadm=" OR o_level='A'";

		$query="SELECT o_user FROM online WHERE (o_level='S' OR o_level='C'$tempadm)";
		// debug system_msg("",0,$u_id,$system_farbe,"$query");
		$result=mysql_query($query,$conn);
		if ($result>0) {
	 	    $nums=mysql_num_rows($result);
		    if ($nums>0) {
			// die Nachricht wurde weitergeleitet.
			$txt=html_parse($privat,htmlspecialchars($chatzeile[1]." ".$chatzeile[2]." ".$chatzeile[3]));
			if (!($admin || $u_level=="A")) {
				// Admins wissen, daß das an alle Admins geht. daher nicht nötig.
				system_msg("",0,$u_id,$system_farbe,$t['chat_msg47']);
				$txt="<b>".$txt."</b>";
			}
			if (strtolower($chatzeile[0])=="/int") {
				$txt2="<b>[=====$t[chat_msg52]&nbsp;".$t['chat_msg51']."=====]</b> ".$txt;
			} else {
				$txt2="<b>[".$t['chat_msg52']."&nbsp;".$t['chat_msg50']."]</b> ".$txt;
			};
			while ($row=mysql_fetch_array($result)) {
				$text=str_replace("%user%",user($u_id,$userdata,TRUE,FALSE,"&nbsp;","","",FALSE),$t['chat_msg49']);
				$text=str_replace("%raum%",$r_name,$text);
				if (!($admin || $u_level=="A")) {
					// User aus Raum... ruft um hilfe
					system_msg("",$u_id,$row['o_user'],$system_farbe,$text);
				}
				if (($admin || $u_level=="A") && $row['o_user']==$u_id) {
					// falls eigener nick:
					if (strtolower($chatzeile[0])=="/int") {
						system_msg("",$u_id,$u_id,$system_farbe,"<B>".user($u_id,$userdata,TRUE,FALSE,"&nbsp;","","",FALSE)."&nbsp; ===== $t[chat_msg24] $t[chat_msg51]: =====</B> $txt");
					} else {
						system_msg("",$u_id,$u_id,$system_farbe,"<B>".user($u_id,$userdata,TRUE,FALSE,"&nbsp;","","",FALSE)."&nbsp; $t[chat_msg24] $t[chat_msg50]: </B> $txt");
					};
				} else {
					priv_msg($u_name,$u_id,$row['o_user'],$u_farbe,$txt2,$userdata);
				}
			}
		    } else {
			// kein Admin im Chat :-(
			system_msg("",$u_id,$u_id,$system_farbe,$t['chat_msg48']);
		    }
		    mysql_free_result($result);
		}
	}
	break;

case "/dupes":
	// sucht nach doppelten IPs 
	// ausgabe geändert - werner 25.01.02
	if ($admin) {
		$query="SELECT o_ip,o_user,o_raum,o_browser,r_name,o_name ".
			"FROM online left join raum on o_raum=r_id ".
			"WHERE (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_aktiv)) <= $timeout ".
			"ORDER BY o_ip";
		$result=mysql_query($query,$conn);
		if ($result>0) {
			while ($row=mysql_fetch_array($result)) {
				if ($row['o_ip']==$alt['o_ip']) {
					$hostname=htmlspecialchars(@gethostbyaddr($row['o_ip']));
					if (!$shown) {
						$dupecount++;
						unset($userdaten);
						$userdaten=ARRAY(u_id=>$alt[o_user],u_nick=>$alt[o_name]);
						$txt="<BR><b>".$hostname."(".$alt['o_ip']."):</b>";
						// $txt="<b>".$alt['r_name']." ".user($alt[o_user],$userdaten,$o_js,FALSE,"&nbsp;","","",FALSE)."</b> ".$hostname."(".$alt['o_ip'].") ".htmlspecialchars($alt[o_browser]);
						$txt.="<br><b>".$alt['r_name']." ".user($alt[o_user],$userdaten,$o_js,FALSE,"&nbsp;","","",FALSE)."</b> ".htmlspecialchars($alt[o_browser])."<br>";
						// system_msg("",0,$u_id,$system_farbe,$txt);
						$shown=true;
					}	
					unset($userdaten);
					$userdaten=ARRAY(u_id=>$row[o_user],u_nick=>$row[o_name]);
					// $txt="<b>".$row['r_name']." ".user($row[o_user],$userdaten,$o_js,FALSE,"&nbsp;","","",FALSE)."</b> ".$hostname."(".$row['o_ip'].") ".htmlspecialchars($row[o_browser]);
					$txt.="<b>".$row['r_name']." ".user($row[o_user],$userdaten,$o_js,FALSE,"&nbsp;","","",FALSE)."</b> ".htmlspecialchars($row[o_browser]);
					system_msg("",0,$u_id,$system_farbe,$txt);
					$txt="";
				} else {
					$shown=false;
				}				
				$alt=$row;
			}
			if ($dupecount>0){
				system_msg("",0,$u_id,$system_farbe,"<BR>\n".$t['chat_msg45']);
			} else {
				system_msg("",0,$u_id,$system_farbe,$t['chat_msg46']);
			};
		}
	}
	break; 

case "/pp":
case "/ip":
	if (!$admin)
	{
		system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
		break;
	}

	unset ($onlineip);
	unset ($onlinedatum);
	
	if (!$chatzeile[1])
	{
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg119']);					
		break;
	}

	if ($admin && preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $chatzeile[1]))
	{
		$onlineip = $chatzeile[1];
	}
	else if ($admin)
        {
		$nick=nick_ergaenze($chatzeile[1],"online",1);

                if ($nick['u_nick']!="") 
		{
                        $query="SELECT o_ip FROM online WHERE o_user = $nick[u_id]";
                        $result=mysql_query($query,$conn);
                        if ($result && mysql_num_rows($result)==1)
			{
                                $o_nick=mysql_fetch_array($result);  
				$onlineip = $o_nick[o_ip];
				unset($o_nick);
                        };
                        @mysql_free_result($result);
		}
		else
		{
			system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%", $chatzeile[1], $t['chat_msg120']));

			// Nick nich Online - Hole IP aus History der Usertabelle
                        $query="SELECT u_nick,u_id,u_ip_historie FROM user ".
                                "WHERE u_nick='".coreCheckName($chatzeile[1],$check_name)."'";
                        $result=mysql_query($query,$conn);
                        if ($result && mysql_num_rows($result)==1)
			{
                                $nick=mysql_fetch_array($result);
			        $uu_ip_historie=unserialize($nick['u_ip_historie']);

				if (is_array($uu_ip_historie))
                                {
                                        while(list($onlinedatum,$onlineip)=each($uu_ip_historie)):
					break;
                                        endwhile;
			                $temp = $t['chat_msg123'];
			                $temp = str_replace("%datum%",date("d.m.y H:i",$onlinedatum),$temp);
                			$temp = str_replace("%ip%",$onlineip,$temp);
                			system_msg("",0,$u_id,$system_farbe,$temp); 
                                }
                        }
                        @mysql_free_result($result);

                }
	}
	
	if ($admin && $onlineip)
	{
		$temp = $t['chat_msg122'];
                $temp = str_replace("%datum%",strftime('%d.%m.%Y'),$temp);             
                $temp = str_replace("%uhrzeit%",strftime('%H:%M:%S'),$temp);
                $temp = str_replace("%ip%",$onlineip,$temp);
		system_msg("",0,$u_id,$system_farbe,$temp);					

	        $query="SELECT o_ip,o_user,o_raum,o_browser,r_name,o_name ".
                        "FROM online left join raum on o_raum=r_id ".
                        "WHERE (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_aktiv)) <= $timeout ".
			"AND o_ip = '$onlineip' ". 
                       	"ORDER BY o_user";

                $result=mysql_query($query,$conn); 

		if ($result && mysql_num_rows($result)>=1) 
		{
			$txt = "";
                        while ($row=mysql_fetch_array($result)) 
			{
				$userdaten=ARRAY(u_id=>$row[o_user],u_nick=>$row[o_name]);
				$txt.="<b>".$row['r_name']." ".user($row[o_user],$userdaten,$o_js,FALSE,"&nbsp;","","",FALSE)."</b> ".htmlspecialchars($row[o_browser])."<br>";
			}
                        system_msg("",0,$u_id,$system_farbe,$txt);
                }
		else
		{
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg121']);
		}
                @mysql_free_result($result);
	}
 
break;


case "/edithistory":
case "/edithistorie":

	if ($admin)
	{
			// system_msg("",0,$u_id,$system_farbe,"DEBUG: Du bist ja admin!");


			$nick=nick_ergaenze($chatzeile[1],"online",1);

			// Falls keinen Empfänger gefunden, in Usertabelle nachsehen
			if ($nick['u_nick']=="") {
				$query="SELECT u_nick,u_id from user ".
					"WHERE u_nick='".coreCheckName($chatzeile[1],$check_name)."'";
				$result=mysql_query($query,$conn);
				if ($result && mysql_num_rows($result)==1){
					$nick=mysql_fetch_array($result);
				};
				@mysql_free_result($result);
			};


if ($nick['u_nick'] != "")
	{
	$query="SELECT u_profil_historie FROM user WHERE u_nick = '$nick[u_nick]'";
	$result=mysql_query($query);
	$bla=mysql_fetch_array($result);
	$uu_profil_historie=unserialize($bla[u_profil_historie]);
	#system_msg("",0,$u_id,$system_farbe,"lade history...");
	#system_msg("",0,$u_id,$system_farbe,$query);
	}
	else
	{
	system_msg("",0,$u_id,$system_farbe,"Es konnte kein eindeutiger Nick gefunden werden!");
	}

if (is_array($uu_profil_historie))
					{
					system_msg("",0,$u_id,$system_farbe,"Das Profil von <b>$nick[u_nick]</b> wurde zuletzt geändert von: ");
					while(list($datum,$nick)=each($uu_profil_historie)):
					$zeile= $nick."&nbsp;(".str_replace(" ","&nbsp;",date("d.m.y H:i",$datum)).")".$f4;
					system_msg("",0,$u_id,$system_farbe,$zeile);
					endwhile;
					}
					

}
else
{
system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
}

break;

case "/knebel":
case "/gag":
case "/gaga":
		//   /gag - zeige alle geknebelten user
		//   /gag user - sperre user für 5 min
		//   /gag user x  - sperre user für x min 
		//   /gag user 0  - freigabe...

		if ( !$admin AND $u_level!="A" ) {
			system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
			break;
		}

		if ( !isset($chatzeile[1]) || $chatzeile[1] == "") 
		{
			// 	Kurzhilfe ausgeben.
			$temp = $t['knebel2'];
			$temp = str_replace("%chatzeile%",$chatzeile[0],$temp);
			system_msg("",0,$u_id,$system_farbe,"<b>$chat</b>: $temp");
	
		} else {

			// knebel setzen, default=5 min...
			if (!$knebelzeit) $knebelzeit = 5;

			// knebelzeit übergeben? dann setzen, absolut, vorzeichen ignorieren
			if (isset($chatzeile[2]) && $chatzeile[2] != "") $knebelzeit = abs(intval($chatzeile[2]));
			
			// maximal 1440 Minuten
			if ($knebelzeit > 1440) $knebelzeit = 1440;

			// User finden, zuerst im aktuellen Raum, dann global
			$nick = nick_ergaenze($chatzeile[1],"raum",1);
			if ( !$nick['u_nick'] ) $nick = nick_ergaenze($chatzeile[1],"online",0);
			
			// Admins dürfen,
			if ( $nick['u_nick'] )
			switch ( $nick['u_level'] ) {
				 case "C" :
				 case "S" :
				 case "M" :
				 case "A" : 
					$txt = str_replace("%admin%",$nick['u_nick'],$t['knebel5']);
					system_msg("",0,$u_id,$system_farbe,$txt);
					break;
				 default : // system_msg("",0,$u_id,$system_farbe,"$knebelzeit");
					$query="update online set o_knebel=FROM_UNIXTIME(UNIX_TIMESTAMP(NOW())+$knebelzeit*60) ".
							"where o_user=$nick[u_id]";
					// system_msg("",0,$u_id,$system_farbe,$query);
					mysql_query($query,$conn);
					$txt = ( $knebelzeit ? $t['knebel4'] : $t['knebel3'] );
					$txt = str_replace("%admin%",$u_nick,$txt);
					$txt = str_replace("%user%",$nick['u_nick'],$txt);
					$txt = str_replace("%zeit%",$knebelzeit,$txt);
					global_msg($u_id,$o_raum,$txt);
					// system_msg("",0,$u_id,$system_farbe,$txt);
			}
		}

		// 	knebel listen 
		if ($chatzeile[0] == "/gaga" && $admin)
		{
			$query="select o_id,o_raum,o_user,o_userdata,o_userdata2,o_userdata3,o_userdata4,".
				"UNIX_TIMESTAMP(o_knebel)-UNIX_TIMESTAMP(NOW()) as knebel from online ".
				"where o_knebel>NOW()";
		}
		else
		{
			$query="select o_id,o_raum,o_user,o_userdata,o_userdata2,o_userdata3,o_userdata4,".
				"UNIX_TIMESTAMP(o_knebel)-UNIX_TIMESTAMP(NOW()) as knebel from online ".
				"where o_raum=$o_raum AND o_knebel>NOW()";
		}
		$result=mysql_query($query,$conn);
		$txt="";
		if ($result>0) {
			$rows=mysql_num_rows($result);
			for ($i=0;$i<$rows;$i++) {
				$row=mysql_fetch_object($result);				
				if ($txt!="") $txt .= ", ";
				$userdaten=unserialize($row->o_userdata.$row->o_userdata2.$row->o_userdata3.$row->o_userdata4);
				$txt .= user($row->o_user,$userdaten,TRUE,FALSE,"&nbsp;","","",FALSE)." ".gmdate("H:i:s",$row->knebel);
			}
			mysql_free_result($result);
		} else {
			system_msg("",0,$u_id,$system_farbe,"Fehler beim Zugriff auf die Datenbank. <!--".mysql_errno()." ".mysql_error()."-->");
		}
		if ($txt!="") system_msg("",0,$u_id,$system_farbe,"<b>$chat</b>:$t[knebel1]: ".$txt);
	break;

case "/einlad":
case "/invite":
	// Einladen in Räume... darf nur Admin oder Raumbesitzer
	// Besitzer des aktuellen Raums ermitteln
	$query="SELECT r_besitzer,r_name from raum WHERE r_id=$r_id ";
	$result=mysql_query($query,$conn);
	if ($result>0) {
		$rows=mysql_num_rows($result);
		if ($rows==1) {
			$r_besitzer=mysql_result($result,0,"r_besitzer");
			$r_name=mysql_result($result,0,"r_name");
		}
		mysql_free_result($result);
	}
	if ($admin || $r_besitzer==$u_id) {
		if ($chatzeile[1]!="") {
			// user eintragen oder löschen

			// schaue zuerst, ob online ein User ist, der genauso heisst wie angegeben
			$nick=nick_ergaenze($chatzeile[1],"online",1);
			if ($nick['u_nick'] == $chatzeile[1])
			{
			}
			else
			{
				$nick['u_nick']=="";
			}
			
			if ($nick['u_nick']=="") $nick=nick_ergaenze($chatzeile[1],"raum",1);
			if ($nick['u_nick']=="") $nick=nick_ergaenze($chatzeile[1],"online",1);
			if ($nick['u_nick']=="") $nick=nick_ergaenze($chatzeile[1],"chat",0);
			if ($nick['u_nick']!="") {
				// nick gefunden. jetzt eintragen oder löschen...
				$query="SELECT inv_user FROM invite WHERE inv_raum=$r_id AND inv_user=$nick[u_id]";
				$result=mysql_query($query,$conn);
				if ($result>0) {
					if (mysql_num_rows($result)>0) {
						$query="DELETE FROM invite WHERE inv_raum=$r_id AND inv_user=$nick[u_id]";
						$result2=mysql_query($query,$conn);
						$msg=$t['invite4'];
					} else {
						$f['inv_id']=0;
						$f['inv_user']=$nick['u_id'];
						$f['inv_raum']=$r_id;
						schreibe_db("invite",$f,$f['inv_id'],"inv_id");
						if (!$beichtstuhl) {
							$msg=str_replace("%admin%",$u_nick,$t['invite5']);	
						} else {
							$msg=str_replace("%admin%",$u_nick,$t['invite6']);	
						};
						$msg=str_replace("%raum%",$r_name,$msg);	
						system_msg($u_nick,$u_id,$nick['u_id'],$system_farbe,$msg);
						$msg=$t['invite3'];
					}
					// altes result löschen.
					mysql_free_result($result);

					$msg=str_replace("%admin%",$u_nick,$msg);	
					$msg=str_replace("%raum%",$r_name,$msg);	
					$msg=str_replace("%user%",$nick['u_nick'],$msg);	
					global_msg($u_id,$o_raum,"$msg");
				}
			}
			else
			{
				// Nick nicht gefunden, d.h. nicht Online, aber vielleicht doch eingeladen zum runterwerfen?
				$query="SELECT u_nick,u_id from user,invite ".
					"WHERE inv_raum=$r_id AND inv_user=u_id AND u_nick='$chatzeile[1]'".
					"ORDER BY u_nick";
				$result=mysql_query($query,$conn);
				if ($result && mysql_num_rows($result)==1){
					$row=mysql_fetch_object($result);
					$query2="DELETE FROM invite WHERE inv_raum=$r_id AND inv_user=$row->u_id";
					$result2=mysql_query($query2,$conn);
					$msg=$t['invite4'];
					$msg=str_replace("%admin%",$u_nick,$msg);	
					$msg=str_replace("%raum%",$r_name,$msg);	
					$msg=str_replace("%user%",$row->u_nick,$msg);	
					global_msg($u_id,$o_raum,"$msg");
					@mysql_free_result($result2);
				}
				@mysql_free_result($result);
			}
			// system_msg("",0,$u_id,$system_farbe,"<b>$chat:</b> $nick[u_nick]");
		}
		// Invite-Liste ausgeben...
		$query="SELECT u_id,u_nick,u_level,u_punkte_gesamt,u_punkte_gruppe,inv_id ".
			"FROM invite LEFT JOIN user ON u_id=inv_user ".
			"WHERE inv_raum=$r_id";
		$result=mysql_query($query,$conn);
		$txt="";
		if ($result && mysql_num_rows($result)>0){
			while ($row=mysql_fetch_object($result)) {
				if ($row->u_id && $row->u_id!="NULL") {
					if ($txt!="") $txt.=", ";
					$txt.=user($row->u_id,$row,TRUE,FALSE,"&nbsp;","","",FALSE);
				} else {
					$query="DELETE FROM invite WHERE inv_id=$row->inv_id";
					$result2=mysql_query($query,$conn);
				}
			}
		}
		if ($txt=="") $txt=$t['invite2'];
		else $txt=$t['invite1']." ".$txt;
		system_msg("",0,$u_id,$system_farbe,"<b>$chat:</b> $txt");
	}
	break;

case "/such":
case "/suche":
	// Gibt Spruchliste aus
	if (!isset($chatzeile[1])) $chatzeile[1] = "";
	$suchtext=addslashes(preg_replace("/[*%$!?.,;\\:]/i","",$chatzeile[1]));
	if (strlen($suchtext)>2):

		// Sprüche in Array lesen
		$spruchliste=file("conf/$datei_spruchliste");

		// Treffer ausgeben
		reset($spruchliste);
		$anzahl=count($spruchliste);
		$i=0;
		while ($i<$anzahl):
			$spname=key($spruchliste);
			if (preg_match("/".$suchtext."/i",$spruchliste[$spname])):
				$spruchtmp=preg_split("/\t/",substr($spruchliste[$spname],0,strlen($spruchliste[$spname])-1),3);
				
				$spruchtmp[2] = str_replace("<", "&lt;", $spruchtmp[2]);
				$spruchtmp[2] = str_replace(">", "&gt;", $spruchtmp[2]);
		                $spruchtmp[2] = preg_replace('|\*(.*?)\*|','<i>\1</i>',     
                		      preg_replace('|_(.*?)_|','<b>\1</b>',$spruchtmp[2]));

				$txt="<B>$t[chat_msg4] <I>".
					$spruchtmp[0]." ".
					$spruchtmp[1]."</I></B> &lt;".
					$spruchtmp[2]."&gt;";
				system_msg("",0,$u_id,$system_farbe,$txt);
			endif;
			next($spruchliste);
			$i++;
		endwhile;

	else:
		// Fehler ausgeben
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg5']);
	endif;
	break;

case "/ende":
case "/exit":
case "/quit":
case "/bye":
	// Ende-Nachricht absetzen?
	if (strlen($chatzeile[1])>0):
		hidden_msg($u_name,$u_id,$u_farbe,$r_id,$u_name."$t[chat_msg6] ".html_parse($privat,htmlspecialchars($chatzeile[1]." ".$chatzeile[2]." ".$chatzeile[3])));
	endif;

	// Logoff
	verlasse_chat($u_id,$u_name,$r_id);
	sleep(2);
	logout($o_id,$u_id, "/quit");

	echo "<HTML><BODY onLoad='javascript:parent.location.href=\"index.php?http_host=$http_host\";'>\n";
		echo "</BODY></HTML>\n";
	exit;
	break;

case "/ig":
case "/ignore":
case "/ignoriere":
	// User ignorieren oder freigeben
	
	if (!$beichtstuhl && strlen($chatzeile[1])>0 && $u_level!="G"){
		// Username angegeben -> ignorieren oder aufheben

		// versuchen, den namen zu ergänzen.
		// wenn geschafft, dann ist User auch online. Somit kann er ignoriert werden.
		// sonst evtl. DB Probs weil der expire evtl. user löscht, die hier gerade 
		// eingetragen werden.
		$nick=nick_ergaenze($chatzeile[1],"online",0);
		if ($nick['u_nick']!=""){
			$i_user_passiv=$nick['u_id'];
			$i_user_name_passiv=$nick['u_nick'];
			
			// testen, ob nicht DAU sich selbst ignoriert...
			if ($i_user_passiv!=$u_id) {
				ignore($o_id,$u_id,$u_name,$i_user_passiv,$i_user_name_passiv);
			} else {
				system_msg("",0,$u_id,$u_farbe,$t['chat_msg7']);
			}
		} else {
			// system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t[chat_msg8]));
			// user nicht gefunden oder nicht online? dann testen, ob ignoriert, damit man das 
			// ignore auch wieder raus bekommt.
			$query="SELECT u_nick,u_id from user,iignore ".
				"WHERE i_user_aktiv=$u_id AND i_user_passiv=u_id AND u_nick='$chatzeile[1]'".
				"ORDER BY u_nick";
			$result=mysql_query($query,$conn);
			if ($result && mysql_num_rows($result)==1){
				$row=mysql_fetch_object($result);
				ignore($o_id,$u_id,$u_name,$row->u_id,$row->u_nick);
			}
			@mysql_free_result($result);
		}
	}


	// Prüfung sollte nach dem Einfügen sein, denn sonst können 17 User drauf sein
        // Prüfung, ob bereits mehr als 16 user ignoriert werden
        // Alle ignorierten user > 16 werden gelöscht
        // Wenn mehr als 16 gebraucht werden, grössere Änderung in Tabelle online Feld o_ignore
        // Denn da passt nur ein Array mit 255 chars rein.
        $query="SELECT u_nick,u_id from user,iignore ".
                "WHERE i_user_aktiv=$u_id AND u_id=i_user_passiv order by i_id";
        $result=@mysql_query($query,$conn); 
        $anzahl=@mysql_num_rows($result)-16;

	# system_msg("",0,$u_id,$system_farbe,"DEBUG: $anzahl");

        if ($result && $anzahl>0){
                for ($i=0; $i<$anzahl; $i++) { 
                        $row=@mysql_fetch_object($result);
                        ignore($o_id,$u_id,$u_name,$row->u_id,$row->u_nick);
                };
        }
        @mysql_free_result($result);



	if ($u_level=="G") {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	} else {

		// Liste der User ausgeben, die von mir ignoriert werden
		$query="SELECT i_id,u_id,u_nick,u_level,u_punkte_gesamt,u_punkte_gruppe ".
			"FROM iignore LEFT JOIN user on i_user_passiv=u_id ".
			"WHERE i_user_aktiv=$u_id ".
			"ORDER BY u_nick,i_id";
		$result=mysql_query($query,$conn);
			
		if ($result && mysql_num_rows($result)>0) {
			$i=0;
			$text=str_replace("%u_name%",$u_name,$t['chat_msg10']);
			while ($row=mysql_fetch_object($result)) {

				if ($row->u_id && $row->u_id!="NULL") {
					if ($i>0) $text=$text.", ";
					$text=$text.user($row->u_id,$row,TRUE,FALSE,"&nbsp;","","",FALSE);
				} else {
					$query="DELETE FROM iignore WHERE i_id=$row->i_id";
					$result2=mysql_query($query,$conn);
				}
				$i++;

			};
			system_msg("",0,$u_id,$system_farbe,$text.""); 
		} else {
			system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%",$u_name,$t['chat_msg9']));
		};
		@mysql_free_result($result);

		// Liste der User ausgeben, die mich ignorieren
		$query="SELECT i_id,u_id,u_nick,u_level,u_punkte_gesamt,u_punkte_gruppe ".
			"FROM iignore LEFT JOIN user on i_user_aktiv=u_id ".
			"WHERE i_user_passiv=$u_id ".
			"ORDER BY u_nick,i_id";
		$result=mysql_query($query,$conn);
			
		if ($result && mysql_num_rows($result)>0) {
			$i=0;
			$text=str_replace("%u_name%",$u_name,$t['chat_msg82']);
			while ($row=mysql_fetch_object($result)) {

				if ($row->u_id && $row->u_id!="NULL") {
					if ($i>0) $text=$text.", ";
					$text=$text.user($row->u_id,$row,TRUE,FALSE,"&nbsp;","","",FALSE);
				} else {
					$query="DELETE FROM iignore WHERE i_id=$row->i_id";
					$result2=mysql_query($query,$conn);
				}
				$i++;

			};
			system_msg("",0,$u_id,$system_farbe,$text.""); 
		};
		@mysql_free_result($result);
	};

	break;

case "/channel":
case "/go":
case "/raum":
case "/rooms":
case "/j":
case "/join":
	if ((($u_level == "G") || ($u_level == "U")) && ($single_room_verhalten == "1"))
	{
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
	}
	else if ( $chatzeile[1] ) {
		
		// Rauname erzeugen
		$chatzeile[1] = preg_replace("/[ \\'\"]/","",$chatzeile[1]);
		$f['r_name']=htmlspecialchars($chatzeile[1]);
                 
                if (!isset($chatzeile[2])) $chatzeile[2] = "";
		// Admin oder Raumbesitzer darf das Betreten geschlossener Räume erzwingen (Prüfung in raum_gehe)
		if ($beichtstuhl || $chatzeile[2]=="immer" || $chatzeile[2]=="force" || $chatzeile[2]=="!") {
			$raum_geschlossen=TRUE;
		} else {
			$raum_geschlossen=FALSE;
		};

		// Raum wechseln 
		$query="SELECT r_id,(LENGTH(r_name)-length('$chatzeile[1]')) as laenge ".
			"FROM raum ".
			"WHERE r_name like '$chatzeile[1]%' ".
			"ORDER BY laenge";
		$result=mysql_query($query,$conn);

		if (mysql_num_rows($result)!=0) {
			$r_id_neu=mysql_result($result,0,"r_id");
			if ($r_id_neu!=$r_id) {
				$r_id=raum_gehe($o_id,$u_id,$u_name,$r_id,$r_id_neu,$raum_geschlossen);
				raum_user($r_id,$u_id,"");
			}
		} elseif ($u_level!="G" && strlen($f['r_name'])<=$raum_max && strlen($f['r_name'])>3) {
			// Neuen Raum anlegen, ausser User ist Gast
			$f['r_topic']=htmlspecialchars($u_name.$t['chat_msg56']);
			$f['r_eintritt']="";
			$f['r_austritt']="";
			$f['r_status1']="O";
			$f['r_status2']="T";
			$f['r_besitzer']=$u_id;
			$raumanlegen=true;
			
			// ab hier neu: wir prüfen ob community an ist, der User kein Admin und $raumanlegenpunkte gesetzt

			if ($communityfeatures && !$admin && $raumanlegenpunkte)
                	{
	                $result=mysql_query("select u_punkte_gesamt FROM user WHERE u_id=$u_id");
        	        if ($result && mysql_num_rows($result)==1) { $u_punkte_gesamt=mysql_result($result,0,0); }

			#system_msg("",0,$u_id,$system_farbe,"raumanlegenpunkte = $raumanlegenpunkte, u_punkte_gesamt=$u_punkte_gesamt");
			
        	        if (  $u_punkte_gesamt < $raumanlegenpunkte )
                                {
				// Fehlermeldung und Raum nicht anlegen
                                system_msg("",0,$u_id,$system_farbe,str_replace("%punkte%",$raumanlegenpunkte,$t['chat_msg108']));
				$raumanlegen=false;
                                }
                	}
			
			if ($raumanlegen==true)
			{
				// Hier wird der Raum angelegt
				$r_id_neu=schreibe_db("raum",$f,$f['r_id'],"r_id");
					if ($r_id_neu!=$r_id) {
					$r_id=raum_gehe($o_id,$u_id,$u_name,$r_id,$r_id_neu,$raum_geschlossen);
					raum_user($r_id,$u_id,"");
					}

			};
		} else {
			// Fehlermeldung
			system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg11']));
		}
        	mysql_free_result($result);
	} else {
		// Liste alle Räume auf
		if ($admin)
		$query="SELECT r_name from raum order by r_name";
		else
		$query="SELECT r_name from raum WHERE r_status1 = 'O' order by r_name";

		$result=mysql_query($query,$conn);
		$rows=mysql_num_rows($result);
		$text=$t['chat_msg12']." ";
		$i=0;
		while ($rows>$i):
			$text=$text.mysql_result($result,$i,"r_name");
			$i++;
			if ($i<$rows):
				$text=$text.", ";
			endif;
		endwhile;
		system_msg("",0,$u_id,$system_farbe,$text);
		mysql_free_result($result);
	}
	break; 

case "/wer":
case "/who":
case "/w":
case "/user":
case "/list":
		// User listen 
		if ($chatzeile[1]=="*") { 

			// * zeigt alle Räume
			$chatzeile[0]="/people";

		} elseif ($chatzeile[1]!="") {

			$query="SELECT r_id from raum WHERE r_name LIKE '$chatzeile[1]%' ";
			if (!$admin) $query.=" AND (r_status1='O' OR r_status1='m' OR r_id=$o_raum) ";	
			$result=mysql_query($query,$conn);
			if ($result AND mysql_num_rows($result)>0):
				$text=$t['chat_msg12']."<br>";
				$row=mysql_fetch_object($result);
				raum_user($row->r_id,$u_id,"");
				mysql_free_result($result);
			else:
				// Raum nicht gefunden -> Fehlermeldung
				$txt=str_replace("%raumname%",$chatzeile[1],$t['chat_msg53']);
				system_msg("",$u_id,$u_id,$system_farbe,$txt);
			endif;
			break;
			
		} else {

			// Der aktuelle Raum wird gezeigt
			raum_user($r_id,$u_id,"");
			break;
		};

case "/people":
       // Liste alle Räume mit Usern auf
	$query="SELECT r_id from raum ";
	if (!$admin) $query.=" WHERE (r_status1='O' OR r_status1='m' OR r_id=$o_raum) ";	
	$query.=" ORDER BY r_name";
	$result=mysql_query($query,$conn);
	if ($result AND mysql_num_rows($result)>0):
		$text=$t['chat_msg12']."<br>";
		while ($row=mysql_fetch_object($result)):
			raum_user($row->r_id,$u_id,"");
		endwhile;
		mysql_free_result($result);
	endif;
	break;

case "/txt":
case "/me":
	// Spruch ausgeben 
	if ($u_level=="G") {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	} else {
		if (!($o_knebel>0)) {
			if ($u_level=="M" || !$ist_moderiert) {
				hidden_msg($u_name,$u_id,$u_farbe,$r_id,$u_name." ".html_parse($privat,htmlspecialchars($chatzeile[1]." ".$chatzeile[2]." ".$chatzeile[3]),1));
			} else {
				system_msg("",$u_id,$u_id,$system_farbe,$t['moderiert1']);
			}
		} else {
			// user ist geknebelt...
			$zeit=gmdate("H:i:s",$o_knebel);
			$txt=str_replace("%zeit%",$zeit,$t['knebel6']);
			system_msg("",$u_id,$u_id,$system_farbe,$txt);
		}
	}
	break;

case "/away":
case "/weg":
	// Away-Text setzen oder löschen
	// Smilies parsen
	$privat=FALSE;

	if ($u_level=="G") {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
		// User ist Gast
	} elseif ($o_knebel>0) {
		// User ist noch geknebelt
		$zeit=gmdate("H:i:s",$o_knebel);
		$txt=str_replace("%zeit%",$zeit,$t['knebel6']);
		system_msg("",0,$u_id,$system_farbe,$txt);
	} else {
		$away=html_parse($privat,htmlspecialchars(substr(trim($chatzeile[1]." ".$chatzeile[2]." ".$chatzeile[3]),0,80)));
		if ($away=="") {
			$text="$u_nick $t[away2]";
			$f['u_away']="";
			schreibe_db("user",$f,$u_id,"u_id");
		} else {
			$text="$u_nick $t[away1] $away";
			$f['u_away']=addslashes($away);
			schreibe_db("user",$f,$u_id,"u_id");
		}
		// Bei Moderation private Nachricht, sonst Nachricht an alle
		if (!$ist_moderiert || $u_level=="M") {
			global_msg($u_id,$r_id,$text);
		} else {
			system_msg("",$u_id,$u_id,$system_farbe,$text);
		}
	}
	break;

case "/schubs":
		// Nur für Admins und Tempadmins, schubst User in einen andere Raum
		if ($admin || $u_level=="A") {
			$user=nick_ergaenze($chatzeile[1],"raum",1);
			if ($user['u_nick']=="") $user=nick_ergaenze($chatzeile[1],"online",1);
			if ($user['u_nick']!="") {
				$raum=$chatzeile[2];
				if ($raum=="") {
					$query="select r_id,r_name from raum where r_id=$o_raum";
				} else {
					$query="select r_id,r_name from raum where r_name like '$raum%'";
				}
				$result=mysql_query($query,$conn);
				if ($result>0) {
					$raumid=mysql_result($result,0,"r_id");
					$raum=mysql_result($result,0,"r_name");
					mysql_free_result($result);
				}
				$query="select o_raum,o_id,r_name from online,raum where o_user=$user[u_id] AND r_id=o_raum";
				$result=mysql_query($query,$conn);
				if ($result>0) {
					$raumalt=mysql_result($result,0,"r_name");
					$uo_id=mysql_result($result,0,"o_id");
					$uo_raum=mysql_result($result,0,"o_raum");
					mysql_free_result($result);
				}		
				// $text="Schubbern...$user[u_nick]/$user[u_id] $raumalt/$uo_raum -&gt; $raum/$raumid";
				$text="<b>$chat:</b> $user[u_nick]: $raumalt -&gt; $raum";
				system_msg("",0,$u_id,$system_farbe,$text);
				global_msg($u_id,$o_raum,"'$u_name' $t[sperre2] '$user[u_nick]' $t[sperre8] $raum");
				raum_gehe($uo_id,$user['u_id'],$user['u_nick'],$uo_raum,$raumid,TRUE);
			}
		}
	break;

case "/plop":
case "/kick":
	// Besitzer des aktuellen Raums ermitteln
	$query="SELECT r_besitzer from raum ".
		"WHERE r_id=$r_id ";
	$result=mysql_query($query,$conn);
	$rows=mysql_num_rows($result);
                
	if ($rows==1):
		$r_besitzer=mysql_result($result,0,"r_besitzer");
	endif;
	mysql_free_result($result);


	// Nur für Admins und Tempadmins, wirft User aus dem aktuellen Raum oder hebt Sperre auf
	if (strlen($chatzeile[1])>0)
	{
		// User kicken....

		// User aus Raum werfen oder Sperre freigeben, falls $u_id ist Admin oder Besitzer
		if ($r_besitzer==$u_id || $admin || $u_level=="A"):

			// Ist User im aktuellen Raum eingeloggt?
			$query="SELECT o_user,o_name,o_level,(LENGTH(o_name)-length('$chatzeile[1]')) as laenge ".
				"FROM online ".
				"WHERE o_name LIKE '%$chatzeile[1]%' ".
				"ORDER BY laenge";

			$result=mysql_query($query,$conn);
			$rows=mysql_num_rows($result);
       	         
			// User bestimmen
			if ($rows!=0):
				$s_user=mysql_result($result,0,"o_user");
				$s_user_name=mysql_result($result,0,"o_name");
				$s_user_level=mysql_result($result,0,"o_level");

				// Auf admin prüfen
				if ($s_user_level=="S" ||  $s_user_level=="C" || $s_user_level=="M" || $s_user_level=="A" ):
					system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%",$u_name,str_replace("%s_user_name%",$s_user_name,$t['chat_msg13'])));
				else:
					// Sperren oder freigeben
					sperre($r_id,$u_id,$u_name,$s_user,$s_user_name,$admin);
				endif;

			else:

				// user nicht gefunden oder nicht online? dann testen, ob gesperrt, damit man das 
				// gesperrt auch wieder raus bekommt.
				$query="SELECT u_nick,u_id from user left join sperre ".
					" on u_id = s_user ".
					"WHERE u_nick='$chatzeile[1]' and s_raum = $r_id ";
				$result=mysql_query($query,$conn);
				if ($result && mysql_num_rows($result)==1)
				{
					$row=mysql_fetch_object($result);
                                        sperre($r_id,$u_id,$u_name,$row->u_id,$row->u_nick,$admin);
				}
				else
				{
					system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg14']));
				}
				@mysql_free_result($result);                                                                                                                                                                                                                                                                                                
			endif;
	       	 	@mysql_free_result($result);
		else:
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg15']);
		endif;

	}
	else if ($r_besitzer==$u_id || $admin || $u_level=="A")
	{
		// Liste der für diesen Raum gesperrten User ausgeben

		$query="SELECT u_nick FROM sperre,user ".
			"WHERE s_user=u_id ".
			"AND s_raum=$r_id ".
			"ORDER BY u_nick";
		$result=mysql_query($query,$conn);
		$rows=mysql_num_rows($result);

		if ($rows>0)
		{
			$text=$t['chat_msg16']." ";
			$i=0;
			while ($rows>$i)
			{
				$text=$text.mysql_result($result,$i,"u_nick");
				$i++;
				if ($rows > $i)
				{
					$text=$text.", ";
				}
			}
		}
		else
		{
			$text=$t['chat_msg17'];
		}
		system_msg("",0,$u_id,$system_farbe,$text);
		mysql_free_result($result);
	}
	else
	{
		// Keine Berechtigung zur Ausgabe der gesperrten user
		system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
	}

	break;

case "/name":
case "/nick":

	// Prüft und ändert Nicknamen
	if (!$einstellungen_aendern) :
		system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
	elseif((strlen($chatzeile[1])>0) && (coreCheckName($chatzeile[1],$check_name)!=$u_name)):

	// if ((strlen($chatzeile[1])>0) && ($chatzeile[1]!=$u_name) && ($u_level!="G")):

				$query="SELECT u_nick_historie FROM user WHERE u_id = '$u_id'";
				$result=mysql_query($query);
				$xyz=mysql_fetch_array($result);
				$nick_historie=unserialize($xyz['u_nick_historie']);
				
				if (is_array($nick_historie)):
					reset ($nick_historie);
					list($key, $value) = each ($nick_historie);
					$differenz=time()-$key;
				endif;
				if (!isset($differenz)) $differenz=999;
				if ($admin) $differenz=999;

		// Sonderzeichen filtern
		$chatzeile[1]=coreCheckName($chatzeile[1],$check_name);
		
		// Länge prüfen
		if (strlen($chatzeile[1])<4 || strlen($chatzeile[1])>20  || $differenz < $nickwechsel):
		if ($differenz<$nickwechsel) 
					 system_msg("",$u_id,$u_id,$system_farbe,str_replace("%nickwechsel%",$nickwechsel,$t['chat_msg107']));
					else
					 system_msg("",$u_id,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg18']));


		else:
			$f['u_nick'] = $chatzeile[1];
       	        	$query="SELECT u_id, u_level ".
                        	"FROM user ". 
                        	"WHERE u_nick LIKE '$f[u_nick]' ".
				"AND u_id != $u_id";

                	$result=mysql_query($query,$conn);
                	$rows=mysql_num_rows($result);
                	if ($rows!=0):
				if ($rows == 1)
				{
					$xyz=mysql_fetch_array($result);
					if ($xyz[u_level] == 'Z')
					{
						system_msg("",$u_id,$u_id,$system_farbe,str_replace("%u_nick%",$f['u_nick'],$t['chat_msg115']));
					}
					else
					{
						system_msg("",$u_id,$u_id,$system_farbe,str_replace("%u_nick%",$f['u_nick'],$t['chat_msg19']));
					}
				}
				else
				{
					system_msg("",$u_id,$u_id,$system_farbe,str_replace("%u_nick%",$f['u_nick'],$t['chat_msg19']));
				}
			else:  
				// Nick in Datenbank ändern
				schreibe_db("user",$f,$u_id,"u_id");

				// Bei Moderation private Nachricht, sonst Nachricht an alle
				if (!$ist_moderiert || $u_level=="M") {
					global_msg($u_id,$r_id,str_replace("%u_nick%",$f['u_nick'],str_replace("%u_name%",$u_name,$t['chat_msg20'])));
				} else {
					system_msg("",$u_id,$u_id,$system_farbe,str_replace("%u_nick%",$f['u_nick'],str_replace("%u_name%",$u_name,$t['chat_msg20'])));
				};
				// Neuen Namen setzen und alte Nicknamen in DB speichern

				$query="SELECT u_nick_historie FROM user WHERE u_id = '$u_id'";
				$result=mysql_query($query);
				$xyz=mysql_fetch_array($result);
				$nick_historie=unserialize($xyz['u_nick_historie']);
				
				$datum=time();
				$nick_historie_neu[$datum]=$u_name;
				
				if (is_array($nick_historie)):
			        $i=0;
			        	while(($i<3) AND list($datum,$nick)=each($nick_historie)):
			                	$nick_historie_neu[$datum]=$nick;
		        	        	$i++;
				        endwhile;
				endif;

				$f['u_nick_historie']=serialize($nick_historie_neu);
				$u_id=schreibe_db("user",$f,$u_id,"u_id");

				$u_name=$f['u_nick'];
			endif;
	        	mysql_free_result($result);
		endif;
	endif;
	break;

case "/color":
case "/farbe":

        if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
	{
		system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
	}
	// Zeigt oder ändert Farbe
	else if (strlen($chatzeile[1])==6)
	{
		if (preg_match("/[a-f0-9]{6}/i", $chatzeile[1])) 
		{
			if (isset($f)) unset($f); 
			$f['u_farbe']=substr(htmlspecialchars($chatzeile[1]),0,6);
			schreibe_db("user",$f,$u_id,"u_id");
			system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$f['u_farbe'],$t['chat_msg21']));
		}
		else
		{
   			system_msg("",0,$u_id,$system_farbe,$t['chat_msg23']);
		}
	}
	else if (strlen($chatzeile[1])==0)
	{
		system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$u_farbe,$t['chat_msg22']));
	}
	else
	{
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg23']);
	}
	break;

case "/farbset":

	if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
	{	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
	} 
	else 
	{
	
	switch($chatzeile[1]) {
	case "1":
	$f['u_farbe']="0000c0";
	$f['u_farbe_alle']="202020";
	$f['u_farbe_priv']="c02020";
	$f['u_farbe_sys']="55555";
        $f['u_farbe_bg']="f0f0f0";
        $f['u_farbe_noise']="008000";
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%nummer%",$chatzeile[1],$t['chat_msg28']));
		break;
	case "2":
        $f['u_farbe']="5050c0";
        $f['u_farbe_alle']="2222cc";
        $f['u_farbe_priv']="f06000";
        $f['u_farbe_sys']="0070e0";
        $f['u_farbe_bg']="d0f0ff";
        $f['u_farbe_noise']="2090b0";
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%nummer%",$chatzeile[1],$t['chat_msg28']));
		break;
	case "3":
        $f['u_farbe']="424242";
        $f['u_farbe_alle']="0000c0";
        $f['u_farbe_priv']="ff4040";
        $f['u_farbe_sys']="707070";
        $f['u_farbe_bg']="ffffff";
        $f['u_farbe_noise']="008000";
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%nummer%",$chatzeile[1],$t['chat_msg28']));
		break;
	case "4":
        $f['u_farbe']="000000";
        $f['u_farbe_alle']="0000ff";
        $f['u_farbe_priv']="ff0000";
        $f['u_farbe_sys']="000000";
        $f['u_farbe_bg']="ffffff";
        $f['u_farbe_noise']="0000ff";
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%nummer%",$chatzeile[1],$t['chat_msg28']));
		break;
	default:
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg29']);
		break;
	}
	}
	break;

case "/farbreset":

	if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
	{	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
	} 
	else 
	{
        $f['u_farbe_alle']="-";
        $f['u_farbe_priv']="-";
        $f['u_farbe_sys']="-";
        $f['u_farbe_bg']="-";
        $f['u_farbe_noise']="-";
        $f['u_farbe']=$user_farbe;
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,$t['chat_msg27']);
        }
	break;

case "/color2":
case "/farbe2":
case "/farbealle":

	if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
	{	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
	} 
	else 
	{

	// Zeigt oder ändert Farbe 2 (Farbe für alle anderen User)
	if ($chatzeile[1]=="off") $chatzeile[1]="-";
	if ($chatzeile[1]=="aus") $chatzeile[1]="-";
	if (strlen($chatzeile[1])==6 || $chatzeile[1]=="-"):
        	$f['u_farbe_alle']=substr(htmlspecialchars($chatzeile[1]),0,6);
        	schreibe_db("user",$f,$u_id,"u_id");
        	system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$f['u_farbe_alle'],$t['chat_msg21']));
        elseif (strlen($chatzeile[1])==0):
        	system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$u_farbe_alle,$t['chat_msg22']));
	else:
        	system_msg("",0,$u_id,$system_farbe,$t['chat_msg23']);
        	endif;
	}
	break;

case "/farbe3";
case "/color3";
case "/farbenoise";

    if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
    {	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
    } 
    else 
    {

    // Zeigt oder ändert Farbe 3 (Farbe für noises)
    if ($chatzeile[1]=="off") $chatzeile[1]="-";
    if ($chatzeile[1]=="aus") $chatzeile[1]="-";
    if (strlen($chatzeile[1])==6 || $chatzeile[1]=="-"):
        $f['u_farbe_noise']=substr(htmlspecialchars($chatzeile[1]),0,6);
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$f['u_farbe_noise'],$t['chat_msg21']));
    elseif (strlen($chatzeile[1])==0):
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$u_farbe_noise,$t['chat_msg22']));
    else:
        system_msg("",0,$u_id,$system_farbe,$t['chat_msg23']);
    endif;
    }
    break;

case "/color4":
case "/farbe4":
case "/farbepriv":

    if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
    {	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
    } 
    else 
    {

    // Zeigt oder ändert Farbe 4 (Farbe für privat) 
    if ($chatzeile[1]=="off") $chatzeile[1]="-";
    if ($chatzeile[1]=="aus") $chatzeile[1]="-";
    if (strlen($chatzeile[1])==6 || $chatzeile[1]=="-"):
        $f['u_farbe_priv']=substr(htmlspecialchars($chatzeile[1]),0,6);
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$f['u_farbe_priv'],$t['chat_msg21']));
    elseif (strlen($chatzeile[1])==0):
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$u_farbe_priv,$t['chat_msg22']));
    else:
        system_msg("",0,$u_id,$system_farbe,$t['chat_msg23']);
    endif;
    }
    break;

case "/color5":
case "/farbe5":
case "/farbebg":

    if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
    {	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
    } 
    else 
    {
    // Zeigt oder ändert Farbe 5 (Farbe für Background) 
    if ($chatzeile[1]=="off") $chatzeile[1]="-";
    if ($chatzeile[1]=="aus") $chatzeile[1]="-";
    if (strlen($chatzeile[1])==6 || $chatzeile[1]=="-"):
        $f['u_farbe_bg']=substr(htmlspecialchars($chatzeile[1]),0,6);
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$f['u_farbe_bg'],$t['chat_msg21']));
    elseif (strlen($chatzeile[1])==0):
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$u_farbe_bg,$t['chat_msg22']));
    else:
        system_msg("",0,$u_id,$system_farbe,$t['chat_msg23']);
    endif;
    }
    break;

case "/color6":
case "/farbe6":
case "/farbesys":


    if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_farbe == "1"))
    {	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));    
    } 
    else 
    {
    // Zeigt oder ändert Farbe 6 (Farbe für Systemmeldungen) 
    if ($chatzeile[1]=="off") $chatzeile[1]="-";
    if ($chatzeile[1]=="aus") $chatzeile[1]="-";
    if (strlen($chatzeile[1])==6 || $chatzeile[1]=="-"):
        $f['u_farbe_sys']=substr(htmlspecialchars($chatzeile[1]),0,6);
        schreibe_db("user",$f,$u_id,"u_id");
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$f['u_farbe_sys'],$t['chat_msg21']));
    elseif (strlen($chatzeile[1])==0):
        system_msg("",0,$u_id,$system_farbe,str_replace("%u_farbe%",$u_farbe_sys,$t['chat_msg22']));
    else:
        system_msg("",0,$u_id,$system_farbe,$t['chat_msg23']);
    endif;
    }	
    break;

case "/help":
case "/hilfe":
case "/?":

    if ((($u_level == "G") || ($u_level == "U")) && ($eingabe_light_hilfe == "1"))
    {	  
                system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));
    } 
    else 
    {

	// Hilfe listen 
        reset($hilfstext);
        $anzahl=count($hilfstext);
        $i=0;                       
        while ($i<$anzahl):
		$hhkey=key($hilfstext);
		system_msg("",0,$u_id,"","<SMALL>".$hilfstext[$hhkey]."</SMALL>");
		next($hilfstext);
		$i++;
	endwhile;
    }
    break;

case "/clearedit":
	// löschen der Eingabezeile erzwingen oder alten Text stehen lassen
	// alter Text wird dann zum überschreiben markiert.
	if ($u_clearedit==0) {
        $f['u_clearedit']="1";
        system_msg("",0,$u_id,$system_farbe,$t['chat_msg30']);
	} else {
        $f['u_clearedit']="0";
        system_msg("",0,$u_id,$system_farbe,$t['chat_msg31']);
	}
	schreibe_db("user",$f,$u_id,"u_id");
	break;

case "/dice":
case "/wuerfel":
	// Würfeln
	if ($u_level=="G") {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	} else {

                if ( $o_knebel>0 ) {
			// user ist geknebelt...
			$zeit=gmdate("H:i:s",$o_knebel);
			$txt=str_replace("%zeit%",$zeit,$t['knebel6']);
			system_msg("",0,$u_id,$system_farbe,$txt);
			break;
		}
                                                                                                                                                        
                                                                                                                                                        
		if (preg_match("!\d+[wW]\d+!",$chatzeile[1])) {			

			$w=preg_split("![wW]!",$chatzeile[1]);
			if ($w[0]>30) $w[0]=30;
			if ($w[1]==0) $w[1]=6;
			$t['chat_msg34']=str_replace("%user%",$u_name,$t['chat_msg34']);
			if ($w[0]==1) $tmp="einem "; else $tmp=htmlspecialchars("$w[0] ");
			if (preg_match("!w!",$chatzeile[1])) $tmp=$tmp."kleinen "; else $tmp=$tmp."großen ";
			if ($w[0]==1) 
			{
				$tmp=$tmp.htmlspecialchars(" $w[1]-seitigen Würfel");
			}
			else
			{
				$tmp=$tmp.htmlspecialchars(" $w[1]-seitigen Würfeln");
			}
			$t['chat_msg34']=str_replace("%wuerfel%",$tmp,$t['chat_msg34']);

			$summe=0;
			//mt_srand((double)microtime()*1000000);
			for ($i=0; $i<$w[0]; $i++) {
				$wurf=mt_rand(1,$w[1]);
				$summe=$summe+$wurf;
				$t['chat_msg34']=$t['chat_msg34'] . " $wurf";	
			}
			if ($w[0]>1) $t['chat_msg34']=$t['chat_msg34'] . ". Summe=$summe.";	

			// Bei Moderation private Nachricht, sonst Nachricht an alle
			if (!$ist_moderiert || $u_level=="M") {
				hidden_msg($u_name,$u_id,$u_farbe,$r_id,$t['chat_msg34']);
			} else {
				system_msg("",$u_id,$u_id,$system_farbe,$t['moderiert1']);
			}

		} else {
			system_msg("",$u_id,$u_id,$system_farbe,$t['chat_msg35']);
		}
	}
	break;

case "/zeige":
case "/whois":
        if ($u_level=="G") {
                system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
        } else {
		if (strlen($chatzeile[1])>=3) {
			$chatzeile[1]=str_replace("$","",$chatzeile[1]);
			$chatzeile[1]=str_replace("^","",$chatzeile[1]);

			if  (preg_match("/%/",$chatzeile[1])) $sucheper = "LIKE"; else $sucheper = "=";

        		if ((strcasecmp($chatzeile[1],"gast")==0) && ($admin)) 
			{
				// suche "/whois gast" zeigt alle Gäste und den  User gastwenn vorhanden
	                       	$query="SELECT *,date_format(u_login,'%d.%m.%y %H:%i') as login ".
        	                       	"FROM user WHERE (u_nick LIKE '$chatzeile[1]') or (u_level = 'G') ".
                	               	"ORDER BY u_nick,u_name limit $max_user_liste";
			}
			else if (($admin) || ($u_level == "A"))
			{
				// suche für Admins und Tempadmins zeigt alle User
				$query="SELECT *,date_format(u_login,'%d.%m.%y %H:%i') as login ".
					"FROM user WHERE u_nick $sucheper '$chatzeile[1]' ".
					"ORDER BY u_nick,u_name limit $max_user_liste";
			}
			else
			{			
				// suche für User zeigt die gesperrten nicht an
				$query="SELECT *,date_format(u_login,'%d.%m.%y %H:%i') as login ".
					"FROM user WHERE u_nick $sucheper '$chatzeile[1]' ".
					"and u_level in ('A','C','G','M','S','U') ".
					"ORDER BY u_nick,u_name limit $max_user_liste";
			}

			
			$result=@mysql_query($query,$conn);

			$rows=mysql_num_rows($result);
			
			# system_msg("",0,$u_id,$system_farbe,$query);

			// system_msg("",0,$u_id,$system_farbe,str_replace("%nummer%",$rows,$t[chat_msg34]));
			$text="<B>WHOIS $chatzeile[1]:</B><BR>\n";
			while ($row=@mysql_fetch_object($result)) {
				$q2="SELECT r_name,o_ip,o_vhost,o_browser,o_id,o_who,".
					"UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_login) AS online_zeit ".
					"FROM online left join raum on r_id=o_raum WHERE o_user=$row->u_id ";
				$r2=mysql_query($q2,$conn);
				if ($r2 && mysql_num_rows($r2)>0) {
					// User ist online
					$r=mysql_fetch_object($r2);
					if ($admin) {
						$text=$text.user($row->u_id,$row,TRUE,TRUE,"&nbsp;",$r->online_zeit,$row->login,FALSE)."&nbsp;(".htmlspecialchars(stripslashes($row->u_name))."), ";
					} else {
						$text=$text.user($row->u_id,$row,TRUE,TRUE,"&nbsp;",$r->online_zeit,$row->login,FALSE).", "; 
					}
					if ($row->u_email) $text.="%email%&nbsp;".htmlspecialchars(stripslashes($row->u_email)).", " ;
					if ($row->u_adminemail && $admin) $text.="%adminemail%&nbsp;".htmlspecialchars(stripslashes($row->u_adminemail)).", " ;
					$text.="<B>[".$whotext[$r->o_who]."]</B>";
					if ($r->o_who==0) $text.=", %raum%&nbsp;".$r->r_name;

                                        if ($row->u_away)
                                        {       
                                                $awaytext = htmlspecialchars(stripslashes($row->u_away));
                                                $awaytext = str_replace('&lt;b&gt;', '<B>', $awaytext);  
                                                $awaytext = str_replace('&lt;/b&gt;', '</B>', $awaytext);      
                                                $awaytext = str_replace('&lt;i&gt;', '<I>', $awaytext);  
                                                $awaytext = str_replace('&lt;/i&gt;', '</I>', $awaytext);
                                                $awaytext = str_replace('&amp;lt;', '<', $awaytext);               
                                                $awaytext = str_replace('&amp;gt;', '>', $awaytext);
                                                $awaytext = str_replace('&amp;quot;', '"', $awaytext);
                                                $text=$text. ", (".$awaytext.")";  
                                        }

					if ($admin) {
						$host_name=@gethostbyaddr($r->o_ip);
						$text=$text. htmlspecialchars(", ($r->o_ip($host_name), $r->o_vhost, $r->o_browser) ");
					};
					$text=$text. "</B><br>\n";
					@mysql_free_result($r2);
				} else {
					// User ist nicht online
					if ($admin) {
						$text=$text.user($row->u_id,$row,TRUE,TRUE,"&nbsp;","",$row->login,FALSE)."&nbsp;(".htmlspecialchars(stripslashes($row->u_name))."), ";
					} else {
						$text=$text.user($row->u_id,$row,TRUE,TRUE,"&nbsp;","",$row->login,FALSE).", "; 
					}
					if ($row->u_email) $text = $text ."%email%&nbsp;".htmlspecialchars(stripslashes($row->u_email)).", " ;
					if ($row->u_adminemail && $admin) $text = $text ."%adminemail%&nbsp;".htmlspecialchars(stripslashes($row->u_adminemail)).", " ;
					if ($row->u_away)
					{	
						$awaytext = htmlspecialchars(stripslashes($row->u_away));
		                                $awaytext = str_replace('&lt;b&gt;', '<B>', $awaytext);
                		                $awaytext = str_replace('&lt;/b&gt;', '</B>', $awaytext);
                                		$awaytext = str_replace('&lt;i&gt;', '<I>', $awaytext);
                          			$awaytext = str_replace('&lt;/i&gt;', '</I>', $awaytext);
                                		$awaytext = str_replace('&amp;lt;', '<', $awaytext);
                                		$awaytext = str_replace('&amp;gt;', '>', $awaytext);
						$text=$text. ", (".$awaytext.")";
					}
					$text=$text. "<br>\n";
				};
			}
			$text=$text."\n";
			$text=str_replace("%email%",$t['chat_msg64'],$text);
			$text=str_replace("%adminemail%",$t['chat_msg65'],$text);
			$text=str_replace("%online%",$t['chat_msg66'],$text);
			$text=str_replace("%raum%",$t['chat_msg67'],$text);
			$text=str_replace("%login%",$t['chat_msg68'],$text);

			system_msg("",0,$u_id,$system_farbe,$text);
			@mysql_free_result($result);

		} else {
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg33']);
		}
		
	}
	break;

case "/msg":    // Sollte hier in den Aliasen etwas dazu kommen, muss der Autoknebel angepasst werden, damit Admins die privaten
case "/talk":	// Nachrichten nicht lesen können, wenn der Autoknebel zuschlägt
case "/tell":
case "/t":
case "/msgpriv": // Extra behandlung für Private Nachrichten im Userfenster, für den fall, daß der User sich ausloggt, keine Nickergänzung

	// Private Nachricht 
	if (!($o_knebel>0) && $u_level!="G" && (!$beichtstuhl || $admin)) {

		// Smilies und Text parsen
		$privat=TRUE;
		$text=html_parse($privat,htmlspecialchars($chatzeile[2]." ".$chatzeile[3]));

		// Empfänger im Chat suchen
		// /talk muss z.B. mit "/talk kleiner" auch dann an kleiner gehen 
		// wenn kleiner in anderem Raum ist und im eigenen Raum ein kleinerpfuscher anwesend ist.
		if (!isset($nick) || $nick['u_nick']=="") 
		{
			if ($chatzeile[0] == "/msgpriv") // Keine Nickergänzung bei diesem Nachrichtentyp
			{
       				// Prüfen ob User noch Online              		
				$query="SELECT o_id, o_name, o_user, o_userdata, o_userdata2, o_userdata3, o_userdata4 FROM online WHERE o_name = '$chatzeile[1]' ";
              	
			        $result=mysql_query($query,$conn);
        			if ($result>=0) 
				{
					if (mysql_num_rows($result) == 1) 
					{
						$nick=unserialize(mysql_result($result,0,"o_userdata").
					                          mysql_result($result,0,"o_userdata2").
                   					          mysql_result($result,0,"o_userdata3").
								  mysql_result($result,0,"o_userdata4"));
						$nick['u_nick'] = mysql_result($result,0,"o_name");
						$nick['u_id'] = mysql_result($result,0,"o_user");
					}
	  				else
					{
						$nick['u_nick'] = "";
						system_msg("",0,$u_id,$u_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg25']));
    					}
				}
      				mysql_free_result($result);

			}
			else
			{
				$nick=nick_ergaenze($chatzeile[1],"online",0);
			}
		}

		// Falls Empfänger gefunden, Nachricht versenden
		if (isset($nick) && $nick['u_nick'] != "") {

			// nick gefunden und Eindeutig.
			if ($text!=" "){
				// Wir prüfen, ob der User ignoriert wird, wenn ja => Fehlermeldung

				$query="SELECT i_user_aktiv, i_user_passiv FROM iignore ".
				       "WHERE (i_user_aktiv='$u_id' AND i_user_passiv = '$nick[u_id]') OR ".
				       "      (i_user_passiv='$u_id' AND i_user_aktiv = '$nick[u_id]') ";
				$result=mysql_query($query);
				$num=mysql_num_rows($result);
				mysql_free_result($result);
				
				#system_msg("",$u_id,$u_id,$system_farbe,"num: $num");
				if ($num == 0)
				{
					priv_msg($u_name,$u_id,$nick['u_id'],$u_farbe,$text,$userdata);
					system_msg("",$u_id,$u_id,$system_farbe,"<B>$u_name $t[chat_msg24] $nick[u_nick]:</B> $text");
				}
				else
				{
					system_msg("",$u_id,$u_id,$system_farbe,str_replace('%nick%',$nick['u_nick'],$t['chat_msg109']));
				}
				if ($nick['u_away']!="") system_msg("",0,$u_id,$system_farbe,"<b>$chat:</b> $nick[u_nick] $t[away1] $nick[u_away]");
			}
		} else {
			// Nachricht konnte nicht verschickt werden, als Kopie ausgeben
			system_msg("",0,$u_id,$system_farbe,str_replace("%nachricht%",$text,$t['chat_msg77']));
		}
	} elseif ($u_level=="G") {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	} elseif ($o_knebel>0) {
		// user ist geknebelt...
		$zeit=gmdate("H:i:s",$o_knebel);
		$txt=str_replace("%zeit%",$zeit,$t['knebel6']);
		system_msg("",$u_id,$u_id,$system_farbe,$txt);
	} elseif (!$admin) {
		system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
	}
	break;

case "/tf":
case "/msgf":

$query="SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_userid=$u_id AND f_status = 'bestaetigt' ".
       "UNION ".
       "SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_freundid=$u_id AND f_status = 'bestaetigt' ".
       "ORDER BY f_zeit desc ";


$result=mysql_query($query);
$fid="";
$nicks="";
while ($a=mysql_fetch_array($result))
	{
	$query2="SELECT o_user,o_name FROM online WHERE (o_user = '$a[f_userid]' or o_user = '$a[f_freundid]') and o_user != '$u_id'";
	$result2=mysql_query($query2);
	if (mysql_numrows($result2) == 1) {
					if ($a[f_userid] != $u_id) $fid[]=$a[f_userid];
					if ($a[f_freundid] != $u_id) $fid[]=$a[f_freundid];
					 }
	}

	$privat=TRUE;
	$text=html_parse($privat,htmlspecialchars($chatzeile[1]." ".$chatzeile[2]." ".$chatzeile[3]));

for ($i=0;$i<count($fid);$i++)
	{
	$nick=user($fid[$i],0,FALSE,FALSE,"");
	priv_msg($u_name,$u_id,$fid[$i],$u_farbe,$text,$userdata);
	system_msg("",$u_id,$u_id,$system_farbe,"<B>$u_name $t[chat_msg24] $nick:</B> $text");
	}

break;

case "/analle":
case "/toall":

	// Private nachricht an alle User
	if ($admin || $u_level=="A") {

		// Smilies und Text parsen
		$privat=TRUE;
		$text=html_parse($privat,$t['chat_msg100'].htmlspecialchars($chatzeile[1]." ".$chatzeile[2]." ".$chatzeile[3]));

		if ($text){
			// Schleife über alle User, die online sind
			$query="SELECT o_user FROM online";
			$result=mysql_query($query,$conn);
			if ($result && mysql_num_rows($result)>0) {
				while ($row=mysql_fetch_array($result)) {
					if ($row['o_user']!=$u_id) {
						priv_msg($u_name,$u_id,$row['o_user'],$u_farbe,$text,$userdata);
					}
				}
				system_msg("",$u_id,$u_id,$system_farbe,"<B>$u_name $t[chat_msg78]:</B> $text");
			} else {
				// Kein User online
				system_msg("",$u_id,$u_id,$system_farbe,$t['chat_msg79']);
			}
		    	@mysql_free_result($result);
		}
	} else {
		// user ist kein Admin
		system_msg("",$u_id,$u_id,$system_farbe,$t['chat_msg1']);
	}
	break;

case "/loescheraum":

	// Raum löschen
	if ( $chatzeile[1] ) {

		// Rechte prüfen 
		$query="SELECT r_id,r_name,r_besitzer FROM raum ".
			"WHERE r_name like '$chatzeile[1]' ";
		$result=mysql_query($query,$conn);
                
		if (mysql_num_rows($result)!=0) {
			$row=mysql_fetch_object($result);
			if ($admin || $row->r_besitzer==$u_id) {

				// Lobby suchen
				$query="SELECT r_id FROM raum WHERE r_name='$lobby' ";
				$result2=mysql_query($query, $conn);
				if ($result2 AND mysql_num_rows($result2)>0):
        				$lobby_id=mysql_result($result2,0,"r_id");
				endif;
				@mysql_free_result($result2);
				if (!$lobby_id) {
					system_msg("",0,$u_id,$system_farbe,$t['chat_msg63']);
					exit;
				};

				// Löschen falls nicht Lobby
				if ($row->r_id==$lobby_id) {
					system_msg("",0,$u_id,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg63']));
				} else {

					// Raum darf gelöscht werden, Raum schließen
					$f['r_status1']="G";
					schreibe_db("raum",$f,$row->r_id,"r_id");

					// Raum leeren
					$query="SELECT o_user,o_name FROM online ".
        					"WHERE o_raum=$row->r_id ";

					$result2=mysql_query($query, $conn);
					while($row2=mysql_fetch_object($result2)):
        					system_msg("",0,$row2->o_user,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg62']));
        					$oo_raum=raum_gehe($o_id,$row2->o_user,$row2->o_name,$row->r_id,$lobby_id,FALSE);
        					raum_user($lobby_id,$row2->o_user,$id);
        					$i++;
					endwhile;
					@mysql_free_result($result2);

        				$query="DELETE FROM raum WHERE r_id=$row->r_id ";
        				$result2=mysql_query($query, $conn);
					@mysql_free_result($result2);

        				// Gesperrte Räume löschen
        				$query="DELETE FROM sperre WHERE s_raum=$row->r_id";
        				$result2=mysql_query($query, $conn);
					@mysql_free_result($result2);

					// Meldung an User
					system_msg("",0,$u_id,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg58']));
				}

			} else {
				system_msg("",0,$u_id,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg57']));
			};
		} else {
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg3']);
		}
        	@mysql_free_result($result);
	} else {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg59']);
	}

	break;

case "/uebergeberaum":
case "/schenke":

	// aktuellen Raum Übergeben
	// Rechte prüfen 
	$query="SELECT r_id,r_name,r_besitzer FROM raum ".
		"WHERE r_id=$o_raum ";
	$result=mysql_query($query,$conn);
                
	if (mysql_num_rows($result)!=0) {
		$row=mysql_fetch_object($result);
		if ($admin || $row->r_besitzer==$u_id) {

			// Empfänger im aktuellen Raum suchen
			$nick=nick_ergaenze($chatzeile[1],"raum",1);

			// Empfänger im Chat suchen
			if ($nick['u_nick']=="") $nick=nick_ergaenze($chatzeile[1],"online",0);

			// Falls Empfänger gefunden und Empfänger ist nicht sich selbst, Nachricht versenden
			if (($nick['u_nick']!="") && ($nick['u_id']) && $nick['u_nick']!=$u_name && $nick['u_level']!="G" && $r_id) {
				// Raum übergeben
				$text = $t['chat_msg60'];
				$text = str_replace("%user%",$nick['u_nick'],$text);
				$text = str_replace("%r_name%",$row->r_name,$text);
				$text = str_replace("%absender%",$u_nick,$text);

				system_msg("",$u_id,$u_id,$system_farbe,$text);
				system_msg("",$u_id,$nick['u_id'],$system_farbe,$text);
				$f['r_besitzer']=$nick['u_id'];
				schreibe_db("raum",$f,$r_id,"r_id");
			} else {
				system_msg("",0,$u_id,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg69']));
			}

		} else {
			system_msg("",0,$u_id,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg61']));
		};
	} else {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg3']);
	}
       	@mysql_free_result($result);

	break;

case "/aendereraum":

	// aktuellen Raum auf den Status G/O/m ändern
	// Rechte prüfen 
	$query="SELECT r_id,r_name,r_besitzer,r_status1 FROM raum ".
		"WHERE r_id=$o_raum ";
	$result=mysql_query($query,$conn);
                
	if (mysql_num_rows($result)!=0) {
		$row=mysql_fetch_object($result);
		if ($admin || $row->r_besitzer==$u_id) {

			switch (strtolower($chatzeile[1])) {

				case "m":
				case "moderiert":
					if ($moderationsmodul==0) system_msg("",$u_id,$u_id,$system_farbe,$t['chat_msg74']);
					else $f['r_status1']="m";
					break;

				case "o":
				case "offen":
					$f['r_status1']="O";
					break;

				case "g":
				case "geschlossen":
					$f['r_status1']="G";
					break;

				default:
					unset($f['r_status1']);
					$text=$t['chat_msg71'].$raumstatus1[O].", ".
						$raumstatus1['G'].", ".
						$raumstatus1['m'].".";
					system_msg("",$u_id,$u_id,$system_farbe,$text);
						
			};

			if ($f['r_status1']==$row->r_status1) {
				// Raum nicht verändert
				system_msg("",$u_id,$u_id,$system_farbe,str_replace("%status%",$raumstatus1[$f['r_status1']],str_replace("%r_name%",$row->r_name,$t['chat_msg75'])));
			} elseif ($f['r_status1'] && $r_id) {
				// Raum ändern
				system_msg("",$u_id,$u_id,$system_farbe,str_replace("%status%",$raumstatus1[$f['r_status1']],str_replace("%r_name%",$row->r_name,$t['chat_msg73'])));
				schreibe_db("raum",$f,$r_id,"r_id");
			} else {
				// Fehler
				if (strlen($chatzeile[1])>0) {
					system_msg("",0,$u_id,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg70']));
				}
			}

		} else {
			system_msg("",0,$u_id,$system_farbe,str_replace("%r_name%",$row->r_name,$t['chat_msg72']));
		};
	} else {
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg3']);
	}
       	@mysql_free_result($result);

	break;

case "/lob":
		// Nur für Admins, lobt User mit Punkten
		if ($admin) {
			if ($communityfeatures) {
				$user=nick_ergaenze($chatzeile[1],"raum",1);
				if ($user['u_nick']=="") {
					$user=nick_ergaenze($chatzeile[1],"online",0);
					$im_raum=FALSE;
				} else {
					$im_raum=TRUE;
				};

				//system_msg("", 0, $u_id, $system_farbe, "DEBUG: $user[u_level] ");

				// Nur Superuser dürfen Gästen Punkte geben
				if ($user['u_level'] == 'G' && $u_level!='S')
				{
					system_msg("",0,$u_id,$system_farbe,$t['punkte22']);
					$user['u_nick']="";
				}
				
				$anzahl=(int)$chatzeile[2];
				if ((string)$anzahl!=(string)$chatzeile[2]) $anzahl=0;

				// Mehr als ein Punkt? Ansonsten Fehlermeldung
				if ($anzahl<1) {
					system_msg("",0,$u_id,$system_farbe,$t['punkte7']);
					$user['u_nick']="";
				};

				// Kein SU und mehr als 1000 Punkte? Ansonsten Fehlermeldung
				if ($anzahl>1000 && $u_level!='S') {
					system_msg("",0,$u_id,$system_farbe,$t['punkte11']);
					$user['u_nick']="";
				};

				// selber Punkte geben ist verboten
				if ($u_nick==$user['u_nick']) {
					system_msg("",0,$u_id,$system_farbe,$t['punkte10']);
					$user['u_nick']="";
				};

				if ($user['u_nick']!="") {

					if ($im_raum) {
						// eine öffentliche Nachricht an alle schreiben
						punkte($anzahl,$user['o_id'],$user['u_id'],"",TRUE);
						$txt=str_replace("%user1%",$u_nick,$t['punkte5']);
						$txt=str_replace("%user2%",$user['u_nick'],$txt);
						$txt=str_replace("%punkte%",$anzahl,$txt);
						global_msg($u_id,$o_raum,str_replace("%user%",$u_nick,$txt));
					} else {
						// Zwei private Nachrichten (admin/user)
						punkte($anzahl,$user['o_id'],$user['u_id'],str_replace("%user%",$u_nick,$t['punkte3']),TRUE);
						$txt=str_replace("%user2%",$user['u_nick'],$t['punkte8']);
						$txt=str_replace("%user1%",$u_nick,$txt);
						$txt=str_replace("%punkte%",$anzahl,$txt);
						global_msg($u_id,$o_raum,str_replace("%user%",$u_nick,$txt));
					};
				}
			} else {
				system_msg("",0,$u_id,$system_farbe,$t['chat_msg74']);
			}
		} else {
			system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
		}
	break;

case "/tadel":
case "/bestraf":
case "/peitsch":
		// Nur für Admins, zieht User Punkte ab
		if ($admin) {
			if ($communityfeatures) {
				$user=nick_ergaenze($chatzeile[1],"raum",1);
				if ($user['u_nick']=="") {
					$user=nick_ergaenze($chatzeile[1],"online",0);
					$im_raum=FALSE;
				} else {
					$im_raum=TRUE;
				};

				$anzahl=(int)$chatzeile[2];
				if ((string)$anzahl!=(string)$chatzeile[2]) $anzahl=0;

				// Mehr als ein Punkt? Ansonsten Fehlermeldung
				if ($anzahl<1) {
					system_msg("",0,$u_id,$system_farbe,$t['punkte7']);
					$user['u_nick']="";
				};

				// Lustiger Text, wenn der Admin sich selbst etwas abzieht
				if ($lustigefeatures && $u_nick==$user['u_nick']) {

					if ($anzahl==1) {
						$txt=$t['punkte12'];
					} elseif ($anzahl==42) {
						$txt=$t['punkte13'];
					} elseif ($anzahl<100) {
						$txt=$t['punkte14'];
					} elseif ($anzahl<200) {
						$txt=$t['punkte15'];
					} elseif ($anzahl<500) {
						$txt=$t['punkte16'];
					} elseif ($anzahl<1000) {
						$txt=$t['punkte17'];
					} elseif ($anzahl<5000) {
						$txt=$t['punkte18'];
					} elseif ($anzahl<10000) {
						$txt=$t['punkte19'];
					} else {
						$txt=$t['punkte20'];
					}

					// eine öffentliche Nachricht an alle schreiben
					punkte($anzahl*(-1),$user['o_id'],$user['u_id'],"",TRUE);

					$txt=str_replace("%user%",$u_nick,$txt);
					$txt=str_replace("%punkte%",$anzahl,$txt);
					global_msg($u_id,$o_raum,str_replace("%user%",$u_nick,$txt));

				} elseif ($user['u_nick']!="") {

					if ($im_raum) {
						// eine öffentliche Nachricht an alle schreiben
						punkte($anzahl*(-1),$user['o_id'],$user['u_id'],"",TRUE);
						$txt=str_replace("%user1%",$u_nick,$t['punkte6']);
						$txt=str_replace("%user2%",$user['u_nick'],$txt);
						$txt=str_replace("%punkte%",$anzahl,$txt);
						global_msg($u_id,$o_raum,str_replace("%user%",$u_nick,$txt));
					} else {
						// Zwei private Nachrichten (admin/user)
						punkte($anzahl*(-1),$user['o_id'],$user['u_id'],str_replace("%user%",$u_nick,$t['punkte4']),TRUE);
						$txt=str_replace("%user2%",$user['u_nick'],$t['punkte9']);
						$txt=str_replace("%user1%",$u_nick,$txt);
						$txt=str_replace("%punkte%",$anzahl,$txt);
						global_msg($u_id,$o_raum,str_replace("%user%",$u_nick,$txt));
					};

				}
			} else {
				system_msg("",0,$u_id,$system_farbe,$t['chat_msg74']);
			}
		} else {
			system_msg("",0,$u_id,$system_farbe,"<B>He $u_name!</B> ".str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
		}
	break;

case "/mail":
case "/m":
	// Mail schreiben
	$text=chat_parse(htmlspecialchars($chatzeile[2]." ".$chatzeile[3]));

	if ($communityfeatures && !($o_knebel>0) && $u_level!="G" && strlen($text)>1) {

		// Empfänger im Chat suchen
		$nick=nick_ergaenze($chatzeile[1],"online",1);

		// Falls keinen Empfänger gefunden, in Usertabelle nachsehen
		if ($nick['u_nick']=="") {
			$query="SELECT u_nick,u_id from user ".
				"WHERE u_nick='".coreCheckName($chatzeile[1],$check_name)."'";
			$result=mysql_query($query,$conn);
			if ($result && mysql_num_rows($result)==1){
				$nick=mysql_fetch_array($result);
			};
			@mysql_free_result($result);
		};
	
	     $ignore=false;
             $query2="SELECT * FROM iignore WHERE i_user_aktiv='$nick[u_id]' AND i_user_passiv = '$u_id'";
             $result2=mysql_query($query2);
             $num=mysql_numrows($result2);
             if ($num >= 1) {$ignore=true;}

		// Falls nick gefunden, Mail versenden
		if ($nick['u_nick']!="" && $nick['u_id'] && $nick['u_level']!="G" && $nick['u_level']!="Z" && $ignore==false) {
			if (strlen($text)>4) {
				$result=mail_sende($u_id,$nick['u_id'],$text);
				if ($result[0])
				system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg80']));
				else
				system_msg("",0,$u_id,$system_farbe,$result[1]);	
			} else {
				system_msg("",0,$u_id,$system_farbe,$t['chat_msg81']);
			};
		} elseif ($nick['u_level']=="G" || $nick['u_level']=="Z") {
			// Nachricht konnte nicht verschickt werden, als Kopie ausgeben
			system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg95']));
			system_msg("",0,$u_id,$system_farbe,str_replace("%nachricht%",$text,$t['chat_msg77']));
		} else {
			// Nachricht konnte nicht verschickt werden, als Kopie ausgeben
			// ignoriere User dürfen kein Mail schicken
			if ($ignore==true)
			{
			 system_msg("",0,$u_id,$system_farbe,str_replace("%user%",$nick['u_nick'],$t['chat_msg103'])); 
			}
			else
			{
			system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg8']));
			system_msg("",0,$u_id,$system_farbe,str_replace("%nachricht%",$text,$t['chat_msg77']));
			}
		}

	} elseif (!$communityfeatures) {

		system_msg("",0,$u_id,$system_farbe,$t['chat_msg74']);

	} elseif ($u_level=="G") {

		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);

	} elseif (strlen($text)<=1) {

		system_msg("",0,$u_id,$system_farbe,$t['chat_msg81']);

	} else { 
		// user ist geknebelt...
		$zeit=gmdate("H:i:s",$o_knebel);
		$txt=str_replace("%zeit%",$zeit,$t['knebel6']);
		system_msg("",$u_id,$u_id,$system_farbe,$txt);

	}
	break;

case "/freunde":
case "/freund":
case "/buddy":
	// Fügt Freund der Freundesliste hinzu oder löscht einen Eintrag

	if ($u_level!="G" && $communityfeatures) {

		$privat=FALSE;
		$text=html_parse($privat,htmlspecialchars($chatzeile[2]." ".$chatzeile[3]));

		if ( $chatzeile[1] ) {

			// Nach nicknamen suchen		
			$nick=nick_ergaenze($chatzeile[1],"online",1);

			// Falls keinen Empfänger gefunden, in Usertabelle nachsehen
			if ($nick['u_nick']=="") {
				$query="SELECT u_nick,u_id,u_level from user ".
					"WHERE u_nick='".coreCheckName($chatzeile[1],$check_name)."'";
				$result=mysql_query($query,$conn);
				if ($result && mysql_num_rows($result)==1){
					$nick=mysql_fetch_array($result);
				};
				@mysql_free_result($result);

			};

			$freunddazu = 1;
			if ($nick['u_nick']!="" && $nick['u_id'])
			{
		        	$query2="SELECT * FROM iignore WHERE i_user_aktiv='$nick[u_id]' AND i_user_passiv = '$u_id'";
             			$result2=mysql_query($query2);
             			$num=mysql_numrows($result2);
             			if ($num >= 1) {$freunddazu=-1;}
				if ($nick['u_level'] == 'Z') {$freunddazu=-2;}
				if ($nick['u_level'] == 'G') {$freunddazu=-3;}
				@mysql_free_result($result2);
			}

			
			// Falls nick gefunden, prüfen ob er bereits in Tabelle steht
			if ($nick['u_nick']!="" && $nick['u_id']) {
				$query="SELECT f_id from freunde WHERE ".
					"(f_userid=$nick[u_id] AND f_freundid=$u_id ) ".
					"OR ".
					"(f_userid=$u_id AND f_freundid=$nick[u_id] )";

				$result=mysql_query($query,$conn);
				if ($result && mysql_num_rows($result)>0){
				
					// User ist bereits Freund -> entfernen
					$query="DELETE from freunde WHERE ".
						"(f_userid=$nick[u_id] AND f_freundid=$u_id) ".
						"OR ".
						"(f_userid=$u_id AND f_freundid=$nick[u_id])";
					$result=mysql_query($query,$conn);
					system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%",$nick['u_nick'],$t['chat_msg84']));

				} elseif($nick['u_id']==$u_id) {

					// Eigener Freund ist verboten
					system_msg("",0,$u_id,$system_farbe,$t['chat_msg89']);

				} else {
					
					if ($freunddazu == 1)
					{
						// User ist noch kein Freund -> hinzufügen
						unset($f);
						$f['u_id']=$nick['u_id'];
						$f['u_nick']=$nick['u_nick'];
						if (strlen($text)>1) $f['f_text']=$text;
						#schreibe_db("freunde",$f,0,"f_id");

						$text=neuer_freund($u_id,$f);
						# system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%",$nick[u_nick],$t[chat_msg83]));
 						system_msg("",0,$u_id,$system_farbe,$text);
					}
					else if ($freunddazu == -1)
					{
						system_msg("", 0, $u_id, $system_farbe, str_replace("%u_name%",$nick['u_nick'],$t['chat_msg116']));
					}
					else if ($freunddazu == -2)
					{
						system_msg("", 0, $u_id, $system_farbe, str_replace("%u_name%",$nick['u_nick'],$t['chat_msg117']));
					}
					else if ($freunddazu == -3)
					{
						system_msg("", 0, $u_id, $system_farbe, str_replace("%u_name%",$nick['u_nick'],$t['chat_msg118']));
					}
					

				}
			} else {
				// User nicht gefunden
				system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg8']));
			};

		};

		// Listet alle Freunde auf
		$query="SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_userid=$u_id AND f_status = 'bestaetigt' ".        
		       "UNION ".
		       "SELECT f_id,f_text,f_userid,f_freundid,f_zeit FROM freunde WHERE f_freundid=$u_id AND f_status = 'bestaetigt' ".
		       "ORDER BY f_zeit desc "; 

		$result=mysql_query($query,$conn);
		if ($result && mysql_num_rows($result)>0) {

			system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%",$u_nick,$t['chat_msg85']));

			while ($row=mysql_fetch_object($result)) {

				// User aus DB lesen
				if ($row->f_userid!=$u_id) {
					$query="SELECT u_nick,u_id,u_level,u_punkte_gesamt,u_punkte_gruppe,o_id,".
						"date_format(u_login,'%d.%m.%y %H:%i') as login, ".
						"UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_login) AS online ".
						"from user left join online on o_user=u_id ".
						"WHERE u_id=$row->f_userid ";
					$result2=mysql_query($query,$conn);
				} elseif ($row->f_freundid!=$u_id) {
					$query="SELECT u_nick,u_id,u_level,u_punkte_gesamt,u_punkte_gruppe,o_id,".
						"date_format(u_login,'%d.%m.%y %H:%i') as login, ".
						"UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_login) AS online ".
						"from user left join online on o_user=u_id ".
						"WHERE u_id=$row->f_freundid ";
					$result2=mysql_query($query,$conn);
				}
				if ($result2 && mysql_num_rows($result2)>0) {
					// User gefunden -> Ausgeben
					$row2=mysql_fetch_object($result2);
					if ($row2->o_id=="" || $row2->o_id=="NULL") $row2->online="";
					$txt=user($row2->u_id,$row2,$o_js,TRUE,"&nbsp;",$row2->online,$row2->login,FALSE);
				} else {
					// User nicht gefunden, Freund löschen
					$txt="NOBODY";
					$query="DELETE from freunde WHERE f_id=$row->f_id";
					$result2=mysql_query($query,$conn);
				};

				// Text als Systemnachricht ausgeben
				if ($row->f_text) $txt.=" (".$row->f_text.")";
				system_msg("",0,$u_id,$system_farbe,"&nbsp;&nbsp;".$txt);

			};
		} else {
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg86']);
		};
		@mysql_free_result($result);

	} elseif (!$communityfeatures) {
		// Fehlermeldung Community
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg74']);

	} else {
		// Fehlermeldung Gast
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	};
	break;

case "/time":
	// Gibt die Systemuhrzeit aus
        if ($u_level=="G") {
                system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
        } else {        
	
		$tempzeit = $t['chat_msg114'];
                $tempzeit = str_replace("%datum%",strftime('%d.%m.%Y'),$tempzeit);           
 		$tempzeit = str_replace("%uhrzeit%",strftime('%H:%M:%S'),$tempzeit);
		system_msg("",0, $u_id,$system_farbe,$tempzeit);
		unset($tempzeit);
 	}
	break;

case "/blacklist":
case "/blackliste":
	// Fügt Eintrag der Blacklist hinzu oder löscht einen Eintrag

	if ($admin && $communityfeatures) {

		$privat=FALSE;
		$text=html_parse($privat,htmlspecialchars($chatzeile[2]." ".$chatzeile[3]));

		if ( $chatzeile[1] ) {

			// Nach nicknamen suchen		
			$nick=nick_ergaenze($chatzeile[1],"online",1);

			// Falls keinen Empfänger gefunden, in Usertabelle nachsehen
			if ($nick['u_nick']=="") {

		$chatzeile[1]=coreCheckName($chatzeile[1],$check_name);
				$query="SELECT u_nick,u_id from user ".
					"WHERE u_nick='$chatzeile[1]'";
				$result=mysql_query($query,$conn);
				if ($result && mysql_num_rows($result)==1){
					$nick=mysql_fetch_array($result);
				};
				@mysql_free_result($result);

			};

			// Falls nick gefunden, prüfen ob er bereits in Tabelle steht
			if ($nick['u_nick']!="" && $nick['u_id']) {
				$query="SELECT f_id from blacklist WHERE ".
					"f_blacklistid=$nick[u_id] ";

				$result=mysql_query($query,$conn);
				if ($result && mysql_num_rows($result)>0){
				
					// User ist bereits auf der Liste -> entfernen
					$query="DELETE from blacklist WHERE ".
						"f_blacklistid=$nick[u_id] ";
					$result=mysql_query($query,$conn);
					system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%",$nick['u_nick'],$t['chat_msg97']));

				} elseif($nick['u_id']==$u_id) {

					// Eigener Freund ist verboten
					system_msg("",0,$u_id,$system_farbe,$t['chat_msg99']);

				} else {

					// User ist noch kein Eintrag -> hinzufügen
					$f['f_userid']=$u_id;
					$f['f_blacklistid']=$nick['u_id'];
					if (strlen($text)>1) $f['f_text']=$text;
					schreibe_db("blacklist",$f,0,"f_id");
					system_msg("",0,$u_id,$system_farbe,str_replace("%u_name%",$nick['u_nick'],$t['chat_msg96']));
				}
			} else {
				// User nicht gefunden
				system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[1],$t['chat_msg8']));
			};

		} else {
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg98']);
		};
		@mysql_free_result($result);

	} elseif (!$communityfeatures) {
		// Fehlermeldung Community
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg74']);

	} else {
		// Fehlermeldung
		system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_msg1']));
	};
	break;

case "/sms":    # martin 26.02.2002
	if ($u_level!="G" && $communityfeatures && $smsfeatures) {

		$text=html_parse($privat,htmlspecialchars($chatzeile[2]." ".$chatzeile[3]));

		// Empfänger im Chat suchen
		// /talk muss z.B. mit "/talk kleiner" auch dann an kleiner gehen 
		// wenn kleiner in anderem Raum ist und im eigenen Raum ein kleinerpfuscher anwesend ist.
		if ($nick['u_nick']=="") $nick=nick_ergaenze($chatzeile[1],"online",1);

		 // Falls keinen Empfänger gefunden, in Usertabelle nachsehen
                if ($nick['u_nick']=="") {

		$chatzeile[1]=coreCheckName($chatzeile[1],$check_name);
                        $query="SELECT u_nick,u_id from user ".
                                "WHERE u_nick='$chatzeile[1]'";
                        $result=mysql_query($query,$conn);
                        if ($result && mysql_num_rows($result)==1){
                                $nick=mysql_fetch_array($result);
                        };
                        @mysql_free_result($result);
                };

		// Falls Empfänger gefunden, Nachricht versenden
		if ($nick['u_nick'] != "") {

			// nick gefunden und Eindeutig.
			if ($text!=" "){
				sms_msg($u_name,$u_id,$nick['u_id'],$u_farbe,$text,$userdata);
				#priv_msg($u_name,$u_id,$nick[u_id],$u_farbe,$text,$userdata);
				#system_msg("",$u_id,$u_id,$system_farbe,"<B>$u_name $t[chat_msg24] $nick[u_nick]:</B> $text");
				if ($nick['u_away']!="") system_msg("",0,$u_id,$system_farbe,"<b>$chat:</b> $nick[u_nick] $t[away1] $nick[u_away]");
			}
		} else {
			// Nachricht konnte nicht verschickt werden, als Kopie ausgeben
			system_msg("",0,$u_id,$system_farbe,str_replace("%nachricht%",$text,$t['chat_msg77']));
		}

	} elseif (!$communityfeatures or !$smsfeatures) {
		// Fehlermeldung Community
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg74']);

	} else {
		// Fehlermeldung Gast
		system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	};
	break;
default:

	// system_msg("",0,$u_id,$system_farbe,"DEBUG: $o_knebel ");
                                
	if (!($o_knebel>0)) { 

	// Gibt Spruch aus, falls Eingabe mit = beginnt
	if ( preg_match("/^=/",$chatzeile[0]) ) {

	    // Spruch suchen und ausgeben
	    if ($u_level=="G") {
			system_msg("",0,$u_id,$system_farbe,$t['chat_msg55']);
	    } else {
		
		// Art des Spruchs
		// 0: Ohne Argumente
		// 1: Username wurde angegeben
		// 2: Username und Zusatztext wurden übergeben

		// Wenn in $chatzeile[1] oder anderen auser 0 ein " vorkommt,
		// dann leerzeichen durch + ersetzen und neu splitten
		if (!isset($chatzeile[1])) $chatzeile[1] = "";
		if (!isset($chatzeile[2])) $chatzeile[2] = "";
		if (!isset($chatzeile[3])) $chatzeile[3] = "";
		if (preg_match("/\"/", $chatzeile[1]) || preg_match("/\"/", $chatzeile[2]) || preg_match("/\"/", $chatzeile[3]))
		{ 
			$text = trim($text);
			// Nur bei gerader anzahl an " geht es
			if (substr_count($text, '"') == 2)
			{
				$temp = explode('"', $text, 3);
				if (trim($temp[2]) == '') { $temp[1] = "\"".$temp[1]."\""; } 
				// system_msg("", 0, $u_id, $system_farbe, "DEBUG: '$temp[0]' '$temp[1]' '$temp[2]'");
				$text = $temp[0].str_replace(' ', '+', trim($temp[1]))." ".str_replace(' ', '+', trim($temp[2]));
				// system_msg("", 0, $u_id, $system_farbe, "DEBUG: $text ");
				$chatzeile=preg_split("/ /", $text, 4);
			}
			else if (substr_count($text, '"') == 4)
			{
				$temp = explode('"', $text, 5);
				$text = $temp[0]."\"".str_replace(' ', '+', $temp[1])."\" ".str_replace(' ', '+', $temp[3]);
				$chatzeile=preg_split("/ /", $text, 4);
			}
			if (!isset($chatzeile[3])) $chatzeile[3] = "";
		} 


		// Befehl bestimmen und ungültige Zeichen filtern
		$spruchname = addslashes(strtr(substr($chatzeile[0],1),"*%$!?.,;:\\",""));
		$username   = $chatzeile[1];
		$zusatztext = $chatzeile[2]." ".$chatzeile[3];
		
		if ($username) $nick=nick_ergaenze($username,"raum",1);
		if (isset($nick) && $nick != "" ) $username=$nick['u_nick'];

                $spruchart  = 0;
                if ( $username ) $spruchart++;
                if ( $zusatztext!=" " ) $spruchart++;

		$username = str_replace("+", " ", $username);
		$username = trim(str_replace("\"", "", $username));
		$zusatztext = str_replace("+", " ", $zusatztext);
		$zusatztext = trim(str_replace("\"", "", $zusatztext));

		// Spruch suchen
		// Sprüche in Array lesen
		$sp_list = file("conf/$datei_spruchliste");
                $sp_such = "^".preg_quote($spruchname, "/")."\t".$spruchart."\t";
                for(@reset($sp_list); (list(,$sp_text)=each($sp_list)) AND (!preg_match("/".$sp_such."/",$sp_text)); );


		// Spruch gefunden?
		if ( preg_match("/".$sp_such."/i",$sp_text) ) {


			// Spruch parsen
			$spruchtmp = preg_split("/\t/", $sp_text, 3);
			$spruchtxt = $spruchtmp[2]; 

			$spruchtxt = str_replace("`2",$zusatztext,$spruchtxt);
			$spruchtxt = str_replace("`1",$username,$spruchtxt);
			$spruchtxt = str_replace("`0",$u_name,$spruchtxt);

			// Spruch ausgeben
			$spruchtxt = str_replace("\n","",$spruchtxt);


			if (!$ist_moderiert || $u_level=="M") 
			{
                                hidden_msg($u_name,$u_id,$u_farbe,$r_id,trim(html_parse($privat,htmlspecialchars($spruchtxt." ")))."<!-- ($u_nick) -->");
 			}
			else 
			{
				system_msg("",$u_id,$u_id,$system_farbe,$t['moderiert1']);
			}

		} else {

			// Fehler ausgeben
			$fehler = $t['chat_spruch3'];
			if ( $spruchart==2 ) $fehler = $t['chat_spruch1'];
			if ( $spruchart==1 ) $fehler = $t['chat_spruch2'];

			$fehler = str_replace("%spruchname%",$spruchname,$fehler);
			$fehler = str_replace("%u_name%",$u_name,$fehler);
			system_msg("",0,$u_id,$system_farbe,$fehler);

			// Hinweise ausgeben
	                for(@reset($sp_list); list(,$sp_text)=each($sp_list) ; )
	                     if ( preg_match("/^".preg_quote($spruchname, "/")."\t/i",$sp_text) ) {
					$spruchtmp=preg_split("/\t/", $sp_text, 3);
					$txt="<SMALL><B>$t[chat_spruch4] <I>".
						$spruchtmp[0]." ".
						$spruchtmp[1]."</I></B> &lt;".
						$spruchtmp[2]."&gt;</SMALL>";
					system_msg("",0,$u_id,$system_farbe,$txt);
	                     }
		}	
	    }

	} else {

		// Gibt Fehler aus, falls Eingabe mit / beginnt
		if (preg_match("/^\//",$chatzeile[0])){
			system_msg("",0,$u_id,$system_farbe,str_replace("%chatzeile%",$chatzeile[0],$t['chat_spruch5']));

		} else { 

			// Normaler Text im Chat ausgeben
			// Text filtern und in DB schreiben

			// Nicknamen ergänzen, gesucht wird nach aktiven usern im selben Raum
			$temp=substr($chatzeile[0],-1);
			
			// Ansprechen mit "Nick:" "@nick" oder "Nick," bzw "Nick."
			// Problem: "Nick," und "Nick." macht Probleme bei allen Wörten, die am Anfang mit , oder "..." 
			// getrennt geschrieben werden
			// z.b. so, ich gehe jetzt wird zu "[zu Sonnenschein] ich gehe zu" wenn User Sonnenschein im Raum ist
			// if ($temp==":" || $temp=="@" || $temp==",") {
			// Daher auskommentiert und nur "Nick:" "@nick" und testweise "Nick." akzeptiert
			// da obriges seltener für den . passiert, und der . öffters aus versehen statt dem : erwischt wird
			if ($temp==":" || $temp=="@") {
				$nick=nick_ergaenze($chatzeile[0],"raum",0);
				if ($nick['u_nick'] <> "") {
					// Falls User gefunden wurde Nicknamen einfügen und filtern
					$f['c_text']="[".$t['chat_spruch6']."&nbsp;$nick[u_nick]] ".html_parse($privat,htmlspecialchars($chatzeile[1]." ".$chatzeile[2]." ".$chatzeile[3]));
					if ($nick['u_away']!="") system_msg("",0,$u_id,$system_farbe,"<b>$chat:</b> $nick[u_nick] $t[away1] $nick[u_away]");
				} else {
					// keine Fehlermeldung, wird von nick_replace schon erzeugt...
					// Ansonsten Chatzeile gefiltert ausgeben
					$f['c_text']=html_parse($privat,htmlspecialchars($text));
				}
			} elseif (isset($chatzeile[1]) && substr($chatzeile[1],0,1)=="@") {
				$nick=nick_ergaenze($chatzeile[1],"raum",0);
				if ($nick['u_nick'] <> "") {
					// Falls User gefunden wurde Nicknamen einfügen und filtern
					$f['c_text']="[".$t['chat_spruch6']."&nbsp;$nick[u_nick]] ".html_parse($privat,htmlspecialchars($chatzeile[0]." ".$chatzeile[2]." ".$chatzeile[3]));
					if ($nick['u_away']!="") system_msg("",0,$u_id,$system_farbe,"<b>$chat:</b> $nick[u_nick] $t[away1] $nick[u_away]");
				} else {
					// keine Fehlermeldung, wird von nick_replace schon erzeugt...
					// Ansonsten Chatzeile gefiltert ausgeben
					$f['c_text']=html_parse($privat,htmlspecialchars($text));
				}
			} else {
				// Chatzeile gefiltert ausgeben
				$f['c_text']=html_parse($privat,htmlspecialchars($text));
			}


			// Attribute ergänzen und Nachricht schreiben
			$f['c_von_user']=$u_name;
			$f['c_von_user_id']=$u_id;
			$f['c_raum']=$r_id;
			$f['c_typ']=$typ;
			$f['c_farbe']=$u_farbe;

			// Ist Raum moderiert? $ist_moderiert und $raum_einstellungen wurde in raum_ist_moderiert() gesetzt
			if (!$ist_moderiert) {

				// raum ist nicht moderiert -> schreiben.
				$back=schreibe_chat($f);

				// In Session merken, dass Text im Chat geschrieben wurde
				$query="UPDATE online SET o_timeout_zeit=DATE_FORMAT(NOW(),\"%Y%m%d%H%i%s\"), o_timeout_warnung='N' ".
					"WHERE o_user=$u_id";       
				$result=mysql_query($query,$conn);  

				// Punkte berechnen und User gutschreiben
				// Punkte=Anzahl der Wörter mit mindestens 4 Buchstaben
				// Punkte werden nur in permanenten, offen Räumen gutgeschrieben!
				if ($communityfeatures && $u_level!="G" && 
				    ($raum_einstellungen['r_status1']=="O" || $raum_einstellungen['r_status1']=="m") &&
				    $raum_einstellungen['r_status2']=="P") {

					// Punkte gutschreiben, wenn im Raum mindestens $punkte_ab_user User online sind
					$query="select count(o_id) from online where o_raum=$o_raum";
					$result=mysql_query($query,$conn);

					if ($punktefeatures && $result && mysql_result($result,0,0)>=$punkte_ab_user) 
// DEBUG				if ($punktefeatures && $result && mysql_result($result,0,0)>=1) 
					{
						// löscht die * und _ aus dem Eingabetext, damit es für z.b. *_*_ keine Punkte mehr gibt
						$punktetext = $f['c_text'];
						$punktetext = str_replace('<i>', '', $punktetext);
						$punktetext = str_replace('<b>', '', $punktetext);
						$punktetext = str_replace('</i>', '', $punktetext);
						$punktetext = str_replace('</b>', '', $punktetext);

						punkte(strlen(trim(preg_replace('/(^[[:space:]]*|[[:space:]]+)[^[:space:]]+/',"x",
							preg_replace('/(^|[[:space:]]+)[^[:space:]]{1,3}/'," ",$punktetext)))),
							$o_id,"");

						unset ($punktetext);
					}

				}

			} else {
				if ($u_level=="M") {

					// sonderfall: moderator -> hier doch schreiben	
					// vorher testen, ob es markierte fragen gibt:
					schreibe_moderation();
					$back=schreibe_chat($f);

					// In Session merken, dass Text im Chat geschrieben wurde
					$query="UPDATE online SET o_timeout_zeit=DATE_FORMAT(NOW(),\"%Y%m%d%H%i%s\"), o_timeout_warnung='N' ".
						"WHERE o_user=$u_id";       
					$result=mysql_query($query,$conn);  

				} else {

					if ($f['c_text'] != "")
					{
 						// raum ist moderiert -> normal nicht schreiben.
 						$back=schreibe_moderiert($f);

 						// In Session merken, dass Text im Chat geschrieben wurde
 						$query="UPDATE online SET o_timeout_zeit=DATE_FORMAT(NOW(),\"%Y%m%d%H%i%s\"), o_timeout_warnung='N' ".
							"WHERE o_user=$u_id";       
						$result=mysql_query($query,$conn);  

						system_msg("",0,$u_id,$system_farbe,$t['moderiert2']);
						system_msg("",0,$u_id,$system_farbe,"&gt;&gt;&gt; ".$f['c_text']);
					}
				}
			}
			return($back);
		} 
	}
	} else {
		// user ist geknebelt...
		$zeit=gmdate("H:i:s",$o_knebel);
		$txt=str_replace("%zeit%",$zeit,$t['knebel6']);
		system_msg("",$u_id,$u_id,$system_farbe,$txt);
	}

};

$quidditch = \Netzhuffle\MainChat\Quidditch\Quidditch::getInstance($r_id);
$quidditch->command($chatzeile, $u_nick);
	
};




function sperre($r_id,$u_id,$u_name,$s_user,$s_user_name,$admin) {
// Sperrt User s_user für Raum r_id oder hebt Sperre auf
// Wirft User ggf aus dem Raum r_id

global $lobby;
global $dbase;
global $timeout;
global $t;
global $u_level;
global $conn;

// Id der Lobby als Voreinstellung ermitteln
$query="SELECT r_id FROM raum WHERE r_name LIKE '$lobby' ";
$result=mysql_query($query,$conn);
$rows=mysql_num_rows($result);

if ($rows>0):
	$lobby_id=mysql_result($result,0,"r_id");
else:
	system_msg("",0,$u_id,$system_farbe,$t['sperre1']);
endif;
mysql_free_result($result);

if ($r_id!=$lobby_id):

	$query="SELECT s_id FROM sperre ".
		"WHERE s_user=$s_user ".
		"AND  s_raum=$r_id";

	$result=mysql_query($query,$conn);
	$rows=mysql_num_rows($result);

	if ($rows==0):

		// Ist User s_user in diesem Raum? $o_id ermitteln
		$query2="SELECT o_id FROM online ".
			"WHERE o_raum=$r_id ".
			"AND o_user=$s_user ".
			"AND (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_aktiv)) <= $timeout ";

	        $result2=mysql_query($query2,$conn);
        	$rows2=mysql_num_rows($result2);

		if ($rows2>0):

			// Sperre neu setzen
			$o_id=mysql_result($result2,0,"o_id");
			$f['s_user']=$s_user;
			$f['s_raum']=$r_id;
			schreibe_db("sperre",$f,"","s_id");

			// User aus Raum werfen
			global_msg($u_id,$r_id,"'$u_name' $t[sperre2] '$s_user_name' $t[sperre3]");
			raum_gehe($o_id,$s_user,$s_user_name,$r_id,$lobby_id,FALSE);

		else:
			// User ist nicht in diesem Raum... meldung ausgeben.
			system_msg("",0,$u_id,$system_farbe,str_replace("%s_user_name%",$s_user_name,$t['sperre4']));
			// trotzdem in Seprre eintragen.
			$f['s_user']=$s_user;
			$f['s_raum']=$r_id;
			schreibe_db("sperre",$f,"","s_id");
		endif;

	else:
		// Sperre löschen
		$s_id=mysql_result($result,0,"s_id");
		$query2="DELETE FROM sperre WHERE s_id=$s_id ";
		$result2=mysql_query($query2,$conn);
		global_msg($u_id,$r_id,"'$u_name' $t[sperre2] '$s_user_name' $t[sperre5]");
	endif;

	mysql_free_result($result);

else:
	// User ist in Lobby
	if (!($admin || $u_level=="A")):
		system_msg("",0,$u_id,$system_farbe,$t['sperre6']);
	else:
		// User aus Chat werfen

		// Ist User s_user in diesem Raum? $o_id ermitteln
		$query2="SELECT o_id FROM online ".
			"WHERE o_raum=$r_id ".
			"AND o_user=$s_user ".
			"AND (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_aktiv)) <= $timeout ";

	        $result2=mysql_query($query2,$conn);
        	$rows2=mysql_num_rows($result2);

		if ($rows2>0):

			// User rauswerfen
			$o_id=mysql_result($result2,0,"o_id");
			global_msg($u_id,$r_id,"'$u_name' $t[sperre2] '$s_user_name' $t[sperre7]");
			// 2 sek vor dem ausloggen warten, damit der Störer die Meldung auch noch lesen kann !!!
			sleep(2);
			verlasse_chat($s_user,$s_user_name,$r_id);
			sleep(2);
			logout($o_id,$s_user, "sperre in lobby");
		else:
			system_msg("",0,$u_id,$system_farbe,str_replace("%s_user_name%",$s_user_name,$t['sperre4']));
		endif;

	endif;
endif;

};



function ignore($o_id,$i_user_aktiv,$i_user_name_aktiv,$i_user_passiv,$i_user_name_passiv) {
// Unterdrückt Mitteilungen von i_user_passiv an i_user_aktiv
// Schaltet bei neuem Aufruf wieder zurück

global $dbase,$chat,$conn,$t;

$query="SELECT * FROM iignore ".
	"WHERE i_user_aktiv=$i_user_aktiv ".
	"AND i_user_passiv=$i_user_passiv ";

$result=mysql_query($query,$conn);


$query="SELECT u_level FROM user WHERE u_id = '$i_user_passiv'";
$result2=mysql_query($query);
$a=mysql_fetch_array($result2);

$isadmin=false;
if ($a['u_level'] == "C" || $a['u_level'] == "S" || $a['u_level'] == "A")  $isadmin=true;

#if ($isadmin==true)  system_msg("",0,$i_user_aktiv,$system_farbe,"DEBUG: user ist admin");
#if ($isadmin==false)  system_msg("",0,$i_user_aktiv,$system_farbe,"DEBUG: user ist kein admin");

if ($result && mysql_num_rows($result)==0):

	if (!$isadmin)
	{
	// Ignore neu setzen
	$f['i_user_aktiv']=$i_user_aktiv;
	$f['i_user_passiv']=$i_user_passiv;
	schreibe_db("iignore",$f,"","i_id");

	system_msg("",0,$i_user_aktiv,(isset($system_farbe) ? $system_farbe : ""),str_replace("%i_user_name_passiv%",$i_user_name_passiv,$t['ignore1']));
	system_msg("",0,$i_user_passiv,(isset($system_farbe) ? $system_farbe : ""),str_replace("%i_user_name_passiv%",$i_user_name_passiv,str_replace("%i_user_name_aktiv%",$i_user_name_aktiv,$t['ignore2'])));
	}
	else
	{
	system_msg("",0,$i_user_aktiv,$system_farbe,str_replace("%i_user_name_passiv%",$i_user_name_passiv,$t['ignore5']));
	}
	
	mysql_free_result($result);

elseif ($result && mysql_num_rows($result)>0):

	// Ignore löschen
	$i_id=mysql_result($result,0,"i_id");
	$query2="DELETE FROM iignore WHERE i_id=$i_id ";
	$result2=mysql_query($query2,$conn);

	system_msg("",0,$i_user_aktiv,$system_farbe,str_replace("%i_user_name_passiv%",$i_user_name_passiv,$t['ignore3']));
	system_msg("",0,$i_user_passiv,$system_farbe,str_replace("%i_user_name_passiv%",$i_user_name_passiv,str_replace("%i_user_name_aktiv%",$i_user_name_aktiv,$t['ignore4'])));

	mysql_free_result($result);

else:
	echo "Fataler Fehler bei ignore ($query)<BR>";
endif;



// Kopie in Onlinedatenbank aktualisieren
// Query muss mit dem Code in login() übereinstimmen
$query="SELECT i_user_passiv FROM iignore WHERE i_user_aktiv=$i_user_aktiv";
$result=mysql_query($query,$conn);

if ($result):

	if (mysql_num_rows($result)>0):
		while ($iignore=mysql_fetch_array($result)):
			$ignore[$iignore['i_user_passiv']]=TRUE;
		endwhile;
	else:
		$ignore[0]=FALSE;
	endif;
	mysql_free_result($result);

	$query="UPDATE online SET ".
		"o_ignore='".addslashes(serialize($ignore))."' WHERE o_user=$i_user_aktiv";
	mysql_query($query,$conn);
	@mysql_free_result($result);

endif;        


};



function nick_ergaenze($part,$scope="raum",$noerror=0) {
	//
	// gibt ein assoziativ_array $nick[u_nick], $nick[u_id] zurück, falls ein Nick ergänzt werden konnt.
	// falls kein Nick gefunden wurde oder mehrere Nicks gefunden wurde, werd bereits hier die Fehlerausgaben 
	// erzeugt
	//
	global $u_id;
	global $o_raum;
	global $u_farbe;
	global $system_farbe;
	global $t;
	global $dbase;
        global $conn;

	// initialisieren:
	unset($nickcomplete);

	// Letztes Zeichen nach Nick merken
	$letzes_zeichen=substr($part,-1);

	// ":" und "," am Ende kürzen.
	if (preg_match("![:,]$!",$part)) $part=substr($part,0,strlen($part)-1);
	if (substr($part,0,1)=="@") $part=substr($part,1,strlen($part)-1);

	
    	// sonderfall für Gäste -> falls nur Ziffern -> Gast ergänzen
    	if (preg_match("!\d$!",$part)) $ziff=".*"; else $ziff="";


	// User suchen, unterschiedliche Queries:
	switch ($scope) {
		case "raum":
			$fehler=$t['chat_msg44'];
			$answer=$t['chat_msg42'];
			$query="SELECT o_id,o_name,o_userdata,o_userdata2,o_userdata3,o_userdata4,(LENGTH(o_name)-length('$part')) as laenge ".
				"FROM online ".
				"WHERE o_name RLIKE '^$ziff$part' ".
				"AND o_raum=$o_raum ORDER BY laenge";
			break;
		case "chat":
			$fehler=$t['chat_msg26'];
			$answer=$t['chat_msg43'];
			$query="SELECT *,(LENGTH(u_nick)-length('$part')) as laenge ".
				"FROM user ".
				"WHERE u_nick RLIKE '^$ziff$part' ".
				"ORDER BY laenge";
			break;
		case "online":
			$query="SELECT o_id,o_name,o_userdata,o_userdata2,o_userdata3,o_userdata4,(LENGTH(o_name)-length('$part')) as laenge ".
				"FROM online ".
				"WHERE o_name RLIKE '^$ziff$part' ".
				"ORDER BY laenge";
			$fehler=$t['chat_msg25'];
			$answer=$t['chat_msg32'];
		break;
	}
	
	$result=mysql_query($query,$conn);
	if ($result>=0) {
		$rows=mysql_num_rows($result);
	} else {
		$rows=0;
	}

    if ($rows!=0) {
        $ok=0;
	for ($i=0; $i<$rows; $i++) {
            if (strcasecmp($part,@mysql_result($result,$i,"o_name"))==0) {
				$nickcomplete=unserialize(mysql_result($result,$i,"o_userdata").
							mysql_result($result,$i,"o_userdata2").
							mysql_result($result,$i,"o_userdata3").
							mysql_result($result,$i,"o_userdata4"));
				$nickcomplete['o_id']=mysql_result($result,$i,"o_id");
                $ok=1;
            }
	};
        if ($rows==1 || $ok==1) {
		// gefunden.
		if ($rows==1) {

		        if ($scope!="chat") {

				// Daten aus online Tabelle lesen, auspacken
				$nickcomplete=unserialize(mysql_result($result,0,"o_userdata").
							mysql_result($result,0,"o_userdata2").
							mysql_result($result,0,"o_userdata3").
							mysql_result($result,0,"o_userdata4"));
				$nickcomplete['o_id']=mysql_result($result,0,"o_id");

			} else {

				// Daten aus Usertabelle lesen
				mysql_data_seek($result,0);
				$nickcomplete=mysql_fetch_array($result,MYSQL_ASSOC);

			};
		}
        } else {
		// nicht ergänzen, ist nicht eindeutig. 
            	$answer=str_replace("%nickkurz%",$part,$answer);
		// Info aufbereiten, wer alles hätte angesprochen werden können
            	for ($i=0; $i<$rows; $i++) {
                	$answer = $answer . @mysql_result($result,$i,"o_name");
                	if ($i<$rows) $answer = $answer . " ";
            	}
		// und ausgeben.
            	system_msg("",0,$u_id,$system_farbe,$answer);
        }

    } elseif ($letzes_zeichen!=",") {
	// Fehler ausgeben, falls erstes Wort nicht mit einem "," endet ("fido, Du..." wird ergänzt, aber "Hallo, ich..." erzeugt keinen Fehler
	// : nicht gefunden.
	if (!$noerror) system_msg("",0,$u_id,$u_farbe,str_replace("%chatzeile%",$part,$fehler));
    }

	// Speicher freigeben.
    	mysql_free_result($result);

	// system_msg("",0,$u_id,$system_farbe,"Debug: $nickcomplete[u_nick], $rows, $part, $fehler");

	if (isset($nickcomplete))
	return $nickcomplete;
}


function auto_knebel($text) {
	global $admin;
	global $akdbase;
	global $dbase;
	global $u_id;
	global $u_nick;
	global $o_raum;
	global $t;
	global $ak;
	global $ak2;
	global $system_farbe;
        global $conn;
	global $knebelzeit;
	global $erweitertefeatures;
	

	// Prüfen ob private Nachricht
	$chatzeile=preg_split("/ /",$text,2);
	$chatzeile[0] = strtolower($chatzeile[0]);
	if (($chatzeile[0] == '/msg') or 
	    ($chatzeile[0] == '/talk') or
	    ($chatzeile[0] == '/tell') or
	    ($chatzeile[0] == '/msgpriv') or
	    ($chatzeile[0] == '/t'))
	{
		$privatnachricht = true;
	}
	else
	{
		$privatnachricht = false;
	}
	unset ($chatzeile);

 
	if (!$knebelzeit) $knebelzeit = 5;


	// für temp-admins gibts nun auch keinen autoknebel mehr
	$query="SELECT o_level FROM online WHERE o_user = '$u_id'";
	$result=mysql_query($query);
	$a=mysql_fetch_array($result);
	if ($a['o_level'] == "A" || $a['o_level'] == "S" || $a['o_level'] == "C" || $a['o_level'] == "M") { $admin2=true;} else { $admin2=false;}
	
# system_msg("",0,$u_id,$system_farbe,"$admin2 , $akdbase, $dbase, $erweitertefeatures");

	// testen auf autoknebel...
	// if (!$admin2 && ($akdbase==$dbase || $erweitertefeatures)) {  nein, so nicht (fido)
	// alt: if (!$admin2 && ($akdbase==$dbase)) {
	if ($akdbase != $dbase) { $ak="";}
	if (!$admin2 ) {
		if ($ak2) {
			$ak2=str_replace("//","",$ak2);
			$ak2=explode("#",$ak2);
			 $ak=array_merge($ak,$ak2);
			}
		for ($i=0; $i<count($ak); $i++) {
			// Text in Kleinschrift gegen verbotene Text prüfen
			$testtext=strtolower($text);
			#$testtext=strip_tags($testtext);
			$testtext=str_replace("*","",$testtext);
			$testtext=str_replace("_","",$testtext);
			#$testtext=str_replace(".","\.",$testtext);
			
			if (isset($ak[$i]) && $ak[$i]) {
				if (preg_match($ak[$i],$testtext,$treffer)) {
				system_msg("",0,$u_id,$system_farbe,$t['knebel7']); 

				// Hole aktuelle Knebelendzeit und geplante neue
				$query= "select o_knebel, FROM_UNIXTIME(UNIX_TIMESTAMP(NOW())+$knebelzeit*60) as knebelneu ".
					"from online where o_user=$u_id";
				$result=mysql_query($query,$conn);
				$row=mysql_fetch_array($result);

				// system_msg("",0,$u_id,$system_farbe,"DEBUG: $row[o_knebel] ... $row[knebelneu] ");				

				// Nur wenn neue Zeit größer als alte, dann erneut knebeln
				if ($row[o_knebel] <= $row[knebelneu])
				{
					$query="update online set o_knebel=FROM_UNIXTIME(UNIX_TIMESTAMP(NOW())+$knebelzeit*60) ".
						"where o_user=$u_id";
					$result=mysql_query($query,$conn);
				}

				// admins benachrichtigen, immer, auch wenn schon geknebelt gewesen
				$query="SELECT r_name FROM raum WHERE r_id=$o_raum";
				$result=mysql_query($query,$conn);
				$r_name=mysql_result($result,0,"r_name");
				$query="SELECT o_level,o_name,o_user,r_name FROM online,raum WHERE (o_level='S' OR o_level='C') AND r_id=o_raum ";
				$result=mysql_query($query,$conn);
				if ($result>0) {
						if (mysql_num_rows($result)>0) {
								while ($row=mysql_fetch_array($result)) {
									$txt=str_replace("%user%",user($u_id,0,TRUE,FALSE,"","","",FALSE),$t['knebel8']);
									$txt=str_replace("%raum%",$r_name,$txt);
									system_msg("",0,$row['o_user'],$system_farbe,$txt);
									if (!$privatnachricht)
									{
										system_msg("",0,$row['o_user'],$system_farbe,$t['knebel9']." ".htmlspecialchars($text));
									}
									else
									{
										system_msg("",0,$row['o_user'],$system_farbe,$t['knebel10']." ".htmlspecialchars($treffer[0]));
									}
								}
							}
						}
					$text="";
				}
			}
		}
	}
	return $text;
}


?>