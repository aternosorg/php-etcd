<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// source: https://github.com/etcd-io/etcd/blob/61354ff8ede7dde7839e0df987f37cda931fd740/etcdserver/etcdserverpb/rpc.proto
// modified: removed unnecessary imports and google.api.http options from all requests, added php namespace options
namespace Aternos\Etcd\Proto\Etcdserverpb;

/**
 */
class KVClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * Range gets the keys in the range from the key-value store.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\RangeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\RangeResponse>
     */
    public function Range(\Aternos\Etcd\Proto\Etcdserverpb\RangeRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.KV/Range',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\RangeResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Put puts the given key into the key-value store.
     * A put request increments the revision of the key-value store
     * and generates one event in the event history.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\PutRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\PutResponse>
     */
    public function Put(\Aternos\Etcd\Proto\Etcdserverpb\PutRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.KV/Put',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\PutResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DeleteRange deletes the given range from the key-value store.
     * A delete request increments the revision of the key-value store
     * and generates a delete event in the event history for every deleted key.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\DeleteRangeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\DeleteRangeResponse>
     */
    public function DeleteRange(\Aternos\Etcd\Proto\Etcdserverpb\DeleteRangeRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.KV/DeleteRange',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\DeleteRangeResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Txn processes multiple requests in a single transaction.
     * A txn request increments the revision of the key-value store
     * and generates events with the same revision for every completed request.
     * It is not allowed to modify the same key several times within one txn.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\TxnRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\TxnResponse>
     */
    public function Txn(\Aternos\Etcd\Proto\Etcdserverpb\TxnRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.KV/Txn',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\TxnResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Compact compacts the event history in the etcd key-value store. The key-value
     * store should be periodically compacted or the event history will continue to grow
     * indefinitely.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\CompactionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\CompactionResponse>
     */
    public function Compact(\Aternos\Etcd\Proto\Etcdserverpb\CompactionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.KV/Compact',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\CompactionResponse', 'decode'],
        $metadata, $options);
    }

}
