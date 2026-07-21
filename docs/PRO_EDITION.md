# Pro Edition Plan

This repository is the Community edition: a vanilla PHP CLI for local API
contract testing. It is MIT licensed and should stay useful on its own.

The Pro edition should live outside this public repository as a private
extension, licensed separately.

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
