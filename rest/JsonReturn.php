<?php 
 
	$timestamp = time();
	$datum = date("d.m.Y - H:i", $timestamp);
	
		
	$handle = fopen("./logfile.log", "a+");	
	fwrite($handle, "Start\r\n");
	

	$mysqli = new mysqli("localhost", "root", "", "bbtf");
	if ($mysqli->connect_error) {
		fwrite($handle,  "Fehler bei der Verbindung: " . mysqli_connect_error());
		exit();
	}
	if (!$mysqli->set_charset("utf8")) {
		fwrite($handle, "Fehler beim Laden von UTF8 ". $mysqli->error);
	}	
	
    // Get Post Data
    $data = urldecode($_POST['data']);
	$json = json_decode($data,true);		
	
	// Logininformationen prüfen und userid zurückgeben
	$email = $json['email'];
	$password = $json['password'];
	$android_id = $json['android_id'];	

	
	fwrite ($handle, "Prüfe $email, $password und $android_id\r\n");
	
	// ID-Users holen, wenn keine ID gefunden wird, scheint es den User nicht zu geben
	$sql = "SELECT id, name FROM users WHERE email='$email' and password='$password';";
	fwrite($handle, "sql: [ $sql ]\r\n");
	
	$ergebnis = $mysqli->query($sql);

	if ($ergebnis->num_rows > 0) {
		/* Mandant erfolgreich geprüft */
		$row = $ergebnis->fetch_assoc(); 
		$user_id = $row["id"];
		$ergebnis->close();
		
		fwrite($handle, "id: " . $row["id"]. " - Name: " . $row["name"]. "\r\n");
		foreach($json['ratings'] as $rating) {
			$sql = "INSERT INTO ratings_native (id, rating, datetime, datum, idpupil,idsubject, pupilname, subject
					, email, password, android_id) 
				VALUES('$rating[id]', '$rating[rating]','$rating[datetime]','$rating[datum]'
				      ,'$rating[idpupil]','$rating[idsubject]', '$rating[pupilname]'
					  ,'$rating[subject]', '$rating[email]', '$rating[password]'
					  ,'$rating[android_id]'  );";
			
			fwrite($handle, "sql: [ $sql ]\r\n");
			
			// Datensatz einfügen
			$ergebnis = $mysqli->query($sql);			
			
			// Schüler eintragen 
			fwrite ($handle, "\nSchüler einfügen!!!\n");
			$insert = "INSERT INTO schueler (idpupil, name, user_id) VALUES ('$rating[idpupil]', '$rating[pupilname]', $user_id) ON DUPLICATE KEY UPDATE name='$rating[pupilname]'";
			fwrite($handle, "$insert \r\n");			
			if($ergebnis = $mysqli->query($insert)) {  
					fwrite($handle, "\nAnzahl der veränderten Datensätze: " 
							. $mysqli->affected_rows);
			} else {
				fwrite($handle, "$mysqli->error");
			}
			
			// Geräte eintragen
			fwrite ($handle, "\nGeräte einfügen!!!\n");
			$insert = "INSERT INTO geraete  (id_geraete, user_id) VALUES ('$rating[android_id]', $user_id)";			
			fwrite($handle, "$insert \r\n");
			$ergebnis = $mysqli->query($insert);		
			
			// Kurse eintragen
			fwrite ($handle, "\nKurse eintragen!!!\n");
			$insert = "INSERT INTO kurs  (idsubject, beschreibung, userid) VALUES ('$rating[idsubject]','$rating[subject]' , $user_id) ON DUPLICATE KEY UPDATE beschreibung='$rating[subject]'";			
			fwrite($handle, "$insert \r\n");
			
			if($ergebnis = $mysqli->query($insert)) {  
					fwrite($handle, "\nAnzahl der veränderten Datensätze: " 
							. $mysqli->affected_rows);
			} else {
				fwrite($handle, "$mysqli->error");
			}		
	
		}	
		
		foreach($json['ratings'] as $rating) {
			// Noch ein Lauf, um die Schüler-Kurs - Tabelle zu füllen
			// Schüler-Kurs-Tabelle füllen --> funktioniert erst, wenn alle Schüler und alle Kurs eingetragen sind!
			fwrite ($handle, "\nSchüler-Kurs-Tabelle eintragen!!!\n");
			$insert = "INSERT INTO schueler_kurs  (pupil_id, subject_id, userid) VALUES ('$rating[idpupil]','$rating[idsubject]' , $user_id)";			
			fwrite($handle, "$insert \r\n");
			
			// Wertungen eintragen --> funktioniert erst, wenn alle Schüler und alle Kurs eingetragen sind!
			fwrite ($handle, "\nWertungen Tabelle eintragen!!!\n");
			//$my_datetime = strtotime($rating[datetime]);
			$my_datetime =date("Y-m-d H:i:s", $rating[datetime]);
			$insert = "INSERT INTO wertung  (idwertung, wert, datumzeit, subject_id, pupil_id, geraete_id, userid) VALUES ('$rating[id]','$rating[rating]' 
					, '$my_datetime', '$rating[idsubject]', '$rating[idpupil]','$rating[android_id]', $user_id)";			
			fwrite($handle, "$insert \r\n");
			
			if($ergebnis = $mysqli->query($insert)) {  
					fwrite($handle, "\nAnzahl der veränderten Datensätze: " 
							. $mysqli->affected_rows);
			} else {
				fwrite($handle, "$mysqli->error");
			}
		}
		
		$mysqli->close();
		$json_ret = array("status" => 1, "msg" => "Alles ok");	
		print_r(json_encode($json_ret));     	
		
	} 
	
	
	else {
		fwrite($handle, "0 results\r\n");
		$json_ret = array("status" => 0, "msg" => "Mandant nicht gefunden!");	
		print_r(json_encode($json_ret));     
		
	}
		

	
	fwrite($handle,"Ende\n");
	fclose($handle);
	

 ?>