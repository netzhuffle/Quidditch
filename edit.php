<?php

// fidion GmbH mainChat

// $Id: edit.php,v 1.28 2012/10/17 06:16:53 student Exp $

require ("functions.php");
require ("functions-msg.php");

// Vergleicht Hash-Wert mit IP und liefert u_id, u_name, o_id, o_raum, o_js, u_level, admin
id_lese($id);


?>
<HTML>
<HEAD><TITLE><?php echo $body_titel."_Einstellungen"; ?></TITLE><META CHARSET=UTF-8>
<SCRIPT LANGUAGE=JavaScript>
        window.focus()     
	function win_reload(file,win_name) {
        	win_name.location.href=file;
}
	function opener_reload(file,frame_number) {
        	opener.parent.frames[frame_number].location.href=file;
}
</SCRIPT>
<?php
echo $stylesheet;
echo "</HEAD>\n";  

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

if (ist_netscape()) {
	$input_breite=15;
	$passwort_breite=6;
} else {
	$input_breite=32;
	$passwort_breite=15;
}

// Login ok?
if (strlen($u_id)!=0):

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

// Ggf Farbe aktualisieren
if (isset($farbe) && strlen($farbe)>0):

	// In Userdatenbank schreiben
        $f['u_farbe']=$farbe;
        unset($f['u_id']);
        unset($f['u_level']);
        unset($f['u_name']);
        unset($f['u_email']);
        unset($f['u_adminemail']);
        unset($f['u_nick']);
        unset($f['u_auth']);
        unset($f['u_passwort']);
	unset($f['u_smilie']);
	unset($f['u_systemmeldungen']);
	unset($f['u_punkte_anzeigen']);
	unset($f['u_signatur']);
	unset($f['u_eintritt']);
	unset($f['u_austritt']);
        schreibe_db("user",$f,$u_id,"u_id");

	if ($o_js && $o_who==0):
		echo "<SCRIPT LANGUAGE=JavaScript>".
			"opener_reload('eingabe.php?http_host=$http_host&id=$id','3')".
			"</SCRIPT>\n";
	endif;
	unset($f['u_farbe']);
endif;

// Benutzer darf Passwort nicht ändern (optional)
if (!$einstellungen_aendern):
	unset($f['u_passwort']);
endif;

// Chat bei u_backup neu aufbauen, damit nach Umstellung der Chat refresht wird
if ($o_js && $o_who==0 && ((isset($u_backup) && $u_backup) || (isset($f['u_backup']) && $f['u_backup']))):
	echo "<SCRIPT LANGUAGE=JavaScript>".
		"opener_reload('chat.php?http_host=$http_host&id=$id&back=$chat_back','1')".
		"</SCRIPT>\n";
        echo "<SCRIPT LANGUAGE=JavaScript>".
                "opener_reload('eingabe.php?http_host=$http_host&id=$id','3')".             
                "</SCRIPT>\n";
endif;

$box = $ft0.$t['menue4'].$ft1;
if ($communityfeatures && $u_level!="G") {
	$ur1="profil.php?http_host=$http_host&id=$id&aktion=aendern";
	$url="HREF=\"$ur1\" TARGET=\"640_$fenster\" onclick=\"window.open('$ur1','640_$fenster','resizable=yes,scrollbars=yes,width=780,height=580'); return(false);\"";
	$text="<A $url>$t[menue7]</A>\n";
	$ur1="home.php?http_host=$http_host&id=$id&aktion=aendern";
	$url="HREF=\"$ur1\" TARGET=\"640_$fenster\" onclick=\"window.open('$ur1','640_$fenster','resizable=yes,scrollbars=yes,width=780,height=580'); return(false);\"";
	$text.="| <A $url>$t[menue10]</A>\n";
	$ur1="freunde.php?http_host=$http_host&id=$id";
	$url="HREF=\"$ur1\" TARGET=\"640_$fenster\" onclick=\"window.open('$ur1','640_$fenster','resizable=yes,scrollbars=yes,width=780,height=580'); return(false);\"";
	$text.="| <A $url>$t[menue9]</A>\n";
	$ur1="aktion.php?http_host=$http_host&id=$id";
	$url="HREF=\"$ur1\" TARGET=\"640_$fenster\" onclick=\"window.open('$ur1','640_$fenster','resizable=yes,scrollbars=yes,width=780,height=580'); return(false);\"";
	$text.="| <A $url>$t[menue8]</A>\n";
	if ($smsfeatures == "1")
	{
	$ur1="sms.php?http_host=$http_host&id=$id";
	$url="HREF=\"$ur1\" TARGET=\"640_$fenster\" onclick=\"window.open('$ur1','640_$fenster','resizable=yes,scrollbars=yes,width=780,height=580'); return(false);\"";
	$text.="| <A $url>SMS</A>\n";
	}
}
if (isset($text) && $text) show_box2 ($box,$text,"100%");
echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";

