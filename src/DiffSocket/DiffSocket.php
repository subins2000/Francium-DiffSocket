<?php 
namespace Fr;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class DiffSocket{

  /**
   * Default Config
   */
  public $default_config = array(
    "server" => array(
      "host" => "127.0.0.1",
      "port" => "9000"
    )
  );

  /**
   * The conifg storing array
   */
  private $config = array();
  
  /**
   * Service name + (class name, class path)
   */
  private $servers = array();

  /**
   * Init
   */
  public function __construct($config = array()){
    $this->config = array_replace_recursive($this->default_config, $config);
  }

  /**
   * Run WS server
   */
  public function run(){
    if(count($this->servers) !== 0){
      /**
       * 
       */
      \Fr\DiffSocket\Server::$servers = $this->servers;
      $this->startServer();
    }
  }
  
  /**
   * $path - Path to class file
   * $name - Class Name
   * $service - The $_GET param to access the service
   */
  public function addServer($service, $name, $path){
    $this->servers[$service] = array($name, $path);
  }

  public function startServer(){
    $ip = $this->config['server']['host'];
    $port = $this->config['server']['port'];

    $server = IoServer::factory(
      new HttpServer(
        new WsServer(
          new \Fr\DiffSocket\Server()
        )
      ),
      $port,
      $ip
    );
    $server->run();
  }
}
