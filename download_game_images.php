<?php

$conn = mysql_connect("localhost", "root", "-") or die(mysql_error());
mysql_select_db("catahya2", $conn) or die(mysql_error());

mysql_query("SET NAMES latin1", $conn) or die(mysql_error());

$sql = "SELECT text_id, type_id, text_meta FROM text WHERE type_id = 4";
$result = mysql_query($sql, $conn) or die(mysql_error());
while ($arr = mysql_fetch_assoc($result)) {
	if (preg_match_all("/<screen\d(text){0,1}>(.*?)<\/screen\d(text){0,1}>/i", $arr["text_meta"], $screens)) {
		$res = array();
		for ($i = 0; $i < 3; $i++) {
			if (!$screens[2][$i]) {
				continue;
			}
			
			$res[$i]["text"] = $screens[2][3+$i];
			$res[$i]["image"] = $screens[2][$i];
		}
		
		if (count($res)) {
			$sqlUpdate = "UPDATE text SET text_gallery = '1' WHERE text_id = %d";
			
			mysql_query(sprintf($sqlUpdate, $arr["text_id"]), $conn) or die(mysql_error());
		
			foreach ($res as $screen) {
				$image = $screen["image"];
				$desc = $screen["text"];
				
				if (strpos($image, "http://") !== 0) {
					$image = "http://www.catahya.net/bilder/" . $image . ".jpg";
				}
				echo "Downloading " . $image."\n";
				
				list($name) = array_reverse(explode("/", $image));
				//echo $name."\n";
				
				$path = "cache/images/" . $name;
				file_put_contents($path, file_get_contents($image));
				
				list($name2, $ext) = explode(".", strtolower($name));
				
				switch ($ext) {
					case "jpg":
					case "jpeg":
						$img = @imagecreatefromjpeg($path);
						break;
					case "png":
						$img = @imagecreatefrompng($path);
						break;
					case "gif":
						$img = @imagecreatefromgif($path);
						break;
				}
				
				if (@imagesx($img) !== FALSE) {
					$size = filesize($path);
					$width = imagesx($img);
					$height = imagesy($img);
					
					$sqlImage  = "INSERT INTO text_image (text_id, member_id, image_timestamp, image_size, image_name, "
							   . "image_title, image_description, image_gallery, image_width, image_height) ";
					$sqlImage .= "VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%d', %d, %d)";
					
					mysql_query(sprintf($sqlImage, $arr["text_id"], 11727, time(), $size, $name2, "", $desc, "1", 
						$width, $height), $conn) or die(mysql_error());
						
					$id = mysql_insert_id();
					
					imagejpeg($img, "public/userdata/text/fullsize/" . $id . ".jpg", 90);
					
					$oldWidth = imagesx($img);
					$oldHeight = imagesy($img);
					
					$newWidth = 100;
					$newHeight = $oldHeight / $oldWidth * $newWidth;
					
					$newImg = imagecreatetruecolor($newWidth, $newHeight);
					
					imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $oldWidth, $oldHeight);
					
					imagejpeg($newImg, "public/userdata/text/thumbs/" . $id . ".jpg", 90);
					
					imagedestroy($img);
					imagedestroy($newImg);
					
					echo "Success!\n";
				} else {
					echo "Failed!\n";
				}
			}
		}
	}
}
