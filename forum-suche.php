<?php

// fidion GmbH mainChat
// $Id: forum-suche.php,v 1.10 2012/10/17 06:16:53 student Exp $

require ("functions.php");
require ("functions.php-func-forum_lib.php");

// Vergleicht Hash-Wert mit IP und liefert u_id, u_name, o_id, o_raum, o_js, u_level, admin
id_lese($id);

function show_pfad_posting2($th_id) {

        global $conn, $f1, $f2, $f3, $f4, $id, $http_host, $thread;
        //Infos über Forum und Thema holen
        $sql = "select fo_id, fo_name, th_name
                from forum, thema
                where th_id = $th_id
                and fo_id = th_fo_id";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $fo_id = htmlspecialchars(stripslashes(mysql_result($query,0,"fo_id")));
        $fo_name = htmlspecialchars(stripslashes(mysql_result($query,0,"fo_name")));
        $th_name = htmlspecialchars(stripslashes(mysql_result($query,0,"th_name")));
        @mysql_free_result($query);

	return "$f3<a href=\"#\" onClick=\"opener_reload('forum.php?id=$id&http_host=$http_host#$fo_id',1); return(false);\">$fo_name</a> > <a href=\"#\" onclick=\"opener_reload('forum.php?id=$id&http_host=$http_host&th_id=$th_id&show_tree=$thread&aktion=show_thema&seite=1',1); return(false);\">$th_name</a>$f4";

}

function vater_rekursiv($vater)
{
    $query="SELECT po_id, po_vater_id FROM posting WHERE po_id = $vater";
    $result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
    $a=mysql_fetch_array($result);
    if (mysql_num_rows($result) <> 1)
    {
        return -1;
    } 
    else if ($a['po_vater_id'] <> 0)
    {
        $vater = vater_rekursiv($a['po_vater_id']);
        return $vater;
    }
    else
    {
	return $a['po_id'];
    }
}


