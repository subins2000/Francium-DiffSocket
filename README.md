# DiffSocket

[![Build Status](https://travis-ci.org/subins2000/Francium-DiffSocket.svg?branch=master)](https://travis-ci.org/subins2000/Francium-DiffSocket)

A PHP Library for serving Multiple WebSocket Services through a **single port**.

Normally, if you want to run multiple services, you would have to run Web Socket server in different ports. With DiffSocket, you can use a single port for different services.

## Installation

```bash
composer require francium/diffsocket
```

[Tutorial](http://subinsb.com/run-multiple-websocket-services-on-same-port)

## Why don't I use different ports for different services ?

Some hosting providers don't allow you to run services on multiple ports, especially on **Free plans**. An example is [OpenShift](http://openshift.redhat.com).

I have created DiffSocket, because my Web Socket server is hosted in OpenShift and would like to use multiple services on a single WebSocket port.

## Demos

These different services are provided through a single WebSocket port (ws-subins.rhcloud.com:8000) :

* [Finding Value Of Pi](http://demos.subinsb.com/pi/)
* [Advanced Live Group Chat With PHP, jQuery & WebSocket](http://demos.subinsb.com/php/advanced-chat-websocket/)
* [Live Group Chat With PHP, jQuery & WebSocket](http://demos.subinsb.com/php/websocketChat)
* [Online Chess Game](https://lobby.subinsb.com/apps/chess)

## Usage

### Server

DiffSocket uses [Ratchet](https://github.com/cboden/ratchet) for the WebSocket server. You should learn Ratchet to create services.

* Configure server :
  ```php
  <?php
  $DS = new Fr\DiffSocket(array(
    "server" => array(
      "host" => "127.0.0.1",
      "port" => "8000"
    )
  ));
```

* To add a new service, create a class under namespace `Fr\DiffSocket\Service`
  
  SayHello.php
  ```php
  namespace Fr\DiffSocket\Service;
  
  use Ratchet\MessageComponentInterface;
  use Ratchet\ConnectionInterface;
  
  class SayHello implements MessageComponentInterface {
    
    public function onOpen(ConnectionInterface $conn){
      echo "New Connection - " . $conn->resourceId;
    }
    
    public function onClose(ConnectionInterface $conn){}
    public function onError(ConnectionInterface $conn, $error){}
    
    public function onMessage(ConnectionInterface $conn, $message){
      $conn->send("Hello");
    }
    
  }
  ```
  Then, you should register the service to DiffSocket by :
  ```php
  $DS->addService("say-hello", "path/to/SayHello.php");
  ```
* Run Server
  ```php
  $DS->run();
  ```
  
You may also add services as an array when the object is made. Like so :

```php
$DS = new Fr\DiffSocket(array(
  "server" => array(
    "host" => "127.0.0.1",
    "port" => "8000"
  ),
  "services" => array(
    "say-hello" => __DIR__ . "/services/SayHello.php",
    "chat" => __DIR__ . "/services/Chat.php",
    "game" => __DIR__ . "/services/GameServer.php"
  )
));
```

### Client

You should pass the service you need to use in the URL itself :
```html
ws://ws.example.com/?service=say-hello
```

If `service` GET paramater is not passed, then the WebSocket connection won't be established.
