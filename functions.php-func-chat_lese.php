<?php
// $Id: functions.php-func-chat_lese.php,v 1.14 2012/10/17 06:16:53 student Exp $

function chat_lese($o_id,$raum,$u_id,$sysmsg,$ignore,$back,$nur_privat=FALSE,$nur_privat_user="") {
// Gibt Text gefiltert aus
// $raum = ID des aktuellen Raums
// $u_id = ID des aktuellen Users

global $dbase,$user_farbe,$letzte_id,$chat,$system_farbe,$t,$chat_status_klein,$admin;
global $u_farbe_alle,$u_farbe_noise,$u_farbe_priv,$u_farbe_sys,$u_farbe_bg,$u_nick,$u_level,$u_smilie,$u_systemmeldungen;
global $show_spruch_owner,$farbe_user_fest,$id,$http_host;
global $user_nick, $conn;

// Systemfarbe setzen
if ($u_farbe_sys!="-") $system_farbe=$u_farbe_sys;

// Workaround, falls User in Community ist
if (!$raum) $raum="-1";

// Voreinstellung
$text_ausgegeben=FALSE;
$erste_zeile=TRUE;
$br="<BR>\n";
$qquery="";

// Optional keine Systemnachrichten
if (!$sysmsg)
        $qquery.=" AND c_typ!='S'";

// Optional keine öffentlichen oder versteckten Nachrichten
if ($nur_privat)
        $qquery.=" AND c_typ!='H' AND c_typ!='N'";

if ($nur_privat_user)
		{
		#echo "nur_privat_user ist gesetzt! $nur_privat_user | $u_id";
			$txt="<b>$u_nick flüstert an ".$user_nick.":</b>";
			$len=strlen($txt);
			#print $txt;
			$qquery.=" AND (c_an_user = '$u_id' and c_von_user_id != '0' and ( (c_von_user_id = '$u_id' and left(c_text,$len) = '$txt') or c_von_user_id = '$nur_privat_user') )";
		#print htmlentities($qquery);
		}
if ($back==1){

	// o_chat_id lesen
	$query="SELECT HIGH_PRIORITY o_chat_id FROM online WHERE o_id=$o_id";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result && mysql_num_rows($result)==1) {
		$o_chat_id=mysql_result($result,0,"o_chat_id");
	}else {
		$o_chat_id=0;
	};
	mysql_free_result($result);

	// Nachrichten ab o_chat_id (Merker) in Tabelle online ausgeben
	// Nur Nachrichten im aktuellen Raum anzeigen, außer Typ P oder S und an User adressiert
	$query="SELECT c_id FROM chat WHERE c_raum='$raum' AND c_id >= $o_chat_id".$qquery;

	unset($rows);
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result){
		while ($row=mysql_fetch_row($result)) {
			$rows[]=$row[0];
		}
	};
	mysql_free_result($result);

	$query="SELECT c_id FROM chat WHERE c_typ IN ('P','S') AND c_an_user=$u_id AND c_id >= $o_chat_id".
		$qquery;
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result){
		while ($row=mysql_fetch_row($result)) {
			$rows[]=$row[0];
		}
	};
	mysql_free_result($result);
	if (isset($rows) && is_array($rows)) sort($rows);


} elseif ($back>1) {

	// o_chat_id lesen (nicht bei Admins)
	// Admins dürfen alle Nachrichten sehen
	if (!$admin) {
		$query="SELECT HIGH_PRIORITY o_chat_id FROM online WHERE o_id=$o_id";
		$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result && mysql_num_rows($result)==1) {
			$o_chat_id=mysql_result($result,0,"o_chat_id");
		} else {
			$o_chat_id=0;
		};
		mysql_free_result($result);
	} else {
		$o_chat_id=0;
	};

	// $back-Zeilen in Tabelle online ausgeben, höchstens  aber ab o_chat_id
	// Nur Nachrichten im aktuellen Raum anzeigen, außer Typ P oder S und an User adressiert
	$query="SELECT c_id FROM chat WHERE c_raum='$raum' AND c_id >= $o_chat_id".
		$qquery;

	unset($rows);
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result){
		while ($row=mysql_fetch_row($result)) {
			$rows[]=$row[0];
		}
	};
	mysql_free_result($result);

	$query="SELECT c_id FROM chat WHERE c_typ IN ('P','S') AND c_an_user=$u_id AND c_id >= $o_chat_id".
		$qquery;

	##### zu debug-zwecken dies mal einkommentieren ####
	#print htmlentities($query);
	
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result){
		while ($row=mysql_fetch_row($result)) {
			$rows[]=$row[0];
		}
	};
	mysql_free_result($result);
	if (isset($rows) && is_array($rows)) sort($rows);

	// Erste Zeile ausrechnen, ab der Nachrichten ausgegeben werden
	// Chat-Zeilen vor $o_chat_id löschen
	if (isset($rows))
	{
		$zeilen=count($rows);
	}
	else
	{
		$zeilen = 0;
	}
	if ($zeilen>$back){
		$o_chat_id=$rows[($zeilen-intval($back))];
		foreach ($rows as $key => $value) {
			if ($value < $o_chat_id) {
				unset($rows[$key]);
			}
		};
	};


} else {

	// Die letzten Nachrichten seit $letzte_id ausgeben
	// Nur Nachrichten im aktuellen Raum anzeigen, außer Typ P oder S und an User adressiert
	$query="SELECT c_id FROM chat WHERE c_raum=$raum AND c_id > $letzte_id".
		$qquery;

	unset($rows);
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result){
		while ($row=mysql_fetch_row($result)) {
			$rows[]=$row[0];
		}
	};
	@mysql_free_result($result);

	$query="SELECT c_id FROM chat WHERE c_typ IN ('P','S') AND c_an_user=$u_id AND c_id > $letzte_id".
		$qquery;

	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result){
		while ($row=mysql_fetch_row($result)) {
			$rows[]=$row[0];
		}
	};
	@mysql_free_result($result);
	if (isset($rows) && is_array($rows)) sort($rows);

};


