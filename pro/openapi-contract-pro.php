<?php
declare(strict_types=1);

const OCP_PRO_VERSION = '0.1.0';
const OCP_PRO_DEFAULT_OUTPUT_DIR = 'openapi-contract-advanced-report';
const OCP_PRO_BASELINE_DIR = '.openapi-contract/advanced-baselines';

function openapi_contract_pro_command(string $action, array $options): int {
    $action = $action === '' ? 'status' : $action;
    return match ($action) {
        'status' => ocp_pro_status($options),
        'features' => ocp_pro_features(),
        'activate', 'issue-license' => ocp_pro_no_license_needed(),
        'verify-license' => ocp_pro_verify_license_command($options),
        'baselines' => ocp_pro_baselines_command($options),
        'trend' => ocp_pro_trend_command($options),
        'laravel-preset' => ocp_pro_laravel_preset($options),
        'advanced-corpus' => ocp_pro_advanced_corpus($options),
        'phar' => ocp_pro_build_phar($options),
        'help' => ocp_pro_help(),
        default => ocp_pro_unknown($action)
    };
}

function openapi_contract_pro_after_run(array $state, array $phaseResults, array $summary): void {
    $license = ocp_pro_license($state['options'] ?? []);
    $options = $state['options'] ?? [];
    $project = ocp_pro_project($options, $state['schema_location'] ?? null);
    $outDir = ocp_pro_output_dir($options);
    $benchmarkCase = ocp_pro_option($options, 'benchmark-case-name', '');
    if ($benchmarkCase !== '') $outDir .= '/runs/' . ocp_pro_slug($benchmarkCase);
    ocp_pro_ensure_dir($outDir);

    $findings = ocp_pro_governance_findings($state, $summary, $options);
    $audit = [
        'schema_version' => 1,
        'kind' => 'run',
        'created_at' => gmdate('c'),
        'project' => $project,
        'customer' => ocp_pro_option($options, 'pro-customer', $license['payload']['sub'] ?? 'local user'),
        'entitlement' => ocp_pro_public_entitlement($license),
        'summary' => $summary,
        'findings' => $findings,
        'cases' => $state['case_records'] ?? [],
        'warnings' => $summary['warnings'] ?? []
    ];

    $jsonPath = $outDir . '/audit.json';
    $htmlPath = $outDir . '/audit.html';
    $pdfPath = $outDir . '/audit.pdf';
    $remediationPath = $outDir . '/remediation.md';

    file_put_contents($jsonPath, json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    file_put_contents($htmlPath, ocp_pro_audit_html($audit));
    ocp_pro_write_pdf($pdfPath, 'OpenAPI Contract Audit', ocp_pro_audit_pdf_lines($audit));
    file_put_contents($remediationPath, ocp_pro_remediation_markdown($audit));
    ocp_pro_record_baseline($project, [
        'kind' => 'run',
        'created_at' => $audit['created_at'],
        'cases' => (int)($summary['cases'] ?? 0),
        'passed' => (int)($summary['passed'] ?? 0),
        'failed' => (int)($summary['failed'] ?? 0),
        'findings' => count($findings),
        'score' => ocp_pro_audit_score($summary, $findings)
    ]);

    echo "Advanced audit JSON: {$jsonPath}\n";
    echo "Advanced audit HTML: {$htmlPath}\n";
    echo "Advanced audit PDF: {$pdfPath}\n";
    echo "Advanced remediation: {$remediationPath}\n";
}

function openapi_contract_pro_after_benchmark(array $report, array $options): void {
    $license = ocp_pro_license($options);
    $project = ocp_pro_project($options, $report['suite']['name'] ?? null);
    $outDir = ocp_pro_output_dir($options);
    ocp_pro_ensure_dir($outDir);

    $dashboard = [
        'schema_version' => 1,
        'kind' => 'benchmark',
        'created_at' => gmdate('c'),
        'project' => $project,
        'customer' => ocp_pro_option($options, 'pro-customer', $license['payload']['sub'] ?? 'local user'),
        'entitlement' => ocp_pro_public_entitlement($license),
        'report' => $report
    ];

    $jsonPath = $outDir . '/benchmark-dashboard.json';
    $htmlPath = $outDir . '/benchmark-dashboard.html';
    $pdfPath = $outDir . '/benchmark-dashboard.pdf';
    file_put_contents($jsonPath, json_encode($dashboard, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    file_put_contents($htmlPath, ocp_pro_benchmark_html($dashboard));
    ocp_pro_write_pdf($pdfPath, 'OpenAPI Contract Benchmark', ocp_pro_benchmark_pdf_lines($dashboard));
    ocp_pro_record_baseline($project, [
        'kind' => 'benchmark',
        'created_at' => $dashboard['created_at'],
        'cases' => (int)($report['aggregate']['cases'] ?? 0),
        'passed' => (int)($report['aggregate']['passed'] ?? 0),
        'failed' => (int)($report['aggregate']['failures'] ?? 0),
        'findings' => (int)($report['aggregate']['unexpected_results'] ?? 0),
        'score' => (float)($report['aggregate']['quality_score'] ?? 0)
    ]);
    file_put_contents($outDir . '/trend.html', ocp_pro_trend_html($project));

    echo "Advanced benchmark JSON: {$jsonPath}\n";
    echo "Advanced benchmark HTML: {$htmlPath}\n";
    echo "Advanced benchmark PDF: {$pdfPath}\n";
}

function ocp_pro_status(array $options): int {
    $license = ocp_pro_license($options);
    echo "OpenAPI Contract Advanced " . OCP_PRO_VERSION . "\n";
    echo str_repeat('-', 34) . "\n\n";
    $payload = $license['payload'];
    echo "Status: open-source\n";
    echo "Source: " . ($payload['source'] ?? $payload['iss'] ?? 'local') . "\n";
    echo "Customer: " . ($payload['sub'] ?? 'unknown') . "\n";
    echo "Plan: " . ($payload['plan'] ?? 'open-source') . "\n";
    echo "Project: " . ($payload['project'] ?? 'all') . "\n";
    echo "License: not required\n";
    echo "Features: " . implode(', ', $payload['features'] ?? []) . "\n";
    return 0;
}

function ocp_pro_features(): int {
    echo "Included core features:\n";
    foreach ([
        'runtime contract checks',
        'examples, coverage, fuzzing, and stateful phases',
        'JUnit, HAR, NDJSON, and VCR reports',
        'replay and public benchmarks'
    ] as $item) echo "  - {$item}\n";
    echo "\nIncluded advanced features:\n";
    foreach ([
        'HTML audit dashboard',
        'PDF audit export',
        'team baseline history and trend dashboard',
        'governance/security rule packs',
        'Laravel/PHP preset generator',
        'expanded benchmark corpus writer',
        'signed PHAR build helper',
        'audit evidence packaging'
    ] as $item) echo "  - {$item}\n";
    return 0;
}

function ocp_pro_issue_license(array $options): int {
    return ocp_pro_no_license_needed();
}

function ocp_pro_verify_license_command(array $options): int {
    $license = ocp_pro_license($options);
    echo "License not required; all advanced features are open source for " . ($license['payload']['sub'] ?? 'local user') . ".\n";
    return 0;
}

function ocp_pro_baselines_command(array $options): int {
    $project = ocp_pro_project($options, null);
    $entries = ocp_pro_baselines($project);
    echo "Baselines for {$project}: " . count($entries) . "\n";
    foreach (array_slice($entries, -20) as $entry) {
        printf(
            "%s %-9s cases=%d failed=%d findings=%d score=%s\n",
            $entry['created_at'] ?? '',
            $entry['kind'] ?? '',
            (int)($entry['cases'] ?? 0),
            (int)($entry['failed'] ?? 0),
            (int)($entry['findings'] ?? 0),
            (string)($entry['score'] ?? '')
        );
    }
    return 0;
}

function ocp_pro_trend_command(array $options): int {
    $project = ocp_pro_project($options, null);
    $outDir = ocp_pro_output_dir($options);
    ocp_pro_ensure_dir($outDir);
    $path = $outDir . '/trend.html';
    file_put_contents($path, ocp_pro_trend_html($project));
    echo "Advanced trend HTML: {$path}\n";
    return 0;
}

function ocp_pro_laravel_preset(array $options): int {
    $path = '.openapi-contract/presets/laravel.json';
    ocp_pro_ensure_dir(dirname($path));
    $preset = [
        'schema-location' => 'storage/api-docs/openapi.yaml',
        'url' => 'http://127.0.0.1:8000',
        'headers' => ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'],
        'phases' => 'examples,coverage,fuzzing,stateful',
        'checks' => 'all',
        'max-examples' => 25,
        'report' => ['ndjson', 'junit', 'har', 'vcr', 'html'],
        'report-dir' => 'openapi-contract-report',
        'pro-output-dir' => 'openapi-contract-advanced-report',
        'pro-rules' => 'security,governance,laravel'
    ];
    file_put_contents($path, json_encode($preset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "Laravel advanced preset written: {$path}\n";
    return 0;
}

function ocp_pro_advanced_corpus(array $options): int {
    $path = '.openapi-contract/advanced-corpus.json';
    ocp_pro_ensure_dir(dirname($path));
    $suite = [
        'name' => 'advanced-regression-corpus',
        'base-url' => 'http://127.0.0.1:8089',
        'defaults' => [
            'seed' => 20260721,
            'max-examples' => 5,
            'request-timeout' => 5,
            'report' => 'ndjson,junit,har,vcr,html',
            'report-dir' => 'openapi-contract-report',
            'pro-output-dir' => 'openapi-contract-advanced-report',
            'pro-rules' => 'security,governance'
        ],
        'cases' => [
            ['name' => 'advanced-core-regression', 'schema' => 'tests/fixtures/random-openapi/working-random-core.json', 'url' => '{base_url}', 'options' => ['phases' => 'examples,coverage,fuzzing', 'checks' => 'all', 'auth-api-key' => 'ApiKeyAuth:secret'], 'expected_exit_code' => 0, 'expected_failures' => 0],
            ['name' => 'advanced-stateful-regression', 'schema' => 'tests/fixtures/random-openapi/working-random-stateful.json', 'url' => '{base_url}', 'options' => ['phases' => 'stateful', 'checks' => 'ensure_resource_availability,use_after_free'], 'expected_exit_code' => 0, 'expected_failures' => 0],
            ['name' => 'advanced-broken-security', 'schema' => 'tests/fixtures/random-openapi/broken-auth.json', 'url' => '{base_url}', 'options' => ['phases' => 'coverage', 'checks' => 'ignored_auth', 'auth-api-key' => 'ApiKeyAuth:secret', 'max-failures' => 1], 'expected_exit_code' => 1, 'expected_failures' => 1],
            ['name' => 'advanced-broken-schema', 'schema' => 'tests/fixtures/random-openapi/broken-schema.json', 'url' => '{base_url}', 'options' => ['phases' => 'examples', 'checks' => 'response_schema_conformance', 'max-failures' => 1], 'expected_exit_code' => 1, 'expected_failures' => 1]
        ]
    ];
    file_put_contents($path, json_encode($suite, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "Advanced corpus written: {$path}\n";
    return 0;
}

function ocp_pro_build_phar(array $options): int {
    if (!class_exists('Phar')) {
        fwrite(STDERR, "Phar extension is not available.\n");
        return 2;
    }
    if ((string)ini_get('phar.readonly') === '1') {
        fwrite(STDERR, "phar.readonly is enabled. Re-run with: php -d phar.readonly=0 bin/openapi-contract pro phar\n");
        return 2;
    }
    $target = ocp_pro_option($options, 'pro-phar', 'dist/openapi-contract.phar');
    ocp_pro_ensure_dir(dirname($target));
    if (is_file($target)) unlink($target);
    $phar = new Phar($target);
    $phar->startBuffering();
    $phar->addFile('bin/openapi-contract', 'bin/openapi-contract');
    $phar->addFile(__FILE__, 'pro/openapi-contract-pro.php');
    $phar->setStub("#!/usr/bin/env php\n<?php Phar::mapPhar('openapi-contract.phar'); require 'phar://openapi-contract.phar/bin/openapi-contract'; __HALT_COMPILER();");
    $phar->setSignatureAlgorithm(Phar::SHA512);
    $phar->stopBuffering();
    chmod($target, 0755);
    echo "Signed PHAR written: {$target}\n";
    return 0;
}

function ocp_pro_help(): int {
    echo "OpenAPI Contract advanced commands:\n";
    echo "  status            Show bundled advanced feature status\n";
    echo "  features          Show included core and advanced features\n";
    echo "  baselines         List stored project baselines\n";
    echo "  trend             Write trend dashboard HTML\n";
    echo "  laravel-preset    Write a Laravel-focused preset config\n";
    echo "  advanced-corpus   Write the expanded benchmark corpus\n";
    echo "  phar              Build a signed PHAR when phar.readonly is disabled\n";
    return 0;
}

function ocp_pro_unknown(string $action): int {
    fwrite(STDERR, "Unknown advanced action: {$action}\n");
    return ocp_pro_help() ?: 2;
}

function ocp_pro_license(array $options): array {
    return [
        'valid' => true,
        'reason' => 'open-source',
        'payload' => [
            'iss' => 'openapi-contract-php',
            'source' => 'open-source',
            'sub' => ocp_pro_option($options, 'pro-customer', 'local user'),
            'plan' => 'open-source',
            'project' => ocp_pro_project($options, null),
            'features' => ['audit-html', 'audit-pdf', 'baselines', 'rule-packs', 'laravel-preset', 'advanced-corpus', 'phar']
        ]
    ];
}

function ocp_pro_no_license_needed(): int {
    echo "No license is required; all advanced features are free and open source.\n";
    return 0;
}

function ocp_pro_public_entitlement(array $license): array {
    $payload = $license['payload'] ?? [];
    return [
        'valid' => (bool)($license['valid'] ?? false),
        'source' => $payload['source'] ?? $payload['iss'] ?? null,
        'customer' => $payload['sub'] ?? null,
        'plan' => $payload['plan'] ?? null,
        'project' => $payload['project'] ?? null,
        'product_id' => $payload['product_id'] ?? null,
        'quantity' => $payload['quantity'] ?? null,
        'expires_at' => isset($payload['exp']) ? gmdate('c', (int)$payload['exp']) : null
    ];
}

function ocp_pro_governance_findings(array $state, array $summary, array $options): array {
    $findings = [];
    foreach (($summary['failure_messages'] ?? []) as $message) {
        $findings[] = [
            'severity' => 'high',
            'rule' => 'runtime.failure',
            'message' => (string)$message,
            'remediation' => 'Fix the API behavior or update the contract, then replay the saved case.'
        ];
    }

    foreach (($state['schema']['paths'] ?? []) as $path => $pathItem) {
        if (!is_array($pathItem)) continue;
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            if (!isset($pathItem[$method]) || !is_array($pathItem[$method])) continue;
            $op = $pathItem[$method];
            $name = strtoupper($method) . ' ' . $path;
            if (empty($op['operationId'])) {
                $findings[] = ocp_pro_finding('medium', 'governance.operation_id', "{$name} has no operationId.", 'Add a stable operationId for reports and baselines.');
            }
            if (empty($op['tags'])) {
                $findings[] = ocp_pro_finding('low', 'governance.tags', "{$name} has no tags.", 'Add tags so teams can filter ownership and service boundaries.');
            }
            if (in_array($method, ['post', 'put', 'patch', 'delete'], true) && !ocp_pro_has_4xx($op['responses'] ?? [])) {
                $findings[] = ocp_pro_finding('medium', 'security.error_responses', "{$name} has no documented 4xx response.", 'Document validation/auth/client-error responses.');
            }
            if (($method !== 'get') && !ocp_pro_operation_requires_auth($op, $state['schema'] ?? [])) {
                $findings[] = ocp_pro_finding('medium', 'security.auth', "{$name} does not declare auth.", 'Declare OpenAPI security requirements for mutating operations.');
            }
            foreach (($op['responses'] ?? []) as $status => $response) {
                if (str_starts_with((string)$status, '2') && (int)$status !== 204 && empty($response['content'])) {
                    $findings[] = ocp_pro_finding('low', 'governance.response_content', "{$name} {$status} has no response content schema.", 'Document successful response bodies for client compatibility.');
                }
            }
        }
    }

    return $findings;
}

function ocp_pro_finding(string $severity, string $rule, string $message, string $remediation): array {
    return ['severity' => $severity, 'rule' => $rule, 'message' => $message, 'remediation' => $remediation];
}

function ocp_pro_has_4xx(array $responses): bool {
    foreach (array_keys($responses) as $status) {
        if (str_starts_with((string)$status, '4') || (string)$status === 'default') return true;
    }
    return false;
}

function ocp_pro_operation_requires_auth(array $operation, array $schema): bool {
    if (array_key_exists('security', $operation)) return is_array($operation['security']) && $operation['security'] !== [];
    return is_array($schema['security'] ?? null) && $schema['security'] !== [];
}

function ocp_pro_audit_score(array $summary, array $findings): float {
    $cases = max(1, (int)($summary['cases'] ?? 0));
    $failed = (int)($summary['failed'] ?? 0);
    $penalty = ($failed / $cases) * 60;
    foreach ($findings as $finding) {
        $penalty += match ($finding['severity']) {
            'high' => 8,
            'medium' => 4,
            default => 1
        };
    }
    return max(0.0, round(100 - $penalty, 1));
}

function ocp_pro_audit_html(array $audit): string {
    $summary = $audit['summary'];
    $findings = $audit['findings'];
    $score = ocp_pro_audit_score($summary, $findings);
    $rows = '';
    foreach ($findings as $finding) {
        $rows .= '<tr><td>' . ocp_pro_h($finding['severity']) . '</td><td>' . ocp_pro_h($finding['rule']) . '</td><td>' . ocp_pro_h($finding['message']) . '</td><td>' . ocp_pro_h($finding['remediation']) . '</td></tr>';
    }
    if ($rows === '') $rows = '<tr><td colspan="4">No advanced findings.</td></tr>';
    $cases = '';
    foreach (array_slice($audit['cases'] ?? [], 0, 200) as $case) {
        $cases .= '<tr><td>' . ocp_pro_h($case['id'] ?? '') . '</td><td>' . ocp_pro_h($case['request']['method'] ?? '') . '</td><td>' . ocp_pro_h($case['request']['path_query'] ?? '') . '</td><td>' . ocp_pro_h((string)($case['response']['status'] ?? '')) . '</td><td>' . ocp_pro_h($case['failure'] ?? '') . '</td><td><pre>' . ocp_pro_h($case['curl'] ?? '') . '</pre></td></tr>';
    }
    return ocp_pro_html_shell('Audit Dashboard', '<h1>OpenAPI Contract Audit</h1>'
        . '<section class="metrics">'
        . ocp_pro_metric('Score', $score . '/100')
        . ocp_pro_metric('Cases', (string)($summary['cases'] ?? 0))
        . ocp_pro_metric('Passed', (string)($summary['passed'] ?? 0))
        . ocp_pro_metric('Failed', (string)($summary['failed'] ?? 0))
        . ocp_pro_metric('Findings', (string)count($findings))
        . '</section>'
        . '<h2>Findings</h2><table><thead><tr><th>Severity</th><th>Rule</th><th>Message</th><th>Remediation</th></tr></thead><tbody>' . $rows . '</tbody></table>'
        . '<h2>Evidence</h2><table><thead><tr><th>Case</th><th>Method</th><th>Path</th><th>Status</th><th>Failure</th><th>Reproducer</th></tr></thead><tbody>' . $cases . '</tbody></table>');
}

function ocp_pro_benchmark_html(array $dashboard): string {
    $report = $dashboard['report'];
    $aggregate = $report['aggregate'] ?? [];
    $rows = '';
    foreach (($report['results'] ?? []) as $result) {
        $rows .= '<tr><td>' . ocp_pro_h($result['name'] ?? '') . '</td><td>' . (($result['matched_expectation'] ?? false) ? 'OK' : 'FAIL') . '</td><td>' . ocp_pro_h((string)($result['cases'] ?? 0)) . '</td><td>' . ocp_pro_h((string)($result['failures'] ?? 0)) . '</td><td>' . ocp_pro_h((string)($result['duration'] ?? 0)) . '</td></tr>';
    }
    return ocp_pro_html_shell('Benchmark Dashboard', '<h1>OpenAPI Contract Benchmark</h1>'
        . '<section class="metrics">'
        . ocp_pro_metric('Quality', ($aggregate['quality_score'] ?? 0) . '/100')
        . ocp_pro_metric('Cases', (string)($aggregate['cases'] ?? 0))
        . ocp_pro_metric('Failures', (string)($aggregate['failures'] ?? 0))
        . ocp_pro_metric('Unexpected', (string)($aggregate['unexpected_results'] ?? 0))
        . '</section>'
        . '<h2>Benchmark Cases</h2><table><thead><tr><th>Name</th><th>Result</th><th>Cases</th><th>Failures</th><th>Runtime</th></tr></thead><tbody>' . $rows . '</tbody></table>');
}

function ocp_pro_trend_html(string $project): string {
    $entries = ocp_pro_baselines($project);
    $rows = '';
    foreach ($entries as $entry) {
        $rows .= '<tr><td>' . ocp_pro_h($entry['created_at'] ?? '') . '</td><td>' . ocp_pro_h($entry['kind'] ?? '') . '</td><td>' . ocp_pro_h((string)($entry['cases'] ?? 0)) . '</td><td>' . ocp_pro_h((string)($entry['failed'] ?? 0)) . '</td><td>' . ocp_pro_h((string)($entry['findings'] ?? 0)) . '</td><td>' . ocp_pro_h((string)($entry['score'] ?? '')) . '</td></tr>';
    }
    if ($rows === '') $rows = '<tr><td colspan="6">No baselines yet.</td></tr>';
    return ocp_pro_html_shell('Trend Dashboard', '<h1>OpenAPI Contract Trend</h1><p>Project: ' . ocp_pro_h($project) . '</p><table><thead><tr><th>Time</th><th>Kind</th><th>Cases</th><th>Failed</th><th>Findings</th><th>Score</th></tr></thead><tbody>' . $rows . '</tbody></table>');
}

function ocp_pro_html_shell(string $title, string $body): string {
    return '<!doctype html><html><head><meta charset="utf-8"><title>' . ocp_pro_h($title) . '</title><style>'
        . 'body{font-family:Arial,sans-serif;margin:32px;color:#18202a;background:#f8fafc}h1{margin:0 0 16px}h2{margin-top:28px}.metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:20px 0}.metric{background:#fff;border:1px solid #d9e2ec;border-radius:6px;padding:14px}.metric b{display:block;font-size:24px}table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #d9e2ec}th,td{text-align:left;border-bottom:1px solid #e6edf3;padding:8px;vertical-align:top}th{background:#eef3f8}pre{white-space:pre-wrap;word-break:break-word;margin:0;font-size:12px}@media print{body{background:#fff;margin:16px}.metric,table{break-inside:avoid}}'
        . '</style></head><body>' . $body . '</body></html>';
}

function ocp_pro_metric(string $label, string $value): string {
    return '<div class="metric"><span>' . ocp_pro_h($label) . '</span><b>' . ocp_pro_h($value) . '</b></div>';
}

function ocp_pro_remediation_markdown(array $audit): string {
    $lines = ['# Remediation Checklist', ''];
    foreach ($audit['findings'] as $finding) {
        $lines[] = '- [' . strtoupper($finding['severity']) . '] ' . $finding['message'];
        $lines[] = '  Remediation: ' . $finding['remediation'];
    }
    if (count($lines) === 2) $lines[] = 'No findings.';
    $failedCases = array_values(array_filter($audit['cases'] ?? [], fn(array $case): bool => ($case['failure'] ?? null) !== null));
    if ($failedCases) {
        $lines[] = '';
        $lines[] = '## Failure Reproducers';
        $lines[] = '';
        foreach ($failedCases as $case) {
            $lines[] = '### Case ' . ($case['id'] ?? '');
            $lines[] = '';
            $lines[] = '```bash';
            $lines[] = (string)($case['curl'] ?? '');
            $lines[] = '```';
            $lines[] = '';
        }
    }
    return implode("\n", $lines) . "\n";
}

function ocp_pro_audit_pdf_lines(array $audit): array {
    $summary = $audit['summary'];
    $lines = [
        'OpenAPI Contract Audit',
        'Project: ' . $audit['project'],
        'Customer: ' . $audit['customer'],
        'Score: ' . ocp_pro_audit_score($summary, $audit['findings']) . '/100',
        'Cases: ' . ($summary['cases'] ?? 0),
        'Passed: ' . ($summary['passed'] ?? 0),
        'Failed: ' . ($summary['failed'] ?? 0),
        'Findings: ' . count($audit['findings']),
        ''
    ];
    foreach (array_slice($audit['findings'], 0, 30) as $finding) {
        $lines[] = strtoupper($finding['severity']) . ' ' . $finding['rule'] . ': ' . $finding['message'];
    }
    return $lines;
}

function ocp_pro_benchmark_pdf_lines(array $dashboard): array {
    $aggregate = $dashboard['report']['aggregate'] ?? [];
    return [
        'OpenAPI Contract Benchmark',
        'Project: ' . $dashboard['project'],
        'Customer: ' . $dashboard['customer'],
        'Quality score: ' . ($aggregate['quality_score'] ?? 0) . '/100',
        'Cases: ' . ($aggregate['cases'] ?? 0),
        'Failures: ' . ($aggregate['failures'] ?? 0),
        'Unexpected: ' . ($aggregate['unexpected_results'] ?? 0)
    ];
}

function ocp_pro_write_pdf(string $path, string $title, array $lines): void {
    $pages = array_chunk(array_map(fn($line) => substr(preg_replace('/[^\x20-\x7E]/', '?', (string)$line) ?? '', 0, 110), $lines), 42);
    if (!$pages) $pages = [[]];
    $objects = [];
    $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
    $kids = [];
    $pageObjectIds = [];
    $contentObjectIds = [];
    $nextId = 3;
    foreach ($pages as $_) {
        $pageObjectIds[] = $nextId++;
        $contentObjectIds[] = $nextId++;
    }
    foreach ($pageObjectIds as $id) $kids[] = $id . ' 0 R';
    $objects[] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pages) . ' >>';
    foreach ($pages as $index => $pageLines) {
        $content = "BT\n/F1 12 Tf\n50 790 Td\n";
        foreach (array_merge([$title, ''], $pageLines) as $line) {
            $content .= '(' . ocp_pro_pdf_escape($line) . ") Tj\n0 -16 Td\n";
        }
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> /Contents ' . $contentObjectIds[$index] . ' 0 R >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "ET\nendstream";
    }
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n{$object}\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    file_put_contents($path, $pdf);
}

function ocp_pro_pdf_escape(string $value): string {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}

function ocp_pro_record_baseline(string $project, array $entry): void {
    ocp_pro_ensure_dir(OCP_PRO_BASELINE_DIR);
    file_put_contents(ocp_pro_baseline_path($project), json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
}

function ocp_pro_baselines(string $project): array {
    $path = ocp_pro_baseline_path($project);
    if (!is_file($path)) return [];
    $entries = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $entry = json_decode($line, true);
        if (is_array($entry)) $entries[] = $entry;
    }
    return $entries;
}

function ocp_pro_baseline_path(string $project): string {
    return OCP_PRO_BASELINE_DIR . '/' . ocp_pro_slug($project) . '.jsonl';
}

function ocp_pro_slug(string $value): string {
    $slug = trim((string)preg_replace('/[^a-zA-Z0-9_.-]+/', '-', $value), '-');
    return $slug === '' ? 'default' : $slug;
}

function ocp_pro_output_dir(array $options): string {
    return rtrim(ocp_pro_option($options, 'pro-output-dir', OCP_PRO_DEFAULT_OUTPUT_DIR), DIRECTORY_SEPARATOR);
}

function ocp_pro_project(array $options, $fallback): string {
    $project = ocp_pro_option($options, 'pro-project', '');
    if ($project !== '') return $project;
    if (is_string($fallback) && $fallback !== '') return basename($fallback);
    return 'default';
}

function ocp_pro_option(array $options, string $name, string $default = ''): string {
    if (!array_key_exists($name, $options) || $options[$name] === true || $options[$name] === null) return $default;
    $value = $options[$name];
    if (is_array($value)) return implode(',', array_map('strval', $value));
    return trim((string)$value);
}

function ocp_pro_ensure_dir(string $dir): void {
    if ($dir === '.' || $dir === '') return;
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function ocp_pro_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
