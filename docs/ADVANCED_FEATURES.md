# Advanced Features

All features in this repository are free and open source. There is no separate
edition, token, or hosted activation service.

The bundled advanced feature extension lives at:

```text
pro/openapi-contract-pro.php
```

The `pro` command name is kept as a stable CLI entry point, but the features it
exposes are included in the open-source project.

## Included Features

Core runtime features:

- OpenAPI and simple GraphQL loading
- examples, coverage, fuzzing, and stateful phases
- built-in runtime contract checks
- replay
- benchmark suites
- JUnit, HAR, NDJSON, VCR, HTML, and curl reproducer reports
- interactive CLI

Advanced bundled features:

- HTML audit dashboard
- PDF audit export
- remediation checklist
- team baseline history
- trend dashboard
- governance/security findings
- Laravel/PHP preset generator
- expanded benchmark corpus writer
- signed PHAR build helper

## Commands

```bash
php bin/openapi-contract pro status
php bin/openapi-contract pro features
php bin/openapi-contract pro baselines --pro-project local
php bin/openapi-contract pro trend --pro-project local
php bin/openapi-contract pro laravel-preset
php bin/openapi-contract pro advanced-corpus
php -d phar.readonly=0 bin/openapi-contract pro phar
```

## Run Hooks

The extension loads automatically when `pro/openapi-contract-pro.php` is
present. Normal `run`, `fuzz`, and `benchmark` commands stay the same, but the
advanced hooks can write extra artifacts.

Run/fuzz artifacts:

- `audit.json`
- `audit.html`
- `audit.pdf`
- `remediation.md`

Benchmark artifacts:

- `benchmark-dashboard.json`
- `benchmark-dashboard.html`
- `benchmark-dashboard.pdf`
- `trend.html`

Core HTML/curl reports are available without the advanced hook:

```bash
php bin/openapi-contract run openapi.json \
  --url http://127.0.0.1:8080 \
  --report html,curl
```

When `benchmark` runs with a `--report-dir`, each benchmark case gets its own
subdirectory so HTML reports and curl reproducers are not overwritten.

By default these go to `openapi-contract-advanced-report`. Override the output
directory with:

```bash
php bin/openapi-contract run openapi.json \
  --url http://127.0.0.1:8080 \
  --pro-output-dir reports/advanced
```

## Extension API

The public hook names remain intentionally stable:

```php
<?php
function openapi_contract_pro_command(string $action, array $options): int {
    return 0;
}

function openapi_contract_pro_after_run(array $state, array $phaseResults, array $summary): void {
}

function openapi_contract_pro_after_benchmark(array $report, array $options): void {
}
```

These names are compatibility hooks; the bundled implementation is open source.
