# Runtime Feature Matrix

This project targets a vanilla PHP CLI implementation for local OpenAPI contract
and fuzz testing. CI/CD integrations are intentionally excluded; local reports,
replay, config, auth, stateful testing, GraphQL, and extension points remain in
scope.

## Product Scope

The project is positioned as a dependency-free PHP runtime tester, not only a
static OpenAPI validator. Its core value is local execution of contract checks,
generated cases, replay, auth probes, and stateful workflows without Composer,
hosted services, or CI/CD coupling.

## Current Native Support

| Area | Status | Notes |
| --- | --- | --- |
| CLI `interactive` command | Supported | Menu-driven setup, diagnostics, run, fuzz, replay, and config refresh. |
| CLI `init` command | Supported | Creates JSON config interactively or with `--no-interaction`. |
| CLI `doctor` command | Supported | Checks PHP extensions, config, schema loading, base URL, operations, reports, and compatibility notes. |
| CLI `benchmark` command | Supported | Runs JSON benchmark suites, writes JSON/Markdown evidence, and compares prior baselines. |
| CLI `pro` command | Supported | Detects and delegates to a private Pro extension when installed. |
| Generated benchmark corpus | Supported | Deterministic working and intentionally broken OpenAPI specs cover the main local runtime checks. |
| CLI `run` command | Supported | Runs the PHP implementation; can use LOCATION or config `schema-location`. |
| CLI `fuzz` command | Supported | Runs fuzzing phase directly; supports `--max-time`, modes, seed, and max examples. |
| CLI `replay` command | Supported | Replays saved failing cases from the crash cache. |
| OpenAPI JSON/YAML loading | Supported | OpenAPI 2.0, 3.0, 3.1, and 3.2 normalize to the internal model; YAML uses php-yaml or the limited built-in parser. |
| GraphQL SDL loading | Partial | Simple Query and Mutation fields over a JSON HTTP endpoint. |
| Remote schema loading | Supported | HTTP(S) JSON, YAML, or GraphQL SDL schemas via curl. |
| Examples phase | Supported | Uses media examples when present. |
| Coverage phase | Supported | Exercises documented calls, invalid path values, missing required headers, ignored auth, and unsupported methods. |
| Fuzzing phase | Partial | Generates positive and negative schema-driven values with lightweight JSON shrinking. |
| Filtering | Supported | Path, method, name, tag, operation id, regex, expression basics, and deprecated exclusion. |
| Network options | Partial | Headers, basic auth, bearer auth, API keys, proxy, TLS verify, redirects, retries, timeout, certs, and rate limit; execution remains sequential. |
| Reports | Supported | JUnit XML, HAR, NDJSON events, and VCR-style cassette output with sanitization/truncation controls. |
| `not_a_server_error` | Supported | Detects HTTP 5xx responses. |
| `status_code_conformance` | Supported | Exact, wildcard class, and default responses are handled. |
| `content_type_conformance` | Supported | JSON-compatible and documented media types are handled. |
| `response_headers_conformance` | Supported | Required headers and scalar/array schema validation. |
| `response_schema_conformance` | Partial | Core JSON Schema object, array, scalar, enum, ref, oneOf, anyOf, allOf, not, and common constraints. |
| `negative_data_rejection` | Partial | Negative generated input must receive a 4xx response. |
| `positive_data_acceptance` | Partial | Positive generated input must not receive a 4xx response. |
| `missing_required_header` | Supported | Coverage omits required request headers and expects 4xx. |
| `ignored_auth` | Supported | Coverage sends unauthenticated and invalid-auth probes and expects 401/403. |
| `use_after_free` | Supported | Stateful checks deleted resources are no longer available when a reader exists. |
| `ensure_resource_availability` | Supported | Stateful follows explicit links, Location headers, and inferred IDs after creation. |
| `unsupported_method` | Supported | Coverage sends TRACE and checks 405 plus Allow. |
| `max_response_time` | Supported | Enabled via `--max-response-time`. |

## Native Backlog

| Priority | Area | Target |
| --- | --- | --- |
| P0 | Package shape | Move the runner into focused source modules with direct tests. |
| P0 | JSON Schema | Replace the local validator with complete draft-aware validation. |
| P0 | Data generation | Replace lightweight generation/shrinking with a full property-based engine. |
| P0 | Workers | Add true concurrent HTTP execution while preserving deterministic reports and rate limits. |
| P1 | Request serialization | Add every OpenAPI edge case for path/query/header/cookie/form/multipart encodings. |
| P1 | Checks | Add richer negative cases and deeper security-focused checks. |
| P1 | Filtering | Expand `--include-by` / `--exclude-by` expression support beyond JSON Pointer comparisons. |
| P1 | Config | Add operation overrides, profile inheritance, and richer generation/output settings. |
| P1 | Reports | Add curl reproducers, richer summary JSON, and sanitization controls. |
| P2 | Stateful | Expand workflow discovery, calibration, cleanup, and cross-resource dependency analysis. |
| P2 | Adaptive testing | Reuse observed response data and bias generation from runtime feedback. |
| P2 | Extensibility | Add PHP hooks, custom serializers, custom checks, custom formats, and custom media handlers. |
| P3 | GraphQL | Expand SDL parsing, selection generation, mutations, variables, and typed response validation. |

## Excluded

| Area | Reason |
| --- | --- |
| CI/CD integrations | Explicitly out of scope for this PHP CLI. |
| Hosted service wiring | Not needed for local runtime testing. |
