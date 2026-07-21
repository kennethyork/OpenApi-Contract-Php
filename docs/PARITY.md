# Schemathesis Parity Matrix

This project targets a vanilla PHP CLI implementation. It does not delegate to
the upstream Python Schemathesis CLI.

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
| OpenAPI 3.x JSON loading | Partial | JSON only; no YAML yet. |
| Remote schema loading | Partial | HTTP(S) JSON schemas via curl. |
| Examples phase | Partial | Uses request-body media example when present. |
| Coverage phase | Partial | Exercises documented calls, invalid path values, and unsupported methods. |
| Fuzzing phase | Partial | Generates simple schema-driven values without shrinking. |
| `not_a_server_error` | Supported | Detects HTTP 5xx responses. |
| `status_code_conformance` | Partial | Exact, wildcard class, and default responses are handled. |
| `content_type_conformance` | Partial | JSON response media type only. |
| `response_headers_conformance` | Partial | Required headers and scalar/array schema validation. |
| `response_schema_conformance` | Partial | Core JSON Schema object, array, scalar, enum, ref, oneOf, allOf. |
| `unsupported_method` | Partial | Coverage sends TRACE and checks 405 plus Allow. |

## Native Parity Backlog

| Priority | Area | Target |
| --- | --- | --- |
| P0 | Package shape | Move the native runner into `src/` classes with focused unit tests. |
| P0 | YAML and OpenAPI versions | Support YAML plus OpenAPI 2.0, 3.0, 3.1, and 3.2 normalization. |
| P0 | JSON Schema | Replace the local validator with complete draft-aware validation. |
| P0 | Data generation | Build a property-based generator with reproducible seeds and shrinking. |
| P0 | Request serialization | Implement OpenAPI path, query, header, cookie, form, multipart, and body serialization rules. |
| P1 | Checks | Add `negative_data_rejection`, `positive_data_acceptance`, `missing_required_header`, `ignored_auth`, and `max_response_time`. |
| P1 | Filtering | Add include/exclude filters for method, path, tag, operation id/name, deprecated, and expressions. |
| P1 | Config | Add config-file support for phases, checks, generation, auth, output, and operation overrides. |
| P1 | Reports | Add replay data, curl reproducers, JUnit XML, HAR, and machine-readable JSON output. |
| P2 | Stateful | Support OpenAPI links, inferred dependencies, Location learning, `use_after_free`, and `ensure_resource_availability`. |
| P2 | Adaptive testing | Reuse observed response data and bias generation from runtime feedback. |
| P2 | Extensibility | Add PHP hooks, custom serializers, custom checks, custom formats, and custom media handlers. |
| P3 | GraphQL | Add GraphQL schema loading, generation, execution, response validation, and negative mode. |
