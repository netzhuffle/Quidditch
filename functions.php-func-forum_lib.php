<?php

function pruefe_leserechte($th_id)
{
	// Prüft anhand der th_id, ob der User im Forum lesen darf
	global $u_level;

	$query="SELECT th_fo_id FROM thema WHERE th_id = '$th_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	$fo=mysql_fetch_array($result);
	$fo_id=$fo['th_fo_id'];

	$query="SELECT fo_admin FROM forum WHERE fo_id = '$fo_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	list($admin_forum)=mysql_fetch_array($result);
	$leserechte=false;
	if ($u_level == "G") 
		if ($admin_forum == 0 || (($admin_forum & 8) == 8) ) $leserechte=true;

	if ($u_level == "U" || $u_level == "A" || $u_level == "M" || $u_level == "Z")
		if ($admin_forum == 0 || (($admin_forum & 2) == 2) ) $leserechte=true;

	if ($u_level == "S" || $u_level == "C") $leserechte=true;

	return($leserechte); 
}

function hole_themen_id_anhand_posting_id($po_id)
{
        // Prüft anhand der po_id ob gesperrt ist
	$query="SELECT po_th_id FROM posting WHERE po_id = '$po_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	$fo=mysql_fetch_array($result);
	
	return ($fo['po_th_id']);
}

function pruefe_schreibrechte($th_id)
{
	// Prüft anhand der th_id, ob der User ins Forum schreiben darf
	global $u_level;

	$query="SELECT th_fo_id FROM thema WHERE th_id = '$th_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	$fo=mysql_fetch_array($result);
	$fo_id=$fo['th_fo_id'];

	$query="SELECT fo_admin FROM forum WHERE fo_id = '$fo_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	list($admin_forum)=mysql_fetch_array($result);

	$schreibrechte=false;
	if ($u_level == "G") if ( (($admin_forum & 16) == 16) ) $schreibrechte=true;
	if ($u_level == "U" || $u_level == "A" || $u_level == "M" || $u_level == "Z") if ($admin_forum == 0 || (($admin_forum & 4) == 4) ) $schreibrechte=true;
	if ($u_level == "S" || $u_level == "C") $schreibrechte=true;
	return($schreibrechte);
}

function ist_thread_gesperrt($thread)
{
	global $forum_thread_sperren;

	// Prüft anhand der thread auf den man antworten will, gesperrt ist
	$query="SELECT po_threadts, po_ts, po_threadgesperrt FROM posting WHERE po_id = '$thread'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	$fo=mysql_fetch_array($result);

	$threadgesperrt = false;

	if ($fo['po_threadgesperrt'] == 'Y')
		$threadgesperrt = true;

	if ($forum_thread_sperren > 0)
	{
		$abwann = mktime(0, 0, 0, date('m')-$forum_thread_sperren,date('d') + 1,date('Y'));

		// Alte Beiträge vor 4.12.2006 (ab hier erst protokoll des po_threadts)
		if ($fo['po_threadts'] == 0)
		{
			if ($fo['po_ts'] <= $abwann)
				$threadgesperrt = true;
		}
		// Alle Beiträge nach/mit 4.12.2006
		else
		{
			if ($fo['po_threadts'] <= $abwann)
				$threadgesperrt = true;
		}
	}

	return($threadgesperrt);
}

function ist_posting_gesperrt($po_id)
{
	// Prüft anhand der po_id ob gesperrt ist
	$query="SELECT po_gesperrt FROM posting WHERE po_id = '$po_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	$fo=mysql_fetch_array($result);

	$postinggesperrt = false;

	if ($fo['po_gesperrt'] == 'Y')
		$postinggesperrt = true;

	return($postinggesperrt);
}

function sperre_posting($po_id)
{
        $query="SELECT po_gesperrt FROM posting WHERE po_id = '$po_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	$fo=mysql_fetch_array($result);
                        
	if (ist_posting_gesperrt($po_id))
	{
		// Posting entsperren
	        $query="UPDATE posting SET po_gesperrt = 'N' WHERE po_id = '$po_id'";
		$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	}
	else
	{
		// Posting sperren
	        $query="UPDATE posting SET po_gesperrt = 'Y' WHERE po_id = '$po_id'";
		$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	}
}

//User verlaesst Raum und geht ins Forum
//Austrittstext im Raum erzeugen, o_who auf 2 und o_raum auf -1 (community) setzen

function gehe_forum($u_id, $u_nick, $o_id, $o_raum) {

	global $conn,$t;

        //Austrittstext im alten raum erzeugen
        verlasse_chat($u_id,$u_nick,$o_raum);
	system_msg("",0,$u_id,"",str_replace("%u_nick%",$u_nick,$t['betrete_forum1']));
        
	//Daten in online-tabelle richten
	$f['o_raum'] = -1;       //-1 allgemein fuer community
        $f['o_who'] = "2";        //2 -> Forum
        schreibe_db("online",$f,$o_id,"o_id");

}


//Gelesene Postings des Users einlesen
function lese_gelesene_postings($u_id) {

        global $conn, $u_gelesene;

        $sql = "select u_gelesene_postings from user
                where u_id = $u_id";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        if (mysql_num_rows($query)>0)
		$gelesene = mysql_result($query,0,"u_gelesene_postings");
        $u_gelesene = unserialize($gelesene);
	/* DEBUG
	while (list($k,$v)=each($u_gelesene)) {

		echo $k . ":";

		while (list($key,$val) = each($u_gelesene[$k]))
			echo $u_gelesene[$k][$key] . " ";

		echo "<br>";
	}
	*/
        @mysql_free_result($query);
}

