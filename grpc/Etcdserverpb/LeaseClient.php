<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// source: https://github.com/etcd-io/etcd/blob/61354ff8ede7dde7839e0df987f37cda931fd740/etcdserver/etcdserverpb/rpc.proto
// modified: removed unnecessary imports and google.api.http options from all requests, added php namespace options
namespace Aternos\Etcd\Proto\Etcdserverpb;

/**
 */
class LeaseClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * LeaseGrant creates a lease which expires if the server does not receive a keepAlive
     * within a given time to live period. All keys attached to the lease will be expired and
     * deleted if the lease expires. Each expired key generates a delete event in the event history.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\LeaseGrantRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\LeaseGrantResponse>
     */
    public function LeaseGrant(\Aternos\Etcd\Proto\Etcdserverpb\LeaseGrantRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Lease/LeaseGrant',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\LeaseGrantResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * LeaseRevoke revokes a lease. All keys attached to the lease will expire and be deleted.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\LeaseRevokeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\LeaseRevokeResponse>
     */
    public function LeaseRevoke(\Aternos\Etcd\Proto\Etcdserverpb\LeaseRevokeRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Lease/LeaseRevoke',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\LeaseRevokeResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * LeaseKeepAlive keeps the lease alive by streaming keep alive requests from the client
     * to the server and streaming keep alive responses from the server to the client.
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\BidiStreamingCall
     */
    public function LeaseKeepAlive($metadata = [], $options = []) {
        return $this->_bidiRequest('/etcdserverpb.Lease/LeaseKeepAlive',
        ['\Aternos\Etcd\Proto\Etcdserverpb\LeaseKeepAliveResponse','decode'],
        $metadata, $options);
    }

    /**
     * LeaseTimeToLive retrieves lease information.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\LeaseTimeToLiveRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\LeaseTimeToLiveResponse>
     */
    public function LeaseTimeToLive(\Aternos\Etcd\Proto\Etcdserverpb\LeaseTimeToLiveRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Lease/LeaseTimeToLive',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\LeaseTimeToLiveResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * LeaseLeases lists all existing leases.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\LeaseLeasesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\LeaseLeasesResponse>
     */
    public function LeaseLeases(\Aternos\Etcd\Proto\Etcdserverpb\LeaseLeasesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Lease/LeaseLeases',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\LeaseLeasesResponse', 'decode'],
        $metadata, $options);
    }

}
