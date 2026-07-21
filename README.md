# openapi-contract-php

PHP-native OpenAPI contract and fuzz testing runner.

This tool is a vanilla PHP CLI for Schemathesis-style OpenAPI contract testing.
It loads an OpenAPI 3.x document, runs examples, coverage checks, and generated
fuzz cases against an HTTP API, then validates status codes, JSON content types,
response headers, response schemas, server errors, and unsupported method
handling.

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

## Checks

- `not_a_server_error`
- `status_code_conformance`
- `content_type_conformance`
- `response_headers_conformance`
- `response_schema_conformance`
- `unsupported_method`

See [docs/PARITY.md](docs/PARITY.md) for the PHP parity matrix and milestone
order.

## Requirements

- PHP 8.2+
- PHP curl extension