// markiert ein komplettes Thema als gelesen
function thema_alles_gelesen($th_id,$u_id)
{
 global $conn, $u_gelesene;

$query="SELECT po_id FROM posting WHERE po_th_id = '$th_id'";
$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);

if ($result && mysql_numrows($result) > 0)

 {
 if (!$u_gelesene[$th_id]) 
                $u_gelesene[$th_id][0] = array();
          
	while ($a=mysql_fetch_array($result))
		{
		array_push($u_gelesene[$th_id], $a['po_id']);
                //wenn schon gelesen, dann wieder raus
       		}
$u_gelesene[$th_id] = array_unique($u_gelesene[$th_id]);
$gelesene = serialize($u_gelesene);
 $sql = "update user set u_gelesene_postings = '$gelesene' where u_id = $u_id";
        mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
 }

#print "Alles Postings des Themas als gelesen markiert...";
}

// markiert einen Thread als gelesen
function thread_alles_gelesen($th_id,$thread_id,$u_id)
{
	global $conn, $u_gelesene;

	$query = "SELECT po_threadorder FROM posting WHERE po_id = '$thread_id'";
	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);


	if ($result && mysql_numrows($result) == 1)
	{
		if (!$u_gelesene[$th_id]) 
                	$u_gelesene[$th_id][0] = array();

		// alle Postings sind im Vater in der Threadorder, dieses array, an die gelesenen anhängen
		$a=mysql_fetch_array($result);
		$b=explode(",", $a['po_threadorder']);

		for ($i=0; $i<count($b); $i++)
		{
		    array_push($u_gelesene[$th_id], $b[$i]);
       		}

                //wenn schon gelesen, dann wieder raus
		$u_gelesene[$th_id] = array_unique($u_gelesene[$th_id]);

		// und zurückschreiben
		$gelesene = serialize($u_gelesene);
 		$sql = "update user set u_gelesene_postings = '$gelesene' where u_id = $u_id";
	        mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

#print "Alles Postings des Themas als gelesen markiert...";

 	}
}

//markiert ein posting fuer einen User als gelesen
function markiere_als_gelesen($po_id, $u_id, $th_id) {

        global $conn, $u_gelesene;

#print "aufruf: makiere_als_gelesen mit (po_id, u_id, th_id) ($po_id, $u_id, $th_id)";

        if (!$u_gelesene[$th_id])
                $u_gelesene[$th_id][0] = $po_id;
        else {

                array_push($u_gelesene[$th_id], $po_id);
                //wenn schon gelesen, dann wieder raus
                $u_gelesene[$th_id] = array_unique($u_gelesene[$th_id]);
        }
	

        $gelesene = serialize($u_gelesene);

        //schreiben nicht ueber schreibe_db, da sonst
        //online-tabelle neu geschrieben wird -> hier unnoetig
        //und unperformant
        $sql = "update user set u_gelesene_postings = '$gelesene' where u_id = $u_id";
        mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

}

//gibt die ungelesenen Postings in einem Thema/Thread zurueck
//je nachdem, ob $arr_postings sich auf Thema oder Thread bezieht
function anzahl_ungelesene(&$arr_postings, $th_id) {


        global $u_gelesene;

        //kein Posting in Gruppe --> keine ungelesenen
        if (count($arr_postings)==0)
                return 0;

        //kein Posting gelesen --> alle ungelesen
        if (!$u_gelesene[$th_id])
                return count($arr_postings);

	// anzahl unterschied zwischen postings im Thread/thema und den gelesenen
	//postings des users zurueckgeben
	
	return count(array_diff($arr_postings, $u_gelesene[$th_id]));


}

function anzahl_ungelesene2(&$arr_postings, $th_id) {


        global $u_gelesene;

        //kein Posting in Gruppe --> keine ungelesenen
        if (count($arr_postings)==0)
                return 0;

        //kein Posting gelesen --> alle ungelesen
        if (!$u_gelesene[$th_id])
                return count($arr_postings);

	// anzahl unterschied zwischen postings im Thread/thema und den gelesenen
	//postings des users zurueckgeben

	 $sql = "select po_id, po_u_id, date_format(from_unixtime(po_ts), '%d.%m') as po_date,
                po_titel, po_threadorder, u_nick,
                u_level, u_punkte_gesamt, u_punkte_gruppe, u_chathomepage
                from posting
                left join user on po_u_id = u_id
                where po_vater_id = 0
                and po_th_id = $th_id
                order by po_ts desc";

	 $sql = "select po_id, po_u_id,  po_threadorder
                from posting
                where po_vater_id = 0
                and po_th_id = $th_id
                order by po_ts desc";
	$query=mysql_query($sql) or trigger_error(mysql_error(), E_USER_ERROR);

	$ungelesene=0;
	while ($posting = mysql_fetch_array($query, MYSQL_ASSOC)) {
	 if ($posting[po_threadorder] == "0") {
                        $anzreplys = 0;
                        $arr_postings = array($posting[po_id]);
                } else {
                        $arr_postings = explode(",", $posting[po_threadorder]);
                        $anzreplys = count($arr_postings);
                        //Erstes Posting mit beruecksichtigen
                        $arr_postings[] = $posting[po_id];
			}

	 $ungelesene+= anzahl_ungelesene($arr_postings, $th_id);
	}
	
	return $ungelesene;

}


