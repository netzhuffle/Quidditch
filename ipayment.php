<?php

// Dieses Programm schreibt dem User seine Über IPayment gekauften SMS gut...
include("functions.php");

reset ($HTTP_POST_VARS);
while (list ($key, $val) = each ($HTTP_POST_VARS)) {
$v.= "$key => $val\n";
}
mail("martin@huskie.de","Mainchat-Chat-SMS-Kauf erfolgreich!",$v,"From: info@fidion.de\nReturn-path: info@fidion.de\n");

// Nur der IPayment-Gateway darf hier durch
if ($REMOTE_ADDR != "195.20.224.139") { print "IP error!";exit;}

// Betrag in EUR-Cent
$cc_amount=$HTTP_POST_VARS['trx_amount'];

// Ausrechnen wieviele SMS der User bekomt
$gekauftesms=floor($cc_amount / $sms[preis] + 0.5);

// Auslesen des bisherigen Guthabens
$query="SELECT u_sms_guthaben FROM user WHERE u_id = '$u_id'";
$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
$a=@mysql_fetch_array($result);
@mysql_free_result($result);

// Dazuaddieren
$f['u_sms_guthaben']=$a['u_sms_guthaben']+$gekauftesms;
// Schreiben
$f['ui_id']=schreibe_db("user",$f,$u_id,"u_id");

$conn2=mysql_connect("localhost","www","");
mysql_set_charset("utf8");
mysql_selectdb("ipayment",$conn2);
$query="INSERT INTO transaction_log (u_nick, u_id, datum, handynr, ip, http_host, trx_amount) VALUES ('$u_nick','$u_id',NOW(),'$handynr','$ret_ip','$http_host','$trx_amount')";
$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
$id=mysql_insert_id();
$v=addslashes($v);
$query="INSERT INTO payment_log (id, payment_text) VALUES ('$id','$v')";
$result=mysql_query($query) or trigger_error(mysql_error(), E_USER_ERROR);
?>