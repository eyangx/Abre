<?php

	/*
	* Copyright (C) 2016-2018 Abre.io Inc.
	*
	* This program is free software: you can redistribute it and/or modify
    * it under the terms of the Affero General Public License version 3
    * as published by the Free Software Foundation.
	*
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU Affero General Public License for more details.
	*
    * You should have received a copy of the Affero General Public License
    * version 3 along with this program.  If not, see https://www.gnu.org/licenses/agpl-3.0.en.html.
    */

	//Required configuration files
	require_once(dirname(__FILE__) . '/../../core/abre_verification.php');
	require_once(dirname(__FILE__) . '/../../core/abre_functions.php');

	$cloudsetting=constant("USE_GOOGLE_CLOUD");
	if ($cloudsetting=="true") 
		require(dirname(__FILE__). '/../../vendor/autoload.php');
	use Google\Cloud\Storage\StorageClient;
	

	//Check for feed image
	if($image != ""){

		$storage = new StorageClient([
			'projectId' => constant("GC_PROJECT")
		]);	
		$bucket = $storage->bucket(constant("GC_BUCKET"));	
			
		//Determine if Custom or Feed Post
		if ($type=="custom"){
			
			//$filename = $portal_path_root . "/../$portal_private_root/stream/cache/images/" .$image;
			$cloud_file = "private_html/stream/cache/images/$image";
			if ($bucket->object($cloud_file)->exists()){

				$fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
				$image = $portal_root."/modules/stream/stream_serve_image.php?file=$cloud_file&ext=$fileExtension";
			}
			else
			{
				$image = "";
			}
		}
		else
		{
			//Make sure image is over http or https
			$url = parse_url($image);
			if($url['scheme'] == 'https' xor $url['scheme'] == 'http'){
	
				//Get the name and sanatize the file name
				$fileExtension = pathinfo($image, PATHINFO_EXTENSION);
				$fileExtension = substr($fileExtension, 0, 3);
				if($fileExtension == "jpe"){ $fileExtension="jpeg"; }
				$imagefile = $date."_rss.$fileExtension";
				//$filename = $portal_path_root . "/../$portal_private_root/stream/cache/images/$imagefile";

				$cloud_file = "private_html/stream/cache/images/$imagefile";

				//If it already saved, read from local server
				if ($bucket->object($cloud_file)->exists()){

					$image = $portal_root."/modules/stream/stream_serve_image.php?file=$cloud_file&ext=$fileExtension";

					$info = $bucket->object($cloud_file)->info();
					if ($info['size'] < 1000) {
						$image = "";						
					}
				}else{
					//Check for 404 and 403 errors
					$file_headers = @get_headers($image);
					if((!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') or (!$file_headers || $file_headers[0] == 'HTTP/1.1 403 Forbidden')
					or (!$file_headers || $file_headers[0] == 'HTTP/1.0 400 Bad Request') or (!$file_headers || $file_headers[0] == 'HTTP/1.1 503 Service Unavailable')){
					    $image = "";
					}else{
						//Make sure file is an image
						if(@exif_imagetype($image)){
							$remote_file = $image;

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $remote_file);
							curl_setopt($ch, CURLOPT_HEADER, 0);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
							$picture = curl_exec($ch);
							$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
							curl_close($ch);

							$options = [
								'resumable' => true,
								'name' => $cloud_file,
								'metadata' => [
									'contentLanguage' => 'en',
									'contentType' => $contentType
								]
							];

							$object = $bucket->upload(
								$picture,
								$options
							);

							//Resize image
							//ResizeImage($local_file, "1000", "90");
													
						}else{
							$image = "";
						}
					}
				}
			}
			
		}
	}

?>