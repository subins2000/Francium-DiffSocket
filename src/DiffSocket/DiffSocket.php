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
    ),
    "services" => array(),
    "debug" => false
  );

  /**
   * The conifg storing array
   */
  private $config = array();

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
    if(!empty($this->config["services"])){
      /**
       * Start the server
       */
      \Fr\DiffSocket\Server::$services = $this->config["services"];
      $this->startServer();
    }
  }
  
  /**
   * @param str $service The $_GET param to access the service
   * @param str $path Path to class file
   */
  public function addService($service, $path){
    $this->config["services"][$service] = realpath($path);
  }

  private function startServer(){
    $ip = $this->config['server']['host'];
    $port = $this->config['server']['port'];

    $ws_server = new WsServer(new \Fr\DiffSocket\Server($this->config));
    
    if (isset($this->config['allowed_origins']) && !empty($this->config['allowed_origins'])) {
        $ws_server = new OriginCheck($ws_server);
        $ws_server->allowedOrigins = $this->config['allowed_origins'];
    }
    
    $server = IoServer::factory(
      new HttpServer($ws_server),
      $port,
      $ip
    );
    echo "Server started on $ip:$port\n";
    $server->run();
  }
}
