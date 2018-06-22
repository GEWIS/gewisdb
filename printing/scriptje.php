<?php

$url = 'https://database.gewis.nl/member/%s/print';
$dbh = new PDO('pgsql:dbname=gewisdb;host=postgres', 'postgres', 'gewisdb');

$lastnum = 0;

while (true) {
    $stmt = $dbh->prepare('SELECT lidnr FROM member WHERE lidnr > :lidnr');
    $stmt->bindValue('lidnr', $lastnum);

    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as $row) {
        echo sprintf($url, $row['lidnr']) . PHP_EOL;
        $lastnum = max($lastnum, (int) $row['lidnr']);
    }

    $stmt->closeCursor();

    usleep(10000);
}
