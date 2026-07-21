# openapi-contract-php

PHP-native OpenAPI contract and fuzz testing runner.

This tool is Schemathesis-style: it loads an OpenAPI 3.x document, runs examples,
coverage checks, and generated fuzz cases against an HTTP API, then validates
status codes, JSON content types, response schemas, server errors, and unsupported
method handling.

It is not a full Schemathesis port. It does not implement Hypothesis shrinking,
stateful links, replay files, GraphQL, or report formats yet.

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
- `response_schema_conformance`
- `unsupported_method`

## Requirements

- PHP 8.2+
- PHP curl extension
