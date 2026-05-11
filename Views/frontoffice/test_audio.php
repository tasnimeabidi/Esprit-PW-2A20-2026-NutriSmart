<?php
$url = "https://en.wikipedia.org/w/api.php?action=query&list=allimages&aimimedir=ascending&aiprop=url&aiformat=mp3&ailimit=5&format=json";
echo file_get_contents($url);
