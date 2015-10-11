<?php
	function download_remote_file_with_fopen($file_url, $save_to)
	{
		$in=    fopen($file_url, "rb");
		$out=   fopen($save_to, "wb");
 
		while ($chunk = fread($in,8192))
		{
			fwrite($out, $chunk, 8192);
		}
 
		fclose($in);
		fclose($out);
	}