function such_bereich()
{
        global $id,$http_host,$eingabe_breite,$PHP_SELF,$f1,$f2,$f3,$f4,$conn,$dbase;
        global $farbe_text,$farbe_tabelle_kopf2,$farbe_tabelle_zeile1,$farbe_tabelle_zeile2;
	global $suche, $t;

        $eingabe_breite=50;
	$select_breite=250;
        $titel=$t['titel'];

	echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";

        echo    "<FORM NAME=\"suche_neu\" ACTION=\"$PHP_SELF\" METHOD=POST>\n".
                "<INPUT TYPE=\"HIDDEN\" NAME=\"id\" VALUE=\"$id\">\n".
                "<INPUT TYPE=\"HIDDEN\" NAME=\"aktion\" VALUE=\"suche\">\n".
                "<INPUT TYPE=\"HIDDEN\" NAME=\"http_host\" VALUE=\"$http_host\">\n".
                "<TABLE WIDTH=100% BORDER=0 CELLPADDING=3 CELLSPACING=0>";

        echo    "<TR BGCOLOR=\"$farbe_tabelle_kopf2\"><TD COLSPAN=2><DIV style=\"color:$farbe_text;\"><B>$titel</B></DIV></TD></TR>\n".
                
	// Suchtext
		"<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD align=\"right\"><B>$t[suche1]</B></TD><TD>".$f1.
                "<INPUT TYPE=\"TEXT\" NAME=\"suche[text]\" VALUE=\"".htmlspecialchars(stripslashes($suche['text']))."\" SIZE=$eingabe_breite>".
                $f2."</TD></TR>\n";

	// Suche in Board/Thema
        echo    "<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD align=\"right\" valign=\"top\">$t[suche2]</TD><TD>".$f1.
		"<SELECT NAME=\"suche[thema]\" SIZE=\"1\" STYLE=\"width: ".$select_breite."px;\">";

	$sql = "SELECT fo_id, fo_admin, fo_name, th_id, th_name FROM forum left join thema on fo_id = th_fo_id ".
	       "WHERE th_anzthreads <> 0 ".
	       "ORDER BY fo_order, th_order ";
	$query=mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	$themaalt="";
	echo "<OPTION "; if (substr($suche['thema'],0,1)<>"B") echo "SELECTED "; echo "VALUE=\"ALL\">$t[option1]</OPTION>";
	while ($thema=mysql_fetch_array($query, MYSQL_ASSOC))
	{
		if (pruefe_leserechte($thema['th_id']))
		{
			if ($themaalt <> $thema['fo_name'])
			{
				echo "<OPTION "; if ($suche['thema']=="B".$thema['fo_id']) echo "SELECTED "; echo "VALUE=\"B".$thema['fo_id']."\">".$thema['fo_name']."</OPTION>";
				$themaalt = $thema['fo_name'];
			}
			echo "<OPTION "; if ($suche['thema']=="B".$thema['fo_id']."T".$thema['th_id']) echo "SELECTED "; echo "VALUE=\"B".$thema['fo_id']."T".$thema['th_id']."\">&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;".$thema['th_name']."</OPTION>";
		}
	}
	echo    "</SELECT></TD></TR>\n";
	@mysql_free_result($query);

	// Sucheinstelung UND/ODER
        echo    "<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD align=\"right\" valign=\"top\">$t[suche3]</TD><TD>".$f1.
		"<SELECT NAME=\"suche[modus]\" SIZE=\"1\" STYLE=\"width: ".$select_breite."px;\">";
	echo    "<OPTION "; if ($suche['modus']<>"O") echo "SELECTED "; echo "VALUE=\"A\">$t[option2]</OPTION>";
	echo    "<OPTION "; if ($suche['modus']=="O") echo "SELECTED "; echo "VALUE=\"O\">$t[option3]</OPTION>";
	echo    "</SELECT></TD></TR>\n";

	// Sucheinstellung Betreff/Text
        echo    "<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD></TD><TD>".$f1.
		"<SELECT NAME=\"suche[ort]\" SIZE=\"1\" STYLE=\"width: ".$select_breite."px;\">";
	echo    "<OPTION "; if ($suche['ort']<>"B" && $suche['ort']<>"T") echo "SELECTED "; echo "VALUE=\"V\">$t[option4]</OPTION>";
	echo    "<OPTION "; if ($suche['ort']=="B") echo "SELECTED "; echo "VALUE=\"B\">$t[option5]</OPTION>";
	echo    "<OPTION "; if ($suche['ort']=="T") echo "SELECTED "; echo "VALUE=\"T\">$t[option6]</OPTION>";
	echo    "</SELECT></TD></TR>\n";

	// Sucheinstellung Zeit
        echo    "<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD></TD><TD>".$f1.
		"<SELECT NAME=\"suche[zeit]\" SIZE=\"1\" STYLE=\"width: ".$select_breite."px;\">";
	echo    "<OPTION "; if (substr($suche['zeit'],0,1)<>"B") echo "SELECTED "; echo "VALUE=\"ALL\">$t[option7]</OPTION>";
	echo    "<OPTION "; if ($suche['zeit']=="B1") echo "SELECTED "; echo "VALUE=\"B1\">$t[option8]</OPTION>";
	echo    "<OPTION "; if ($suche['zeit']=="B7") echo "SELECTED "; echo "VALUE=\"B7\">$t[option9]</OPTION>";
	echo    "<OPTION "; if ($suche['zeit']=="B14") echo "SELECTED "; echo "VALUE=\"B14\">$t[option10]</OPTION>";
	echo    "<OPTION "; if ($suche['zeit']=="B30") echo "SELECTED "; echo "VALUE=\"B30\">$t[option11]</OPTION>";
	echo    "<OPTION "; if ($suche['zeit']=="B90") echo "SELECTED "; echo "VALUE=\"B90\">$t[option12]</OPTION>";
	echo    "<OPTION "; if ($suche['zeit']=="B180") echo "SELECTED "; echo "VALUE=\"B180\">$t[option13]</OPTION>";
	echo    "<OPTION "; if ($suche['zeit']=="B365") echo "SELECTED "; echo "VALUE=\"B365\">$t[option14]</OPTION>";
	echo    "</SELECT></TD></TR>\n";

	// Sucheinstellung Sortierung
        echo    "<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD></TD><TD>".$f1.
		"<SELECT NAME=\"suche[sort]\" SIZE=\"1\" STYLE=\"width: ".$select_breite."px;\">";
	echo    "<OPTION "; if (substr($suche['sort'],0,1)<>"S") echo "SELECTED "; echo "VALUE=\"DEFAULT\">$t[option15]</OPTION>";
	echo    "<OPTION "; if ($suche['sort']=="SZA") echo "SELECTED "; echo "VALUE=\"SZA\">$t[option16]</OPTION>";
	echo    "<OPTION "; if ($suche['sort']=="SBA") echo "SELECTED "; echo "VALUE=\"SBA\">$t[option17]</OPTION>";
	echo    "<OPTION "; if ($suche['sort']=="SBD") echo "SELECTED "; echo "VALUE=\"SBD\">$t[option18]</OPTION>";
	echo    "<OPTION "; if ($suche['sort']=="SAA") echo "SELECTED "; echo "VALUE=\"SAA\">$t[option19]</OPTION>";
	echo    "<OPTION "; if ($suche['sort']=="SAD") echo "SELECTED "; echo "VALUE=\"SAD\">$t[option20]</OPTION>";
	echo    "</SELECT></TD></TR>\n";

	// nur von User
	echo    "<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD align=\"right\">$t[suche4]</TD><TD>".$f1.
                "<INPUT TYPE=\"TEXT\" NAME=\"suche[username]\" VALUE=\"".htmlspecialchars(stripslashes($suche['username']))."\" SIZE=\"20\">".
                $f2."</TD></TR>\n".

                "<TR BGCOLOR=\"$farbe_tabelle_zeile1\"><TD COLSPAN=\"2\" align=\"center\">".$f1."<INPUT TYPE=\"SUBMIT\" NAME=\"los\" VALUE=\"$t[suche5]\">".$f2."</TD></TR>\n".
                "</TABLE></FORM>\n";

	echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";

}

