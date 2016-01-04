<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

$GLOBALS['allow_user_to_run'] = true;
class PiServer implements MessageComponentInterface {
  protected $clients;
  private $dbh;
  private $users = array();
  
  public function __construct() {
    global $dbh, $docRoot;
    $this->clients = array();
    $this->dbh = $dbh;
    date_default_timezone_set('UTC');
  }
  
  public function onOpen(ConnectionInterface $conn) {
    $this->clients[$conn->resourceId] = $conn;
    $this->checkIfPiEnded($conn);
    echo "New connection! ({$conn->resourceId})\n";
  }

  public function onMessage(ConnectionInterface $conn, $message) {
    $id  = $conn->resourceId;

    if($message == "status"){
      $sql = $this->dbh->query("SELECT `value` FROM `pi` WHERE `key_name` = 'start' OR `key_name` = 'status'");
      $r = $sql->fetchAll(\PDO::FETCH_ASSOC);
      $response = array(
        "start" => $r[0]['value'],
        "status" => explode(",", $r[1]['value'])
      );
      $this->send($conn, "status", $response);
      $this->checkIfPiEnded();
    }else if($message == "pi"){
      if($this->piProcessRunning()){
        $this->send($conn, "pi", "running");
      }else{
        $sql = $this->dbh->query("SELECT `value` FROM `pi` WHERE `key_name` = 'pi'");
        $pi = zlib_decode($sql->fetchColumn());
        $pi = "3." . substr($pi, 1);
        $this->send($conn, "pi", $pi);
      }
    }else if(substr($message, 0, 3) == "run"){
      if($GLOBALS['allow_user_to_run']){
        if($this->piProcessRunning()){
          $this->sendToAll("running_as_per_user_request");
        }else{
          $digits = substr($message, 4);
          if(is_numeric($digits) && $digits > 5 && $digits <= 1000000){
            $this->runPiFindingProcess($digits);
            $this->sendToAll("running_as_per_user_request");
          }else{
            $this->send($conn, "invalid_digits");
          }
        }
      }else{
        $this->send($conn, "not_allowed");
      }
    }
  }

  public function onClose(ConnectionInterface $conn) {
    unset($this->clients[$conn->resourceId]);
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
    $conn->close();
  }
  
  public function piProcessRunning(){
    exec("ps aux | grep 'extra/pi.py';", $out);
    foreach($out as $o){
      if(strpos($o, 'python') !== false){
        return true;
        break;
      }
    }
    return false;
  }
  
  public function runPiFindingProcess($digits = "20000"){
    if(!$this->piProcessRunning()){
      $command = "cd ". __DIR__ ."/../;nohup python extra/pi.py $digits > /dev/null 2>&1 &";
      var_dump($command);
      exec($command);
    }
  }
  
  public function checkIfPiEnded(){
    if(!$this->piProcessRunning()){
      $this->sendToAll("ended");
    }
  }
  
  public function send(ConnectionInterface $client, $type, $data = ""){
    $send = array(
      "type" => $type,
      "data" => $data
    );
    $send = json_encode($send, true);
    $client->send($send);
  }
  
  public function sendToAll($type, $data = ""){
    foreach($this->clients as $client){
      $this->send($client, $type, $data);
    }
  }
  
}
?>
