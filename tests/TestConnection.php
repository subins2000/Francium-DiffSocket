<?php
use WebSocket\Client;
use Fr\Process;

class TestConnection extends PHPUnit_Framework_TestCase{

  private $PR = null;
  
  protected function setUp(){
    // Server
    $this->PR = new Process(Process::getPHPExecutable(), array(
      "arguments" => array(
        __DIR__ . "/start-server.php",
        $GLOBALS["ip"],
        $GLOBALS["port"]
      )
    ));
    $this->PR->start();
    sleep(1);
  }
  
  public function testHelloWorld(){
    $ws = new Client("ws://{$GLOBALS["ip"]}:{$GLOBALS["port"]}/?service=hello");
    $ws->send("Hello World");
    $this->assertEquals("Hello World", $ws->receive());
    $ws->close();
  }
  
  public function testRandomInt(){
    /**
     * Message returned by WebSocket server is string
     */
    $txt = (string) rand(10, 1000);
    $ws = new Client("ws://{$GLOBALS["ip"]}:{$GLOBALS["port"]}/?service=hello");
    $ws->send($txt);
    
    $this->assertEquals($txt, $ws->receive());
    $ws->close();
  }
  
  public function tearDown(){
    $this->PR->stop();
  }
  
}
