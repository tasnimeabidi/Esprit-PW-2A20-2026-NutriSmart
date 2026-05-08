<?php
$ids = ['NgsQ8mVkN8w', 'iSpglxHTJVM', 'DZb-35oV_7E', 'aCUbvOtcCVg', 'oC0j7FkKT1A', 'Jf44vLudiZ0', 'qKqj85oo2wI', 'Y647tNm8nTI', 'P4bW7Xq6aIk', 'nV04zyfLyN4', '5eKYyD14d_0', '6uaWekLrilY', 'Gv3vEXy_EwU'];
foreach ($ids as $id) {
    $headers = @get_headers("http://img.youtube.com/vi/$id/0.jpg");
    if($headers && strpos($headers[0], '200') !== false) {
        echo "$id OK\n";
    } else {
        echo "$id BROKEN\n";
    }
}
