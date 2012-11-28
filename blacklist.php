<?php

// fidion GmbH mainChat
// $Id: blacklist.php,v 1.5 2012/10/17 06:16:53 student Exp $

require ("functions.php");

// Vergleicht Hash-Wert mit IP und liefert u_id, u_name, o_id, o_raum, o_js
id_lese($id);

// Fenstername
$fenster=str_replace("+","",$u_nick);
$fenster=str_replace("-","",$fenster);
$fenster=str_replace("ä","",$fenster);
$fenster=str_replace("ö","",$fenster);
$fenster=str_replace("ü","",$fenster);
$fenster=str_replace("Ä","",$fenster);
$fenster=str_replace("÷","",$fenster);
$fenster=str_replace("Ü","",$fenster);
$fenster=str_replace("ß","",$fenster);


// Kopf ausgeben
?>
<HTML>
<HEAD><TITLE><?php echo $body_titel."_Blacklist"; ?></TITLE><META CHARSET=UTF-8>
<SCRIPT LANGUAGE=JavaScript>
        window.focus()
        function win_reload(file,win_name) {
                win_name.location.href=file;
        }
        function opener_reload(file,frame_number) {
                opener.parent.frames[frame_number].location.href=file;
        }
        function neuesFenster(url,name) {
                hWnd=window.open(url,name,"resizable=yes,scrollbars=yes,width=300,height=580");
        }
        function neuesFenster2(url) {
                hWnd=window.open(url,"<?php echo "640_".$fenster; ?>","resizable=yes,scrollbars=yes,width=780,height=580");
        }
        function toggle(tostat ) {
                for(i=0; i<document.forms["blacklist_loeschen"].elements.length; i++) {
                     e = document.forms["blacklist_loeschen"].elements[i];
                     if ( e.type=='checkbox' )
                         e.checked=tostat;
                }
        }
</SCRIPT>
<?php echo $stylesheet; ?>
</HEAD> 
<?php

// Body-Tag definieren
$body_tag="<BODY BGCOLOR=\"$farbe_mini_background\" ";
if (strlen($grafik_mini_background)>0):
        $body_tag=$body_tag."BACKGROUND=\"$grafik_mini_background\" ";
endif;
$body_tag=$body_tag."TEXT=\"$farbe_mini_text\" ".
                "LINK=\"$farbe_mini_link\" ".
                "VLINK=\"$farbe_mini_vlink\" ".
                "ALINK=\"$farbe_mini_vlink\">\n";
echo $body_tag;



// Timestamp im Datensatz aktualisieren
aktualisiere_online($u_id,$o_raum);


// Browser prüfen
if (ist_netscape()) {
        $eingabe_breite=30;
} else {
        $eingabe_breite=45;
}


if ($admin && $u_id && $communityfeatures) {


	// Menü als erstes ausgeben
	$box = $ft0."Menü Blacklist".$ft1;
	$text = "<A HREF=\"blacklist.php?http_host=$http_host&id=$id&aktion=\">Blacklist zeigen</A>\n".
		"| <A HREF=\"blacklist.php?http_host=$http_host&id=$id&aktion=neu\">Neuen Eintrag hinzufügen</A>\n".
		"| <A HREF=\"sperre.php?http_host=$http_host&id=$id\">Zugangssperren</A>\n";

	show_box2 ($box,$text,"100%");
	echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";

	if (!isset($neuer_blacklist)) $neuer_blacklist[] = "";
	if (!isset($sort)) $sort = "";
	

	switch($aktion) {

	case "neu":
		// Formular für neuen Eintrag ausgeben
		formular_neuer_blacklist($neuer_blacklist);
		break;

	case "neu2":
		// Neuer Eintrag, 2. Schritt: Nick Prüfen
		$neuer_blacklist['u_nick']=AddSlashes($neuer_blacklist['u_nick']); // sec
		$query="SELECT u_id FROM user WHERE u_nick = '$neuer_blacklist[u_nick]'";
		$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result && mysql_num_rows($result)==1) {
			$neuer_blacklist['u_id']=mysql_result($result,0,0);
			neuer_blacklist($u_id,$neuer_blacklist);
			unset($neuer_blacklist);
			$neuer_blacklist[] = "";
			formular_neuer_blacklist($neuer_blacklist);
			zeige_blacklist("normal","",$sort);
		} elseif ($neuer_blacklist['u_nick']=="") {
			echo "<B>Fehler:</B> Bitte geben Sie einen Nicknamen an!<BR>\n";
			formular_neuer_blacklist($neuer_blacklist);
		} else {
			echo "<B>Fehler:</B> Der Nickname '$neuer_blacklist[u_nick]' existiert nicht!<BR>\n";
			formular_neuer_blacklist($neuer_blacklist);
		}
		@mysql_free_result($result);
		break;

	case "loesche":
		// Eintrag löschen


		if (isset($f_blacklistid) && is_array($f_blacklistid)) {
			// Mehrere Einträge löschen
			foreach($f_blacklistid as $key => $loesche_id){
				loesche_blacklist($loesche_id);
			}

		} else {
			// Einen Eintrag löschen
			if (isset($f_blacklistid)) loesche_blacklist($f_blacklistid);
		}

		formular_neuer_blacklist($neuer_blacklist);
		zeige_blacklist("normal","",$sort);
		break;

	default:
		formular_neuer_blacklist($neuer_blacklist);
		zeige_blacklist("normal","",$sort);
	}

} else {
	echo "<P ALIGN=CENTER><B>Fehler:</B> Sie sind ausgelogt oder haben keine Berechtigung!</P>\n";
};

if ($o_js || !$u_id):
	echo $f1."<CENTER>[<A HREF=\"javascript:window.close();\">$t[sonst1]</A>]</CENTER>".$f2."<BR>\n";
endif;

?>
</BODY></HTML>
