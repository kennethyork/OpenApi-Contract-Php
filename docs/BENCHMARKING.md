# Benchmarking

Use benchmarks to support quality claims with repeatable local evidence.

This project includes a local benchmark corpus that exercises:

- OpenAPI 3 JSON
- OpenAPI 3 YAML
- Swagger 2 normalization
- simple GraphQL SDL execution
- auth probes
- required request-header probes
- positive and negative fuzzing
- stateful resource checks
- replay-quality failure capture

## Run

Start the fixture API:

```bash
php -S 127.0.0.1:8089 tests/fixtures/router.php
```

Run the benchmark:

```bash
php bin/openapi-contract benchmark tests/benchmarks/local.json
```

Use another local port:

```bash
php bin/openapi-contract benchmark tests/benchmarks/local.json \
  --base-url http://127.0.0.1:8724
```

Write explicit outputs:

```bash
php bin/openapi-contract benchmark tests/benchmarks/local.json \
  --benchmark-output benchmark-report/results.json \
  --benchmark-markdown benchmark-report/summary.md
```

Compare to a prior result:

```bash
php bin/openapi-contract benchmark tests/benchmarks/local.json \
  --benchmark-baseline benchmark-report/results.json \
  --fail-on-regression
```

## Interpreting Results

The benchmark reports:

- total runtime
- operations selected and discovered
- HTTP cases executed
- failures observed
- unexpected results
- quality score
- per-case output tail for debugging

The quality score is a local regression signal, not a universal ranking. A
credible "best" claim needs broader evidence from real APIs: bug findings,
false positives, runtime, reproducibility, and feature coverage across many
independent specs.

## Adding Real Specs

Add new benchmark entries to a suite JSON file:

```json
{
  "name": "my-api",
  "schema": "path/to/openapi.yaml",
  "url": "http://127.0.0.1:8080",
  "options": {
    "phases": "examples,coverage,fuzzing,stateful",
    "checks": "all",
    "max-examples": 25
  },
  "expected_exit_code": 0,
  "expected_failures": 0
}
```

When a case is intentionally broken, set `expected_exit_code` and
`expected_failures` to the expected non-zero result. That lets the benchmark
distinguish bug detection from tool regression.