function anzahl_ungelesene3(&$arr_postings, $th_id) {


        global $u_gelesene;

        //kein Posting in Gruppe --> keine ungelesenen
        if (count($arr_postings)==0)
                return 0;

        //kein Posting gelesen --> alle ungelesen
        if (!$u_gelesene[$th_id])
                return count($arr_postings);

	// anzahl unterschied zwischen postings im Thread/thema und den gelesenen
	//postings des users zurueckgeben
	$arr=array_diff($arr_postings, $u_gelesene[$th_id]);
	$diff=count($arr);

	reset ($arr);
	while (list($key, $value) = each ($arr)) 
	{
	   # echo "Key: $key; Value: $value<br>\n";
	$query="SELECT * FROM posting WHERE po_id = '$value'";

	$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	$num=mysql_numrows($result);
	#if ($num == 1) print "<font color=white>$th_id: ".$query." $num</font><BR>";
	if ($num == 1) 
		       {
			#checke_posting($value);
		       }
	
	if ($num==0) $diff--;
	}


	#echo "<tr><td colspan=5 bgcolor=#FFFFFF><pre>".print_r($arr).": $th_id</pre>";
	#echo "</td></tr>";


	return $diff;


}

//Prüft Usereingaben auf Vollständigkeit
// mode --> forum, thema, posting 
function check_input($mode) {

        global $t;

        $missing = "";

        if ($mode == "forum") {

                global $fo_name;
                if (!$fo_name)
                        $missing = $t['missing_foname'];


        } else if ($mode == "thema") {
                global $th_name, $th_desc;
                if (!$th_name)
                        $missing .= $t['missing_thname'];
                if (!$th_desc)
                        $missing .= $t['missing_thdesc'];

        } else if ($mode == "posting") {
                global $po_titel, $po_text;
                if (strlen(trim($po_titel)) <= 0)
                        $missing .= $t['missing_potitel'];
                if (strlen(trim($po_text)) <= 0)
                        $missing .= $t['missing_potext'];

        }

        return $missing;
}

