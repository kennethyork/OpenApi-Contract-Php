# openapi-contract-php

PHP-native OpenAPI contract and fuzz testing runner.

This tool is a vanilla PHP CLI for Schemathesis-style OpenAPI contract testing.
It loads an OpenAPI 3.x document, runs examples, coverage checks, and generated
fuzz cases against an HTTP API, then validates status codes, JSON content types,
response headers, response schemas, server errors, and unsupported method
handling.

The target is runtime parity with Schemathesis in PHP: local CLI commands,
checks, generation, filtering, stateful workflows, reports, replay, auth, and
extensibility. CI/CD integrations are intentionally out of scope.

## Usage

```bash
php bin/openapi-contract run https://api.example.com/openapi.json --phases examples,coverage,fuzzing --max-examples 5
```

Continuous fuzzing-style runs are available through the `fuzz` command:

```bash
php bin/openapi-contract fuzz ./openapi.json --url http://127.0.0.1:8080 --max-time 60
```

For file-based schemas, pass a base URL:

```bash
php bin/openapi-contract run ./openapi.json --url http://127.0.0.1:8080/api/v1
```

Useful options:

```bash
-H, --header NAME:VALUE
-a, --auth USER:PASS
--phases examples,coverage,fuzzing,stateful
--checks all
--exclude-checks unsupported_method
--include-path /users
--exclude-method DELETE
--include-tag public
--include-operation-id getUser
--include-path-regex '^/api/v1/'
--exclude-deprecated
--max-examples 10
--max-failures 20
--max-response-time 2.5
--request-timeout 5
--request-retries 2
--max-redirects 3
--proxy http://127.0.0.1:8081
--tls-verify false
--seed 1234
```

## Compare With Schemathesis

Use the comparison helper when you want to measure parity against upstream
Schemathesis. It runs this PHP CLI first, then runs Schemathesis with the same
shared options.

```bash
php bin/compare-schemathesis ./openapi.json \
  --url http://127.0.0.1:8080/api/v1 \
  --phases examples,fuzzing \
  --checks not_a_server_error,status_code_conformance,content_type_conformance,response_headers_conformance,response_schema_conformance \
  --max-examples 10 \
  --seed 123 \
  --no-color
```

The helper looks for `schemathesis`, then `st`, then `uvx schemathesis`. To use
a specific executable:

```bash
php bin/compare-schemathesis ./openapi.json --url http://127.0.0.1:8080 --schemathesis-bin /path/to/schemathesis
```

When comparing against PHP's built-in development server, avoid the coverage
phase unless your server accepts uncommon HTTP methods and routes them to PHP.
Schemathesis may send methods such as `QUERY`, which the built-in server rejects
with `501` before your router can return `405`.

## Checks

- `not_a_server_error`
- `status_code_conformance`
- `content_type_conformance`
- `response_headers_conformance`
- `response_schema_conformance`
- `unsupported_method`

The CLI also accepts Schemathesis runtime checks that are not implemented yet,
including `negative_data_rejection`, `positive_data_acceptance`,
`use_after_free`, `ensure_resource_availability`, `ignored_auth`, and
`missing_required_header`. Those checks produce warnings until their engines are
implemented.

See [docs/PARITY.md](docs/PARITY.md) for the PHP parity matrix and milestone
order.

## Requirements

- PHP 8.2+
- PHP curl extension
- PHP json extension
