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
$client->putIf("key", "newValue", "valueToCompareWith");
$client->deleteIf("key", "valueToCompareWith");

// complex transaction example
$leaseId = $client->getLeaseID(10);
$putOp = $client->getPutOperation('key', 'someValueToPutOnSuccess', $leaseId);
$getOp = $client->getGetOperation('key');
// following compare checks for key existence
$compare = $client->getCompare('key', '0', \Etcdserverpb\Compare\CompareResult::EQUAL, \Etcdserverpb\Compare\CompareTarget::MOD);
// execute Put operation and return the key we stored, just return the key value if it already exists
$txnResponse = $client->txnRequest([$putOp, $getOp], [$getOp], [$compare]);
$result = $client->getResponses($txnResponse, 'response_range', true);
// $result[0] contains "someValueToPutOnSuccess"
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

### Failover client

- automatically and transparently fails-over in case etcd host fails 
```php
<?php

$clients = [
    new Aternos\Etcd\Client("hostA:2379"),
    new Aternos\Etcd\Client("hostB:2379"),
    new Aternos\Etcd\Client("hostC:2379")
];
$failoverClient = new Aternos\Etcd\FailoverClient($clients);

// set 60 seconds as a hold-off period between another connection attempt to the failing host 
// default is 120 seconds
// failing host is being remembered within FailoverClient object instance 
$failoverClient->setHoldoffTime(60);
$failoverClient->put("key", "value");
$failoverClient->get("key");
```
