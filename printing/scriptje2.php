<?php

while ($line = fgets(STDIN)) {
    $line = trim($line);
    $fp = stream_socket_client("tcp://pcgewis1.gewiswg.gewis.nl:3333", $error_number, $error_string);
    if (!$fp) {
        echo "$error_number ($error_string)\n";
    } else {
        fwrite($fp, file_get_contents($line));
    }
    fclose($fp);
}
