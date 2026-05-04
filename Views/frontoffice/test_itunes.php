<?php
$d = json_decode(file_get_contents('https://itunes.apple.com/search?term=ratatouille&entity=movie'), true);
echo $d['results'][0]['artworkUrl100'];
echo "\n";
echo $d['results'][0]['previewUrl'];
