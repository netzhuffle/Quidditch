<?php

include ("functions.php"); 
include ("functions.php-forum.php");
// Userdaten setzen
id_lese($id);   

#print "u_id: $u_id<BR>o_raum: $o_raum<BR>u_level $u_level<BR>";

// raumwechsel nicht erlaubt, wenn alter Raum teergrube (ausser für Admins + Tempadmins)

// Info zu altem Raum lesen
$query="SELECT r_name,r_status1,r_austritt from raum ".
        "WHERE r_id=$o_raum ";

$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

if ($result && mysql_num_rows($result)==1):
        $alt=mysql_fetch_object($result);
        mysql_free_result($result);
endif;

if ($alt->r_status1=="L" && $u_level!="A" && !$admin) $darf_forum=false; else $darf_forum=true;
#if ($darf_forum) print "Dieser User darf ins Forum";  else print "Dieser User darf nicht ins Forum";

# if (!$darf_forum) Header("Location: index.php?http_host=$http_host&id=$id&aktion=relogin");
# noch nicht ganz Perfekt hier müsste der User wieder zurück in den Chat kommen.

if (!$darf_forum) 
{
//    Header("Location: index.php?http_host=$http_host&id=$id&aktion=relogin&neuer_raum=$o_raum");
    // Da manche User immernoch übers Forum gehen, weil sie den link fürs Forum kopieren
    // erstmal ein Logout, bis ich ne Möglichkeit gefunden habe, das schöner zu machen
    Header("Location: index.php");
    exit();
}

//Userdaten u_gelesene_postings bereinigen
bereinige_u_gelesene_postings($u_id);

//Bereinige Anzahl Threads und Antworten wenn ein SU das Forum betritt
if ($u_level=="S")
{
    bereinige_anz_in_thema();
}

//ins forum wechseln
gehe_forum($u_id, $u_nick, $o_id, $o_raum);

// Body-Tag definieren
$body_tag="<BODY BGCOLOR=\"$farbe_background\" ";
if (strlen($grafik_background)>0):
       	$body_tag=$body_tag."BACKGROUND=\"$grafik_background\" ";
endif;
$body_tag=$body_tag."TEXT=\"$farbe_text\" ".
          "LINK=\"$farbe_link\" ".
          "VLINK=\"$farbe_vlink\" ".
          "ALINK=\"$farbe_vlink\">\n";  


// Frame-Einstellungen für Browser definieren
$user_agent=strtolower($HTTP_USER_AGENT);
if (preg_match("/linux/",$user_agent)):
	$frame_type="linux";
elseif (preg_match("/solaris/",$user_agent)):
        $frame_type="solaris";
elseif (preg_match("/msie/",$user_agent)):
        $frame_type="ie";
elseif (preg_match("/mozilla/",$user_agent)):
        $frame_type="nswin";
else:
        $frame_type="def";
endif;
 
// Obersten Frame definieren
if (isset($frame_online) && strlen($frame_online)==0) {
	$frame_online="frame_online.php";
};                

// Falls user eigene Einstellungen für das Frameset hat -> überschreiben
$sql = "select u_frames from user where u_id = $u_id";
$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
$u_frames = mysql_result($query,0,"u_frames");
if ($u_frames) {
        $u_frames = unserialize($u_frames);
        if (is_array($u_frames)) {
                foreach ($u_frames as $key => $val) {
                        if ($val) $frame_size[$frame_type][$key]=$val;
                }
        };
}

// Frameset aufbauen
echo "<FRAMESET ROWS=\"$frame_online_size,*,5,".$frame_size[$frame_type]['interaktivforum'].",1\" border=0 frameborder=0 framespacing=0>\n";
echo "<FRAME SRC=\"$frame_online?http_host=$http_host\" name=\"frame_online\" MARGINWIDTH=0 MARGINHEIGHT=0 SCROLLING=NO>\n";
echo "<FRAME SRC=\"forum.php?http_host=$http_host&id=$id\" name=\"forum\" MARGINWIDTH=\"0\" MARGINHEIGHT=\"0\" SCROLLING=AUTO>\n";
echo "<FRAME SRC=\"leer.php?http_host=$http_host\" name=\"leer\" MARGINWIDTH=\"0\" MARGINHEIGHT=\"0\" SCROLLING=NO>\n";
echo "<FRAMESET COLS=\"*,".$frame_size[$frame_type]['messagesforum']."\" border=0 frameborder=0 framespacing=0>\n";
echo "<FRAME SRC=\"messages-forum.php?http_host=$http_host&id=$id\" name=\"messages\" MARGINWIDTH=0 MARGINHEIGHT=0 SCROLLING=AUTO>\n";
echo "<FRAME SRC=\"interaktiv-forum.php?http_host=$http_host&id=$id\" name=\"interaktiv\" MARGINWIDTH=0 MARGINHEIGHT=0 SCROLLING=NO>\n";
echo "</FRAMESET>\n";
echo "<FRAME SRC=\"schreibe.php?http_host=$http_host&id=$id&o_who=2\" name=\"schreibe\" MARGINWIDTH=0 MARGINHEIGHT=0 SCROLLING=NO>\n";
echo "</FRAMESET>\n";
echo "<NOFRAMES>\n";
echo $body_tag.(isset($t['login6']) ? $t['login6'] : "");

?>
