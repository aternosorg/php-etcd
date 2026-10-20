# Regenerating the gRPC classes

The PHP classes in [`grpc/`](grpc) are generated from the `.proto` files in [`proto/`](proto).
Don't edit them by hand: change the `.proto` files (if needed) and regenerate.

## Requirements

- `protoc`, the Protocol Buffers compiler
- `grpc_php_plugin`, the gRPC plugin for `protoc` that generates the service clients (`KVClient`, `LeaseClient`, ...)

On macOS, both are available via Homebrew:

```bash
brew install protobuf grpc
```

On other systems, see the [protobuf installation docs](https://protobuf.dev/installation/) for `protoc` and the
[gRPC PHP docs](https://github.com/grpc/grpc/tree/master/src/php#php-protoc-plugin) for building `grpc_php_plugin`.

## Regenerating

```bash
composer generate-grpc
```

This regenerates all classes and replaces `grpc/` with them. It runs the following commands, which you can also run
yourself:

```bash
rm -rf .grpc-tmp && mkdir .grpc-tmp
protoc --proto_path=proto --php_out=.grpc-tmp --grpc_out=.grpc-tmp --plugin=protoc-gen-grpc="$(command -v grpc_php_plugin)" proto/*.proto
rm -rf grpc && mv .grpc-tmp/Aternos/Etcd/Proto grpc && rm -rf .grpc-tmp
```

`protoc` writes the files into directories matching their namespace (`Aternos/Etcd/Proto/...`), so they are generated
into a temporary directory first and only the `Proto` directory is moved to `grpc/`, which is autoloaded as
`Aternos\Etcd\Proto\`. If `protoc` fails, `grpc/` is left untouched.

Afterward, check the result with `git status`/`git diff`. Files that are no longer generated are deleted. That is
intended, but it means the generated classes' public API changed.

## Keep `protoc` and `google/protobuf` in sync

Generated code is only supported with a protobuf runtime that is **at least as new as the `protoc` that generated it**.
The PHP runtime version is `<major>.<protoc version>`, e.g. `protoc` 36.2 (`libprotoc 36.2`) matches `google/protobuf` 5.36.x.

When regenerating with a newer `protoc`, raise the `google/protobuf` constraint in [`composer.json`](composer.json)
accordingly. Check your version with:

```bash
protoc --version
```

The classes currently in `grpc/` were generated with `protoc` 36.2, so `google/protobuf` must be `^5.36`.

## Proto files

The `.proto` files are copied from the [etcd repository](https://github.com/etcd-io/etcd) (see the `source:` comment at
the top of each file). They were modified so they compile without etcd's Go build dependencies:

- The `gogoproto` import and all `option (gogoproto.*)` lines were removed (Go code generation only).
- In `rpc.proto`, the imports of `kv.proto` and `auth.proto` were changed to local paths, and the `google/api/annotations.proto`
  import and all `option (google.api.http)` blocks were removed (grpc-gateway only).

Additionally, each file sets the PHP namespaces of its generated classes, so they don't end up in the global namespace:

```protobuf
option php_namespace = "Aternos\\Etcd\\Proto\\Etcdserverpb"; // Mvccpb in kv.proto, Authpb in auth.proto
option php_metadata_namespace = "Aternos\\Etcd\\Proto\\GPBMetadata";
```

Newer etcd versions keep these files under `api/` (e.g. `api/etcdserverpb/rpc.proto`). They no longer use `gogoproto`,
but `rpc.proto` additionally has `versionpb` annotations (`import "etcd/api/versionpb/version.proto"` and
`option (versionpb.etcd_version_*)`) and an OpenAPI option (`import "protoc-gen-openapiv2/options/annotations.proto"`
and the `option (grpc.gateway.protoc_gen_openapiv2.options.openapiv2_swagger)` block), which have to be removed the same
way when updating. Remember to add the `php_namespace` and `php_metadata_namespace` options again.
