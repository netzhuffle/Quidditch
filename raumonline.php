<?php

	// Die öffentlichen Räume als Liste für ein Loginformular ausgeben

	require ("functions.php");

        // Falls eintrittsraum nicht gesetzt ist, mit Lobby überschreiben
        if (strlen($eintrittsraum)==0){
                $eintrittsraum=$lobby; 
        };

        // Raumauswahlliste erstellen
        $query="SELECT r_name,r_id FROM raum ".
                "WHERE (r_status1='O' OR r_status1 LIKE BINARY 'm') AND r_status2='P' ".
                "ORDER BY r_name";

        $result=mysql_query($query,$conn) or trigger_error(mysql_error(), E_USER_ERROR);
        if ($result) {
                $rows=mysql_num_rows($result);
        };

        $raeume="";

        if ($rows>0){
                $i=0;
                while ($i<$rows){
                        $r_id=mysql_result($result,$i,"r_id");
                        $r_name=mysql_result($result,$i,"r_name");
                        if ((!$eintritt AND $r_name==$eintrittsraum) || ($eintritt AND $r_id==$eintritt)){
                                $raeume=$raeume."<OPTION SELECTED VALUE=\"$r_id\">$r_name\n";
                        }else{
                                $raeume=$raeume."<OPTION VALUE=\"$r_id\">$r_name\n";
                        }
                        $i++; 
                };
        };
        mysql_free_result($result);

        echo "$raeume";
?>
