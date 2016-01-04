<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class TextChatServer implements MessageComponentInterface {
	private $dbh;
	private $users = array();
	
	public function __construct() {
    global $dbh, $docRoot;
    $this->dbh = $dbh;
    $this->root = $docRoot;
  }
	
	public function onOpen(ConnectionInterface $conn) {
    $this->clients[$conn->resourceId] = $conn;
		$this->send($conn, "fetch", $this->fetchMessages());
		$this->checkOnliners($conn);
		echo "New connection! ({$conn->resourceId})\n";
	}

	public function onMessage(ConnectionInterface $conn, $data) {
		$id	= $conn->resourceId;
		$data = json_decode($data, true);
    
		if(isset($data['data']) && count($data['data']) != 0){
			$type = $data['type'];
			$user = isset($this->users[$id]) ? $this->users[$id]['name'] : false;
      
			if($type == "register"){
				$name = htmlspecialchars($data['data']['name']);
        if(array_search($name, array_map(function($element){return $element['name'];}, $this->users)) === false){
				  $this->users[$id] = array(
					  "name" 	=> $name,
  					"seen"	=> time()
				  );
          $this->send($conn, "register", "success");
          $this->checkOnliners($conn);
        }else{
          $this->send($conn, "register", "taken");
        }
			}elseif($type == "send" && $user !== false){
				$msg = htmlspecialchars($data['data']['msg']);
				$sql = $this->dbh->prepare("INSERT INTO `wsMessages` (`name`, `msg`, `posted`) VALUES(?, ?, NOW())");
				$sql->execute(array($user, $msg));
        
				foreach($this->clients as $client) {
					$this->send($client, "single", array("name" => $user, "msg" => $msg, "posted" => date("Y-m-d H:i:s")));
				}
			}elseif($type == "fetch"){
				$this->send($conn, "fetch", $this->fetchMessages());
			}elseif($type == "onliners"){
        $this->checkOnliners($conn);
      }
		}
	}

	public function onClose(ConnectionInterface $conn) {
		if(isset($this->users[$conn->resourceId])){
			unset($this->users[$conn->resourceId]);
		}
		unset($this->clients[$conn->resourceId]);
    $this->checkOnliners($conn);
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		if(isset($this->users[$conn->resourceId])){
			unset($this->users[$conn->resourceId]);
		}
    $this->checkOnliners($conn);
    $conn->close();
	}
	
	/* My custom functions */
	public function fetchMessages(){
		$sql = $this->dbh->prepare("SELECT * FROM `wsMessages`");
    $sql->execute();
		$msgs = $sql->fetchAll();
		return $msgs;
	}
	
	public function checkOnliners(ConnectionInterface $conn){
		date_default_timezone_set("UTC");
		if(isset($this->users[$conn->resourceId])){
			$this->users[$conn->resourceId]['seen'] = time();
		}
		
		$limit_time = strtotime('-10 seconds');
		foreach($this->users as $id => $user){
			$usertime = $user['seen'];
			if($usertime < $limit_time){
				unset($this->users[$id]);
			}
		}
		
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
