<?php

// fidion GmbH mainChat
// chat.php muss mit id=$hash_id aufgerufen werden
// Optional kann $back als Trigger für die Ausgabe der letzten n-Zeilen angegeben werden

// $Id: chat.php,v 1.5 2012/10/17 06:16:53 student Exp $

require ("functions.php");

// Userdaten setzen
id_lese($id);

// Zeit in Sekunden bis auch im Normalmodus die Seite neu geladen wird
$refresh_zeit=600;

// Systemnachrichten ausgeben
$sysmsg=TRUE;

// Userdaten gesetzt?
if ($u_id) {

	// Timestamp im Datensatz aktualisieren -> User im Chat / o_who=0
	aktualisiere_online($u_id,$o_raum,0);

	// eigene Farbe für BG gesetzt? dann die nehmen.
	if ($u_farbe_bg!="" && $u_farbe_bg!="-") $farbe_chat_background1=$u_farbe_bg;

	// Algorithmus wählen
	if ($backup_chat || $u_backup):

		// n-Zeilen ausgeben und nach Timeout neu laden

		// Kopf ausgeben
		echo "<HTML><HEAD>\n".
			"<TITLE></TITLE><META CHARSET=\"UTF-8\">".
			"<META HTTP-EQUIV=\"expires\" content=\"0\" />\n".
			"<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"7; ".
			"URL=chat.php?http_host=$http_host&id=$id\" />\n".
			"<SCRIPT LANGUAGE=JavaScript>\n".
		        "setInterval(\"window.scrollTo(1,300000)\",100)\n".
			"function neuesFenster(url,name) {\n".
                	"hWnd=window.open(url,name,\"resizable=yes,scrollbars=yes,width=300,height=700\");\n".
			"}\n</SCRIPT>\n".
			$stylesheet."</HEAD>\n";

		// Body-Tag definieren
		$body_tag="<BODY BGCOLOR=\"$farbe_chat_background1\" ";
		if (strlen($grafik_background1)>0):
		        $body_tag=$body_tag."BACKGROUND=\"$grafik_background1\" ";
		endif;
		$body_tag=$body_tag."TEXT=\"$farbe_chat_text1\" ".
		                "LINK=\"$farbe_chat_link1\" ".        
		                "VLINK=\"$farbe_chat_vlink1\" ".
		                "ALINK=\"$farbe_chat_vlink1\">\n";

		echo $body_tag;

		// Chatausgabe, $letzte_id ist global
		chat_lese($o_id,$o_raum,$u_id,$sysmsg,$ignore,$chat_back);

		echo "</BODY></HTML>\n";

	else:

		// Endlos-Push-Methode - Normalmodus

		// Kopf ausgeben
		// header("Content-Type: multipart/mixed;boundary=myboundary");
		// echo "\n--myboundary\n";
		// echo "Content-Type: text/html\n\n";
		//echo "<HTML><HEAD>".
		//	"<SCRIPT LANGUAGE=JavaScript>\n".
		//	"function scroll() {\n".
		//	"window.scrollTo(0,50000);\n".
		//	"setTimeout(\"scroll()\",200);\n".
		//	"}\n".
		//	"setTimeout(\"scroll()\",100);\n".
		//	"</SCRIPT>\n".
		//	"$stylesheet</HEAD>\n";

		echo "<HTML><HEAD><META CHARSET=\"UTF-8\"><TITLE></TITLE>".
			"<META HTTP-EQUIV=\"expires\" content=\"0\" />\n".
			"<SCRIPT LANGUAGE=JavaScript>\n".
		        "setInterval(\"window.scrollTo(1,300000)\",100)\n".
			"function neuesFenster(url,name) {\n".
                	"hWnd=window.open(url,name,\"resizable=yes,scrollbars=yes,width=300,height=700\");\n".
			"}\n".
        		"function neuesFenster2(url) {\n";
			$tmp=str_replace("-","",$u_nick);
			$tmp=str_replace("+","",$tmp);
			$tmp=str_replace("-","",$tmp);
			$tmp=str_replace("ä","",$tmp);
			$tmp=str_replace("ö","",$tmp);
			$tmp=str_replace("ü","",$tmp);
			$tmp=str_replace("Ä","",$tmp);
			$tmp=str_replace("÷","",$tmp);
			$tmp=str_replace("Ü","",$tmp);
			$tmp=str_replace("ß","",$tmp);


                	echo "hWnd=window.open(url,\"640_$tmp\",\"resizable=yes,scrollbars=yes,width=780,height=700\");\n".
        		"}\n".
			"</SCRIPT>\n".
			"$stylesheet</HEAD>\n";

		// Body-Tag definieren
		$body_tag="<BODY BGCOLOR=\"$farbe_chat_background1\" ";
		if (strlen($grafik_background1)>0):
		        $body_tag=$body_tag."BACKGROUND=\"$grafik_background1\" ";
		endif;
		$body_tag=$body_tag."TEXT=\"$farbe_chat_text1\" ".
		                "LINK=\"$farbe_chat_link1\" ".        
		                "VLINK=\"$farbe_chat_vlink1\" ".
		                "ALINK=\"$farbe_chat_vlink1\">\n";


		echo $body_tag;



		// Voreinstellungen
		$j=0;
		$i=0;
		$beende_prozess=FALSE;
		set_time_limit($refresh_zeit+30);
		ignore_user_abort(FALSE);

		// 1 Sek pro Durchlauf fest eingestellt
		if ($erweitertefeatures && FALSE):	
			// Für 0,2 Sek pro Durchlauf
			$durchlaeufe=$refresh_zeit*5;
			$zeige_userliste=500;
		else:
			// Für 1 Sek pro Durchlauf
			$durchlaeufe=$refresh_zeit;
			$zeige_userliste=100;
		endif;

		while ($j<($durchlaeufe) && !$beende_prozess):

			// Raum merken
			$o_raum_alt=$o_raum;

			// Bin ich noch online?
			$result=mysql_query("SELECT HIGH_PRIORITY o_raum,o_ignore FROM online WHERE o_id=$o_id ", $conn);
			if ($result>0) {
				if (mysql_Num_Rows($result)==1) {
					$row=mysql_fetch_object($result);
					$o_raum=$row->o_raum;
					$ignore=unserialize($row->o_ignore);
				} else {
					// Raus aus dem Chat, vorher kurz warten
					sleep(10);
					$beende_prozess=TRUE;
				}
				mysql_free_result($result);
			}

			$j++;
			$i++;

			// Falls nach mehr als 100 sek. keine Ausgabe erfolgt, Userliste anzeigen
			// Nach > 120 Sekunden schlagen bei einigen Browsern Timeouts zu ;)
			if ($i>$zeige_userliste):
				if (isset($raum_msg) && $raum_msg != "AUS") 
				{
				system_msg("",0,$u_id,$system_farbe,$raum_msg);
				}
				else
				{
				raum_user($o_raum,$u_id,"");
				}
				$i=0;
			endif;

			// Raumwechsel?
			if (($back==0) && ($o_raum!=$o_raum_alt)):
				// Trigger für die letzten Nachrichten setzen
				$back=1;
			endif;

			// Chatausgabe, $letzte_id ist global
			// Falls Result=wahr wurde Text ausgegeben, Timer für Userliste zurücksetzen
			if (chat_lese($o_id,$o_raum,$u_id,$sysmsg,$ignore,$back)):
				$i=0;
			endif;

			// Trigger zurücksetzen
			$back=0;

			// 0,2 oder 1 Sekunde warten
			if ($erweitertefeatures && FALSE):
				usleep(200000); 
			else:
				sleep(1);
			endif;

			// Ausloggen falls Browser abgebrochen hat
			if (connection_status()!=0):
				// Verbindung wurde abgebrochen -> Schleife verlassen
				$beende_prozess=TRUE;
			endif;
        
			// Quidditch
			$quidditch = \Netzhuffle\MainChat\Quidditch\Quidditch::getInstance();
			$quidditch->doStack();

		endwhile;


		// Trigger für die Ausgabe der letzten 20 Nachrichten setzen
		$back=20;

		echo "</BODY></HTML>\n";
		// echo "\n\n--myboundary\nContent-Type: text/html\n\n";
		echo "<HTML><HEAD><META CHARSET=\"UTF-8\"><TITLE></TITLE>".
			"</HEAD><BODY onLoad='parent.chat.location=\"chat.php?http_host=$http_host&id=$id&back=$back\"'>\n".
			"</BODY></HTML>";
		flush();

	endif;


} else {
	// Auf Chat-Eingangsseite leiten
	echo "</BODY></HTML>\n";
	echo "<HTML><HEAD><META CHARSET=\"UTF-8\"><TITLE></TITLE>";
	echo "</HEAD><BODY onLoad='parent.location=\"index.php?http_host=$http_host\"'>\n";
	echo "</BODY></HTML>\n";
	exit;
};


?>
