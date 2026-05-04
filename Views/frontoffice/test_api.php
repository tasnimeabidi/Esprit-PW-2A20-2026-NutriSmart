<?php
function getiTunesMovie($title) {
    $url = "https://itunes.apple.com/search?term=" . urlencode($title) . "&entity=movie&limit=1";
    $json = file_get_contents($url);
    $data = json_decode($json, true);
    if (!empty($data['results'])) {
        $img = str_replace('100x100bb', '600x600bb', $data['results'][0]['artworkUrl100']);
        $video = $data['results'][0]['previewUrl'];
        return ['p' => $img, 'v' => $video];
    }
    return false;
}
print_r(getiTunesMovie("The Magic Pill"));
print_r(getiTunesMovie("Super Size Me"));
