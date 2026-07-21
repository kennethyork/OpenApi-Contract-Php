# Pro Edition Plan

This repository is the Community edition: a vanilla PHP CLI for local API
contract testing. It is MIT licensed and should stay useful on its own.

The Pro edition should live outside this public repository as a private
extension, licensed separately.

## Same CLI Model

Free and paid users run the same CLI:

```bash
php bin/openapi-contract run openapi.json --url http://127.0.0.1:8080
php bin/openapi-contract benchmark tests/benchmarks/throw-the-book.json
php bin/openapi-contract replay --cache-dir .openapi-contract/cache
```

The difference is whether a private Pro extension and valid license token are
available. Without Pro, the commands run the Community feature set. With Pro,
the same commands can add paid reports, paid checks, paid exports, and paid
workflow integrations.

## Product Boundary

Community edition:

- OpenAPI and simple GraphQL loading
- examples, coverage, fuzzing, and stateful phases
- built-in checks
- replay
- benchmark suites
- JUnit, HAR, NDJSON, and VCR reports
- interactive CLI

Pro edition:

- polished HTML report dashboard
- PDF/customer-ready audit export
- team baseline history and trend comparison
- larger private benchmark corpus
- organization rule packs
- custom checks and serializers
- Laravel/PHP framework presets
- license status command
- signed PHAR builds
- priority support

## Paid Additions

| Area | Community | Pro |
| --- | --- | --- |
| CLI commands | Same commands | Same commands, unlocked by extension/token |
| Runtime checks | Built-in local checks | Private security, governance, and organization rule packs |
| Reports | JUnit, HAR, NDJSON, VCR, Markdown benchmark summaries | HTML audit dashboard, PDF export, customer-ready evidence bundle |
| Benchmarks | Public local corpuses | Larger private corpus, baseline history, trend/regression reports |
| Framework workflow | Generic HTTP API testing | Laravel/PHP presets, common auth/header profiles, app-specific templates |
| Packaging | Source checkout | Signed PHAR or single-file paid extension |
| Support | Community/self-serve | Priority support, setup help, paid API audits |

## Extension Loading

The public CLI has a small Pro extension hook. Private code can be placed in one
of these ignored paths:

```text
openapi-contract-pro.php
pro/openapi-contract-pro.php
.openapi-contract/pro.php
```

Or loaded explicitly:

```bash
php bin/openapi-contract pro --pro-path path/to/openapi-contract-pro.php status
```

The extension file should define:

```php
<?php
function openapi_contract_pro_command(string $action, array $options): int {
    // Dispatch private Pro commands here.
    return 0;
}
```

To add paid behavior to the same `run` and `fuzz` commands, define:

```php
function openapi_contract_pro_after_run(array $state, array $phaseResults, array $summary): void {
    // Write paid HTML/PDF/audit reports here.
}
```

To add paid behavior to the same `benchmark` command, define:

```php
function openapi_contract_pro_after_benchmark(array $report, array $options): void {
    // Write paid baseline, trend, or dashboard outputs here.
}
```

License keys should be passed through environment variables, an ignored local
file, or `--license-key`. Do not commit license keys to the public repository.

## Pricing Starting Point

Start simple:

- Community: free
- Pro Solo: $19/month or $190/year
- Pro Team: $49/user/month or $490/user/year
- Pro Audit: fixed-price service, starting around $499 per API
- Enterprise/support: custom annual contract

The first paid product should be an audit/report package, because it can sell
before a full hosted product exists.

## First Paid Feature

Build the HTML audit report first. It should turn one run into a customer-ready
artifact:

- executive summary
- failure categories
- affected operations
- reproduction commands
- raw request/response evidence
- benchmark score
- remediation checklist

That is easier to sell than another raw CLI flag.

## Launch Checklist

- Create a private Pro repository.
- Keep this repository MIT and public.
- Add a landing page with screenshots of the HTML report.
- Publish three example reports using the included broken corpus.
- Offer paid API contract audits.
- Add license terms for the private Pro extension.
- Ship Pro as a single PHP file or PHAR that the public CLI can load.
