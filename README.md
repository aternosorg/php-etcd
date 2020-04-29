# PHP gRPC client for etcd v3
Full and/or simplified client for [etcd](https://github.com/etcd-io/etcd) v3 using [gRPC](https://github.com/grpc/grpc/).

### About
This library includes the generated gRPC classes from the `.proto` files in the etcd repository 
in the [grpc](grpc) folder. You can use the (more complicated) gRPC classes or the simpler [`Client`](src/Client.php)
class for basic functionality. There are currently only a few basic functions implemented in the 
Client class, so feel free to add more functions when you implement them with the gRPC classes and
create a pull request.

### Installation
The gRPC PHP extension has to be installed to use this library.
See [this](https://github.com/grpc/grpc/tree/master/src/php) for a full explanation.

```bash
sudo apt install php php-dev php-pear
sudo pecl install grpc
```

And add `extension=grpc.so` to the `php.ini` file.

The `protobuf` extension is not necessary, because it's a dependency of this library, which you
can install using

```bash
composer require aternos/etcd
```

### Usage

#### Client class
```php
<?php

$client = new Aternos\Etcd\Client();
$client = new Aternos\Etcd\Client("localhost:2379");
$client = new Aternos\Etcd\Client("localhost:2379", "username", "password");

// currently implemented functions
$client->put("key", "value");
$client->get("key");
$client->delete("key");
$client->putIf("key", "newValue", "valueToCompareWith", "=", Etcdserverpb\Compare\CompareTarget::VALUE);
$client->deleteIf("key", "3", ">", Etcdserverpb\Compare\CompareTarget::VERSION);
```

#### Sharded client
```php
<?php

$clients = [
    new Aternos\Etcd\Client("hostA:2379"),
    new Aternos\Etcd\Client("hostB:2379"),
    new Aternos\Etcd\Client("hostC:2379")
];
$shardedClient = new Aternos\Etcd\ShardedClient($clients);

$shardedClient->put("key", "value");
$shardedClient->get("key");
```
