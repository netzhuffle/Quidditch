<?php

// $Id: functions.php-moderator.php,v 1.3 2012/10/17 06:16:53 student Exp $

function zeige_moderations_antworten($o_raum,$answer="") {
	global $t;
	global $id;
	global $dbase, $conn;
	global $u_id;
	global $http_host;
	global $farbe_tabelle_zeile1;
	global $farbe_tabelle_zeile2;
	global $farbe_moderationr_zeile1;
	global $farbe_moderationr_zeile2;
	global $farbe_moderationg_zeile1;
	global $farbe_moderationg_zeile2;

	$query="SELECT c_id,c_text FROM moderation WHERE c_raum=$o_raum AND c_typ='P' ORDER BY c_text";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	print "<table width=100% border=0 cellpadding=0 cellspacing=0>";
	if ($result>0) {
		$i=0;
		while ($row=mysql_fetch_object($result)) {
			$i++;
			if ($i % 2 ==0) {
				echo "<tr bgcolor=$farbe_tabelle_zeile1>";
				$g=$farbe_moderationg_zeile1;
				$r=$farbe_moderationr_zeile1;
			} else {
				echo "<tr bgcolor=$farbe_tabelle_zeile2>";
				$g=$farbe_moderationg_zeile2;
				$r=$farbe_moderationr_zeile2;
			}
			print "<td align=left><small>";
			print "<A HREF=\"#\" onclick=\"opener.parent.frames['schreibe'].location='schreibe.php?http_host=$http_host&id=$id&text=";
			print urlencode($row->c_text);
			print "'; return(false);\">";
			print "$row->c_text</a></small></td><td align=right><small>";
			print "<a href=moderator.php?id=$id&http_host=$http_host&mode=answeredit&answer=$row->c_id>$t[mod12]</a> ";
			print "<a href=moderator.php?id=$id&http_host=$http_host&mode=answerdel&answer=$row->c_id>$t[mod13]</a> ";
			print "</small></td>";
			print "</tr>";
		}
		mysql_free_result($result);
	}
	print "</table>";
	print "<br><center>";
	print "<form>";
	print "<font>".$t['mod11']."</font><br>";
	print "<input type=hidden name=id value=$id>";
	print "<input type=hidden name=http_host value=$http_host>";
	print "<input type=hidden name=mode value=answernew>";
	if ($answer!="") print "<input type=hidden name=answer value=$answer>";
	print "<textarea name=answertxt rows=5 cols=60>";
	if ($answer!="") {
	$answer=addslashes($answer);
	$query="SELECT c_id,c_text FROM moderation WHERE c_id=$answer AND c_typ='P'";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
		if ($result>0) {
			print mysql_result($result,0,"c_text");	
		}
		mysql_free_result($result);
	}
	print "</textarea>";
	print "<br><input type=submit value=\"$t[mod1]\">";
	print "</form>";
	print "<a href=# onclick=window.close();>".$t['mod10']."</a></center>";
}

