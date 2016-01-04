<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class BaseServer implements MessageComponentInterface {
  private $servers = array(
    "text-chat" => "TextChat",
    "voice-chat" => "VoiceChat",
    "advanced-chat" => "AdvancedChat",
    "pi" => "Pi"
  );
  private $obj = array();
  public $clients = array();
	
	public function onOpen(ConnectionInterface $conn) {
    $this->getService($conn);
    
    if(isset($_GET['service']) && isset($this->servers[$_GET['service']])){
      if(!isset($this->obj[$_GET['service']])){
        require_once __DIR__ . "/servers/class.{$_GET['service']}.php";
        $className = $this->servers[$_GET['service']] . "Server";
        $this->obj[$_GET['service']] = new $className;
      }
      $this->obj[$_GET['service']]->onOpen($conn);
      
      $this->clients[$conn->resourceId] = $conn;
    }else{
      $conn->close();
      return false;
    }
	}

	public function onMessage(ConnectionInterface $conn, $data) {
		$this->getService($conn);
    return isset($this->obj[$_GET['service']]) ? $this->obj[$_GET['service']]->onMessage($conn, $data) : "";
	}

	public function onClose(ConnectionInterface $conn) {
		$this->getService($conn);
    
    if(isset($this->clients[$conn->resourceId])){
			unset($this->clients[$conn->resourceId]);
		}
    
    if(isset($_GET['service'])){
      return isset($this->obj[$_GET['service']]) ? $this->obj[$_GET['service']]->onClose($conn) : "";
    }
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		$this->getService($conn);
    
    if(isset($this->clients[$conn->resourceId])){
			unset($this->clients[$conn->resourceId]);
		}
    
    return isset($this->obj[$_GET['service']]) ? $this->obj[$_GET['service']]->onError($conn, $e) : "";
	}
  
  public function getService(ConnectionInterface $conn){
    $querystring = $conn->WebSocket->request->getQuery();
    $_GET['service'] = explode("=", $querystring);
    
    if(isset($_GET['service'][1])){
      $_GET['service'] = $_GET['service'][1];
    }else{
      unset($_GET['service']);
    }
  }
}
?>
