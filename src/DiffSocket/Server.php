<?php
namespace Fr\DiffSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Server implements MessageComponentInterface {
  
  /**
   * Service name + (class name, class path)
   */
  public static $services = array();
  
  /**
   * Instances of Services
   */
  private $obj = array();
  
  /**
   * Array of connected clients' ConnectionInterface Object
   */
  public $clients = array();
  
  public $debug = false;
  
  public function __construct($config){
    $this->debug = $config["debug"];
  }
  
  public function onOpen(ConnectionInterface $conn) {
    $service = $this->getService($conn);
    if($service !== null){
      $classFile = self::$services[$service];
      
      if(!isset($this->obj[$service])){
        require_once $classFile;
        
        if($this->debug)
          echo "Loaded $service Class from $classFile\n";
        
        $className = "Fr\\DiffSocket\\Service\\" . self::getClassName($classFile);
        $this->obj[$service] = new $className;
      }
      $this->obj[$service]->onOpen($conn);
      
      $this->clients[$conn->resourceId] = $conn;
      if($this->debug)
        echo "New connection! - " . $conn->resourceId;
      
      return true;
    }else{
      $conn->close();
      return false;
    }
  }

  public function onMessage(ConnectionInterface $conn, $data) {
    $service = $this->getService($conn);
    return $service !== null ? $this->obj[$service]->onMessage($conn, $data) : "";
  }

  public function onClose(ConnectionInterface $conn) {
    $service = $this->getService($conn);
    
    if(isset($this->clients[$conn->resourceId])){
      unset($this->clients[$conn->resourceId]);
    }
    
    if($service !== null){
      return $this->obj[$service]->onClose($conn);
    }
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
    $service = $this->getService($conn);
    
    if(isset($this->clients[$conn->resourceId])){
      unset($this->clients[$conn->resourceId]);
    }
    
    if($service !== null){
      return $this->obj[$service]->onError($conn, $e);
    }
  }
  
  /**
   * Return service if it's valid, else NULL
   */
  public function getService(ConnectionInterface $conn){
    $query = $conn->WebSocket->request->getQuery();
    return isset(self::$services[$query["service"]]) ? $query["service"] : null;
  }
  
  public static function getClassName($file){
    $fp = fopen($file, 'r');
    $class = $buffer = '';
    $i = 0;
    
    if(!$fp)
      return null;

    while (!$class) {
      if (feof($fp)) break;
  
      $buffer .= fread($fp, 512);
      $tokens = token_get_all($buffer);
  
      if (strpos($buffer, '{') === false) continue;
  
      for (;$i<count($tokens);$i++) {
        if ($tokens[$i][0] === T_CLASS) {
          for ($j=$i+1;$j<count($tokens);$j++) {
            if ($tokens[$j] === '{') {
                $class = $tokens[$i+2][1];
            }
          }
        }
      }
    }
    return $class;
  }
  
}
?>
