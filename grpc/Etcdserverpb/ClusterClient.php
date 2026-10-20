<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// source: https://github.com/etcd-io/etcd/blob/61354ff8ede7dde7839e0df987f37cda931fd740/etcdserver/etcdserverpb/rpc.proto
// modified: removed unnecessary imports and google.api.http options from all requests
namespace Etcdserverpb;

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
     * @param \Etcdserverpb\MemberAddRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Etcdserverpb\MemberAddResponse>
     */
    public function MemberAdd(\Etcdserverpb\MemberAddRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberAdd',
        $argument,
        ['\Etcdserverpb\MemberAddResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * MemberRemove removes an existing member from the cluster.
     * @param \Etcdserverpb\MemberRemoveRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Etcdserverpb\MemberRemoveResponse>
     */
    public function MemberRemove(\Etcdserverpb\MemberRemoveRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberRemove',
        $argument,
        ['\Etcdserverpb\MemberRemoveResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * MemberUpdate updates the member configuration.
     * @param \Etcdserverpb\MemberUpdateRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Etcdserverpb\MemberUpdateResponse>
     */
    public function MemberUpdate(\Etcdserverpb\MemberUpdateRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberUpdate',
        $argument,
        ['\Etcdserverpb\MemberUpdateResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * MemberList lists all the members in the cluster.
     * @param \Etcdserverpb\MemberListRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Etcdserverpb\MemberListResponse>
     */
    public function MemberList(\Etcdserverpb\MemberListRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Cluster/MemberList',
        $argument,
        ['\Etcdserverpb\MemberListResponse', 'decode'],
        $metadata, $options);
    }

}
