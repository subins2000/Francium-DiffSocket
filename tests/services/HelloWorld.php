<?php
namespace Fr\DiffSocket\Service;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class HelloWorld implements MessageComponentInterface {

  public function onOpen(ConnectionInterface $conn){
    
  }
  
  public function onClose(ConnectionInterface $conn){
    
  }
  
  public function onError(ConnectionInterface $conn, \Exception $e){
    
  }
  
  public function onMessage(ConnectionInterface $conn, $message){
    $conn->send($message);
  }

}