function bearbeite_moderationstexte($o_raum) {
	global $t;
	global $id;
	global $dbase, $conn;
	global $action;
	global $u_id;
	global $system_farbe;

	if (is_array($action)) {
		print "<font><small>";
		$a=0;
		reset ($action);
		// erst mal die Datensätze reservieren...
		while ($a<count($action)) {
			$key=key($action);
			// nur markieren, was noch frei ist.
			$query="UPDATE moderation SET c_moderator=$u_id WHERE c_id=$key AND c_typ='N' AND c_moderator=0";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			next($action);
			$a++;
		}
		// jetzt die reservierten Aktionen bearbeiten.
		$a=0;
		reset ($action);
		while ($a<count($action)) {
			$key=key($action);
			// nur auswählen, was bereits von diesem Moderator reserviert ist
			$query="SELECT * FROM moderation WHERE c_id=$key AND c_typ='N' AND c_moderator=$u_id";
			$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			if ($result>0) {
				if (mysql_num_rows($result)>0) {
					$f=mysql_fetch_array($result);
					// print "bearbeite $key $action[$key] $f[c_text]<br>";
					switch($action[$key]) {
						case "ok":
						case "clear":
						case "thru":
							// print "$t[mod6] $f[c_text]<br>";
							// vorbereiten für umspeichern... geht leider nicht 1:1, 
							// weil fetch_array mehr zurückliefert als in $f[] sein darf...
							$c['c_von_user']=$f['c_von_user'];
							$c['c_an_user']=$f['c_an_user'];
							$c['c_typ']=$f['c_typ'];
							$c['c_raum']=$f['c_raum'];
							$c['c_text']=$f['c_text'];
							$c['c_farbe']=$f['c_farbe'];
							$c['c_zeit']=$f['c_zeit'];
							$c['c_von_user_id']=$f['c_von_user_id'];
							if ($action[$key]=="ok") {
								// eigene ID vermerken
								$c['c_moderator']=$u_id;
								// Zeit löschen, damit markierte oben erscheint...
								unset($c['c_zeit']);
							} else {
								// freigeben -> id=0 schreiben
								$c['c_moderator']=0;
							}
							// und in moderations-tabelle schreiben
							if ($action[$key]=="thru") {
								unset($c['c_moderator']);
								schreibe_chat($c);
							} else {
								schreibe_moderiert($c);
							}
							break;
						case "notagain":
							// print "$t[mod7] $f[c_text]<br>";
							system_msg("",0,$f['c_von_user_id'],$system_farbe,$t['moderiertdel1']);
							system_msg("",0,$f['c_von_user_id'],$system_farbe,"&gt;&gt;&gt; ".$f['c_text']);
							break;
						case "better":
							// print "$t[mod7] $f[c_text]<br>";
							system_msg("",0,$f['c_von_user_id'],$system_farbe,$t['moderiertdel2']);
							system_msg("",0,$f['c_von_user_id'],$system_farbe,"&gt;&gt;&gt; ".$f['c_text']);
							break;
						case "notime":
							// print "$t[mod7] $f[c_text]<br>";
							system_msg("",0,$f['c_von_user_id'],$system_farbe,$t['moderiertdel3']);
							system_msg("",0,$f['c_von_user_id'],$system_farbe,"&gt;&gt;&gt; ".$f['c_text']);
							break;
						case "delete":
							// print "$t[mod7] $f[c_text]<br>";
							system_msg("",0,$f['c_von_user_id'],$system_farbe,$t['moderiertdel4']);
							system_msg("",0,$f['c_von_user_id'],$system_farbe,"&gt;&gt;&gt; ".$f['c_text']);
							break;
					}
					// jetzt noch aus moderierter Tabelle löschen.
					mysql_free_result($result);
					$query="DELETE FROM moderation WHERE c_id=$key AND c_moderator=$u_id";
					$result2=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
			    } else {
					print "$t[mod9]<br>";
			    }
			}
			next($action);
			$a++;
		}
		print "</small></font>";
	}
}