//neues Forum in Datenbank schreiben
function schreibe_forum() {

        global $fo_id, $fo_name, $fo_admin, $conn;
	global $fo_gast,$fo_user;


        $f['fo_name'] = $fo_name;
        #$f["fo_admin"] = $fo_admin;

        $f['fo_admin'] = $fo_gast+$fo_user+1;
	#print "fo_gast: $fo_gast<BR>";
	#print "fo_user: $fo_user<BR>";
	#print "DEBUG: setze fo_admin auf: $f[fo_admin]<BR>Dies ist binär: ".decbin($f[fo_admin]);

        //groesste Order holen
        $sql = "select max(fo_order) as maxorder from forum";
        $query = mysql_query($sql,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $maxorder = mysql_result($query,0,"maxorder");
        @mysql_free_result($query);
        if ($maxorder)
                $maxorder++;
        else
                $maxorder = 1;
        $f['fo_order'] = $maxorder;
        $fo_id = schreibe_db("forum",$f,"","fo_id");

        //Damit leeres Forum angezeigt wird, dummy-eintrag in Thema-Tabelle
        $ff["th_fo_id"] = $fo_id;
        $ff["th_name"] = "dummy-thema";
        $ff["th_anzthreads"] = 0;
        $ff["th_anzreplys"] = 0;
        $ff["th_order"] = 0;
        schreibe_db("thema", $ff, "", "th_id");

}

//Forum aendern
function aendere_forum() {

	global $fo_id, $fo_name, $fo_admin, $conn;
	global $fo_gast,$fo_user;


	$f['fo_name'] = $fo_name;
        $f['fo_admin'] = $fo_gast+$fo_user+1;
	#print "fo_gast: $fo_gast<BR>";
	#print "fo_user: $fo_user<BR>";
	#print "DEBUG: setze fo_admin auf: $f[fo_admin]<BR>Dies ist binär: ".decbin($f[fo_admin]);
	schreibe_db("forum",$f,$fo_id,"fo_id");

}

//Schiebt Forum in Darstellungsreihenfolge nach oben
function forum_up($fo_id, $fo_order) {

        global $conn;

        if (!$fo_id) return;

        //forum über aktuellem Forum holen
        $sql = "select fo_id, fo_order as prev_order
                from forum
                where fo_order < $fo_order
                order by fo_order desc
                limit 1";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        
	$numrows = mysql_num_rows($query);

        //ist Forum oberstes Forum?
        if ($numrows == 1) {

		$prev_order = mysql_result($query,0,"prev_order");
        	$prev_id = mysql_result($query,0,"fo_id");
		@mysql_free_result($query);
	
                //nein -> orders vertauschen
                $f['fo_order'] = $fo_order;
                schreibe_db("forum", $f, $prev_id, "fo_id");

                $f['fo_order'] = $prev_order;
                schreibe_db("forum", $f, $fo_id, "fo_id");

        } else {
		@mysql_free_result($query);
	}

}

//Schiebt Forum in Darstellungsreihenfolge nach oben
function forum_down($fo_id, $fo_order) {

        global $conn;

        if (!$fo_id) return;

        //forum über aktuellem Forum holen
        $sql = "select fo_id, fo_order as next_order
                from forum
                where fo_order > $fo_order
                order by fo_order 
                limit 1";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	
        $numrows = mysql_num_rows($query);

        //ist Thema schon letztes Thema?
        if ($numrows == 1) {
                $next_order = mysql_result($query,0,"next_order");
                $next_id = mysql_result($query,0,"fo_id");
                @mysql_free_result($query);

                //nein -> orders vertauschen
                $f['fo_order'] = $fo_order;
                schreibe_db("forum", $f, $next_id, "fo_id");

                $f['fo_order'] = $next_order;
                schreibe_db("forum", $f, $fo_id, "fo_id");

        } else {
                @mysql_free_result($query);
        }

}

//Komplettes Forum mit allen Themen und postings loeschen
function loesche_forum($fo_id) {

	global $conn;
	
	if (!$fo_id)
		return;

	$sql = "select fo_name from forum where fo_id=$fo_id";
	$query = mysql_query($sql,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $fo_name = mysql_result($query,0,"fo_name");
        @mysql_free_result($query);


	$sql = "select th_id from thema where th_fo_id=$fo_id";
	$query = mysql_query($sql,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
	while ($thema = mysql_fetch_array($query, MYSQL_ASSOC)) {

		$delsql = "delete from posting where po_th_id=$thema[th_id]";
		mysql_query($delsql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

	}
	@mysql_free_result($query);

	$sql = "delete from thema where th_fo_id=$fo_id";
	mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

	$sql = "delete from forum where fo_id=$fo_id";
	mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

	echo "<b>Forum $fo_name komplett gelöscht!</b><br>";

}

//Thema in Datenbank schreiben
function schreibe_thema($th_id=0) {

        global $fo_id, $th_name, $th_desc, $conn, $th_forumwechsel, $th_verschiebe_nach;

        //neues Thema
        if ($th_id==0) {
                $f['th_fo_id'] = $fo_id;
                $f['th_name'] = $th_name;
                $f['th_desc'] = $th_desc;
                $f['th_anzthreads'] = 0;
                $f['th_anzreplys'] = 0;

                //groesste Order holen
                $sql = "select max(th_order) as maxorder from thema where th_fo_id=$fo_id";
                $query = mysql_query($sql,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
                $maxorder = mysql_result($query,0,"maxorder");
                @mysql_free_result($query);
                if ($maxorder)
                        $maxorder++;
                else
                        $maxorder = 1;
                $f['th_order'] = $maxorder;
                $th_id = schreibe_db("thema",$f,"","th_id");
        } else { //Thema editieren

		if ($th_forumwechsel=="Y" && preg_match("/^([0-9])+$/i", $th_verschiebe_nach))
		{
	                //groesste Order holen
        	        $sql = "select max(th_order) as maxorder from thema where th_fo_id=$th_verschiebe_nach";
                	$query = mysql_query($sql,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
     	        	$maxorder = mysql_result($query,0,"maxorder");
        	        @mysql_free_result($query);
                	if ($maxorder)
                        	$maxorder++;
                	else
                        	$maxorder = 1;

                	$f['th_fo_id'] = $th_verschiebe_nach;
                	$f['th_order'] = $maxorder;
		}

                $f['th_name'] = $th_name;
                $f['th_desc'] = $th_desc;

                schreibe_db("thema", $f, $th_id, "th_id");
        }
}

//Schiebt Thema in Darstellungsreihenfolge nach oben
function thema_up($th_id, $th_order, $fo_id) {

        global $conn;

        if (!$th_id || !$fo_id) return;

        //thema über aktuellem Thema holen
        $sql = "select th_id, th_order as prev_order
                from thema
                where th_fo_id = $fo_id
                and th_order < $th_order
                order by th_order desc
                limit 1";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $prev_order = mysql_result($query,0,"prev_order");
        $prev_id = mysql_result($query,0,"th_id");
        @mysql_free_result($query);

        //ist Thema oberstes Thema?
        if ($prev_order > 0) {

                //nein -> orders vertauschen
                $f['th_order'] = $th_order;
                schreibe_db("thema", $f, $prev_id, "th_id");

                $f['th_order'] = $prev_order;
                schreibe_db("thema", $f, $th_id, "th_id");

        }

}

//Schiebt Thema in Darstellungsreihenfolge nach unten
function thema_down($th_id , $th_order, $fo_id) {

        global $conn;

        if (!$th_id || !$fo_id) return;

        //thema unter aktuellem Thema holen
        $sql = "select th_id, th_order as next_order
                from thema
                where th_fo_id = $fo_id
                and th_order > $th_order
                order by th_order
                limit 1";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

        $numrows = mysql_num_rows($query);

        //ist Thema schon letztes Thema?
        if ($numrows == 1) {
                $next_order = mysql_result($query,0,"next_order");
                $next_id = mysql_result($query,0,"th_id");
                @mysql_free_result($query);

                //nein -> orders vertauschen
                $f['th_order'] = $th_order;
                schreibe_db("thema", $f, $next_id, "th_id");

                $f['th_order'] = $next_order;
                schreibe_db("thema", $f, $th_id, "th_id");

        } else {
                @mysql_free_result($query);
        }

}

//Komplettes Thema mit allen Postings loeschen
function loesche_thema($th_id) {

        global $conn;

        if (!$th_id)
                return;

        $sql = "select th_name from thema where th_id=$th_id";
        $query = mysql_query($sql,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $th_name = @mysql_result($query,0,"th_name");
        @mysql_free_result($query);

        $delsql = "delete from posting where po_th_id=$th_id";
        mysql_query($delsql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

        $sql = "delete from thema where th_id=$th_id";
        mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

        echo "<b>Thema $th_name komplett gelöscht!</b><br>";

}


//schreibt neues/editiertes Posting in Datenbank
function schreibe_posting() {

        global $conn, $th_id, $po_vater_id, $u_id, $po_id, $po_tiefe, $u_nick;
        global $po_titel, $po_text, $thread, $mode, $user_id, $autor;
	global $po_topposting, $po_threadgesperrt, $forum_admin, $t, $forum_aenderungsanzeige;

        if ($mode == "edit") {

                //muss autor neu gesetzt werden?
                if ($forum_admin && $autor) {
                        if (!preg_match("/[a-z]|[A-Z]/",$autor))
                                $sql = "select u_id from user where u_id=$autor";
                        else
                                $sql = "select u_id from user where u_nick='$autor'";
                        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                        if (mysql_num_rows($query) > 0)
                        $u_id_neu = mysql_result($query,0,"u_id");

                        if (!$u_id_neu)
                                echo "<b>Ein User mit dem Nick/der ID $autor existiert nicht!</b>";
                        else if ($u_id_neu != $user_id)
                                $f['po_u_id'] = $u_id_neu;
                }

		if ($forum_admin)
		{
			$f['po_topposting']=$po_topposting;
			$f['po_threadgesperrt']=$po_threadgesperrt;
		}

                $f['po_titel'] = htmlspecialchars($po_titel);
                $f['po_text'] = htmlspecialchars(erzeuge_umbruch($po_text,80));
	
		if ($forum_aenderungsanzeige=="1")
		{		
			$append = $t['letzte_aenderung'];
			$append = str_replace("%datum%", date("d.m.Y"), $append);
			$append = str_replace("%uhrzeit%", date("H:i"), $append);
			$append = str_replace("%user%", $u_nick, $append);

			$f['po_text'] .= $append;
		}
	
		#$f[po_text] = $po_text;
                schreibe_db("posting", $f, $po_id, "po_id");
	#print "DEBUG: edit-mode: <PRE>";
	#print_r($f);
	#print "</PRE>";
        } else {        //neues Posting

		$f['po_th_id'] = $th_id;
                $f['po_u_id'] = $u_id;
                $f['po_vater_id'] = $po_vater_id;
                $f['po_tiefe'] = $po_tiefe;
                $f['po_titel'] = htmlspecialchars($po_titel);
                $f['po_text'] = htmlspecialchars(erzeuge_umbruch($po_text,80));
                $f['po_ts'] = time();
		$f['po_threadts'] = time();
                if ($po_vater_id != 0)
                        $f['po_threadorder'] = 1;
                else
                        $f['po_threadorder'] = 0;
		
                //Posting schreiben
                $new_po_id = schreibe_db("posting", $f, "", "po_id");

                //ist was schiefgelaufen?
                if (!$new_po_id)
                        exit;

		#print "neue posting-id: $new_po_id<BR>";

                //falls reply muss po_threadorder des vaters neu geschrieben werden
                if ($po_vater_id != 0) {

                        //po_threadorder des threadvaters neu schreiben
                        //dazu Tabelle posting locken
                        $sql = "LOCK TABLES posting WRITE";
                        @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

                        //alte Threadorder holen
                        $sql = "select po_threadorder from posting where po_id = $thread";
                        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                        $threadorder = mysql_result($query,0,"po_threadorder");
                        @mysql_free_result($query);
			
			#print "alte threadorder: $threadorder<BR>";

			//erste Antwort?
                        if ($threadorder == "0")
                                $threadorder = $new_po_id;
                        else {
                                //jetzt hab ich arbeit...
                                //rekursiv das unterste Posting dieses Teilbaums holen
                                $insert_po_id = hole_letzten($po_vater_id, $new_po_id);

				#print "po_vater_id = $po_vater_id<BR>";
				#print "new_po_id = $new_po_id<BR>";
				#print "unterstes posting des teilbaums: $insert_po_id<BR>";
				
                                //alte threadorder in feld aufsplitten
                                $threadorder_array = explode(",",$threadorder);
                                $threadorder_array_new = array();
                                $i=0;
                                while (list($k,$v)=each($threadorder_array)) {
                                        if ($v == $insert_po_id) {
                                                $threadorder_array_new[$i] = $v;
                                                $i++;
                                                $threadorder_array_new[$i] = $new_po_id;

                                        } else {
                                                $threadorder_array_new[$i] = $v;
                                        }
                                        $i++;
                                }
                                $threadorder = implode(",",$threadorder_array_new);
                        }

                        //threadorder neu schreiben
                        $sql = "update posting
                                set po_threadorder = '$threadorder', po_threadts = ".time()."
                                where po_id = $thread";
                        mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			
			#print "neue threadorder: $threadorder<BR>";

                        //schliesslich noch die markierung des letzten in der Ebene entfernen
                        $sql = "update posting
                                set po_threadorder = '0'
                                where po_threadorder = '1'
                                and po_id <> $new_po_id
                                and po_vater_id = $po_vater_id";
                        mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

                        //Tabellen wieder freigeben
                        $sql = "UNLOCK TABLES";
                        @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

                } else {
                        //Thread neu setzen
			$thread = $new_po_id;

                }


                //th_postings muss neu geschrieben werden
                //anz_threads und anz_replys im Thema setzen
                //erst Tabelle thema sperren
                $sql = "LOCK TABLES thema WRITE";
                @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

                //altes th_postings und anz_threads und anz_replys holen
                $sql = "select th_postings, th_anzthreads, th_anzreplys from thema where th_id = $th_id";
                $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                $postings = mysql_result($query,0,"th_postings");
                $anzthreads = mysql_result($query,0,"th_anzthreads");
                $anzreplys = mysql_result($query,0,"th_anzreplys");

                if (!$postings)         //erstes Posting in diesem Thema
                        $postings = $new_po_id;
                else {                  //schon postings da
                        $postings_array = explode(",",$postings);
                        $postings_array[] = $new_po_id;
                        $postings = implode(",", $postings_array);
                }

                if ($po_vater_id == 0) {
                        //neuer Thread
                        $anzthreads++;
                } else {
                        //neue Antwort
                        $anzreplys++;
                }

                //schreiben
		$sql = "update thema
                        set th_postings = '$postings',
                        th_anzthreads = $anzthreads,
                        th_anzreplys = $anzreplys
                        where th_id = $th_id";
                mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

                //Tabellen wieder freigeben
                $sql = "UNLOCK TABLES";
                @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

        }

        if (!isset($new_po_id)) $new_po_id = 0;
        return $new_po_id;
}

//holt ausgehend von root_id das letzte Posting
//im Teilbaum unterhalb der root_id
function hole_letzten($root_id, $new_po_id) {

        global $conn;
        $sql = "select po_id
                from posting
                where po_vater_id = $root_id
                and po_id <> $new_po_id
                order by po_ts desc
                limit 1";

	#print "<P>Aufruf-Funktion: hole_letzten ($root_id, $new_po_id)<P>";
	#print $sql."<p>";

        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $anzahl = mysql_num_rows($query);
        if ($anzahl > 0)
                $new_root_id = mysql_result($query, 0, "po_id");
        @mysql_free_result($query);

	#print "<BR>new_root_id = $new_root_id<BR>";

        if ($anzahl > 0) {
                //es geht noch tiefer...
                $retval = hole_letzten($new_root_id, $new_po_id);
        } else {
                $retval = $root_id;
        }

        return $retval;


}

//loescht das Posting und alle Antworten darauf
function loesche_posting() {

        global $conn, $th_id, $po_id, $thread;
        global $arr_delete;
	global $farbe_tabellenrahmen,$farbe_tabelle_kopf2,$punkte_pro_posting,$t,$farbe_text;
	
        $arr_delete = array();

        //tabelle posting und thema locken
        $sql = "LOCK TABLES posting, thema WRITE";
        @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

        //rekursiv alle zu loeschenden postings in feld einlesen
        $arr_delete[] = $po_id;
        hole_alle_unter($po_id);

        //po_threadorder neu schreiben
        //nur relevant, wenn posting nicht erster im Thread
        //ansonsten wird es eh geloescht
        if ($po_id != $thread) {

                $sql = "select po_threadorder, po_ts from posting where po_id=$thread";
                $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                $threadorder = mysql_result($query,0,"po_threadorder");
                $new_ts = mysql_result($query,0,"po_ts");

                //in array einlesen und zu loeschende rausschmeissen
                $arr_new_threadorder = explode(",",$threadorder);

                $arr_new_threadorder = array_diff($arr_new_threadorder, $arr_delete);

                if (count($arr_new_threadorder)==0)
		{
                        $arr_new_threadorder[0] = 0;
		}
		else
		{
			// beim löschen eines Postings wird hier die letzte änderung aller postings gesucht, damit 
			// der thread_ts wieder stimmt
			$new_ts = '0000000000';
	                $new_threadorder = implode(",",$arr_new_threadorder);
	                $arr_new_threadorder = explode(",",$new_threadorder);
	                for ($i=0; $i<count($arr_new_threadorder); $i++)
        	        {
				$sql = "select po_ts from posting where po_id = $arr_new_threadorder[$i] ";
				$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
				$ts = mysql_result($query,0,"po_ts");
				if ($ts > $new_ts) $new_ts = $ts;
         	       }

		}

                $new_threadorder = implode(",",$arr_new_threadorder);

                $sql = "update posting
                        set po_threadorder = '$new_threadorder', po_threadts = $new_ts
                        where po_id = $thread";
                mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		//eventuell letztes Posting auf ebene neu markieren
                $sql = "select po_vater_id, po_threadorder from posting where po_id = $po_id";
                $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                $threadorder = mysql_result($query,0,"po_threadorder");
                $vater_id = mysql_result($query,0,"po_vater_id");

                //falls letztes posting, dann neu setzen
                if ($threadorder == "1") {

                        $sql = "select po_id from posting
                                where po_vater_id = $vater_id
                                and po_id <> $po_id
                                order by po_ts desc
                                limit 1";
                        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                        if (mysql_num_rows($query) > 0) {

                                $po_id_update = mysql_result($query,0,"po_id");

                                $sql = "update posting
                                        set po_threadorder = '1'
                                        where po_id = $po_id_update";
                                mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
                        }
                }

        }

        //eintragungen in Thema neu schreiben

	$sql = "select th_anzthreads, th_anzreplys, th_postings from thema where th_id=$th_id";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $postings = mysql_result($query,0,"th_postings");
        $anzthreads = mysql_result($query,0,"th_anzthreads");
        $anzreplys = mysql_result($query,0,"th_anzreplys");

        //in array einlesen und zu loeschende rausschmeissen
        $arr_new_postings = explode(",",$postings);

        $arr_new_postings = array_diff($arr_new_postings, $arr_delete);

        $new_postings = implode(",",$arr_new_postings);

        //th_anzthreads und th_anzreplys neu schreiben
        if ($po_id == $thread) {

                $anzthreads--;
                $anzreplys = $anzreplys - count($arr_delete) + 1;

        } else {

                $anzreplys = $anzreplys - count($arr_delete);

        }

        $sql = "update thema
                set th_anzthreads = $anzthreads,
                th_anzreplys = $anzreplys,
                th_postings = '$new_postings'
                where th_id = $th_id";
        mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);


	// Punkte abziehen
	echo "<table width=\"760\" cellspacing=\"0\" cellpadding=\"1\" border=\"0\" bgcolor=\"$farbe_tabellenrahmen\"><tr><td>\n".
		"<table width=\"100%\" cellspacing=\"0\" cellpadding=\"3\" border=\"0\">".
		"<tr bgcolor=\"$farbe_tabelle_kopf2\" valign=\"bottom\">\n<TD><DIV style=\"color:$farbe_text; font-weight:bold;\">";
        reset($arr_delete);
        while (list($k,$v)=@each($arr_delete)) {
                $sql = "select po_u_id from posting where po_id = $v";
                $result=mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result && mysql_num_rows($result)==1) {
			$po_u_id=mysql_result($result,0,0);
			if($po_u_id)
				echo $t['forum_punkte2'].punkte_offline($punkte_pro_posting*(-1),$po_u_id)."<BR>";
		}
		@mysql_free_result($result);
        }
	echo "</DIV></TD></tr></table></td></tr></table><BR>\n";	

        reset($arr_delete);
        while (list($k,$v)=@each($arr_delete)) {
		$sql = "delete from posting where po_id = $v";
                mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        }

        //Tabellen wieder freigeben
        $sql = "UNLOCK TABLES";
        @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

}

//holt zum Löschen alle Postings unterhalb des vaters
function hole_alle_unter($vater_id) {

        global $conn, $arr_delete;

        $sql = "select po_id
                from posting
                where po_vater_id = $vater_id";
        $query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
        $anzahl = mysql_num_rows($query);
        if ($anzahl > 0) {
                while ($posting = mysql_fetch_array($query, MYSQL_ASSOC)) {

                        $arr_delete[] = $posting['po_id'];
                        hole_alle_unter($posting['po_id']);

                }
        }
        @mysql_free_result($query);


}

//bereinigt jede Woche einmal die Spalte u_gelesene_postings 
//des users $u_id
function bereinige_u_gelesene_postings($u_id) {

	global $conn;
	
	$sql = "select u_gelesene_postings, u_lastclean 
		from user
		where u_id = $u_id";
	$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

	if ($query && mysql_num_rows($query)>0) {

		$lastclean = mysql_result($query,0,"u_lastclean");
		$gelesene = mysql_result($query,0,"u_gelesene_postings");
		if ($lastclean == "0") {			//keine Bereinignng nötig
			
			$lastclean = time();
			$sql = "update user set u_lastclean = $lastclean where u_id = $u_id";
			mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

	
		} else if ($lastclean < (time()-2592000)) { 	//Bereinigung nötig
			$lastclean = time();
		        $arr_gelesene = unserialize($gelesene);

			//alle Postings in Feld einlesen
			$sql = "select po_id from posting order by po_id";
			$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$arr_postings = array();
			while ($posting = mysql_fetch_array($query, MYSQL_ASSOC))
				$arr_postings[] = $posting['po_id'];

			if (is_array($arr_gelesene))
			{
			
				while (list($k,$v) = each($arr_gelesene)) {

					$arr_gelesene[$k] = array_intersect($arr_gelesene[$k], $arr_postings);

				}

			} 	

			$gelesene_neu = serialize($arr_gelesene);
		
			$sql = "update user set 
				u_lastclean = $lastclean,
				u_gelesene_postings = '$gelesene_neu'
				where u_id = $u_id";
		
			mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		} 
	
		

	}

}

//bereinigt wenn ein SU das Forum betritt die Anzahl der beiträge und Replays mim Thema
function bereinige_anz_in_thema() 
{
	global $conn;
	
	$sql = "select th_id from thema order by th_id";
	$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

	if ($query && mysql_num_rows($query)>0) 
	{
		while ($row = mysql_fetch_array($query, MYSQL_ASSOC))
		{

                        $sql = "LOCK TABLES posting WRITE, thema WRITE";
                        @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

			$sql2 = "select count(*) from posting where po_vater_id = 0 and po_th_id = ".$row['th_id'];
			$query2 = mysql_query($sql2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$anzahl_thread = mysql_result($query2,0,0);

			$sql2 = "select count(*) from posting where po_vater_id <> 0 and po_th_id = ".$row['th_id'];
			$query2 = mysql_query($sql2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			$anzahl_reply = mysql_result($query2,0,0);

			$sql2 = "update thema set th_anzthreads = $anzahl_thread, th_anzreplys = $anzahl_reply  where th_id = $row[th_id]";
			mysql_query($sql2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

                        $sql = "UNLOCK TABLES";
                        @mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		}
	}
}

function verschiebe_posting_ausfuehren()
{
	global $conn, $thread_verschiebe, $verschiebe_von, $verschiebe_nach;

	if (!preg_match("/^([0-9])+$/i", $thread_verschiebe))
		exit();
	if (!preg_match("/^([0-9])+$/i", $verschiebe_von))
		exit();
 	if (!preg_match("/^([0-9])+$/i", $verschiebe_nach))
		exit();


	// Ändert die alle Postings eine Threads
	$sql = "SELECT po_threadorder FROM posting WHERE po_id = $thread_verschiebe AND po_th_id = $verschiebe_von";
	$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($query && mysql_num_rows($query)==1) 
	{
	        $sql = "LOCK TABLES posting WRITE, thema WRITE";
        	@mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		// Verschiebt alle Kinder wenn vorhanden
      		$postings = mysql_result($query,0,"po_threadorder");
		if (trim($postings) <> "0")
		{
			$postings2 = explode(",", $postings);
			for ($i=0; $i<count($postings2); $i++)
			{
				$sqlupdate="UPDATE posting SET po_th_id = $verschiebe_nach WHERE po_id = ".$postings2[$i];
//				echo "DEBUG: $sqlupdate<br>";
				mysql_query($sqlupdate, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			}
		}

		// Verschiebt den Vater
		$sqlupdate="UPDATE posting SET po_th_id = $verschiebe_nach WHERE po_id = $thread_verschiebe";
//		echo "DEBUG: $sqlupdate<br>";
		mysql_query($sqlupdate, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		// Baut Threadorder des Themas ALT und NEU komplett neu auf		
		// Da manchmal auch diese Threadorder kaputt geht
		$sql2="SELECT po_id FROM posting WHERE po_th_id = $verschiebe_von ";
		$query2=mysql_query($sql2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		$neuethreadorder="0";
		if ($query2 && mysql_num_rows($query2)>0)
		{
	                while ($row2 = mysql_fetch_array($query2, MYSQL_ASSOC))
			{
				if ($neuethreadorder == "0") $neuethreadorder = "$row2[po_id]";
				else $neuethreadorder .= ",$row2[po_id]";
			}
		}
		$sqlupdate="UPDATE thema SET th_postings = \"$neuethreadorder\" WHERE th_id = $verschiebe_von";
//		echo "DEBUG: $sqlupdate<br>";
		mysql_query($sqlupdate, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		$sql2="SELECT po_id FROM posting WHERE po_th_id = $verschiebe_nach ";
		$query2=mysql_query($sql2, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		$neuethreadorder="0";
		if ($query2 && mysql_num_rows($query2)>0)
		{
	                while ($row2 = mysql_fetch_array($query2, MYSQL_ASSOC))
			{
				if ($neuethreadorder == "0") $neuethreadorder = "$row2[po_id]";
				else $neuethreadorder .= ",$row2[po_id]";
			}
		}
		$sqlupdate="UPDATE thema SET th_postings = \"$neuethreadorder\" WHERE th_id = $verschiebe_nach";
//		echo "DEBUG: $sqlupdate<br>";
		mysql_query($sqlupdate, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

	        $sql = "UNLOCK TABLES";
        	@mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

		bereinige_anz_in_thema();
	}

}

function ersetzte_smilies($text) {

	global $sprachconfig,$smilies_pfad,$smilies_datei,$smilies_config;
	
	preg_match_all("/(&amp;[^ |^<]+)/",$text,$test,PREG_PATTERN_ORDER);
	
	if ($smilies_config)
		require("conf/".$smilies_config);
        else
        	require("conf/".$sprachconfig."-".$smilies_datei);

	while(list($i,$smilie_code)=each($test[0])){
		$smilie_code2=str_replace("&amp;","&",$smilie_code);
		$smilie_code2 = chop($smilie_code2);
		if ($smilie[$smilie_code2]) {
                	$text=str_replace($smilie_code,"<IMG SRC=\"".$smilies_pfad.$smilie[$smilie_code2]."\">",$text);
                }
        }
	
	return $text;

}

// erzeugt Aktionen bei Antwort auf ein Posting
function aktion_sofort($po_id, $po_vater_id, $thread) {

	global $conn, $t;
	//aktionen nur fuer Antworten
	if ($po_vater_id > 0) {

		$sql = "select po_u_id, date_format(from_unixtime(po_ts), '%d.%m.%Y') as po_date, po_titel,
			th_name, fo_name 
			from posting, thema, forum
			where po_id = $po_vater_id
			and po_th_id = th_id
			and th_fo_id = fo_id";
		
		$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);

        	if ($query && mysql_num_rows($query)>0) {

			//Daten des Vaters holen
                	$user = mysql_result($query,0,"po_u_id");
               		$po_ts = mysql_result($query,0,"po_date");
			$po_titel = mysql_result($query,0,"po_titel");
			$thema = mysql_result($query, 0, "th_name");
			$forum = mysql_result($query, 0, "fo_name");

			@mysql_free_result($query);

		} else 
			return;

		$sql = "select u_id, u_nick, 
			date_format(from_unixtime(po_ts), '%d.%m.%Y %H:%i') as po_date, po_titel  
			from user, posting 
			where po_u_id = u_id 
			and po_id = $po_id";
		
		$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		
		if ($query && mysql_num_rows($query)>0) {
			$user_from_id = mysql_result($query,0,"u_id");
                        $user_from_nick = mysql_result($query,0,"u_nick");
                	$po_ts_antwort = mysql_result($query,0,"po_date");
			$po_titel_antwort = mysql_result($query,0,"po_titel");
		        @mysql_free_result($query);
                } else
                        return;
 
		//Ist betroffener User Online?
		$online = ist_online($user);

		if ($online)
			$wann = "Sofort/Online";
		else
			$wann = "Sofort/Offline";
		
		//Threadorder fuer diesen Thread holen
		$sql = "select po_threadorder 
			from posting 
			where po_id=$thread";
		$query = mysql_query($sql, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($query && mysql_num_rows($query)==1) {

			$threadorder = mysql_result($query,0,"po_threadorder");

		} else
			return;

		$baum = erzeuge_baum($threadorder, $po_id, $thread);
		
		$text['po_titel'] = $po_titel;
		$text['po_ts'] = $po_ts;
		$text['forum'] = $forum;
		$text['thema'] = $thema;
		$text['user_from_nick'] = $user_from_nick;
		$text['po_titel_antwort'] = $po_titel_antwort;
		$text['po_ts_antwort'] = $po_ts_antwort;
		$text['baum'] = $baum;

		aktion($wann, $user, $user_from_id, "", "Antwort auf eigenes Posting", $text);
	}

}

?>