// + für regulären Ausdruck filtern
$nick=str_replace("+","\\+",$u_nick);


// Query aus Array erzeugen und die Chatzeilen lesen
if (isset($rows) && is_array($rows)) {
	$query="SELECT * FROM chat WHERE c_id IN (".implode(",",$rows).") ORDER BY c_id";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
} else {
	unset($result);
};


// Nachrichten zeilenweise ausgeben
if (isset($result) && $result){

	$text_weitergabe = "";                
	while ($row=mysql_fetch_object($result)){


		// Falls ID ignoriert werden soll -> Ausgabe überspringen
		// Falls noch kein Text ausgegeben wurde und es eine Zeile in 
		// der Mitte oder am Ende einer Serie ist -> Ausgabe überspringen

		// Systemnachrichten, die <<< oder >>> an Stelle 4-16 enthalten herausfiltern
		$ausgeben = true;
                if ($u_systemmeldungen == "N")
		{	
			if (($row->c_typ == "S") && (substr($row->c_text,3,12) == "&gt;&gt;&gt;" || substr($row->c_text,3,12) == "&lt;&lt;&lt;"))
				{ $ausgeben = false; }
		}

		// Die Ignorierten User rausfiltern                                                                       
                if (isset($ignore[$row->c_von_user_id]) && $ignore[$row->c_von_user_id])                                                                   
                       { $ausgeben = false; }      

		if ($ausgeben && ($text_ausgegeben || $row->c_br=="normal" || $row->c_br=="erste")){

 		// Alter Code 
		// if (!$ignore[$row->c_von_user_id] && ($text_ausgegeben || $row->c_br=="normal" || $row->c_br=="erste")){

			// Letzte ID merken
			$letzte_id=$row->c_id;

			// Userfarbe setzen
			if (strlen($row->c_farbe)==0):
				$row->c_farbe="#".$user_farbe;
			else:
				$row->c_farbe="#".$row->c_farbe;
			endif;


			// Student: 29.08.07 - Problem ML BGSLH
			// Wenn das 255. Zeichen ein leerzeichen
			// ist, dann wird es in der Datenbank nicht
			// gespeichert, da varchar feld
			// hier wird es reingehängt, wenn es in sequenz am
			// anfang oder in der mitte, und der text nur 254
			// zeichen breit ist
			if ( (strlen($row->c_text)==254) && (($row->c_br=="erste") || ($row->c_br=="mitte")) )
			{
				$row->c_text.=' ';
			}

			// Text filtern
			$c_text=stripslashes($row->c_text);
			$c_text=$text_weitergabe.$c_text;
			$text_weitergabe = "";

			// Merken, dass Text ausgegeben wurde
			$text_ausgegeben=TRUE;


			// Smilies ausgeben oder unterdrücken
			if ($u_smilie=="N") {
				$c_text=str_replace("<SMIL","<small>&lt;SMILIE&gt;</small><!--",$c_text);
				$c_text=str_replace("SMIL>","-->",$c_text);
			} else {
				$c_text=str_replace("<SMIL","<IMG",$c_text);
				$c_text=str_replace("SMIL>",">",$c_text);
			}


			// bugfix: gegen große Is... wg. verwechslung mit kleinen Ls ...
			// $row->c_von_user=str_replace("I","i",$row->c_von_user);

			if ($chat_status_klein) {
				$sm1="<small>";
				$sm2="</small>";
			} else {
				$sm1="";
				$sm2="";
			}


			// In Zeilen mit c_br=letzte das Zeilenende/-umbruch ergänzen
			if ($row->c_br=="erste" || $row->c_br=="mitte") {
				$br="";
			} else {
				$br="<BR>\n";
			};

			// im Text die Session-IDs in den Platzhalter <ID> einfügen
			if ($id) $c_text=str_replace("<ID>",$id,$c_text);

			// alternativ, falls am ende der Zeile, und das "<ID>" auf 2 Zeilen verteilt wird
			if ( ($id) && (($row->c_br=="erste") || ($row->c_br=="mitte")) )  
			{
				if (substr($c_text, -3) == '<ID')
				{
					$text_weitergabe = substr($c_text, -3);
					$c_text = substr($c_text, 0, -3);
				}
				
				if (substr($c_text, -2) == '<I')
				{
					$text_weitergabe = substr($c_text, -2);
					$c_text = substr($c_text, 0, -2);
				}
				
				if (substr($c_text, -1) == '<')
				{
					$text_weitergabe = substr($c_text, -1);
					$c_text = substr($c_text, 0, -1);
				}
			}
			
			// im Text die HTTP_HOST in den Platzhalter <HTTP_HOST> einfügen
			$c_text=str_replace("<HTTP_HOST>",$http_host,$c_text);

			// Verschienen Nachrichtenarten unterscheiden und Nachricht ausgeben
			switch ($row->c_typ) {

				case "S":
					if (($admin || $u_level=="A" )) {
						$c_text=str_replace("<!--","",$c_text);
						$c_text=str_replace("-->","",$c_text);
					}
					// S: Systemnachricht
					if ($row->c_an_user):
						// an aktuellen User
						if(!$erste_zeile):
							$zanfang="";
						else:
							$zanfang=$sm1."<FONT COLOR=\"$system_farbe\" TITLE=\"$row->c_zeit\">";
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="</FONT>".$sm2.$br;
						endif;
					else:
						// an alle User
						if(!$erste_zeile):
							$zanfang="";
						else:
							$zanfang=$sm1."<FONT COLOR=\"$system_farbe\" TITLE=\"$row->c_zeit\"><B>$chat:</B>&nbsp;";
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="</FONT>".$sm2.$br;
						endif;
					endif;
				break;

				case "P":
					// P: Privatnachricht an einen User

					// Falls dies eine Folgezeile ist, Von-Text unterdrücken

					if (strlen($row->c_von_user)!=0):
						if ($u_farbe_priv!="-") $row->c_farbe="#$u_farbe_priv";
						if (!$erste_zeile):
							$zanfang="";
						else:
							$temp_von_user=str_replace("<ID>",$id,$row->c_von_user);
							$temp_von_user=str_replace("<HTTP_HOST>",$http_host,$temp_von_user);
							$zanfang="<FONT COLOR=\"".$row->c_farbe."\" TITLE=\"$row->c_zeit\"><B>".$temp_von_user."&nbsp;($t[chat_lese1]):</B> ";
							#$zanfang="<FONT COLOR=\"".$row->c_farbe."\"><B>&nbsp;($t[chat_lese1]):</B> ";
							#$zanfang=htmlentities($zanfang);
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="</FONT>".$br;
						endif;
					else:
						if (!$erste_zeile):
							$zanfang="";
						else:
							$temp_von_user=str_replace("<ID>",$id,$row->c_von_user);
							$temp_von_user=str_replace("<HTTP_HOST>",$http_host,$temp_von_user);
							$zanfang=$sm1."<FONT COLOR=\"$system_farbe\" TITLE=\"$row->c_zeit\"><B>".$temp_von_user."&nbsp;($t[chat_lese1]):</B> ";
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="</FONT>".$sm2.$br;
						endif;
					endif;
				break;

				case "H":
					// H: Versteckte Nachricht an alle ohne Absender
					if ($show_spruch_owner && ($admin || $u_level=="A" )) {
						$c_text=str_replace("<!--","<b>",$c_text);
						$c_text=str_replace("-->","</b>",$c_text);
					}
					if ($row->c_von_user_id!=0):
						// eigene Farbe für noise, falls gesetzt.
						if ($u_farbe_noise!="-") $row->c_farbe="#$u_farbe_noise";
						if (!$erste_zeile):
							$zanfang="";
						else:
							$zanfang="<FONT COLOR=\"$row->c_farbe\" TITLE=\"$row->c_zeit\"><I>&lt;";
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="&gt;</I></FONT>".$br;
						endif;
					else:
						if (!$erste_zeile):
							$zanfang="";
						else:
							$zanfang=$sm1."<FONT COLOR=\"$system_farbe\" TITLE=\"$row->c_zeit\"><I>&lt;";
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="&gt;</I></FONT>".$sm2.$br;
						endif;
					endif;
				break;

				default:
					// N: Normal an alle mit Absender
					// eigene Farbe, falls gesetzt
					if ($row->c_von_user_id!=$u_id && $u_farbe_alle!="-") $row->c_farbe=$u_farbe_alle;

					// eigene Farbe für nachricht an Privat, falls gesetzt.
					if (preg_match("/\[.*&nbsp;$nick\]/i",$c_text) && $u_farbe_priv!="-") $row->c_farbe=$u_farbe_priv;

					// Nur Nick in Userfarbe oder ganze Zeile
					if ($farbe_user_fest) {
						if (!$erste_zeile):
							$zanfang="";
						else:
							$temp_von_user=str_replace("<ID>",$id,$row->c_von_user);
							$temp_von_user=str_replace("<HTTP_HOST>",$http_host,$temp_von_user);
							$zanfang="<FONT COLOR=\"".$row->c_farbe."\" TITLE=\"$row->c_zeit\"><B>".$temp_von_user.":</B> </FONT><FONT COLOR=\"$system_farbe\" TITLE=\"$row->c_zeit\"> ";
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="</FONT>".$br;
						endif;
					} else {
						if (!$erste_zeile):
							$zanfang="";
						else:
							$temp_von_user=str_replace("<ID>",$id,$row->c_von_user);
							$temp_von_user=str_replace("<HTTP_HOST>",$http_host,$temp_von_user);
							$zanfang="<FONT COLOR=\"".$row->c_farbe."\" TITLE=\"$row->c_zeit\">"."<B>".$temp_von_user.":</B> ";
						endif;
						if($br==""):
							$zende="";
						else:
							$zende="</FONT>".$br;
						endif;
					};
			};
			// Chatzeile ausgeben
			echo $zanfang.$c_text.$zende;

			// Ist aktuelle Zeile die erste Zeile oder eine Folgezeile einer Serie ?
			// Eine Serie steht immer am Stück in der DB (schreiben mit Lock Table)
			// Falls br gesetzt ist, ist nächste Zeile eine neue Zeile (erste zeile einer Serie)
			if ($br) {
				$erste_zeile=TRUE;
			} else {
				$erste_zeile=FALSE;
			}

		};

	};

};

if (isset($result)) mysql_free_result($result);

flush();
return($text_ausgegeben);

};

	
?>
