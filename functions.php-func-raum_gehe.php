<?php

// $Id: functions.php-func-raum_gehe.php,v 1.17 2012/10/17 06:16:53 student Exp $

function raum_gehe($o_id,$u_id,$u_name,$raum_alt,$raum_neu,$geschlossen) {
// user $u_id/$u_name geht von $raum_alt in Raum $raum_neu
// falls $geschlossen=TRUE -> auch geschlossene Räume betreten
// Nachricht in Raum $r_id wird erzeugt
// ID des neuen Raums wird zurückgeliefert
 
global $dbase,$conn,$chat,$admin,$u_level,$u_punkte_gesamt,$farbe_chat_background2,$t,$beichtstuhl,$lobby,$timeout;
global $http_host,$id, $erweitertefeatures, $forumfeatures, $communityfeatures;
global $raum_eintrittsnachricht_anzeige_deaktivieren, $raum_austrittsnachricht_anzeige_deaktivieren;
global $raum_eintrittsnachricht_kurzform, $raum_austrittsnachricht_kurzform;

// Info zu altem Raum lesen
$query="SELECT r_name,r_status1,r_austritt,r_min_punkte from raum ".
	"WHERE r_id=$raum_alt ";

$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                
if ($result && mysql_num_rows($result)==1):
	$alt=mysql_fetch_object($result);
	mysql_free_result($result);
endif;



// Ist User aus dem Raum ausgesperrt?
$query="SELECT s_id FROM sperre WHERE s_raum=$raum_neu AND s_user=$u_id";
$result=@mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
$rows=@mysql_Num_Rows($result);
if ($rows==0):
	$gesperrt=0;
else:
	$gesperrt=1;
endif;
@mysql_free_result($result);


// Info zu neuem Raum lesen
$query="SELECT * from raum ".
	"WHERE r_id=$raum_neu ";

$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

if ($result && mysql_num_rows($result)==1):
	$neu=mysql_fetch_object($result);
	mysql_free_result($result);

	// Online Punkte Holen, damit der User zum Raumwechsel nicht ein/ausloggen muss
	$o_punkte = 0;
	if ($erweitertefeatures == 1)
	{
		$query2="SELECT o_punkte FROM online WHERE o_id=$o_id ";	
		$result2=mysql_query($query2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		if ($result2 && mysql_num_rows($result2)==1)
		{
        		$online=mysql_fetch_object($result2);	
			mysql_free_result($result2);
			$o_punkte = $online->o_punkte;
			unset($online);
		}
		unset($query2);
		unset($result2);

	}

	// wenn hier nach Erweitertefeatures oder Punkte geprüft werden würde, was sinn machen würde,
	// kommen User aus Kostenlosen chats, die mit der MainChat Community verbunden sind, trotzdem in den Raum, 
	// trotz zu wenigen Punkten
	if (($neu->r_name != $lobby) && ($neu->r_min_punkte > ($u_punkte_gesamt+$o_punkte)) && !$admin && $u_level!="A") 
	{
		$zuwenigpunkte = 1;
	} 
	else 
	{
		$zuwenigpunkte = 0;	
	}

	$raumwechsel=false;
	// Prüfen ob Raum geschlossen oder Admin
	// Prüfen, ob Raumwechsel erlaubt...

	// Raumwechsel erlaubt wenn Raum nicht geschlossen und user nicht gesperrt.
	if ($neu->r_status1=="G" || $neu->r_status1=="M" || $zuwenigpunkte == 1) {
		// Raum geschlossen. nur rein, wenn auf invite liste.
		$query="SELECT inv_user FROM invite WHERE inv_raum=$neu->r_id AND inv_user=$u_id";
		$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result>0) {
			if (mysql_num_rows($result)>0) $raumwechsel=true;
			mysql_free_result($result);
		}
		// oder falls user=raumbesitzer...
		// macht wenig sinn, das ein RB in seinen Raum ein ! angeben muss
		//if ($neu->r_besitzer==$u_id && $geschlossen) $raumwechsel=true;
		if ($neu->r_besitzer==$u_id) $raumwechsel=true;
	} else {
		// Raum offen, nur rein, wenn nicht gesperrt.
		if ($gesperrt == 0 && $zuwenigpunkte == 0) $raumwechsel=true;
	}


	// raumwechsel nicht erlaubt, wenn alter Raum teergrube (ausser für Admins + Tempadmins)
	if ($alt->r_status1=="L" && $u_level!="A" && !$admin) $raumwechsel=false;

	// für admin raumwechsel erlaubt.
	if ($admin && $geschlossen) $raumwechsel=true;	

	// Falls Beichtstuhl-Modus und $geschlossen!=TRUE, Anzahl der User im Raum
	// ermitteln. Der Raum darf betreten werden, wenn:
	// 1) genau ein Admin im Raum ist und
	// 2) kein User im Raum ist oder
	// 3) Raum temporär ist oder
	// 4) der Raum Lobby ist oder
	// 5) der User ein Admin ist
	if ($raumwechsel && $beichtstuhl && !$admin) {
		$query="SELECT r_id,count(o_id) as anzahl, ".
			"count(o_level='C') as CADMIN, count(o_level='S') as SADMIN, ".
			"r_name='$lobby' as LOBBY, r_status2='T' AS STATUS ".
			"FROM raum LEFT JOIN online ON o_raum=r_id ".
			"WHERE ((UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(o_aktiv)) <= $timeout ".
			"OR r_name='Lobby' OR r_status2='T') ".
			"AND r_id=$raum_neu ".
			"GROUP BY r_id HAVING anzahl=1 AND (CADMIN=1 OR SADMIN=1) OR LOBBY OR STATUS";
		// system_msg("",0,$u_id,"","DEBUG $query");
		$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		if ($result && mysql_num_rows($result)==1) {
			$raumwechsel=TRUE;
		} else {
			$raumwechsel=FALSE;
		}
		@mysql_free_result($result);

	};

	// Darf Raum nun betreten werden?
	if ($raumwechsel) {

		// Raum verlassen
		$back=nachricht_verlasse($raum_alt,$u_name,$alt->r_name);

		// back in DB merken
		$f['o_chat_id']=$back;
		schreibe_db("online",$f,$o_id,"o_id");

		// Neuen Raum eintragen
		$query="UPDATE online SET o_raum=$raum_neu ".
			"WHERE o_user=$u_id ".
			"AND   o_raum=$raum_alt";
		$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		// Austrittstext
		if($t['raum_gehe1']) {
			$txt=$t['raum_gehe1']." ".$alt->r_name.":";
		} else {
			unset($txt);
		}
		
		if ($raum_austrittsnachricht_kurzform == "1") unset ($txt);
		
		if (strlen($alt->r_austritt)>0) {
			$txt="<B>$txt</B> $alt->r_austritt<BR>";
		} else {
			unset($txt);
		}

		if ($raum_austrittsnachricht_anzeige_deaktivieren == "1") unset($txt);

		if (!isset($txt)) $txt = "";
		// Trenner zwischen den Räumen, Austrittstext
		system_msg("",0,$u_id,""," ");
		system_msg("",0,$u_id,"",$txt."<BR><TABLE WIDTH=100% BGCOLOR=\"$farbe_chat_background2\" BORDER=0 CELLPADDING=0 CELLSPACING=0><TR><TD>".
				"<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR></TD></TR></TABLE>\n");

		// Raum betreten
		nachricht_betrete ($u_id,$raum_neu,$u_name,$neu->r_name);


		// Wenn der Neue Raum eine Teergrube ist, dann Eingabzeile aktualisieren, daß der [FORUM] Link verschwindet
		// Es sei denn man ist Admin, dann braucht es nicht aktualisiert werden, denn der Link wird nicht ausgeblendet
		// bzw. wenn alter Raum Teergrube war, dann auch aktualisieren
		// $u_id über Online Tabelle, da der User auch geschubst werden kann, deswegen dessen o_vhost und o_hash 

		if (($forumfeatures) && ($communityfeatures) && (!$beichtstuhl) && (($neu->r_status1 == "L") || ($alt->r_status1 == "L")) && ($u_level!="A") && (!$admin))
		{
			$query2="SELECT o_hash, o_vhost FROM online WHERE o_id=$o_id ";	
			$result2=mysql_query($query2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

			if ($result2 && mysql_num_rows($result2)==1)
			{
        			$online=mysql_fetch_object($result2);	
				mysql_free_result($result2);

				system_msg("",0,$u_id,"","<SCRIPT LANGUAGE=JavaScript>parent.frames[3].location.href='eingabe.php?http_host=$online->o_vhost&id=$online->o_hash';</SCRIPT>");		

				unset($online);
			}
			unset($query2);
			unset($result2);
			
		}


		// Nachricht falls gesperrt ausgeben
		if ($gesperrt || $zuwenigpunkte):
			system_msg("",0,$u_id,"",str_replace("%r_name_neu%",$neu->r_name,$t['raum_gehe2']));
		endif;

		// Topic vorhanden? ausgeben
		if ($t['raum_gehe6']) {
			$txt=$t['raum_gehe6']." ".$neu->r_name.":";
		} else {
			unset($txt);
		}
		if (strlen($neu->r_topic)>0) {
			system_msg("",0,$u_id,"","<BR><B>$txt</B> $neu->r_topic");
		}
  
		// Eintrittsnachricht
		if($t['raum_gehe3']) {
			$txt=$t['raum_gehe3']." ".$neu->r_name.":";
		} else {
			unset($txt);
		}

		if ($raum_eintrittsnachricht_kurzform == "1") unset($txt);
				
		if ($raum_eintrittsnachricht_anzeige_deaktivieren == "1")
		{
		}
		else if (strlen($neu->r_eintritt)>0){
			system_msg("",0,$u_id,"","<BR><B>$txt $neu->r_eintritt, $u_name!</B><BR>");
		} else {
			system_msg("",0,$u_id,"","<BR><B>$txt</B> $t[betrete_chat2], $u_name!</B><BR>");
		}

		$raum=$raum_neu;

	} else {
		// Raum kann nicht betreten werden
		system_msg("",0,$u_id,"",str_replace("%r_name_neu%",$neu->r_name,$t['raum_gehe4']));

		// Nachricht das gesperrt ausgeben
		if ($gesperrt):
			system_msg("",0,$u_id,"",str_replace("%r_name_neu%",$neu->r_name,$t['raum_gehe5']));
		endif;

		// Nachricht das zu wenige Punkte ausgeben
		if ($zuwenigpunkte):
			if ($u_level=="G")
			{
				$fehler = str_replace("%r_name_neu%",$neu->r_name,$t['raum_gehe8']);
			}
			else
			{
				$fehler = str_replace("%r_name_neu%",$neu->r_name,$t['raum_gehe7']);
			}
			$fehler = str_replace("%r_min_punkte%",$neu->r_min_punkte,$fehler);
			system_msg("",0,$u_id,"",$fehler);
			unset($fehler);
		endif;

		$raum=$raum_alt;
	}
endif;

return($raum);
}
?>
