# openapi-contract-php

Zero-dependency PHP CLI for local OpenAPI and GraphQL contract testing,
fuzzing, replay, and stateful API checks.

This tool is a vanilla PHP CLI for OpenAPI and GraphQL contract and fuzz
testing. It loads OpenAPI 2.0/3.x JSON or YAML documents and simple GraphQL SDL
files, runs examples, coverage checks, generated fuzz cases, and stateful
workflows against an HTTP API, then validates status codes, content types,
response headers, response schemas, server errors, input rejection, auth
enforcement, required request headers, and unsupported method handling.

The target is a complete local API testing workflow in PHP: CLI commands,
checks, generation, filtering, stateful workflows, reports, replay, auth, and
extensibility. CI/CD integrations are intentionally out of scope.

## Positioning

This is not just a static spec linter. It reads an API description, generates
HTTP traffic, validates live responses against the contract, records failures,
and replays saved repro cases.

The distinct angle is:

- vanilla PHP CLI
- no Composer dependency
- no hosted service dependency
- no CI/CD coupling
- local contract, fuzz, replay, auth, and stateful workflow checks in one tool

Commercial add-ons can be shipped as a private Pro extension while this
repository stays useful as the MIT-licensed Community edition. See
[docs/PRO_EDITION.md](docs/PRO_EDITION.md). Free and paid users run the same
CLI; without a license token the CLI stays Community-only, and with a token a
private extension can unlock paid reports, rule packs, baselines, packaging, and
support.

## Usage

Start the menu-driven CLI:

```bash
php bin/openapi-contract interactive
```

Create a reusable config interactively:

```bash
php bin/openapi-contract init
```

The repository includes `openapi-contract.example.json` as a safe fixture
configuration. Local generated config files are gitignored because they may
contain environment-specific URLs, headers, or credentials.

Check the local PHP/runtime/schema setup:

```bash
php bin/openapi-contract doctor --config openapi-contract.json
```

Run from config:

```bash
php bin/openapi-contract run --config openapi-contract.json
```

Run the local benchmark corpus:

```bash
php -S 127.0.0.1:8089 tests/fixtures/router.php
php bin/openapi-contract benchmark tests/benchmarks/local.json
```

Run the broader randomized contract corpus:

```bash
php tests/fixtures/random-openapi/generate.php
php bin/openapi-contract benchmark tests/benchmarks/throw-the-book.json
```

Check whether a private Pro extension is installed:

```bash
php bin/openapi-contract pro status
```

Paid users keep the same commands and pass a license token:

```bash
php bin/openapi-contract run ./openapi.json --url http://127.0.0.1:8080 \
  --license-key YOUR_TOKEN --pro-output-dir pro-report
```

The token can also come from `OPENAPI_CONTRACT_PRO_TOKEN` or
`.openapi-contract/license.key`. If you sell Pro through Gumroad, activate a
buyer key once and then use the same CLI normally:

```bash
php bin/openapi-contract pro activate \
  --pro-path pro/openapi-contract-pro.php \
  --gumroad-product-id PRODUCT_ID \
  --gumroad-key GUMROAD_LICENSE_KEY
```

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
--auth-token TOKEN
--auth-api-key ApiKeyAuth:secret
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
--report ndjson,junit
--report-dir openapi-contract-report
--cache-dir .openapi-contract/cache
--seed 1234
```

Replay saved failing cases:

```bash
php bin/openapi-contract replay --cache-dir .openapi-contract/cache
```

## Checks

- `not_a_server_error`
- `status_code_conformance`
- `content_type_conformance`
- `response_headers_conformance`
- `response_schema_conformance`
- `negative_data_rejection`
- `positive_data_acceptance`
- `use_after_free`
- `ensure_resource_availability`
- `ignored_auth`
- `missing_required_header`
- `unsupported_method`

Stateful checks use explicit OpenAPI links, `Location` headers, and simple
resource dependency inference. Complex API workflows may need explicit links in
the schema.

See [docs/ROADMAP.md](docs/ROADMAP.md) for the feature matrix and milestone
order. See [docs/BENCHMARKING.md](docs/BENCHMARKING.md) for repeatable local
quality measurements.

## Requirements

- PHP 8.2+
- PHP curl extension
- PHP json extension
