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
    
    if(isset($data['data']) && count($data['data']) != 0){
      $type = $data['type'];
      $user = isset($this->users[$id]) ? $this->users[$id] : false;
      
      if($type == "register"){
        $name = htmlspecialchars($data['data']['name']);
        if(array_search($name, $this->users) === false){
          $this->users[$id] = $name;
          $this->send($conn, "register", "success");
          
          $this->fetchMessages($conn);
          $this->checkOnliners($conn);
        }else{
          $this->send($conn, "register", "taken");
        }
      }elseif($type == "send" && isset($data['data']['type']) && $user !== false){
        $msg = htmlspecialchars($data['data']['msg']);
        if(isset($data['data']['base64'])){
          /**
           * The base64 value of Audio Or Image
           */
          $base64 = $data['data']['base64'];
        }else{
          $base64 = null;
        }
        
        if($data['data']['type'] == "text"){
          $sql = $this->dbh->prepare("SELECT `id`, `user`, `msg`, `type` FROM `wsAdvancedChat` ORDER BY `id` DESC LIMIT 1");
          $sql->execute();
          $lastMsg = $sql->fetch(PDO::FETCH_ASSOC);
          
          if($lastMsg['user'] == $user && $lastMsg['type'] == "text"){
            // Append message
            $msg = $lastMsg['msg'] . "<br/>" . $msg;
            
            $sql = $this->dbh->prepare("UPDATE `wsAdvancedChat` SET `msg` = ?, `posted` = NOW() WHERE `id` = ?");
            $sql->execute(array($msg, $lastMsg['id']));
            
            $id = $this->dbh->query("SELECT `id` FROM `wsAdvancedChat` ORDER BY `id` DESC LIMIT 1")->fetchColumn();
            $return = array(
              "id" => $id,
              "name" => $user,
              "type" => "text",
              "msg" => $msg,
              "posted" => date("Y-m-d H:i:s"),
              "append" => true
            );
          }else{
            $sql = $this->dbh->prepare("INSERT INTO `wsAdvancedChat` (`user`, `msg`, `type`, `posted`) VALUES(?, ?, ?, NOW())");
            $sql->execute(array($user, $msg, "text"));
            
            $id = $this->dbh->query("SELECT `id` FROM `wsAdvancedChat` ORDER BY `id` DESC LIMIT 1")->fetchColumn();
            $return = array(
              "id" => $id,
              "name" => $user,
              "type" => "text",
              "msg" => $msg,
              "posted" => date("Y-m-d H:i:s")
            );
          }
        }elseif($data['data']['type'] == "img"){
          $uploaded_file_name = $data['data']['file_name'];
          $sql = $this->dbh->prepare("INSERT INTO `wsAdvancedChat` (`user`, `msg`, `type`, `file_name`, `posted`) VALUES(?, ?, ?, ?, NOW())");
          $sql->execute(array($user, $msg, "img", $uploaded_file_name));
          
          $id = $this->dbh->query("SELECT `id` FROM `wsAdvancedChat` ORDER BY `id` DESC LIMIT 1")->fetchColumn();
          
          $return = array(
            "id" => $id,
            "name" => $user,
            "type" => "img",
            "msg" => $msg,
            "file_name" => $uploaded_file_name,
            "posted" => date("Y-m-d H:i:s")
          );
        }elseif($data['data']['type'] == "audio"){
          $uploaded_file_name = $data['data']['file_name'];
          $sql = $this->dbh->prepare("INSERT INTO `wsAdvancedChat` (`user`, `msg`, `type`, `file_name`, `posted`) VALUES(?, ?, ?, ?, NOW())");
          $sql->execute(array($user, $msg, "audio", $uploaded_file_name));
          
          $id = $this->dbh->query("SELECT `id` FROM `wsAdvancedChat` ORDER BY `id` DESC LIMIT 1")->fetchColumn();
          $return = array(
            "id" => $id,
            "name" => $user,
            "type" => "audio",
            "msg" => $msg,
            "file_name" => $uploaded_file_name,
            "posted" => date("Y-m-d H:i:s")
          );
        }
        
        foreach($this->clients as $client){
          $this->send($client, "single", $return);
        }
      }elseif($type == "onliners"){
        $this->checkOnliners($conn);
      }elseif($type == "fetch"){
        /**
         * Fetch previous messages
         */
        $this->fetchMessages($conn, $data['data']['id']);
      }
    }
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
