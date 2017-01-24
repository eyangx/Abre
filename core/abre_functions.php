<?php
	
	/*
	* Copyright 2015 Hamilton City School District	
	* 		
	* This program is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
	* 
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
	* 
    * You should have received a copy of the GNU General Public License
    * along with this program.  If not, see <http://www.gnu.org/licenses/>.
    */
	
	//Include required files
	require_once(dirname(__FILE__) . '/../configuration.php'); 
	
	//Encryption function
	function encrypt($string, $encryption_key)
	{
		$encryption_key=constant("DB_KEY");
		$string = rtrim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $encryption_key, $string, MCRYPT_MODE_ECB)));
		return $string;
	}
	
	//Decryption function
	function decrypt($string, $encryption_key)
	{
		$encryption_key=constant("DB_KEY");
		$string = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $encryption_key, base64_decode($string), MCRYPT_MODE_ECB));
		return $string;
	}
	
	//Find user ID in directory module given an email
	function finduserid($email)
	{
		$email=encrypt($email, "");
		$sql = "SELECT *  FROM directory where email='$email'";
		$result = $db->query($sql);
		while($row = $result->fetch_assoc())
		{
			$id=$row["id"];
			return $id;
		}
	}
	
	//Find user ID given an email
	function superadmin()
	{
		include "abre_dbconnect.php";
		$sql = "SELECT * FROM users where email='".$_SESSION['useremail']."' and superadmin=1";
		$result = $db->query($sql);
		while($row = $result->fetch_assoc())
		{
			return true;
		}
	}
	
	//Find user ID given an email
	function finduseridcore($email)
	{
		include "abre_dbconnect.php";
		$sql = "SELECT *  FROM users where email='".$_SESSION['useremail']."'";
		$result = $db->query($sql);
		while($row = $result->fetch_assoc())
		{
			$id=$row["id"];
			return $id;
		}
	}
	
	//Determine the grades that students do not have email access
	function studentaccess()
	{
		$email = $_SESSION['useremail'];
		if(preg_replace('/[^0-9]+/', '', $email))
		{
			$gradyear = intval(preg_replace('/[^0-9]+/', '', $email), 10);
			$currentyear = date("y");
			$difference=$gradyear-$currentyear;
			if($difference<6){
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	
	//Query the database
	function databasequery($query)
	{
		include "abre_dbconnect.php";
		$result = $db->query($query);
		$rowarray = array();
		while($row = $result->fetch_assoc())
		{
			array_push($rowarray, $row);	
		}
		return $rowarray;
		$db->close();
	}
	
	//Insert into the database
	function databaseexecute($query)
	{
		include "abre_dbconnect.php";
		$stmt = $db->stmt_init();
		$stmt->prepare($query);
		$stmt->execute();
		$newcommentid = $stmt->insert_id;
		$stmt->close();
		return $newcommentid;
		$db->close();
	}
	
	//Save Screenshot to server
	function savescreenshot($website, $filename)
	{
		//Get Image and Use Google Page Speed API
		$website = $website;
		$api = "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$website&screenshot=true";
		$string = file_get_contents($api);
		$json = json_decode($string, true);
		
		//Get Data from JSON
		$data=$json['screenshot']['data'];
		
		//Replace characters for correct decode
		$data=str_replace("_","/",$data);
		$data=str_replace("-","+",$data);
		
		//Decode base64
		$data = base64_decode($data);
		
		//Save image to server
		$im = imagecreatefromstring($data);
		
		if (!file_exists("../../../$portal_private_root/guide")) {
			mkdir("../../../$portal_private_root/guide", 0777, true);
		}
		
		imagejpeg($im, "../../../$portal_private_root/guide/$filename");
	}
	
	//Retrieve Site Title
	function sitesettings($value)
	{
		include "abre_dbconnect.php";
		
		if(!$result = $db->query("SELECT * FROM settings"))
		{
	  		$sql = "CREATE TABLE `settings` (`id` int(11) NOT NULL,`options` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
	  		$sql .= "INSERT INTO `settings` (`id`, `options`) VALUES (1, '');";
	  		$sql .= "ALTER TABLE `settings` ADD PRIMARY KEY (`id`);";
	  		$sql .= "ALTER TABLE `settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";
	  		if ($db->multi_query($sql) === TRUE) { }
		}		

		$result_count = mysqli_num_rows($result);
		while($row = $result->fetch_assoc())
		{
			$options = $row["options"];
			if($options!="")
			{
				$options = json_decode($options);
				$valuereturn = $options->$value;
			}
			else
			{
				$valuereturn="";
			}
			if($value=="sitetitle" && $valuereturn==""){ $valuereturn="Abre"; }
			if($value=="sitecolor" && $valuereturn==""){ $valuereturn="#2B2E4A"; }
			if($value=="sitedescription" && $valuereturn==""){ $valuereturn="Abre Open Platform for Education"; }
			if($value=="sitelogintext" && $valuereturn==""){ $valuereturn="Open Platform for Education"; }
			if($value=="siteanalytics" && $valuereturn==""){ $valuereturn=""; }
			if($value=="siteadminemail" && $valuereturn==""){ $valuereturn=""; }
			if($value=="sitevendorlinkurl" && $valuereturn==""){ $valuereturn=""; }
			if($value=="sitevendorlinkidentifier" && $valuereturn==""){ $valuereturn=""; }
			if($value=="sitevendorlinkkey" && $valuereturn==""){ $valuereturn=""; }
			if($value=="certicabaseurl" && $valuereturn==""){ $valuereturn=""; }
			if($value=="certicaaccesskey" && $valuereturn==""){ $valuereturn=""; }
			if($value=="studentdomain" && $valuereturn==""){ $valuereturn=""; }
			if($value=="studentdomainrequired" && $valuereturn==""){ $valuereturn=""; }
			if($value=="sitelogo" && $valuereturn!="")
			{ 
				if($valuereturn!='/core/images/abre_siteicon.png')
				{
					$valuereturn="/content/$valuereturn";
				}
				else
				{
					$valuereturn="/core/images/abre_siteicon.png";
				}
			}
			if($value=="sitelogo" && $valuereturn==""){ $valuereturn="/core/images/abre_siteicon.png"; }
			return $valuereturn;
		}
		$db->close();
	}
	
	function linkify($value, $protocols = array('http', 'mail'), array $attributes = array())
    {
        // Link attributes
        $attr = '';
        foreach ($attributes as $key => $val) {
            $attr = ' ' . $key . '="' . htmlentities($val) . '"';
        }
        
        $links = array();
        
        // Extract existing links and tags
        $value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) { return '<' . array_push($links, $match[1]) . '>'; }, $value);
        
        // Extract text links for each protocol
        foreach ((array)$protocols as $protocol) {
            switch ($protocol) {
                case 'http':
                case 'https':   $value = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { if ($match[1]) $protocol = $match[1]; $link = $match[2] ?: $match[3]; return '<' . array_push($links, "<a $attr href=\"$protocol://$link\" target=\"_blank\" style='color: ".sitesettings("sitecolor")."'>$link</a>") . '>'; }, $value); break;
                case 'mail':    $value = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\" target=\"_blank\" style='color: ".sitesettings("sitecolor")."'>{$match[1]}</a>") . '>'; }, $value); break;
                case 'twitter': $value = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1]  . "\" target=\"_blank\" style='color: ".sitesettings("sitecolor")."'>{$match[0]}</a>") . '>'; }, $value); break;
                default:        $value = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\" target=\"_blank\" style='color: ".sitesettings("sitecolor")."'>{$match[1]}</a>") . '>'; }, $value); break;
            }
        }
        
        // Insert all link
        return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) { return $links[$match[1] - 1]; }, $value);
    }
    
	//Insert into the database
	function vendorLinkGet($call)
	{
		$VendorLinkURL=sitesettings("sitevendorlinkurl");
		$vendorIdentifier=sitesettings("sitevendorlinkidentifier");
		$vendorKey=sitesettings("sitevendorlinkkey");
		$userID = "";
		$requestDate = gmdate('D, d M Y H:i:s').' GMT';
		$userName = $vendorIdentifier."|".$userID."|".$requestDate;
		$password = $vendorIdentifier.$userID.$requestDate.$vendorKey;
		$hmacData = $vendorIdentifier.$userID.$requestDate.$vendorKey;
		$hmacSignature = base64_encode(pack("H*", sha1($hmacData)));
		$vlauthheader = $vendorIdentifier."||".$hmacSignature;
		$url = $call;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('VL-Authorization: '.$vlauthheader, 'Date: '.$requestDate));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$json = json_decode($result,true);
		return $json;
	}
	
?>