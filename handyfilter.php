<?

// Liest aus der Userinfo die Handynummern, formatiert diese und schreibt sie zurück

include("functions.php");
include("smstools.php");

$query="SELECT ui_id, ui_handy FROM userinfo";
$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
while ($a=mysql_fetch_array($result))
	{
	$nummer=FormatNumber($a[ui_handy]);

	$query="UPDATE userinfo SET ui_handy = '$nummer' WHERE ui_id = '$a[ui_id]'";
	print "<li>$a[ui_id] - $a[ui_handy] => $nummer<BR>";
	mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
	#print $query."<br>";
	}
?>