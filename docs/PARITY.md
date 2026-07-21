# Schemathesis Parity Matrix

This project targets a vanilla PHP CLI implementation with Schemathesis runtime
parity. It does not delegate to the upstream Python Schemathesis CLI. CI/CD
integrations are intentionally excluded; local reports, replay, config, auth,
stateful testing, GraphQL, and extension points remain in scope.

Sources checked on July 21, 2026:

- [Schemathesis introduction](https://schemathesis.readthedocs.io/en/stable/):
  OpenAPI 2.0, 3.0, 3.1, 3.2 and GraphQL support.
- [Checks reference](https://schemathesis.readthedocs.io/en/stable/reference/checks/):
  response checks, input-handling checks, stateful checks, authentication
  checks, and max response time.
- [Data-generation guide](https://schemathesis.readthedocs.io/en/latest/explanations/data-generation/):
  examples, coverage, fuzzing, stateful phases, positive and negative
  generation modes, and shrinking.
- [Stateful testing guide](https://schemathesis.readthedocs.io/en/stable/explanations/stateful/):
  OpenAPI links, inferred dependencies, response data reuse, and workflow
  testing.

## Current Native Support

| Area | Status | Notes |
| --- | --- | --- |
| CLI `run` command | Partial | Runs PHP-native implementation by default. |
| CLI `fuzz` command | Partial | Runs fuzzing phase directly; time-bounded with `--max-time`. |
| OpenAPI 3.x JSON loading | Partial | JSON only; no YAML yet. |
| Remote schema loading | Partial | HTTP(S) JSON schemas via curl. |
| Examples phase | Partial | Uses request-body media example when present. |
| Coverage phase | Partial | Exercises documented calls, invalid path values, and unsupported methods. |
| Fuzzing phase | Partial | Generates simple schema-driven values without shrinking. |
| Filtering | Partial | Path, method, tag, operation id, regex, expression basics, and deprecated exclusion. |
| Network options | Partial | Headers, basic auth, proxy, TLS verify, redirects, retries, timeout, and rate limit. |
| `not_a_server_error` | Supported | Detects HTTP 5xx responses. |
| `status_code_conformance` | Partial | Exact, wildcard class, and default responses are handled. |
| `content_type_conformance` | Partial | JSON response media type only. |
| `response_headers_conformance` | Partial | Required headers and scalar/array schema validation. |
| `response_schema_conformance` | Partial | Core JSON Schema object, array, scalar, enum, ref, oneOf, allOf. |
| `unsupported_method` | Partial | Coverage sends TRACE and checks 405 plus Allow. |
| `max_response_time` | Supported | Enabled via `--max-response-time`. |

## Native Parity Backlog

| Priority | Area | Target |
| --- | --- | --- |
| P0 | Package shape | Move the native runner into `src/` classes with focused unit tests. |
| P0 | YAML and OpenAPI versions | Support YAML plus OpenAPI 2.0, 3.0, 3.1, and 3.2 normalization. |
| P0 | JSON Schema | Replace the local validator with complete draft-aware validation. |
| P0 | Data generation | Build a property-based generator with reproducible seeds and shrinking. |
| P0 | Request serialization | Implement OpenAPI path, query, header, cookie, form, multipart, and body serialization rules. |
| P1 | Checks | Add `negative_data_rejection`, `positive_data_acceptance`, `missing_required_header`, and `ignored_auth`. |
| P1 | Filtering | Expand `--include-by` / `--exclude-by` expression parity and operation-name aliases. |
| P1 | Config | Add config-file support for phases, checks, generation, auth, output, and operation overrides. |
| P1 | Reports | Add replay data, curl reproducers, JUnit XML, HAR, and machine-readable JSON output. |
| P2 | Stateful | Support OpenAPI links, inferred dependencies, Location learning, `use_after_free`, and `ensure_resource_availability`. |
| P2 | Adaptive testing | Reuse observed response data and bias generation from runtime feedback. |
| P2 | Extensibility | Add PHP hooks, custom serializers, custom checks, custom formats, and custom media handlers. |
| P3 | GraphQL | Add GraphQL schema loading, generation, execution, response validation, and negative mode. |

## Excluded

| Area | Reason |
| --- | --- |
| CI/CD integrations | Explicitly out of scope for this PHP CLI. |
| Hosted service wiring | Not needed for local runtime parity. |
