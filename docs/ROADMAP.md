# Runtime Feature Matrix

This project targets a vanilla PHP CLI implementation for local OpenAPI contract
and fuzz testing. CI/CD integrations are intentionally excluded; local reports,
replay, config, auth, stateful testing, GraphQL, and extension points remain in
scope.

## Current Native Support

| Area | Status | Notes |
| --- | --- | --- |
| CLI `interactive` command | Partial | Menu-driven setup, diagnostics, run, fuzz, replay, and config refresh. |
| CLI `init` command | Partial | Creates JSON config interactively or with `--no-interaction`. |
| CLI `doctor` command | Partial | Checks PHP extensions, config, schema loading, base URL, operations, reports, and compatibility notes. |
| CLI `run` command | Partial | Runs the PHP implementation; can use LOCATION or config `schema-location`. |
| CLI `fuzz` command | Partial | Runs fuzzing phase directly; time-bounded with `--max-time`. |
| CLI `replay` command | Partial | Replays saved failing cases from the crash cache. |
| OpenAPI 3.x JSON/YAML loading | Partial | JSON, php-yaml when installed, and a limited built-in YAML parser. |
| Remote schema loading | Partial | HTTP(S) JSON or YAML schemas via curl. |
| Examples phase | Partial | Uses request-body media example when present. |
| Coverage phase | Partial | Exercises documented calls, invalid path values, and unsupported methods. |
| Fuzzing phase | Partial | Generates positive and negative schema-driven values without shrinking. |
| Filtering | Partial | Path, method, tag, operation id, regex, expression basics, and deprecated exclusion. |
| Network options | Partial | Headers, basic auth, proxy, TLS verify, redirects, retries, timeout, and rate limit. |
| Reports | Partial | JUnit XML, HAR, NDJSON events, and VCR-style cassette output. |
| `not_a_server_error` | Supported | Detects HTTP 5xx responses. |
| `status_code_conformance` | Partial | Exact, wildcard class, and default responses are handled. |
| `content_type_conformance` | Partial | JSON response media type only. |
| `response_headers_conformance` | Partial | Required headers and scalar/array schema validation. |
| `response_schema_conformance` | Partial | Core JSON Schema object, array, scalar, enum, ref, oneOf, allOf. |
| `negative_data_rejection` | Partial | Negative generated input must receive a 4xx response. |
| `positive_data_acceptance` | Partial | Positive generated input must not receive a 4xx response. |
| `unsupported_method` | Partial | Coverage sends TRACE and checks 405 plus Allow. |
| `max_response_time` | Supported | Enabled via `--max-response-time`. |

## Native Backlog

| Priority | Area | Target |
| --- | --- | --- |
| P0 | Package shape | Move the runner into focused source modules with direct tests. |
| P0 | YAML and OpenAPI versions | Support YAML plus OpenAPI 2.0, 3.0, 3.1, and 3.2 normalization. |
| P0 | JSON Schema | Replace the local validator with complete draft-aware validation. |
| P0 | Data generation | Build a property-based generator with reproducible seeds and shrinking. |
| P0 | Request serialization | Implement OpenAPI path, query, header, cookie, form, multipart, and body serialization rules. |
| P1 | Checks | Add `missing_required_header`, `ignored_auth`, richer negative cases, and security-focused checks. |
| P1 | Filtering | Expand `--include-by` / `--exclude-by` expression support and operation-name aliases. |
| P1 | Config | Add operation overrides, profile inheritance, and richer generation/output settings. |
| P1 | Reports | Add curl reproducers, richer summary JSON, and sanitization controls. |
| P2 | Stateful | Support OpenAPI links, inferred dependencies, Location learning, `use_after_free`, and `ensure_resource_availability`. |
| P2 | Adaptive testing | Reuse observed response data and bias generation from runtime feedback. |
| P2 | Extensibility | Add PHP hooks, custom serializers, custom checks, custom formats, and custom media handlers. |
| P3 | GraphQL | Add GraphQL schema loading, generation, execution, response validation, and negative mode. |

## Excluded

| Area | Reason |
| --- | --- |
| CI/CD integrations | Explicitly out of scope for this PHP CLI. |
| Hosted service wiring | Not needed for local runtime testing. |
