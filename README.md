# openapi-contract-php

PHP-native OpenAPI contract and fuzz testing runner.

This tool is a PHP-native Schemathesis-style runner: it loads an OpenAPI 3.x
document, runs examples, coverage checks, and generated fuzz cases against an
HTTP API, then validates status codes, JSON content types, response headers,
response schemas, server errors, and unsupported method handling.

The default CLI path stays entirely in PHP. An explicit upstream bridge is kept
only for behavior comparison while native parity work continues.

## Usage

```bash
php bin/openapi-contract run https://api.example.com/openapi.json --phases examples,coverage,fuzzing --max-examples 5
```

For file-based schemas, pass a base URL:

```bash
php bin/openapi-contract run ./openapi.json --url http://127.0.0.1:8080/api/v1
```

Useful options:

```bash
-H, --header NAME:VALUE
--phases examples,coverage,fuzzing
--checks all
--exclude-checks unsupported_method
--max-examples 10
--max-failures 20
--request-timeout 5
--seed 1234
```

To compare behavior with the upstream Python Schemathesis CLI:

```bash
php bin/openapi-contract schemathesis run ./openapi.json --url http://127.0.0.1:8080
```

## Checks

- `not_a_server_error`
- `status_code_conformance`
- `content_type_conformance`
- `response_headers_conformance`
- `response_schema_conformance`
- `unsupported_method`

See [docs/PARITY.md](docs/PARITY.md) for the native parity matrix and milestone
order.

## Requirements

- PHP 8.2+
- PHP curl extension
