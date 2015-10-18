<?php
	function download_remote_file($file_url)
	{
		$content = file_get_contents($file_url);
		$save_to = $_SERVER['DOCUMENT_ROOT']."/pic/".time().".jpg";
		file_put_contents($save_to, $content);
		return $save_to;
	}

