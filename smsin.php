<?
// $Id: smsin.php,v 1.2 2012/10/17 06:16:53 student Exp $

// Über diese Funktion werden alle eingehenden SMS eingelesen und in die MySQL-Datenbank geschrieben...
include("functions.php");
include("functions-msg.php");

function HoleNick($handynummer)
{
// holt den nick, wenn nicht gefunden, gibt handynummer mit 3 Stellen X aus

 $handynummer=trim($handynummer);
 $handynummer=preg_replace("/^0049/","0",$handynummer);

$query="SELECT user.u_id, user.u_nick,u_login FROM user, userinfo WHERE user.u_id=userinfo.ui_userid AND ui_handy='$handynummer' order by u_login desc limit 0,1";
#print $query;
$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
$nick="";
if (mysql_numrows($result) == "1") 
				{
				print "user gefunden!";
				$a=mysql_fetch_array($result);
				$nick=$a['u_nick'];
				}
if (!$nick)
		{
		 $nick=$handynummer;
		 $nick=substr($nick,0,strlen($nick)-3)."XXX";
		}
return($nick);
}

if ($timestamp && $smstext && $nummer && $keyword && $shortnumber)
			{
			$query="INSERT INTO smsin (s_timestamp,  s_text, s_handynummer,s_keyword,s_shortnumber) VALUES ('$timestamp','$smstext','$nummer','$keyword','$shortnumber')";
			mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
			print mysql_error();
			print "OK!<BR><HR>";

			$text=trim(preg_replace("/^MC/i","",$smstext));

			$text2=explode(" ",$text);
			$nick=trim($text2[0]);
			$text2[0]="";
			$text3=implode(" ",$text2);
			print "nick: $nick | text: $text3<BR>";
			
			$s_nick=nick_ergaenze($nick,"online",1);
			$u_id1=$s_nick['u_id'];

			$s_nick=nick_ergaenze($nick,"chat",1);
			$u_id2=$s_nick['u_id'];
			if ($u_id1)
				   {
				    print "u_id1 ist gesetzt!";
	              		    $nick=holeNick($nummer);
	  			    priv_msg("MainChat: SMS von $nick:",0,$u_id1,"#000000",$text3);
			#	   }

			#if ($u_id2 && !$u_id1)	
			#		{
			#	   	$nick=holenick($nummer);
					mail_sende(0,$u_id2,$text3,"SMS von $nick");
					print "u_id2 ist gesetzt!";
					}

			}
			else
			{
			print "Fehler! Parameter fehlen!";
			}
?>