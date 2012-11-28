<?php

$u_id="";
if (function_exists("id_lese")) id_lese($id);
if (!$u_id) {
	echo "<HTML><BODY><B>Fehler:</B> Diese Seite darf nur aus dem User-Menue als Admin aufgerufen werden!</BODY></HTML>\n";
	die();
};

include ("statistik-functions.php");
include ("conf/".$sprachconfig."-statistik.php");

// config-bereich

$STAT_TITLE_BACK0 = $farbe_tabelle_kopf;// "#0078BE"; // Farben die für den Rahmen und den Hintergrund der Überschrift und    
$STAT_TITLE_BACK1 = $farbe_tabelle_koerper; // des Menü verwendet werden sollen. 
$STAT_BAR_HEIGHT = 300; // Höhe einer Statistiktabelle in Pixel. 

// Hintergründe die für die Statistiktabelle verwendet werden  
$STAT_BAR_BACK0 = "#E5E5E5";
$STAT_BAR_BACK1 = "#BDC6D5";
$STAT_BAR_BACK2 = $farbe_tabelle_kopf;

// Images aus denen ein Balken für die Statistik zusammengesetzt werden soll
$STAT_BAR_IMAGE_T = "14,2,pics/bar-t.gif";
$STAT_BAR_IMAGE_M = "14,2,pics/bar-m.gif";
$STAT_BAR_IMAGE_B = "14,2,pics/bar-b.gif";

// Fontstart und Fontende die zum Anzeigen der unteren Beschriftung verwendet werden sollen.                                                                
$STAT_BAR_FONTBEG1 = "<SMALL>";
$STAT_BAR_FONTEND1 = "</SMALL>";

// Fontstart und Fontende die zum Anzeigen der seitlichen Beschriftung wendet werden sollen.                                                     */
$STAT_BAR_FONTBEG2 = "<SMALL><B>";
$STAT_BAR_FONTEND2 = "</B></SMALL>";

// Fontstart und Fontende die zum Anzeigen der Überschrift einer Statistik verwendet werden sollen.                                                          
$STAT_BAR_FONTBEG3 = "<FONT COLOR=\"#FFFFFF\"><B>";
$STAT_BAR_FONTEND3 = "</B></FONT>";


// Bitte ab hier nichts ändern!

// nur admins dürfen Statistik ansehen...
if (!$admin || !$erweitertefeatures) return;

$c=@mysql_connect($STAT_DB_HOST, $STAT_DB_USER, $STAT_DB_PASS);
if ($c) {
	mysql_set_charset("utf8");
	mysql_select_db($STAT_DB_NAME, $c);
}
$v=$http_host;

// Wenn User Statistiken gesammelt werden, dann nicht HTTP_HOST sondern die Zeichenkette aus $STAT_DB_COLLECT
if (isset($STAT_DB_COLLECT) && strlen($STAT_DB_COLLECT)>0) 
{
    $v = $STAT_DB_COLLECT;
}

// Testen, ob Statistiken funktionieren...//{{{
$fehler=false;


if (!$c) {
    // DB-Connect bereits danebengegangen...
    $fehler=true;
    $msg=$t['statistik4'];
} else {
    //testen, ob statisiken überhaupt geschrieben werden:
    $r1 = mysql_query("SELECT DISTINCT c_host FROM chat WHERE c_host LIKE '$v' ORDER BY c_host") or trigger_error(mysql_error(), E_USER_ERROR);
    if ($r1>0) {
        if (mysql_num_rows($r1)==0) {
            // host taucht nicht auf... nix wars.
            $msg=$t['statistik5'];
            $fehler=true;
        }
    } else {
        $fehler=true;
        $msg=$t['statistik5'];
    }
}

if ($fehler) {
    show_box2($t['statistik1'],$msg,"100%");
    return;
}//}}}

// Menu ausgeben//{{{
$msg ="[<a href=\"$PHP_SELF?http_host=$http_host&id=$id&aktion=statistik&type=monat\">".$t['statistik3']."</a>]\n".
      "[<a href=\"$PHP_SELF?http_host=$http_host&id=$id&aktion=statistik&type=stunde\">".$t['statistik2']."</a>]";
show_box2($t['statistik1'],$msg,"100%");
print "<IMG SRC=\"pics/fuell.gif\" ALT=\"\" WIDTH=4 HEIGHT=4><br>";
//}}}

