# Tars-server documentation



Package name: phptars / tar server

Tars is the underlying dependency of PHP server.



## How to use

Tars-server uses composer for package management. Developers only need to install composer according to the corresponding version.



For specific usage, please refer to HTTP server, timer server and TCP server under the corresponding PHP / examples.



## Frame description

Tars-server is based on the bottom layer of the swoole network transceiver. The framework mainly includes the following directories:

* CMD: responsible for the implementation of the start and stop commands of the framework, and now supports the start, stop and restart commands

* Core: core implementation of framework

* Protocol: responsible for protocol processing



CMD layer

For the CMD layer, it now contains the following files:

1. Command.php:

Responsible for specifying the configuration file and start command when the service starts



2. CommandBase

It specifies the implementation necessary for a command. All such as start are subclasses of commandbase. Getprocess method is provided to get the currently started service process.



3. Restart

Restart the command, only call the start after the call stops



4. Start

When you start the command, you will first parse the configuration issued by the platform, and then import the services.php file necessary for the business.

Next, monitor whether the process has been started to avoid repeated start-up;

Finally, the configured and predefined swooletable will be passed to the server for service initialization and startup.




5. Stop

The current service stop mode is violent. It will pull out all processes according to the service name, and then kill them. In the future, reload will be introduced to reload the service code.




### Core layer

The core layer is mainly composed of event, server, request and response.



1. Server.php

Be responsible for the initialization of services before startup, including:

* Determine whether it is TCP or HTTP type, register the corresponding callback and start the corresponding server

* Judge if it is timer, it will start the timer scan for the corresponding directory

* Pass through the configuration of swoole

* Register generic callback functions

* Pass the swooletable of the server

* Specify the boot file for the entire framework and force require

* Specify the protocol processing mode of the framework, tar or http



After starting the service, you will first enter onmaster start:

* Name of the write process

* Write PID to file

* Initiate service escalation



onManagerStart:

* Rename process



Next is onworkerstart:

* If it is a TCP type, you need to first convert the comments in the interface to PHP data, which is convenient to handle when routing

* If it is of HTTP type, you need to specify the corresponding namespacename

* Set the name of the corresponding worker

* If it is a timer, you need to start the corresponding timer

* When workerid = 0 (it is guaranteed to trigger only once), submit the service's report task to task




Ontask: submit the appName servername servantname of the service.



You need to pay attention to onReceive and onrequest callbacks respectively.



For the server of TCP, pay attention to onReceive:

* Initialize the request object, pass the SW object into the super global variable $_server

* Set the protocol to tarsprotocol

* Deal with the protocol and return the package

* Clear global variables

For HTTP servers, focus on onrequest:

* Processing cookie, get, post request parameters

* Initialize the request object, pass the SW object into the super global variable $_server

* Deal with the protocol and return the package

* Clear global variables




2. Event.php

OnReceive method:

* The request of TCP protocol will first enter the route method of tarsprotocol for routing

* After routing, make the actual function call

* Pack back

* Send back package




Onrequest method:

* Provide a default probe interface

* Perform basic routing protocol analysis

* Call the corresponding controller method

* Send back package



3. Request.php

Store some necessary request data;

Set and remove global variables



4. Response.php

Responsible for some work of returning package




### Service startup process

The start of the whole service is initiated by start under CMD,

After that, we call the creation of the Server object.

Then, initialize swoole in turn,

After starting the service, you only need to process the onReceive or onrequest listening




## Framework dependency

The framework relies on the following packages:

* Phptars / tar client: making calls to the tar service

* Phptars / tar report: responsible for reporting the running status of the service itself

* Phptars / tar config: responsible for pulling configuration uploaded by the platform



Changelog

### v0.6.0(2020-07-07)
- Support json version
- Support tars gateway

### v0.5.0(2020-09-08)


### v0.4.0(2019-07-16)

-Support for protobuf



### v0.3.1(2019-06-21)

-Support for multiple servants

-Using swoole addListener as the underlying support

-Support one service to deploy multiple objs, using the tars or HTTP protocol respectively

-Adjust the format of services.php to return a two-dimensional array with objname as the key.

-Protocolname, servertype, istimer are not read from the private template, they need to be specified in services.php

-Fix support for websocket by multi servant



### v0.2.4(2019-03-20)

-Format code according to PSR rules

-Fix bugs in code

-Support custom master cache

-Open access to swoole objects
