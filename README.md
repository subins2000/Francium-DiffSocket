# DiffSocket

[![Build Status](https://travis-ci.org/subins2000/Francium-DiffSocket.svg?branch=master)](https://travis-ci.org/subins2000/Francium-DiffSocket)

A PHP Library for serving Multiple WebSocket Services through a **single port**.

Normally, if you want to run multiple services, you would have to run Web Socket server in different ports. With DiffSocket, you can use a single port for different services.

## Installation

```bash
composer require francium/diffsocket
```

[Tutorial & Documentation](http://subinsb.com/francium-diffsocket)

## Why don't I use different ports for different services ?

Some hosting providers don't allow you to run services on multiple ports, especially on **Free plans**. An example is [OpenShift](http://openshift.redhat.com).

I have created DiffSocket, because my Web Socket server is hosted in OpenShift and would like to use multiple services on a single WebSocket port.

## Example

These different services are provided through a single WebSocket port (ws-subins.rhcloud.com:8000) :

* [Finding Value Of Pi](http://demos.subinsb.com/pi/)
* [Advanced Live Group Chat With PHP, jQuery & WebSocket](http://demos.subinsb.com/php/advanced-chat-websocket/)
* [Live Group Chat With PHP, jQuery & WebSocket](http://demos.subinsb.com/php/websocketChat)
* [Online Chess Game](https://lobby.subinsb.com/apps/chess)
