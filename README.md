# DiffSocket

A PHP Library for serving Multiple WebSocket Services in a single server

[Tutorial & Documentation](http://subinsb.com/francium-diffsocket)

## Why don't I use different ports for different services ?

Some hosting providers don't allow you to run services on multiple ports, especially on **Free plans**. An example is [OpenShift](http://openshift.redhat.com).

I have created DiffSocket, because my WS server is hosted in OpenShift and would like to use multiple services on a single WebSocket port.
