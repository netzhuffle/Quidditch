<?php
// function html_parse wird von user und interaktiv benötigt.
// $Id: functions.php-func-html_parse.php,v 1.9 2012/10/17 06:16:53 student Exp $
 
function html_parse($privat,$text,$at_sonderbehandlung=0) {
// Filtert Text, ersetzt Smilies und ersetzt folgende Zeichen:
// _ in <B>
// * in <I>
// http://###### oder www.###### in <A HREF="http://###" TARGET=_new>http://###</A>
// E-Mail Adressen in A-Tag mit Mailto
// privat ist wahr bei privater Nachricht
 
global $admin,$smilies_pfad,$smilies_datei,$sprachconfig,$o_raum,$u_id,$u_level;
global $t,$system_farbe,$erweitertefeatures,$conn,$smilies_anzahl,$smilies_config;
global $ist_moderiert,$smilies_aus;

// Grafik-Smilies ergänzen, falls Funktion aktiv und Raum ist nicht moderiert
// Für Gäste gesperrt
// $ist_moderiert ist in raum_ist_moderiert() (Caching!) gesetzt worden!

if (!$ist_moderiert && $erweitertefeatures):
        preg_match_all("/(&amp;[^ ]+)/",$text,$test,PREG_PATTERN_ORDER);
	$anzahl=count($test[0]);
	if ($anzahl>0):
		if ($anzahl>$smilies_anzahl):
			// Fehlermeldung ausgeben
			system_msg("",0,$u_id,$system_farbe,$t[chat_msg54]);
		elseif ($u_level=="G"):
			system_msg("",0,$u_id,$system_farbe,$t[chat_msg55]);
		endif;

		// Prüfen, ob im aktuellen Raum smilies erlaubt sind
		if (!$privat):
			$query="SELECT r_smilie FROM raum WHERE r_id=$o_raum ";
			$result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
			if ($result && mysql_num_rows($result)>0 && mysql_result($result,0,0)!="Y"):
				$smilie_ok=FALSE;
			else:
				$smilie_ok=TRUE;
			endif;
		else:
			$smilie_ok=TRUE;
		endif;

		if ($smilies_aus == "1") $smilie_ok=false;

		// Konfiguration für smilies lesen
		if ($smilies_config):
			@require("conf/".$smilies_config);
		else:
	        	@require("conf/".$sprachconfig."-".$smilies_datei);
		endif;

		if (!$smilie_ok && $smilies_datei!="")
		{
			// Nur die Fehlermeldung ausgeben, falls es das angegeben Smile auch gibt
			$anzahl=0;
			while(list($i,$smilie_code)=each($test[0]))
			{
				$smilie_code=str_replace("&amp;", "&", $smilie_code);
				if ($smilie[$smilie_code]) $anzahl++;
			}
			
			if ($anzahl>0) system_msg("",0,$u_id,$system_farbe,$t[chat_msg76]);

		}
		else
		{
       			while(list($i,$smilie_code)=each($test[0])):
				if ($anzahl>$smilies_anzahl || $u_level=="G"):
					// Mehr als $smilies_anzahl Smilies sind nicht erlaubt
       	        			$text=str_replace(" ".$smilie_code." ","",$text);
				else:

					// Falls smilie existiert, ersetzen
					$smilie_code2=str_replace("&amp;","&",$smilie_code);
					if ($smilie[$smilie_code2]):
        	        			$text=str_replace($smilie_code,"<SMIL SRC=\"".$smilies_pfad.$smilie[$smilie_code2]."\" SMIL>",$text);
					endif;

				endif;
	       		endwhile;

		}
	endif;
endif;

// doppelte Zeichen merken und wegspeichern...
$text=str_replace("__","###substr###",$text); 
$text=str_replace("**","###stern###",$text);
$text=str_replace("@@","###klaffe###",$text);
$text=str_replace("[","###auf###",$text);
$text=str_replace("]","###zu###",$text);
$text=str_replace("|","###strich###",$text);
$text=str_replace("+","###plus###",$text);

// jetzt nach nach italic und bold parsen...  * in <I>, $_ in <B>
if (substr_count($text,"http://") == 0 && substr_count($text,"www.") == 0 && !preg_match("(\w[-._\w]*@\w[-._\w]*\w\.\w{2,3})",$text) )
{
$text=preg_replace('|\*(.*?)\*|','<i>\1</i>',
	preg_replace('|_(.*?)_|','<b>\1</b>',$text));
}


// if ($u_level!="G"):

	// erst mal testen ob www oder http oder email vorkommen
	if (preg_match("/(http:|www\.|@)/i",$text)) {
		// Zerlegen der Zeile in einzelne Bruchstücke. Trennzeichen siehe $split
		// leider müssen zunächst erstmal in $text die gefundenen urls durch dummies 
		// ersetzt werden, damit bei der Angabge von 2 gleichen urls nicht eine bereits 
		// ersetzte url nochmal ersetzt wird -> gibt sonst Müll bei der Ausgabe.

		// wird evtl. später nochmal gebraucht...
		$split='/[ ,\[\]\(\)]/';
		$txt=preg_split($split,$text);

		// wieviele Worte hat die Zeile?
		for ($i=500;$i>=0;$i--) {
			if ((isset($txt[$i])) && $txt[$i]!="") break;
		}
		$text2=$text;
		// Schleife über alle Worte...
		for ($j=0; $j<=$i; $j++) {
			// test, ob am Ende der URL noch ein Sonderzeichen steht...
			$txt[$j]=preg_replace("!\?$!","",$txt[$j]);
			$txt[$j]=preg_replace("!\.$!","",$txt[$j]);
		}
		for ($j=0; $j<=$i; $j++) {
			// erst mal nick_replace, falls Wort mit "@" beginnt.
			if (substr($txt[$j],0,1)=="@") {
				$nick=nick_ergaenze($txt[$j],"raum",1);		 // fehlermeldungen unterdrücken.
				if (($admin || $u_level=="A") && $nick['u_nick']=="") $nick=nick_ergaenze($txt[$j],"online",1);		 // fehlermeldungen unterdrücken.
				if ($nick['u_nick']!="") 
				{
					if ($at_sonderbehandlung == 1) // in /me sprüchen kein [zu Nick] an den anfang stellen, sondern nur nick ergänzen
					{
						$rep=$nick[u_nick];	
						$text=preg_replace("!$txt[$j]!",$rep,$text);	
					}
					else
					{
						$rep="[".$t['chat_spruch6']."&nbsp;".$nick['u_nick']."] ";	
						$text=$rep.preg_replace("!$txt[$j]!","",$text);	
					}
				}
				// $txt[$j]="nick....nick";

				if ($u_level == "G")
				{
					break;
				}
			}
			
			if ($u_level!="G"):
			
			// E-Mail Adressen in A-Tag mit Mailto
			// E-Mail-Adresse -> Format = *@*.*
			// (ssilk, 20.06.03): Besser und schneller ist
			if (preg_match('/^[\w][\w.-]*@[[:alnum:].-]+\.[[:alnum:]-]{2,}$/',$txt[$j]) ) {
				// Aber im Prinzip müsste man sich die RFCs für Mailadressen und Domainnamen nochmal anschauen!
				// Wort=Mailadresse? -> im text durch dummie ersetzen, im wort durch href.
				$text=preg_replace("/!$txt[$j]!/","####$j####",$text);	
				// kranke lösung für ie: neues fenster mit mailto aufpoppen, dann gleich wieder schließen...
				$rep="<a href=mailto:".$txt[$j]." onclick=\"ww=window.open('mailto:".$txt[$j]."','Chat_Klein','resizable=yes,scrollbars=yes,width=10,height=10'); ww.window.close(); return(false);\">".$txt[$j]."</a>";
				$txt[$j]=preg_replace("/".$txt[$j]."/",$rep,$txt[$j]);	
			}

			// www.###### in <A HREF="http://###" TARGET=_new>http://###</A>
			if (preg_match("/^www\..*\..*/",$txt[$j])) {
				// sonderfall -> "?" in der URL -> dann "?" als Sonderzeichen behandeln...
				$txt2=preg_replace("!\?!","\\?",$txt[$j]);

				// Doppelcheck, kann ja auch mit http:// in gleicher Zeile nochmal vorkommen. also erst die 
				// http:// mitrausnehmen, damit dann in der Ausgabe nicht http://http:// steht...
				$text=preg_replace("!http://$txt2!","-###$j####",$text);	

				// Wort=URL mit www am Anfang? -> im text durch dummie ersetzen, im wort durch href.
				$text=preg_replace("!$txt2!","####$j####",$text);

				// und den ersten Fall wieder Rückwärts, der wird ja später in der schleife nochmal behandelt.
				$text=preg_replace("!-###\d*####/!","http://$txt2/",$text);

				// url aufbereiten
				$txt[$j]=preg_replace("/".$txt2."/","<a href=\"redirect.php?url=".urlencode("http://$txt2")."\" target=_blank>http://$txt2</a>",$txt[$j]);  
			}
			// http://###### in <A HREF="http://###" TARGET=_new>http://###</A>
			if (preg_match("!^https?://!",$txt[$j])) {
				// Wort=URL mit http:// am Anfang? -> im text durch dummie ersetzen, im wort durch href.
				// Zusatzproblematik.... könnte ein http-get-URL sein, mit "?" am Ende oder zwischendrin... urgs.

				// sonderfall -> "?" in der URL -> dann "?" als Sonderzeichen behandeln...
				$txt2=preg_replace("!\?!","\\?",$txt[$j]);
				$text=preg_replace("!$txt2!","####$j####",$text);	

				// und wieder Rückwärts, falls zuviel ersetzt wurde...
				$text=preg_replace("!####\d*####/!","$txt[$j]/",$text);

				// url aufbereiten
				$txt[$j]=preg_replace("!$txt2!","<a href=\"redirect.php?url=".urlencode($txt2)."\" target=_blank>$txt2</a>",$txt[$j]);  
			}

			endif;
		}
			
		// nun noch die Dummy-Strings durch die urls ersetzen...
		for ($j=0; $j<=$i; $j++) {
			// erst mal "_" in den dummy-strings vormerken...
			// es soll auch urls mit "_" geben ;-)
			$txt[$j]=str_replace("_","###substr###",$txt[$j]);
			$text=preg_replace("![-#]###$j####!",$txt[$j],$text);
		}
	} // ende http, mailto, etc.

// endif;

// gemerkte @, _ und * zurückwandeln.
$text=str_replace("###plus###","+",$text);
$text=str_replace("###strich###","|",$text);
$text=str_replace("###auf###","[",$text);
$text=str_replace("###zu###","]",$text);
$text=str_replace("###substr###","_",$text);
$text=str_replace("###stern###","*",$text);
$text=str_replace("###klaffe###","@",$text);

return($text);
};



?>
