# Pro Edition

This repository is the Community edition: a vanilla PHP CLI for local API
contract testing. It is MIT licensed and should stay useful on its own.

The Pro edition lives outside this public repository as a private extension,
licensed separately. A local development copy can sit at
`pro/openapi-contract-pro.php`; that path is ignored by git so paid code is not
published with the Community repo.

## Same CLI Model

Free and paid users run the same CLI:

```bash
php bin/openapi-contract run openapi.json --url http://127.0.0.1:8080
php bin/openapi-contract benchmark tests/benchmarks/throw-the-book.json
php bin/openapi-contract replay --cache-dir .openapi-contract/cache
```

The difference is whether a private Pro extension and license token are
available. Without a license token, the CLI only runs the Community feature set,
even if a Pro extension file is present. With a token, the CLI loads the private
extension, and that extension can validate the token before adding paid reports,
paid checks, paid exports, and paid workflow integrations.

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
- Gumroad license activation
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

## License Unlock

No license token means Community-only behavior. The public CLI will not load the
private Pro extension unless it can find a token.

Token sources, in order:

1. `--license-key`
2. `OPENAPI_CONTRACT_PRO_TOKEN`
3. `.openapi-contract/license.key`
4. `license.key`

Examples:

```bash
php bin/openapi-contract pro status --license-key YOUR_TOKEN
```

```bash
OPENAPI_CONTRACT_PRO_TOKEN=YOUR_TOKEN php bin/openapi-contract run openapi.json \
  --url http://127.0.0.1:8080
```

```bash
mkdir -p .openapi-contract
printf '%s' YOUR_TOKEN > .openapi-contract/license.key
php bin/openapi-contract benchmark tests/benchmarks/throw-the-book.json
```

Do not commit license keys to the public repository.

## Gumroad Sales Flow

Gumroad works for a first paid version because it can sell a digital product and
issue software license keys. Gumroad's license verification API checks a
`product_id`, a `license_key`, and an optional `increment_uses_count` flag. For
products created on or after January 9, 2023, Gumroad requires `product_id`
rather than `product_permalink` for verification.

Recommended setup:

1. Create a Gumroad product for the private Pro extension or PHAR.
2. Add Gumroad's license key block to the product content.
3. Put the private Pro file or PHAR in the paid download.
4. Tell buyers to activate once:

```bash
php bin/openapi-contract pro activate \
  --pro-path pro/openapi-contract-pro.php \
  --gumroad-product-id PRODUCT_ID \
  --gumroad-key GUMROAD_LICENSE_KEY
```

Activation verifies the Gumroad key, checks for refunds, disputes,
chargebacks, and ended/cancelled/failed subscriptions, then writes the ignored
local license files:

```text
.openapi-contract/license.key
.openapi-contract/gumroad-product.id
```

After activation, paid users run the same commands as free users:

```bash
php bin/openapi-contract run openapi.json --url http://127.0.0.1:8080
php bin/openapi-contract benchmark tests/benchmarks/local.json
```

Direct Gumroad verification is the simplest first version, but Gumroad-backed
keys need network access when the private extension validates them. A later
hosted license service can exchange a Gumroad purchase for your own signed
offline token.

For a one-off command without writing local activation files:

```bash
php bin/openapi-contract pro status \
  --pro-path pro/openapi-contract-pro.php \
  --gumroad-product-id PRODUCT_ID \
  --gumroad-key GUMROAD_LICENSE_KEY
```

The official Gumroad license key documentation is here:
https://gumroad.com/help/article/76-license-keys

## Private Extension Commands

The local private Pro extension implements these commands:

- `status`
- `features`
- `activate`
- `issue-license`
- `verify-license`
- `baselines`
- `trend`
- `laravel-preset`
- `private-corpus`
- `phar`

It also adds paid run and benchmark hooks that can write:

- `audit.json`
- `audit.html`
- `audit.pdf`
- `remediation.md`
- `benchmark-dashboard.json`
- `benchmark-dashboard.html`
- `benchmark-dashboard.pdf`
- `trend.html`

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
