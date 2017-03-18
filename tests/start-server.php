<?php
require_once __DIR__ . "/../vendor/autoload.php";

$ds = new Fr\DiffSocket(array(
  "server" => array(
    "host" => $argv[1],
    "port" => $argv[2]
  ),
  "services" => array(
    "hello" => __DIR__ . "/services/HelloWorld.php"
  )
));
$ds->run();