function such_ergebnis()
{
        global $id,$http_host,$eingabe_breite,$PHP_SELF,$f1,$f2,$f3,$f4,$conn,$dbase,$check_name,$u_id;
        global $farbe_text,$farbe_tabelle_kopf2,$farbe_tabelle_zeile1,$farbe_tabelle_zeile2,$farbe_hervorhebung_forum, $farbe_link;
	global $suche,$o_js,$farbe_neuesposting_forum,$t,$u_level;

        $eingabe_breite=50;
	$select_breite=250;
	$maxpostingsprosuche=1000;
        $titel=$t['ergebnis1'];

        $sql = "select u_gelesene_postings from user where u_id=$u_id";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        if (mysql_num_rows($query)>0)
                $gelesene = mysql_result($query,0,"u_gelesene_postings");
        $u_gelesene = unserialize($gelesene);


	$fehler = "";
	if ($suche['username'] <> coreCheckName($suche['username'],$check_name))  $fehler .= $t['fehler1'];
	$suche['username'] = coreCheckName($suche['username'],$check_name);
	unset($suche['u_id']);
	if (strlen($fehler)==0 && $suche['username'] <> "") 
	{
		$sql = "SELECT u_id FROM user where u_nick = \"".$suche['username']."\"";
		$query=mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if (mysql_num_rows($query) == 1)
		{
			$suche['u_id']=mysql_result($query, 0, "u_id");
		}
		else
		{
			$fehler.='Username unbekannt<br>';
		}
	}

	if (trim($suche['username']) == "" && trim($suche['text']) == "" && (!($suche['zeit']=="B1" || $suche['zeit']=="B7" || $suche['zeit']=="B14"))) $fehler.=$t['fehler2'];
	if ($suche['modus'] <> "A" && $suche['modus'] <> "O") $fehler.=$t['fehler3'];
	if ($suche['ort'] <> "V" && $suche['ort'] <> "B" && $suche['ort'] <> "T") $fehler.=$t['fehler4'];     
	if (!$suche['thema'] == "ALL" && !preg_match("/^B([0-9])+T([0-9])+$/i", $suche['thema']) && !preg_match("/^B([0-9])+$/i", $suche['thema'])) $fehler.=$t['fehler5'];      

	if (strlen($fehler)>0)
	{
	        echo "<p><center><b><font color=\"$farbe_hervorhebung_forum\">$fehler</font></b></center></p>";
	}
	else
	{
		$querytext = "";
		$querybetreff = "";
		if (trim($suche['text']) <> "")
		{
			$suche['text'] = htmlspecialchars($suche['text']);
			$suchetext = explode(" ", $suche['text']);

			for ($i=0; $i<count($suchetext); $i++)
			{
				if (strlen($querytext) == 0)
				{
					$querytext = "po_text LIKE \"%".$suchetext[$i]."%\"";
				}
				else
				{
					if ($suche['modus'] == "O")
					{
						$querytext .= " OR po_text LIKE \"%".$suchetext[$i]."%\"";
					}
					else
					{
						$querytext .= " AND po_text LIKE \"%".$suchetext[$i]."%\"";
					}
				}

				if (strlen($querybetreff) == 0)
				{
					$querybetreff = "po_titel LIKE \"%".$suchetext[$i]."%\"";
				}
				else
				{
					if ($suche['modus'] == "O")
					{
						$querybetreff .= " OR po_titel LIKE \"%".$suchetext[$i]."%\"";
					}
					else
					{
						$querybetreff .= " AND po_titel LIKE \"%".$suchetext[$i]."%\"";
					}
				}


			}
			$querytext = " (".$querytext.") ";
			$querybetreff = " (".$querybetreff.") ";
		}

		$sql = "SELECT posting.*, date_format(from_unixtime(po_ts), '%d.%m.%Y, %H:%i:%s') as po_zeit, u_id, u_nick, u_level, u_punkte_gesamt, u_punkte_gruppe, u_chathomepage FROM posting left join user on po_u_id = u_id WHERE ";

		$abfrage = "";
		if ($suche['ort'] == "V" && $querybetreff <> "")
		{
			$abfrage = " (".$querybetreff." or ".$querytext.") ";  
		}
		else if ($suche['ort'] == "B" && $querybetreff <> "")
		{
			$abfrage = " ".$querybetreff." ";  
		}
		else if ($suche['ort'] == "T" && $querytext <> "")
		{
			$abfrage = " ".$querytext." ";  
		}

		if (isset($suche['u_id']) && $suche['u_id'])
		{
			if ($abfrage == "")
			{
				$abfrage = " (po_u_id = $suche[u_id]) ";
			}
			else
			{
				$abfrage .= " AND (po_u_id = $suche[u_id]) ";				
			}
		}


		$boards = "";
		$sql2 = "SELECT fo_id, fo_admin, fo_name, th_id, th_name FROM forum left join thema on fo_id = th_fo_id ".
		       "WHERE th_anzthreads <> 0 ".
	     	       "ORDER BY fo_order, th_order ";
		$query2=mysql_query($sql2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		while ($thema=mysql_fetch_array($query2, MYSQL_ASSOC))
		{
			if (pruefe_leserechte($thema['th_id']))
			{
				if ($suche['thema'] == "ALL")
				{
					if (strlen($boards) == 0)
					{
						$boards = "po_th_id = $thema[th_id]";
					}
					else
					{
						$boards .= " OR po_th_id = $thema[th_id]";
					}
				}
				else if (preg_match("/^B([0-9])+T([0-9])+$/i", $suche['thema']))
				{
					$tempthema = substr($suche['thema'], -1*strpos($suche['thema'], "T"), 4);
					if ($thema['th_id'] = $tempthema)
					{
						$boards = "po_th_id = $thema[th_id]";
					}
				}
				else if (preg_match("/^B([0-9])+$/i", $suche['thema']))
				{
					$tempboard = substr($suche['thema'], 1, 4);
					if ($thema['fo_id'] == $tempboard)
					{
						if (strlen($boards) == 0)
						{
							$boards = "po_th_id = $thema[th_id]";
						}
						else
						{
							$boards .= " OR po_th_id = $thema[th_id]";
						}
					}
				}
			}
		}
		@mysql_free_result($query2);


		if (strlen(trim($boards)) == 0) $boards = " 1 = 2 ";
		if (strlen(trim($abfrage)) == 0)
		{
			$abfrage .= " (".$boards.") ";
		}
		else
		{
			$abfrage .= " AND (".$boards.") ";
		}

		$sucheab = 0;
		if ($suche['zeit']=="B1") 
		{
			$sucheab = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));
		}
		else if ($suche['zeit']=="B7") 
		{
			$sucheab = mktime(0, 0, 0, date("m"), date("d")-7, date("Y"));
		}
		else if ($suche['zeit']=="B14") 
		{
			$sucheab = mktime(0, 0, 0, date("m"), date("d")-14, date("Y"));
		}
		else if ($suche['zeit']=="B30") 
		{
			$sucheab = mktime(0, 0, 0, date("m")-1, date("d"), date("Y"));
		}
		else if ($suche['zeit']=="B90") 
		{
			$sucheab = mktime(0, 0, 0, date("m")-3, date("d"), date("Y"));
		}
		else if ($suche['zeit']=="B180") 
		{
			$sucheab = mktime(0, 0, 0, date("m")-6, date("d"), date("Y"));
		}
		else if ($suche['zeit']=="B365") 
		{
			$sucheab = mktime(0, 0, 0, date("m"), date("d"), date("Y")-1);
		}

		if ($sucheab > 0)
		{
			$abfrage .= " AND (po_ts >= $sucheab) ";
		}

		if ($u_level<>"S" and $u_level<>"C")
		        $abfrage .= " AND po_gesperrt = 'N' ";
		
		if ($suche['sort'] == "SZD")
		{
			$abfrage .= " ORDER BY po_ts DESC";
		}
		else if ($suche['sort'] == "SZA")
		{
			$abfrage .= " ORDER BY po_ts ASC";
		}
		else if ($suche['sort'] == "SBD")
		{
			$abfrage .= " ORDER BY po_titel DESC, po_ts DESC";
		}
		else if ($suche['sort'] == "SBA")
		{
			$abfrage .= " ORDER BY po_titel ASC, po_ts ASC";
		}
		else if ($suche['sort'] == "SAD")
		{
			$abfrage .= " ORDER BY u_nick DESC, po_ts DESC";
		}
		else if ($suche['sort'] == "SAA")
		{
			$abfrage .= " ORDER BY u_nick ASC, po_ts ASC";
		}
		else
		{
			$abfrage .= " ORDER BY po_ts DESC";
		}	
	
		echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";
       		echo    "<TABLE WIDTH=100% BORDER=0 CELLPADDING=3 CELLSPACING=0>";
	        echo    "<TR BGCOLOR=\"$farbe_tabelle_kopf2\"><TD COLSPAN=3><DIV style=\"color:$farbe_text;\"><B>$titel</B></DIV></TD></TR>\n";

		flush();
		$sql = $sql . " " . $abfrage;
		$query=mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		$anzahl = mysql_num_rows($query);

        	echo    "<TR BGCOLOR=\"$farbe_tabelle_kopf2\"><TD COLSPAN=3><DIV style=\"color:$farbe_text;\">$f1<B>$t[ergebnis2] $anzahl</B>";
		if ($anzahl > $maxpostingsprosuche)
		{
			echo "<font color=\"red\"><b> (Ausgabe wird auf $maxpostingsprosuche begrenzt.)</b></font>";
		}
		echo	"$f2</DIV></TD></TR>\n";
		if ($anzahl > 0)
		{

		echo	"<TR BGCOLOR=\"$farbe_tabelle_kopf2\"><TD>".$f1."<B>$t[ergebnis3]<BR>$t[ergebnis4]</B>".$f2."</TD>";
		echo	"<TD>".$f1."<B>$t[ergebnis6]</B>".$f2."</TD>";
		echo	"<TD>".$f1."<B>$t[ergebnis7]</B>".$f2."</TD>";
		echo	"</TR>";

		$i = 0;
	        while ($fund=mysql_fetch_array($query, MYSQL_ASSOC))
        	{
			$i++;

			if ($i > $maxpostingsprosuche) break;

                        if (($i%2)>0):
	                        $bgcolor=$farbe_tabelle_zeile1;
                        else:
                                $bgcolor=$farbe_tabelle_zeile2;
                        endif;

			if (!@in_array($fund['po_id'], $u_gelesene[$fund['po_th_id']]))
	                        $col = $farbe_neuesposting_forum;
        	        else
                	        $col = $farbe_link;
                

			echo "<TR BGCOLOR=\"$bgcolor\"><TD>".show_pfad_posting2($fund['po_th_id'])."<BR>";
                        $thread = vater_rekursiv($fund['po_id']);
			echo $f1."<b><a href=\"#\" onClick=\"opener_reload('forum.php?id=$id&http_host=$http_host&th_id=".$fund['po_th_id']."&po_id=".$fund['po_id']."&thread=".$thread."&aktion=show_posting&seite=1',1); return(false);\"><font size=-1 color=\"$col\">".stripslashes($fund['po_titel'])."</font></a>";
			if ($fund['po_gesperrt'] == 'Y')
			    echo " <font color=\"red\">(gesperrt)</font>";
			echo $f2."</b></TD>";

			echo "<TD>".$f1.$fund['po_zeit'].$f2."</TD>";
			


                if (!$fund['u_nick']) {
                        echo "<td>$f3<b>Nobody</b>$f4</td>\n";
                } else {
                        
                        $userdata=array();
                        $userdata['u_id'] = $fund['po_u_id'];
                        $userdata['u_nick'] = $fund['u_nick'];
                        $userdata['u_level'] = $fund['u_level'];
                        $userdata['u_punkte_gesamt'] = $fund['u_punkte_gesamt'];
                        $userdata['u_punkte_gruppe'] = $fund['u_punkte_gruppe'];
                        $userdata['u_chathomepage'] = $fund['u_chathomepage'];   
                        $userlink = user($fund['po_u_id'],$userdata,$o_js,FALSE,"&nbsp;","","",TRUE,FALSE,29);
                        if ($fund['u_level'] == 'Z')
                        {
                                echo "<td>$f1 $userdata[u_nick] $f2</td>\n";
                        }
                        else
                        {
                                echo "<td>$f1 $userlink $f2</td>\n";
                        }
                }


			echo "</TR>";
        	}
        	@mysql_free_result($query);

		}
        	echo    "</TABLE>\n";

		echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";
	}

}


