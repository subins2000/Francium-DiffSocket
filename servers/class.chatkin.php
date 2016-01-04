<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class AdvancedChatServer implements MessageComponentInterface {
  protected $clients;
  private $dbh;
  private $users = array();
  
  public function __construct() {
    global $dbh, $docRoot;
    $this->clients = array();
    $this->dbh = $dbh;
    $this->root = $docRoot;
    date_default_timezone_set('UTC');
  }
  
  public function onOpen(ConnectionInterface $conn) {
    $this->clients[$conn->resourceId] = $conn;
    $this->checkOnliners($conn);
    echo "New connection! ({$conn->resourceId})\n";
  }

  public function onMessage(ConnectionInterface $conn, $data) {
    $id  = $conn->resourceId;
    $data = json_decode($data, true);
    
    
  }

  public function onClose(ConnectionInterface $conn) {
    if(isset($this->users[$conn->resourceId])){
      unset($this->users[$conn->resourceId]);
    }
    $this->checkOnliners($conn);
    unset($this->clients[$conn->resourceId]);
  }

  public function onError(ConnectionInterface $conn, \Exception $e) {
    if(isset($this->users[$conn->resourceId])){
      unset($this->users[$conn->resourceId]);
    }
    $conn->close();
    $this->checkOnliners();
  }
  
  /**
   * My custom functions
   */
  public function fetchMessages(ConnectionInterface $conn, $id = ""){
    if($id == ""){
      $sql = $this->dbh->query("SELECT * FROM `wsAdvancedChat` ORDER BY `id` ASC");
      $msgs = $sql->fetchAll();
      $msgCount = count($msgs);

      if($msgCount > 5){
        $msgs = array_slice($msgs, $msgCount - 5, $msgCount);
      }
    
      foreach($msgs as $msg){
        $return = array(
          "id" => $msg['id'],
          "name" => $msg['user'],
          "type" => $msg['type'],
          "msg" => $msg['msg'],
          "file_name" => $msg['file_name'],
          "posted" => $msg['posted']
        );
        $this->send($conn, "single", $return);
      }
      if($msgCount > 5){
        $this->send($conn, "single", array(
          "type" => "more_messages"
        ));
      }
    }else{
      $sql = $this->dbh->prepare("SELECT * FROM `wsAdvancedChat` WHERE `id` < :id ORDER BY `id` DESC LIMIT 10");
      $sql->bindParam(":id", $id, PDO::PARAM_INT);
      $sql->execute();
      
      $msgs = $sql->fetchAll();
      foreach($msgs as $msg){
        $return = array(
          "id" => $msg['id'],
          "name" => $msg['user'],
          "type" => $msg['type'],
          "msg" => $msg['msg'],
          "posted" => $msg['posted'],
          "earlier_msg" => true
        );
        $this->send($conn, "single", $return);
      }

      sort($msgs);
      $firstID = $msgs[0]['id'];
      if($firstID != "1"){
        $this->send($conn, "single", array(
          "type" => "more_messages"
        ));
      }
    }
  }
  
  public function checkOnliners(ConnectionInterface $conn){    
    /**
     * Send online users to everyone
     */
    $data = $this->users;
    foreach($this->clients as $id => $client) {
      $this->send($client, "onliners", $data);
    }
  }
  
  public function send(ConnectionInterface $client, $type, $data){
    $send = array(
      "type" => $type,
      "data" => $data
    );
    $send = json_encode($send, true);
    $client->send($send);
  }
}
?>