switch($type) {
    case "monat": //{{{

        // Auswahlbox Monat //{{{
        $y = urldecode($y);
        $m = urldecode($m);
        $v = urldecode($v);

        $grapharray=array();

        if (strlen($v) < 1) $v = "%";
        if (strlen($y) < 1) $y = date("Y", time());

        if ((intval($m) < 1) || (intval($m) > 12)) $m = intval(date("m", time()));
        
        $m = SPrintF("%02d", $m);
        $y = SPrintF("%04d", $y);

        $msg="";
        $msg.="<center>\n";
        $msg.="<TABLE BORDER=\"0\" CELLPADDING=\"2\" CELLSPACING=\"0\">\n";
        $msg.="<FORM name=form ACTION=\"$PHP_SELF\" METHOD=\"POST\">\n";
        $msg.="<INPUT TYPE=\"HIDDEN\" NAME=\"type\" VALUE=\"$type\">\n";
        $msg.="<INPUT TYPE=\"HIDDEN\" NAME=\"aktion\" VALUE=\"$aktion\">\n";
        $msg.="<INPUT TYPE=\"HIDDEN\" NAME=\"http_host\" VALUE=\"$http_host\">\n";
        $msg.="<INPUT TYPE=\"HIDDEN\" NAME=\"id\" VALUE=\"$id\">\n";
        $msg.="<INPUT TYPE=\"HIDDEN\" NAME=\"page\" VALUE=\"chat-month\">\n";
        $msg.="<TR>\n";
        $msg.="<TD" . coreMakeBackground($STAT_TITLE_BACK1) . ">" . $STAT_TITLE_FONTBEG0 . "\n";
        $msg.=$STAT_TXT["0061"];
        $msg.=$STAT_TITLE_FONTEND0 . "</TD>\n";
        $msg.="<TD" . coreMakeBackground($STAT_TITLE_BACK1) . ">" . $STAT_TITLE_FONTBEG0 . "\n";
        $msg.="<SELECT NAME=\"m\" onchange='form.submit();'>\n";

        while (list($i, $n) = each($STAT_MONTH_TXT))
        {
            if ($i == $m)
                $msg.="<OPTION VALUE=\"$i\" SELECTED>$n\n";
            else
                $msg.="<OPTION VALUE=\"$i\">$n\n";

        }

        $msg.="</SELECT>\n";
        // Auswahlbox Jahr  
        $msg.="<SELECT NAME=\"y\">\n";

        $i = 0;
        
        while ($i < 2)
        {
            $n = (date("Y", time()) - $i);

            if ($n == $y)
                $msg.="<OPTION VALUE=\"$n\" SELECTED>$n\n";
            else
                $msg.="<OPTION VALUE=\"$n\">$n\n";

            $i++;
        }

        $msg.="</SELECT>\n";
        $msg.=$STAT_TITLE_FONTEND0;
        $msg.="<INPUT TYPE=\"SUBMIT\" VALUE=\"" . $STAT_TXT["0053"] . "\">\n";
        $msg.="</TD>\n";
        $msg.="</TR>\n";
        $msg.="</FORM>\n";
        $msg.="</TABLE>\n";
        $msg.="</CENTER>\n";
        //}}}

        // Statistiken einzeln nach Monaten//{{{

        $r1 = @mysql_query("SELECT DISTINCT c_host FROM chat WHERE date(c_timestamp) or trigger_error(mysql_error(), E_USER_ERROR) LIKE '$y-$m%' AND c_host LIKE '$v' ORDER BY c_host");
        if ($r1 > 0)
        {
            $j = 0;
            $o = @mysql_num_rows($r1);
            
            while ($j < $o)
            {
                $c_host = @mysql_result($r1, $j, "c_host");

                statsResetMonth($y, $m);
        
                $r0 = @mysql_query("SELECT *, DATE_FORMAT(c_timestamp,'%d') or trigger_error(mysql_error(), E_USER_ERROR) as tag FROM chat WHERE date(c_timestamp) LIKE '$y-$m%' AND c_host='" . AddSlashes($c_host) . "' ORDER BY c_timestamp");
                if ($r0 > 0)
                {
                    $i = 0;
                    $n = @mysql_num_rows($r0);

                    while ($i < $n)
                    {
                        $x = @mysql_result($r0, $i, "tag");
                        $c_users     = @mysql_result($r0, $i, "c_users"		 );
            
                        if ($c_users > $grapharray["$x"]) $grapharray["$x"] = $c_users;

                        $i++;
                    }
        
                    $msg.=statsPrintGraph($c_host, $STAT_TXT["0102"], $STAT_TXT["0105"]);
                }

                $j++;
            }
        }//}}}

        show_box2($t['statistik3'],$msg,"100%");
        break;//}}}

    case "stunde"://{{{

            $h = 24;
            $msg="";

            $showtime = (time() - (($h - 1) * 60 * 60));

            statsResetHours($showtime, $h);

            $r0 = @mysql_query("SELECT *, DATE_FORMAT(c_timestamp,'%k') or trigger_error(mysql_error(), E_USER_ERROR) as stunde FROM chat WHERE UNIX_TIMESTAMP(c_timestamp)>$showtime AND c_host LIKE '$v' ORDER BY c_timestamp");

            if ($r0 > 0)
            {
                $i = 0;
                $n = @mysql_num_rows($r0);
                while ($i < $n)
                {
                    $x = @mysql_result($r0, $i, "stunde");
                    $c_users		 = @mysql_result($r0, $i, "c_users"		 );
                    $grapharray["$x"] += $c_users;
                    $i++;
                }

                $title = $v;

                $msg.=statsPrintGraph($title, $STAT_TXT["0102"], $STAT_TXT["0103"]);
            }

        show_box2($t['statistik2'],$msg,"100%");
        break;//}}}
} // switch


?>