// Kopf ausgeben
?>
<HTML>
<HEAD><TITLE><?php echo $body_titel."_Info"; ?></TITLE><META CHARSET=UTF-8>
<SCRIPT LANGUAGE=JavaScript>
        window.focus()     
</SCRIPT>
<?php echo $stylesheet; ?>
        <SCRIPT LANGUAGE="JavaScript">
        function neuesFenster(url) {
                hWnd=window.open(url,"<?php echo $fenster; ?>","resizable=yes,scrollbars=yes,width=300,height=580");
        }
        function neuesFenster2(url) {
                hWnd=window.open(url,"<?php echo "640_".$fenster; ?>","resizable=yes,scrollbars=yes,width=780,height=580");
        }
	function win_reload(file,win_name) 
	{
                win_name.location.href=file;
	}
        function opener_reload(file,frame_number) 
	{
                opener.parent.frames[frame_number].location.href=file;
	}

</SCRIPT>
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



if (strlen($u_id)>0):

	// Menü als erstes ausgeben
	$box=$ft0."$chat Menü".$ft1;
	$text="<A HREF=\"forum-suche.php?http_host=$http_host&id=$id\">$t[menue1]</A>\n";

	show_box2 ($box,$text,"100%");
	echo "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><BR>\n";

	// Auswahl
	switch($aktion) {
	
	case "suche":
		such_bereich();
		flush();
		such_ergebnis();
		break;

	default;
		such_bereich();

	};

else:
        echo "<P ALIGN=CENTER>$t[sonst2]</P>\n";
endif;


// Fuß
if ($o_js):
	echo $f1."<P ALIGN=CENTER>[<A HREF=\"javascript:window.close();\">$t[sonst1]</A>]</P>".$f2."\n";
endif;

?>

</BODY></HTML>
