<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// source: https://github.com/etcd-io/etcd/blob/61354ff8ede7dde7839e0df987f37cda931fd740/etcdserver/etcdserverpb/rpc.proto
// modified: removed unnecessary imports and google.api.http options from all requests, added php namespace options
namespace Aternos\Etcd\Proto\Etcdserverpb;

/**
 */
class MaintenanceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * Alarm activates, deactivates, and queries alarms regarding cluster health.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AlarmRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AlarmResponse>
     */
    public function Alarm(\Aternos\Etcd\Proto\Etcdserverpb\AlarmRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Maintenance/Alarm',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AlarmResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Status gets the status of the member.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\StatusRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\StatusResponse>
     */
    public function Status(\Aternos\Etcd\Proto\Etcdserverpb\StatusRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Maintenance/Status',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\StatusResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Defragment defragments a member's backend database to recover storage space.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\DefragmentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\DefragmentResponse>
     */
    public function Defragment(\Aternos\Etcd\Proto\Etcdserverpb\DefragmentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Maintenance/Defragment',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\DefragmentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Hash computes the hash of whole backend keyspace,
     * including key, lease, and other buckets in storage.
     * This is designed for testing ONLY!
     * Do not rely on this in production with ongoing transactions,
     * since Hash operation does not hold MVCC locks.
     * Use "HashKV" API instead for "key" bucket consistency checks.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\HashRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\HashResponse>
     */
    public function Hash(\Aternos\Etcd\Proto\Etcdserverpb\HashRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Maintenance/Hash',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\HashResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * HashKV computes the hash of all MVCC keys up to a given revision.
     * It only iterates "key" bucket in backend storage.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\HashKVRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\HashKVResponse>
     */
    public function HashKV(\Aternos\Etcd\Proto\Etcdserverpb\HashKVRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Maintenance/HashKV',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\HashKVResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Snapshot sends a snapshot of the entire backend from a member over a stream to a client.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\SnapshotRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Snapshot(\Aternos\Etcd\Proto\Etcdserverpb\SnapshotRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/etcdserverpb.Maintenance/Snapshot',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\SnapshotResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * MoveLeader requests current leader node to transfer its leadership to transferee.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\MoveLeaderRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\MoveLeaderResponse>
     */
    public function MoveLeader(\Aternos\Etcd\Proto\Etcdserverpb\MoveLeaderRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Maintenance/MoveLeader',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\MoveLeaderResponse', 'decode'],
        $metadata, $options);
    }

}
