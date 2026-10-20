<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// source: https://github.com/etcd-io/etcd/blob/61354ff8ede7dde7839e0df987f37cda931fd740/etcdserver/etcdserverpb/rpc.proto
// modified: removed unnecessary imports and google.api.http options from all requests, added php namespace options
namespace Aternos\Etcd\Proto\Etcdserverpb;

/**
 */
class AuthClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * AuthEnable enables authentication.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthEnableRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthEnableResponse>
     */
    public function AuthEnable(\Aternos\Etcd\Proto\Etcdserverpb\AuthEnableRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/AuthEnable',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthEnableResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * AuthDisable disables authentication.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthDisableRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthDisableResponse>
     */
    public function AuthDisable(\Aternos\Etcd\Proto\Etcdserverpb\AuthDisableRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/AuthDisable',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthDisableResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Authenticate processes an authenticate request.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthenticateRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthenticateResponse>
     */
    public function Authenticate(\Aternos\Etcd\Proto\Etcdserverpb\AuthenticateRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/Authenticate',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthenticateResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UserAdd adds a new user.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthUserAddRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthUserAddResponse>
     */
    public function UserAdd(\Aternos\Etcd\Proto\Etcdserverpb\AuthUserAddRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/UserAdd',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthUserAddResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UserGet gets detailed user information.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthUserGetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthUserGetResponse>
     */
    public function UserGet(\Aternos\Etcd\Proto\Etcdserverpb\AuthUserGetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/UserGet',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthUserGetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UserList gets a list of all users.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthUserListRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthUserListResponse>
     */
    public function UserList(\Aternos\Etcd\Proto\Etcdserverpb\AuthUserListRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/UserList',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthUserListResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UserDelete deletes a specified user.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthUserDeleteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthUserDeleteResponse>
     */
    public function UserDelete(\Aternos\Etcd\Proto\Etcdserverpb\AuthUserDeleteRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/UserDelete',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthUserDeleteResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UserChangePassword changes the password of a specified user.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthUserChangePasswordRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthUserChangePasswordResponse>
     */
    public function UserChangePassword(\Aternos\Etcd\Proto\Etcdserverpb\AuthUserChangePasswordRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/UserChangePassword',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthUserChangePasswordResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UserGrant grants a role to a specified user.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthUserGrantRoleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthUserGrantRoleResponse>
     */
    public function UserGrantRole(\Aternos\Etcd\Proto\Etcdserverpb\AuthUserGrantRoleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/UserGrantRole',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthUserGrantRoleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UserRevokeRole revokes a role of specified user.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthUserRevokeRoleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthUserRevokeRoleResponse>
     */
    public function UserRevokeRole(\Aternos\Etcd\Proto\Etcdserverpb\AuthUserRevokeRoleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/UserRevokeRole',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthUserRevokeRoleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RoleAdd adds a new role.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthRoleAddRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleAddResponse>
     */
    public function RoleAdd(\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleAddRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/RoleAdd',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleAddResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RoleGet gets detailed role information.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGetResponse>
     */
    public function RoleGet(\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/RoleGet',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RoleList gets lists of all roles.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthRoleListRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleListResponse>
     */
    public function RoleList(\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleListRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/RoleList',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleListResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RoleDelete deletes a specified role.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthRoleDeleteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleDeleteResponse>
     */
    public function RoleDelete(\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleDeleteRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/RoleDelete',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleDeleteResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RoleGrantPermission grants a permission of a specified key or range to a specified role.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGrantPermissionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGrantPermissionResponse>
     */
    public function RoleGrantPermission(\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGrantPermissionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/RoleGrantPermission',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleGrantPermissionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RoleRevokePermission revokes a key or range permission of a specified role.
     * @param \Aternos\Etcd\Proto\Etcdserverpb\AuthRoleRevokePermissionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall<\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleRevokePermissionResponse>
     */
    public function RoleRevokePermission(\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleRevokePermissionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/etcdserverpb.Auth/RoleRevokePermission',
        $argument,
        ['\Aternos\Etcd\Proto\Etcdserverpb\AuthRoleRevokePermissionResponse', 'decode'],
        $metadata, $options);
    }

}