if ($aktion == "edit2")
{

	if ((strlen($f['u_name'])<4 || strlen($f['u_name'])>50))
	{
		echo "<P><B>$t[edit2]</B></P>\n";
		$aktion = "andereadminmail";
	}
	
	if (!preg_match("(\w[-._\w]*@\w[-._\w]*\w\.\w{2,3})",addslashes($f['u_adminemail'])))
	{
		echo "<P><B>$t[edit1]</B></P>\n";
		$aktion = "andereadminmail";
	}

	// oder Domain ist lt. Config verboten
	if ($domaingesperrtdbase != $dbase) { $domaingesperrt="";}
	for ($i=0; $i<count($domaingesperrt); $i++) 
	{ 
		$teststring = strtolower($f['u_adminemail']);
		if (($domaingesperrt[$i]) && (preg_match($domaingesperrt[$i],$teststring)))
		{
			echo "<P><B>$t[edit1]</B></P>\n";
			$aktion = "andereadminmail";
		}
	}       
                                                                                                                                                                                                                                                                	
}


// Auswahl
switch($aktion) {

case "andereadminmail":

	echo "<TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0 WIDTH=100% BGCOLOR=$farbe_tabelle_kopf>\n";
	echo "<TR><TD COLSPAN=2>";
	echo "<A HREF=\"javascript:window.close();\">".
    	     "<IMG SRC=\"pics/button-x.gif\" ALT=\"schließen\" ".
	     "WIDTH=15 HEIGHT=13 ALIGN=\"RIGHT\" BORDER=0></A>\n";
	echo "<FONT SIZE=-1 COLOR=$farbe_text><B>$box</B></FONT>\n";
	echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";
	echo "<TABLE CELLPADDING=5 CELLSPACING=0 BORDER=0 WIDTH=100% BGCOLOR=\"$farbe_tabelle_koerper\">\n";
	echo "<TR><TD COLSPAN=2>";	
	
	echo "<FORM NAME=\"$u_nick\" ACTION=\"edit.php\" METHOD=POST>\n".
	     "<INPUT TYPE=\"HIDDEN\" NAME=\"id\" VALUE=\"$id\">\n".
	     "<INPUT TYPE=\"HIDDEN\" NAME=\"http_host\" VALUE=\"$http_host\">\n".
	     "<INPUT TYPE=\"HIDDEN\" NAME=\"f[u_id]\" VALUE=\"$u_id\">\n".
	     "<INPUT TYPE=\"HIDDEN\" NAME=\"aktion\" VALUE=\"edit2\">\n";
																			
	echo "<TABLE BORDER=0 CELLPADDING=0 WIDTH=100%>";
		
	echo "<TR><TD COLSPAN=2>".
	     $f1.
	     "Sie können hier Ihren Username und die Interne E-Mailadresse abändern.<br><br>".
	     "Nach dem Ändern werden Sie automatisch ausgeloggt, und ein neues Passwort an ".
	     "Ihre neue E-Mailadresse gesendet. Mit dem neuen Passwort können Sie sich sofort ".
	     "einloggen und ggf. Ihr Passwort wieder anpassen.<br><br>".
	     "\n".$f2.
	     "</TD></TR>\n";

	echo "<TR><TD COLSPAN=2>".
	     $f1."<B>".$t['user_zeige17']."</B><BR>\n".$f2.
	     "<INPUT TYPE=\"TEXT\" VALUE=\"$u_name\" NAME=\"f[u_name]\" SIZE=$input_breite>".
	     "</TD></TR>\n";

	$query="SELECT user.* ".
		"FROM user WHERE u_id=$u_id ";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	$rows=mysql_num_rows($result);

	if ($rows==1)
	{
		$row=mysql_fetch_object($result);
		$u_adminemail=stripslashes($row->u_adminemail);
		mysql_free_result($result);
																																										
		echo "<TR><TD COLSPAN=2>".
		     $f1."<B>".$t['user_zeige3']."</B><BR>\n".$f2.
		     "<INPUT TYPE=\"TEXT\" VALUE=\"$u_adminemail\" NAME=\"f[u_adminemail]\" SIZE=$input_breite>".
		     "</TD></TR>\n";
	}

																				
	echo "</TABLE>\n";
	echo $f1."<BR><INPUT TYPE=\"SUBMIT\" NAME=\"eingabe\" VALUE=\"Ändern!\">".$f2;
	echo "</FORM>\n";
	echo "</TD></TR></TABLE></TD></TR></TABLE>\n";
																																								 
break;


case "edit2":

	if (($eingabe=="Ändern!") && ($u_id==$f['u_id']) && ($einstellungen_aendern))
	{
		
		$query="SELECT user.* FROM user WHERE u_id=$u_id ";
		$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		$rows=mysql_num_rows($result);

		if ($rows==1)
		{
			$row=mysql_fetch_object($result);
			$u_adminemail=stripslashes($row->u_adminemail);
			mysql_free_result($result);

			unset ($p);
			
			// Länge des Feldes und Format Mailadresse werden weiter oben geprüft
			$p['u_id'] = $u_id;
			$p['u_name'] = addslashes($f['u_name']);	
			$p['u_adminemail'] = $f['u_adminemail'];	
                        $pwdneu=genpassword(8);
			$p['u_passwort']=$pwdneu;
                                                
			$ok=mail($p['u_adminemail'],$t['chat_msg112'],str_replace("%passwort%",$p['u_passwort'],$t['chat_msg113']),"From: $webmaster ($chat)");
	                if ($ok) {
				echo "Die Änderungen wurden gespeichert und Sie wurden ausgeloggt. Ihr neues Passwort wurde an Ihre neue E-Mailadresse gesendet.";	                        
				echo $f1.$t['chat_msg111'].$f2;
				schreibe_db("user",$p,$p['u_id'],"u_id");
	                } else {
	                        echo $f1."<P><B>Fehler: Die Mail konnte nicht verschickt werden. Es wurden keine Einstellungen geändert!</B></P>".$f2;
	                };

			$user=$row->u_nick;
		        $query="SELECT o_id,o_raum,o_name FROM online WHERE o_user='$u_id' AND o_level!='C' AND o_level!='S'";

	        	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		        if ($result && mysql_num_rows($result)>0){
	        	        $row=mysql_fetch_object($result);
		                verlasse_chat($f['u_id'],$row->o_name,$row->o_raum);
	        	        logout($row->o_id,$f['u_id'],"edit->einständerung");
	                	mysql_free_result($result);
			};



		}
		
	}
	

break;

case "loesche":

	if ($eingabe=="Löschen!" && $admin):

		if ($u_id == $f['u_id']) {
			// nicht sich selbst löschen...
			print "$t[edit16]<BR>";
		} else {
			// test, ob zu löschender Admin ist...
			$query="SELECT * FROM user WHERE u_id=$f[u_id] ";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$del_level=mysql_result($result,0,"u_level");
			if ($del_level!="S" && $del_level!="C" && $del_level!="M") {

				// Userdaten löschen
				echo "<P><B>".str_replace("%u_nick%",$f['u_nick'],$t['menue5'])."</B></P>\n";
				$query="DELETE FROM user WHERE u_id=$f[u_id] ";
				$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

				// Ignore-Einträge löschen
				$query="DELETE FROM iignore WHERE i_user_aktiv=$f[u_id] OR i_user_passiv=$f[u_id]";
				$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

				// Gesperrte Räume löschen
				$query="DELETE FROM sperre WHERE s_user=$f[u_id]";
				$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			} else {
				echo "<P><B>".str_replace("%u_nick%",$f['u_nick'],$t['menue6'])."</B></P>\n";
			}
		}

	else:
		echo "<P><B>".str_replace("%u_nick%",$f['u_nick'],$t['menue6'])."</B></P>\n";
	endif;
break;


case "edit":


	if ( ((isset($eingabe) && $eingabe=="Ändern!") || (isset($farben['u_farbe']) && $farben['u_farbe'])) && ($u_id==$f['u_id'] || $admin)):

		// In Namen die unerlaubten Zeichen entfernen
		$f['u_nick']=coreCheckName($f['u_nick'],$check_name);

		// Bestimmte Felder dürfen nicht geändert werden
		unset($f['u_neu']);
		unset($f['u_login']);
		unset($f['u_agb']);
		unset($f['u_ip_historie']);

		// Nicht-Admin darf Einstellungen nicht ändern
		if (!$admin):
			unset($f['u_name']);
			unset($f['u_adminemail']);
			unset($f['u_level']);
			unset($f['u_kommentar']);
		endif;

		// Gast darf Daten nicht ändern
		if ($u_level=="G"):
		        unset($f['u_email']);
		        unset($f['u_auth']);
		        unset($passwort1);
		endif;

		$ok=1;

		// Farbe aus Farb-Popup in Hidden-Feld
		if ($farben['u_farbe']) $f['u_farbe']=$farben['u_farbe'];

		// Farbe direkt über Input-Feld
		if (substr($f['u_farbe'],0,1)=="#") 
		{
                        $f['u_farbe']=substr($f['u_farbe'],1,6);
                }
		if (strlen($f['u_farbe'])!=6) 
		{
			unset($f['u_farbe']);
		}
		else if (!preg_match("/[a-f0-9]{6}/i", $f['u_farbe'])) 
		{
			unset($f['u_farbe']);
		}

		// E-Mail ok
	        if (isset($f['u_email']) && (strlen($f['u_email'])>0) && (!preg_match("(\w[-._\w]*@\w[-._\w]*\w\.\w{2,3})",addslashes($f['u_email']))))
		{ 
			echo "<P><B>$t[edit1]</B></P>\n";
			unset($f['u_email']);
			$ok=0;
		}

		// Name muss 4-50 Zeichen haben
		if ($admin && (strlen($f['u_name'])<4 || strlen($f['u_name'])>50)):
			echo "<P><B>$t[edit2]</B></P>\n";
			unset($f['u_name']);
			$ok=0;
		endif;



		// Nick muss 4-20 Zeichen haben
		if (isset($keineloginbox) && !$keineloginbox && (strlen($f['u_nick'])<4 || strlen($f['u_nick'])>20)) 
		{
		
			// Wenn man den Nicknamen nicht ändern darf, und man User ist, dann den Parameter
			// sicherheitshalber löschen
			if (!$einstellungen_aendern && !$admin)
			{
				unset($f['u_nick']);
			}

			// Falls er oben gelöscht wurde, den Alten aus der Datenbank holen
			if ((!$einstellungen_aendern) && (strlen($f['u_nick'])==0)) 
			{
				// wird nicht übergeben, wenn $einstellungen_aendern==0, also aus DB laden falls $admin.
				$query="SELECT u_nick FROM user WHERE u_id=$f[u_id]";
				$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
				if ($result) {
					$f['u_nick']=mysql_result($result,0);
				}
			}

			// immernoch keine 4-20 Zeichen?
			// Hier müsste aus den oberen beiden Fällen der Nickname nun da sein,
			// Jetzt wird geprüft, ob man normalerweise den Nick ändern darf, und dort 4-20 
			// Zeichen eingegeben hat
			if (!$keineloginbox && (strlen($f['u_nick'])<4 || strlen($f['u_nick'])>20)) {
				echo "<P><B>$t[edit3]</B></P>\n";
				unset($f['u_nick']);
				$ok=0;
			}
		}


		// Homepage muss http:// enthalten
		if (isset($f['u_url']) && strlen($f['u_url'])>0 && !preg_match("/^http:\/\//i",$f['u_url'])):
			$f['u_url']="http://".$f['u_url'];
		endif;

		// nur Zahlen zulassen bei den Fenstergrößen
		$size['eingabe']=preg_replace("/[^0-9]/","",(isset($size['eingabe']) ? $size['eingabe'] : ""));
		$size['interaktiv']=preg_replace("/[^0-9]/","",(isset($size['interaktiv']) ? $size['interaktiv'] : ""));
		$size['chatuserliste']=preg_replace("/[^0-9]/","",(isset($size['chatuserliste']) ? $size['chatuserliste'] : ""));
		$size['interaktivforum']=preg_replace("/[^0-9]/","",(isset($size['interaktivforum']) ? $size['interaktivforum'] : ""));
		$size['messagesforum']=preg_replace("/[^0-9]/","",(isset($size['messagesforum']) ? $size['messagesforum'] : ""));

		// Gibts den User/Nicknamen schon?
		if ($f['u_nick']):
			$query="SELECT u_id FROM user ".
				"WHERE u_nick = '$f[u_nick]' AND u_id!=$f[u_id]";
			// echo "Debug: $query<BR>";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$rows=mysql_num_rows($result);
			if ($rows!=0):
				echo "<P><B>$t[edit7]</B></P>\n";
				unset($f['u_name']);
				unset($f['u_nick']);
				$ok=0;
			endif;
		else:
			// Nickname nicht schreiben (keine Änderung oder ungültiger Text)
			unset($f['u_nick']);
		endif;


		// Wenn noch keine 30 Sekunden Zeit seit der letzten Änderung vorbei sind, 
		// dann Nickname nicht speichern
		if (isset($f['u_nick']) && $f['u_nick'])
		{
			$query="SELECT u_nick_historie, u_nick FROM user WHERE u_id = '$f[u_id]'";
			$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
			$xyz=mysql_fetch_array($result);
			$nick_historie=unserialize($xyz['u_nick_historie']);
			$nick_alt=$xyz['u_nick'];
			
			if (is_array($nick_historie)):
				reset ($nick_historie);
				list($key, $value) = each ($nick_historie);
				$differenz=time()-$key;
			endif;
			if (!isset($differenz)) $differenz=999;
			if ($admin) $differenz=999;
			
			if ($nick_alt <> $f['u_nick'])
			{
			
				if ($differenz < $nickwechsel)
				{
					echo "<P><B>Sie dürfen Ihren Nicknamen nur alle $nickwechsel Sekunden ändern!</B></P>\n";
					unset($f['u_nick']);
				}
				else
				{
					$datum=time();
					$nick_historie_neu[$datum]=$nick_alt;
					if (is_array($nick_historie)):
                                                $i=0;
                                                while(($i<3) AND list($datum,$nick)=each($nick_historie)):
                                                        $nick_historie_neu[$datum]=$nick;
                                                        $i++;
                                                endwhile;
                                        endif;
                                        $f['u_nick_historie']=serialize($nick_historie_neu);               
                                } 
			}
		}		

		// Level "M" nur vergeben, wenn moderation in diesem Chat erlaubt ist.
		if (isset($f['u_level']) && $f['u_level']=="M" && !$moderationsmodul) {
			unset($f['u_level']);
			echo $t['edit18'];
		}
		
		// aufpassen, wenn Admin sich selbst ändert -> keine leveländerung zulassen.
		if ($u_id==$f['u_id'] && $admin) {
			if ($u_level!=$f['u_level']) print "$t[edit16]";
			unset($f['u_level']);
		}

		$query="SELECT u_level FROM user WHERE u_id=$f[u_id]";	
		$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result && mysql_num_rows($result)>0) {
			$uu_level=mysql_result($result,0,"u_level");

			// Falls Userlevel G -> Änderung verboten
			if (isset($f['u_level']) && strlen($f['u_level'])!=0 && $f['u_level']!="G" && $uu_level=="G") {
						echo $t['edit15'];
						unset($f['u_level']);
			}

			// uu_level = Level des Users, der geändert wird
			// u_level  = Level des Users, der ändert
			// Admin (C) darf für anderen Admin (C oder S) nicht ändern: Level, Passwort, Admin-EMail
			if ($u_id!=$f['u_id'] && $u_level=="C" && ($uu_level=="S" || $uu_level=="C")) {

				// Array Löschen
				echo $t['edit17']."<BR>";
				$ok=0;
			        $f=array(u_id=>$f['u_id']);
				unset($passwort1);
			}
			mysql_free_result($result);

		} else {

			// Per default nichts ändern -> Array Löschen
			echo $t['edit17']."<BR>";
			$ok=0;
		        $f=array(u_id=>$f['u_id']);
			unset($passwort1);
		};
		// echo "### $ok u_id $u_id u_level $u_level uu_level $uu_level<PRE>"; print_r($f);

		// Nur Superuser darf Level S oder C vergeben
		if ($ok && isset($f['u_level']) && ($f['u_level']=="S" || $f['u_level']=="C") && $u_level!="S"): 
			unset($f['u_level']);
			echo $t['edit17']."<BR>";
		endif;

		// Ist passwort gesetzt?
		if (isset($passwort1) && strlen($passwort1)>0):
			if ($passwort1!=$passwort2):
				echo "<P><B>$t[edit4]</B></P>\n";
				$ok=0;
			elseif (strlen($passwort1)<4):
				echo "<P><B>$t[edit5]</B></P>\n";
				$ok=0;
 			else:
				// Paßwort neu eintragen
				echo "<P><B>$t[edit6]</B></P>\n";
				$f['u_passwort']=$passwort1;
			endif;		
		endif; 

		// Fenstergrößen setzen
		if(is_array($size)) {
			$f['u_frames']=serialize($size);
		}


		// Userdaten schreiben
		if ($ok):

			if (isset($zeige_loesch) && $zeige_loesch!=1):

				// Änderungen anzeigen

				$query="SELECT o_userdata,o_userdata2,o_userdata3,o_userdata4,o_raum ".
					"FROM online ".
					"WHERE o_user=$f[u_id] ";
				$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
				if ($result && mysql_num_rows($result)==1):
					$row=mysql_fetch_object($result);
					$userdata=unserialize($row->o_userdata.$row->o_userdata2.$row->o_userdata3.$row->o_userdata4);
					if (($f['u_name']!=$userdata['u_name']) AND $f['u_name'] AND $admin):
						echo "<P><B>".str_replace("%u_name%",htmlspecialchars(stripslashes($f['u_name'])),$t['edit8'])."</B></P>\n";
					endif;
					if ($f['u_nick'] AND ($f['u_nick']!=$userdata['u_nick'])):
						echo "<P><B>".str_replace("%u_nick%",$f['u_nick'],$t['edit9'])."</B></P>\n";
						global_msg($u_id,$row->o_raum,str_replace("%u_nick%",$f['u_nick'],str_replace("%row->u_nick%",$userdata['u_nick'],$t['edit10'])));
					endif;
				endif;
				@mysql_free_result($result);
				echo "<P><B>$t[edit11]</B></P>\n";

			endif;

			$query="SELECT u_profil_historie FROM user WHERE u_id = '$f[u_id]'";
			$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
			$g=mysql_fetch_array($result);
						
			$g['u_profil_historie']=unserialize($g['u_profil_historie']);

			$datum=time();
	                $u_profil_historie_neu[$datum]=$u_nick;
			
			if (is_array($g['u_profil_historie'])):
		           $i=0;
			while(($i<3) AND list($datum,$nick)=each($g['u_profil_historie'])):
                            $u_profil_historie_neu[$datum]=$nick;
                            $i++;
                    		endwhile;
            		endif;
			$f['u_profil_historie']=serialize($u_profil_historie_neu);
			$f['u_eintritt']=addslashes(isset($f['u_eintritt']) ? $f['u_eintritt'] : "");
			$f['u_austritt']=addslashes(isset($f['u_austritt']) ? $f['u_austritt'] : "");

			schreibe_db("user",$f,$f['u_id'],"u_id");


			// Hat der User den u_level = 'Z', dann lösche die Ignores, wo er der Aktive ist
			if (isset($f['u_level']) && $f['u_level'] == "Z")
			{
				$queryii="SELECT u_nick,u_id from user,iignore ".
				       "WHERE i_user_aktiv=$f[u_id] AND u_id=i_user_passiv order by i_id";
				$resultii=@mysql_query($queryii,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
				$anzahlii=@mysql_num_rows($resultii);
		
				if ($resultii && $anzahlii>0)
				{
					for ($i=0; $i<$anzahlii; $i++) 
					{ 
						$rowii=@mysql_fetch_object($resultii);
						ignore($o_id,$f['u_id'],$f['u_nick'],$rowii->u_id,$rowii->u_nick);
					};
				}
				@mysql_free_result($resultii);
			}
                        // Hat der User den u_level = 'C' oder 'S', dann lösche die Ignores, wo er der Passive ist
                        else if ((isset($f['u_level']) && $f['u_level'] == "C") || (isset($f['u_level']) && $f['u_level'] == "S"))
                        {
                                $queryii="SELECT u_nick,u_id from user,iignore ".
                                       "WHERE i_user_passiv=$f[u_id] AND u_id=i_user_aktiv order by i_id";
                                $resultii=@mysql_query($queryii,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
                                $anzahlii=@mysql_num_rows($resultii);
                
                                if ($resultii && $anzahlii>0)
                                {
                                        for ($i=0; $i<$anzahlii; $i++)
                                        {
                				$rowii=@mysql_fetch_object($resultii);                                
						ignore($o_id,$rowii->u_id,$rowii->u_nick,$f['u_id'],$f['u_nick']);
                                        };
                                }
                                @mysql_free_result($resultii);
                        }


			// Warnung für sicheren Modus ausgeben
                        if (($u_backup == 0) && ($f['u_backup']==1) && ($u_id == $f['u_id'])) warnung($u_id,$u_nick,"sicherer_modus");

			// Eingabe-Frame mit Farben aktualisieren
			if ($o_js && $o_who==0 && isset($f['u_farbe']) && $f['u_farbe']):
				echo "<SCRIPT LANGUAGE=JavaScript>".
					"opener_reload('eingabe.php?http_host=$http_host&id=$id','3')".
				"</SCRIPT>\n";
			endif;
		endif;


		// Falls User auf Level "Z" gesetzt wurde -> logoff
		if (ist_online($f['u_id']) && isset($f['u_level']) && $f['u_level']=="Z"):

			// o_id und o_raum bestimmen
			$query="SELECT o_id,o_raum FROM online ".
				"WHERE o_user=$f[u_id] ";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$rows=mysql_num_rows($result);
	
			if ($rows>0):
				$row=mysql_fetch_object($result);
				verlasse_chat($f['u_id'],$f['u_nick'],$row->o_raum);
				logout($row->o_id,$f['u_id'],"edit->levelZ");
				echo "<P><B>".str_replace("%u_name%",$f['u_nick'],$t['edit12'])."</B></P>\n";
			endif;
			mysql_free_result($result);
		endif;


		// User mit ID $u_id anzeigen
		$query="SELECT user.* ".
			"FROM user WHERE u_id=$f[u_id] ";
		$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		if ($result && mysql_num_rows($result)==1):
			$row=mysql_fetch_object($result);
			$f['u_id']=$u_id;
			$f['u_name']=htmlspecialchars(stripslashes($row->u_name));
			$f['u_nick']=$row->u_nick;
			$f['u_id']=$row->u_id;
			$f['u_email']=htmlspecialchars(stripslashes($row->u_email));
			$f['u_adminemail']=htmlspecialchars(stripslashes($row->u_adminemail));
			$f['u_url']=htmlspecialchars(stripslashes($row->u_url));
			$f['u_level']=$row->u_level;
			$f['u_farbe']=$row->u_farbe;
			$f['u_zeilen']=$row->u_zeilen;	
			$f['u_backup']=$row->u_backup;	
			$f['u_smilie']=$row->u_smilie;
			$f['u_systemmeldungen']=$row->u_systemmeldungen;
			$f['u_eintritt']=stripslashes($row->u_eintritt);
			$f['u_austritt']=stripslashes($row->u_austritt);
			$f['u_punkte_anzeigen']=$row->u_punkte_anzeigen;
			$f['u_signatur']=$row->u_signatur;
			$f['u_kommentar']=$row->u_kommentar;
			$size=unserialize($row->u_frames);
			user_edit($f,$admin,$u_level,$size);
		endif;
		mysql_free_result($result);


		// Bei Änderungen an u_smilie, u_systemmeldungen, u_punkte_anzeigen chat-Fenster neu laden
		if (($u_smilie!=$f['u_smilie'] || $u_systemmeldungen!=$f['u_systemmeldungen'] || $u_punkte_anzeigen!=$f['u_punkte_anzeigen']) && $o_who==0):
			echo "<SCRIPT LANGUAGE=JavaScript>".
				"opener_reload('chat.php?http_host=$http_host&id=$id&back=$chat_back','1')".
				"</SCRIPT>\n";
		endif;


	elseif ((isset($eingabe) && $eingabe=="Löschen!") && $admin):
		// User löschen

                // Ist User noch Online?
                if (!ist_online($f['u_id'])):

			// Nachfrage ob sicher	
			echo "<P><B>".str_replace("%u_nick%",$f['u_nick'],$t['edit13'])."</B></P>\n";
			echo "<FORM NAME=\"$f[u_nick]\" ACTION=\"edit.php\" METHOD=POST>\n".
				"<INPUT TYPE=\"HIDDEN\" NAME=\"id\" VALUE=\"$id\">\n".
				"<INPUT TYPE=\"HIDDEN\" NAME=\"http_host\" VALUE=\"$http_host\">\n".
				"<INPUT TYPE=\"HIDDEN\" NAME=\"f[u_id]\" VALUE=\"$f[u_id]\">\n".
				"<INPUT TYPE=\"HIDDEN\" NAME=\"f[u_name]\" VALUE=\"$f[u_name]\">\n".
				"<INPUT TYPE=\"HIDDEN\" NAME=\"f[u_nick]\" VALUE=\"$f[u_nick]\">\n".
				"<INPUT TYPE=\"HIDDEN\" NAME=\"aktion\" VALUE=\"loesche\">\n";
			echo $f1."<INPUT TYPE=\"SUBMIT\" NAME=\"eingabe\" VALUE=\"Löschen!\">";
			echo "&nbsp;<INPUT TYPE=\"SUBMIT\" NAME=\"eingabe\" VALUE=\"Abbrechen\">".$f2;
			echo "</FORM>\n";
		else:
			echo "<P><B>".str_replace("%u_nick%",$f['u_nick'],$t['edit14'])."</B></P>\n";

			// User mit ID $u_id anzeigen

			$query="SELECT user.* ".
				"FROM user WHERE u_id=$f[u_id] ";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$rows=mysql_num_rows($result);

			if ($rows==1):
				$row=mysql_fetch_object($result);
				$f['u_id']=$u_id;
				$f['u_name']=htmlspecialchars(stripslashes($row->u_name));
				$f['u_nick']=$row->u_nick;
				$f['u_id']=$row->u_id;
				$f['u_email']=htmlspecialchars(stripslashes($row->u_email));
				$f['u_adminemail']=htmlspecialchars(stripslashes($row->u_adminemail));
				$f['u_url']=htmlspecialchars(stripslashes($row->u_url));
				$f['u_level']=$row->u_level;
				$f['u_farbe']=$row->u_farbe;	
				$f['u_zeilen']=$row->u_zeilen;	
				$f['u_backup']=$row->u_backup;	
				$f['u_smilie']=$row->u_smilie;
				$f['u_systemmeldungen']=$row->u_systemmeldungen;
				$f['u_eintritt']=stripslashes($row->u_eintritt);
				$f['u_austritt']=stripslashes($row->u_austritt);
				$f['u_punkte_anzeigen']=$row->u_punkte_anzeigen;
				$f['u_signatur']=$row->u_signatur;
				$size=unserialize($row->u_frames);
				user_edit($f,$admin,$u_level,$size);
				mysql_free_result($result);
			endif;


		endif;

	elseif (isset($eingabe) && $eingabe=="Homepage löschen!" && $admin):

	if ($aktion3 == "loeschen")
	{
	print "<font face=\"Arial\">Homepage wurde gelöscht!</font>";
	$query="DELETE FROM userinfo WHERE ui_userid = '$f[u_id]'";
	mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);

	$query="UPDATE user SET u_login = u_login, u_chathomepage = 'N' WHERE u_id = '$f[u_id]'";
	mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	
	$query="DELETE FROM bild WHERE b_user = '$f[u_id]'";
	mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	}
	else
	{
	 echo "<FORM NAME=\"edit\" ACTION=\"edit.php\" METHOD=POST>\n".
              "<INPUT TYPE=\"HIDDEN\" NAME=\"id\" VALUE=\"$id\">\n".
              "<INPUT TYPE=\"HIDDEN\" NAME=\"http_host\" VALUE=\"$http_host\">\n".
              "<INPUT TYPE=\"HIDDEN\" NAME=\"f[u_id]\" VALUE=\"$f[u_id]\">\n".
              "<INPUT TYPE=\"HIDDEN\" NAME=\"f[u_name]\" VALUE=\"$f[u_name]\">\n".
              "<INPUT TYPE=\"HIDDEN\" NAME=\"f[u_nick]\" VALUE=\"$f[u_nick]\">\n".
	      "<INPUT TYPE=\"HIDDEN\" NAME=\"aktion\" VALUE=\"edit\">\n".
              "<INPUT TYPE=\"HIDDEN\" NAME=\"aktion3\" VALUE=\"loeschen\">\n".
              "<INPUT TYPE=\"SUBMIT\" NAME=\"eingabe\" VALUE=\"Homepage löschen!\">".$f2.

              "</FORM>\n";

	}


	elseif ( isset($eingabe) && $eingabe==$t['chat_msg110'] && $admin):

	// Admin E-Mailadresse aus DB holen
	$query="SELECT u_adminemail,u_level FROM user WHERE u_nick = '$f[u_nick]'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	
		$x=mysql_fetch_array($result);
		$f['u_adminemail']=$x['u_adminemail'];
		$pwdneu=genpassword(8);
		$f['u_passwort']=$pwdneu;
		$uu_level=$x['u_level'];

		// Prüfung ob der User das überhaupt darf...

		if ($f['u_adminemail']=="") {
			echo $f1."<P><B>Fehler: Keine E-Mail Adresse hinterlegt!</B></P>".$f2;
		} elseif ((($u_level == "C" || $u_level == "A") && ($uu_level == "U" || $uu_level=="M" || $uu_level=="Z" ) ) || ($u_level == "S")) {

			$ok=mail($f['u_adminemail'],$t['chat_msg112'],str_replace("%passwort%",$f['u_passwort'],$t['chat_msg113']),"From: $webmaster ($chat)");
	                if ($ok) {
	                        echo $f1.$t['chat_msg111'].$f2;
	                        schreibe_db("user",$f,$f['u_id'],"u_id");
	                } else {
	                        echo $f1."<P><B>Fehler: Die Mail konnte nicht verschickt werden. Das Passwort wurde beibehalten!</B></P>".$f2;
	                };

			$user=$f['u_nick'];
		        $query="SELECT o_id,o_raum,o_name FROM online WHERE o_user='$f[u_id]' AND o_level!='C' AND o_level!='S'";

	        	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		        if ($result && mysql_num_rows($result)>0){
	        	        $row=mysql_fetch_object($result);
		                verlasse_chat($f['u_id'],$row->o_name,$row->o_raum);
	        	        logout($row->o_id,$f['u_id'],"edit->pwänderung");
	                	mysql_free_result($result);
			};
		} else { 
			echo $f1."<P><B>Fehler: Aktion nicht erlaubt!</B></P>".$f2;
		}

	else:


		// User mit ID $u_id anzeigen

		if ($admin && strlen($f['u_id'])>0):
			// Jeden User anzeigen
			$query="SELECT user.* ".
				"FROM user WHERE u_id=$f[u_id] ";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$rows=mysql_num_rows($result);

		else:
			// Nur eigene Daten anzeigen
			$query="SELECT user.* ".
				"FROM user WHERE u_id=$u_id ";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$rows=mysql_num_rows($result);
		endif;

		if ($rows==1):
			$row=mysql_fetch_object($result);
			$f['u_id']=$u_id;
			$f['u_name']=stripslashes($row->u_name);
			$f['u_nick']=$row->u_nick;
			$f['u_id']=$row->u_id;
			$f['u_email']=stripslashes($row->u_email);
			$f['u_adminemail']=stripslashes($row->u_adminemail);
			$f['u_url']=stripslashes($row->u_url);
			$f['u_level']=$row->u_level;
			$f['u_farbe']=$row->u_farbe;
			$f['u_zeilen']=$row->u_zeilen;	
			$f['u_backup']=$row->u_backup;	
			$f['u_smilie']=$row->u_smilie;
			$f['u_eintritt']=stripslashes($row->u_eintritt);
			$f['u_austritt']=stripslashes($row->u_austritt);
			$f['u_systemmeldungen']=$row->u_systemmeldungen;
			$f['u_punkte_anzeigen']=$row->u_punkte_anzeigen;
			$f['u_signatur']=$row->u_signatur;
			$f['u_kommentar']=$row->u_kommentar;
			$size=unserialize($row->u_frames);
			user_edit($f,$admin,$u_level,$size);
			mysql_free_result($result);
		endif;
	endif;

break;

default:

	// User mit ID $u_id anzeigen
                
	$query="SELECT user.* ".
		"FROM user WHERE u_id=$u_id ";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	$rows=mysql_num_rows($result);

	if ($rows==1):
		$row=mysql_fetch_object($result);
		$f['u_id']=$u_id;
		$f['u_name']=stripslashes($row->u_name);
		$f['u_nick']=$row->u_nick;
		$f['u_id']=$row->u_id;
		$f['u_email']=stripslashes($row->u_email);
		$f['u_adminemail']=stripslashes($row->u_adminemail);
		$f['u_url']=stripslashes($row->u_url);
		$f['u_level']=$row->u_level;
		$f['u_farbe']=$row->u_farbe;
		$f['u_zeilen']=$row->u_zeilen;	
		$f['u_backup']=$row->u_backup;	
		$f['u_smilie']=$row->u_smilie;
		$f['u_systemmeldungen']=$row->u_systemmeldungen;
		$f['u_eintritt']=stripslashes($row->u_eintritt);
		$f['u_austritt']=stripslashes($row->u_austritt);
		$f['u_punkte_anzeigen']=$row->u_punkte_anzeigen;
		$f['u_signatur']=$row->u_signatur;
		$size=unserialize($row->u_frames);
		user_edit($f,$admin,$u_level,$size);
		mysql_free_result($result);
	endif;

};


else:
	echo "<P ALIGN=CENTER>$t[sonst1]</P>\n";
endif;



// Fuß
if ($o_js):
	echo $f1."<P ALIGN=CENTER>[<A HREF=\"javascript:window.close();\">$t[sonst2]</A>]</P>".$f2."\n";
endif;

?>

</BODY></HTML>
