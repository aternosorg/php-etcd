<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// source: https://github.com/etcd-io/etcd/blob/61354ff8ede7dde7839e0df987f37cda931fd740/etcdserver/etcdserverpb/rpc.proto
// modified: removed unnecessary imports and google.api.http options from all requests, added php namespace options
namespace Aternos\Etcd\Proto\Etcdserverpb;

/**
 */
class ClusterClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * MemberAdd adds a member into the cluster.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\MemberAddRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\MemberAddResponse>
     */
    public function MemberAdd(\Aternos\Etcd\Proto\Etcdserverpb\MemberAddRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberAdd',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\MemberAddResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * MemberRemove removes an existing member from the cluster.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\MemberRemoveRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\MemberRemoveResponse>
     */
    public function MemberRemove(\Aternos\Etcd\Proto\Etcdserverpb\MemberRemoveRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberRemove',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\MemberRemoveResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * MemberUpdate updates the member configuration.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\MemberUpdateRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\MemberUpdateResponse>
     */
    public function MemberUpdate(\Aternos\Etcd\Proto\Etcdserverpb\MemberUpdateRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberUpdate',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\MemberUpdateResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * MemberList lists all the members in the cluster.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\MemberListRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\MemberListResponse>
     */
    public function MemberList(\Aternos\Etcd\Proto\Etcdserverpb\MemberListRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberList',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\MemberListResponse', 'decode'],
        $metadata, $options);
    }

}