function zeige_moderationstexte($o_raum,$limit=20) {
	global $t;
	global $farbe_tabelle_kopf;
	global $farbe_tabelle_zeile1;
	global $farbe_tabelle_zeile2;
	global $farbe_moderationr_zeile1;
	global $farbe_moderationr_zeile2;
	global $farbe_moderationg_zeile1;
	global $farbe_moderationg_zeile2;
	global $http_host;
	global $id;
	global $dbase, $conn;
	global $action;
	global $moderation_rueckwaerts;
	global $moderation_grau;
	global $moderation_schwarz;
	global $moderationsexpire;
	global $u_id;
	global $o_js;

	// gegen DAU-Eingaben sichern...
	$limit=max(intval($limit),20);
	// erst mal alle alten Msgs expiren...
	if ($moderationsexpire==0) $moderationsexpire=30;
	$expiretime=$moderationsexpire*60;
	$query="DELETE from moderation WHERE unix_timestamp(c_zeit)+$expiretime<unix_timestamp(NOW()) AND c_moderator=0 AND c_typ='N'";
	// print "<pre>\n$query<\pre>";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
  
	if ($moderation_rueckwaerts==1) $rev=" DESC";
	$query="SELECT c_id,c_text,c_von_user,c_moderator FROM moderation WHERE c_raum=$o_raum AND c_typ='N' ORDER BY c_id $rev LIMIT 0,$limit";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	$i=0;
	$rows=0;
	if ($result>0) {
		$rows=mysql_num_rows($result);
		if ($o_js) {
			echo "<SCRIPT LANGUAGE=Javascript>\n";
			echo "function sel() {\n";
			echo "	document.forms['modtext'].elements['ok'].focus();\n";
			echo "}\n";
			echo "</SCRIPT>\n";
		}
		echo "<form name=modtext action=\"moderator.php?http_host=$http_host&id=$id\" method=\"post\">\n";
		if ($rows>0) {

			echo "<table width=100% cellpadding=0 cellspacing=0 border=0>\n";
			echo "<tr bgcolor=$farbe_tabelle_kopf>";
			echo "<td align=center valign=bottom><img src=\"pics/ok.gif\" height=20 width=20 alt=\"".$t['mod16']."\"></td>";
			echo "<td align=center valign=bottom><img src=\"pics/nope.gif\" height=20 width=20 alt=\"".$t['mod17']."\"></td>";
			echo "<td valign=bottom>";
			echo "<table width=100% cellpadding=0 cellspacing=0 border=0><tr><td>";
			echo "<small><b>".$t['mod2'];
			echo "</td><td align=right>";
			echo "<input type=submit name=ok2 value=\"go!\">\n";
			echo "</b></small>";
			echo "</td></tr></table>";
			echo "</td>";
			echo "<td align=center valign=bottom><img src=\"pics/ok.gif\" height=20 width=20 alt=\"".$t['mod14']."\"></td>";
			echo "<td align=center valign=bottom><img src=\"pics/wdh.gif\" height=20 width=20 alt=\"".$t['mod3']."\"></td>";
			echo "<td align=center valign=bottom><img src=\"pics/smile.gif\" height=20 width=20 alt=\"".$t['mod4']."\"></td>";
			echo "<td align=center valign=bottom><img src=\"pics/time.gif\" height=20 width=20 alt=\"".$t['mod5']."\"></td>";
			echo "<td align=center valign=bottom><img src=\"pics/nope.gif\" height=20 width=20 alt=\"".$t['mod15']."\"></td>";
			echo "</tr>\n";
			
			while ($row=mysql_fetch_object($result)) {
				$i++;
				if ($i % 2 ==0) {
					echo "<tr bgcolor=$farbe_tabelle_zeile1>";
					$g=$farbe_moderationg_zeile1;
					$r=$farbe_moderationr_zeile1;
				} else {
					echo "<tr bgcolor=$farbe_tabelle_zeile2>";
					$g=$farbe_moderationg_zeile2;
					$r=$farbe_moderationr_zeile2;
				}
				echo "<td align=center bgcolor=$g><small>";
				if ($row->c_moderator==$u_id || $row->c_moderator==0) {
					echo "<input type=radio name=action[$row->c_id] value='ok' onclick=submit();";
					if ($row->c_moderator==$u_id) echo " checked";
					echo ">";
					$tc=$moderation_schwarz;
				} else {
					echo "&nbsp";
					$tc=$moderation_grau;
				}
				echo "</small></td>";
				echo "<td align=center bgcolor=$g><small>";
				if ($row->c_moderator==$u_id) {
					echo "<input type=radio name=action[$row->c_id] value='clear' onclick=submit();>";
					$b1="<b>";
					$b2="</b>";
				} else {
					$b1="";
					$b2="";
				}
				echo "&nbsp;</small></td>";
				echo "<td width=100%><small><font color=$tc>$b1$row->c_von_user: $row->c_text$b2</font></small></td>";
				if ($row->c_moderator==$u_id || $row->c_moderator==0) {
/*
					echo "<td align=center bgcolor=$g><small><input type=radio name=action[$row->c_id] value='thru' onclick=sel();></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='notagain' onclick=sel();></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='better' onclick=sel();></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='notime' onclick=sel();></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='delete' onclick=sel();></small></td>";
*/
					echo "<td align=center bgcolor=$g><small><input type=radio name=action[$row->c_id] value='thru';></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='notagain';></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='better';></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='notime';></small></td>";
					echo "<td align=center bgcolor=$r><small><input type=radio name=action[$row->c_id] value='delete';></small></td>";
				} else {
					echo "<td bgcolor=$g><small>&nbsp;</small></td>";
					echo "<td bgcolor=$r><small>&nbsp;</small></td>";
					echo "<td bgcolor=$r><small>&nbsp;</small></td>";
					echo "<td bgcolor=$r><small>&nbsp;</small></td>";
					echo "<td bgcolor=$r><small>&nbsp;</small></td>";
				}
				echo "</tr>\n";
			}
			echo "</table>\n";
		}
		echo "<center>";
		/*
			echo "<select name=limit>";
			for ($i=25; $i<=200; $i+=25){
				if ($limit==$i) $sel="selected";
				else $sel="";
				echo "<option value=$i $sel>$i";
			}
			echo "</select>";
		*/
		echo "<input type=text name=limit value=$limit size=5>\n";
		echo "<input type=submit name=ok value=".$t['mod_ok'].">\n";
		echo "</form>\n";
	}
	return $rows;
}

function anzahl_moderationstexte($o_raum) {
	global $http_host;
	global $id;
	global $dbase, $conn;

	$query="SELECT c_id FROM moderation WHERE c_raum=$o_raum AND c_typ='N' ORDER BY c_id";
	$result=mysql_query($query, $conn) or trigger_error(mysql_error(), E_USER_ERROR);
	if ($result>0) {
		$rows=mysql_num_rows($result);
	};
	return $rows;
}

?>
