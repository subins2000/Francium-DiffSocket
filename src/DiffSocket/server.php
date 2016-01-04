<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
  
if(isset($startNow)){
  $ip = getenv("OPENSHIFT_PHP_IP") ?:"192.168.1.2";
  $port = getenv("OPENSHIFT_PHP_PORT") ?:"8000";
  require_once "$docRoot/vendor/autoload.php";
  require_once "$docRoot/class.base.php";
  
  $server = IoServer::factory(
    new HttpServer(
      new WsServer(
        new BaseServer()
      )
    ),
    $port,
    $ip
  );
  $server->run();
}
