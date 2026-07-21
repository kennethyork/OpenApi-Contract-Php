<?php
declare(strict_types=1);

mt_srand(20260721);

$root = dirname(__DIR__, 3);
$outDir = __DIR__;
$benchmarkDir = $root . '/tests/benchmarks';

if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Could not create {$outDir}\n");
    exit(1);
}
if (!is_dir($benchmarkDir) && !mkdir($benchmarkDir, 0777, true) && !is_dir($benchmarkDir)) {
    fwrite(STDERR, "Could not create {$benchmarkDir}\n");
    exit(1);
}

write_json($outDir . '/working-random-core.json', working_core_spec());
write_json($outDir . '/working-random-fuzz.json', working_fuzz_spec());
write_json($outDir . '/working-random-stateful.json', working_stateful_spec());
write_json($outDir . '/broken-status.json', drift_spec('Broken Status', '/random/status-drift', [
    '200' => json_response(object_schema(['ok'], ['ok' => ['type' => 'boolean']]))
]));
write_json($outDir . '/broken-content-type.json', drift_spec('Broken Content Type', '/random/content-drift', [
    '200' => json_response(object_schema(['ok'], ['ok' => ['type' => 'boolean']]))
]));
write_json($outDir . '/broken-schema.json', drift_spec('Broken Response Schema', '/random/schema-drift', [
    '200' => json_response(object_schema(['id', 'count', 'ok'], [
        'id' => ['type' => 'string'],
        'count' => ['type' => 'integer'],
        'ok' => ['type' => 'boolean']
    ]))
]));
write_json($outDir . '/broken-header.json', drift_spec('Broken Required Header', '/random/header-drift', [
    '200' => json_response(object_schema(['ok'], ['ok' => ['type' => 'boolean']]), [
        'X-Drift-Token' => ['required' => true, 'schema' => ['type' => 'string', 'minLength' => 1]]
    ])
]));
write_json($outDir . '/broken-auth.json', broken_auth_spec());
write_json($outDir . '/broken-negative-rejection.json', broken_negative_rejection_spec());
write_json($outDir . '/broken-positive-acceptance.json', broken_positive_acceptance_spec());
write_json($outDir . '/broken-unsupported-method.json', drift_spec('Broken Unsupported Method', '/random/allow-drift', [
    '200' => json_response(object_schema(['ok'], ['ok' => ['type' => 'boolean']]))
]));
write_json($outDir . '/broken-stateful-availability.json', broken_stateful_availability_spec());
write_json($outDir . '/broken-use-after-free.json', broken_use_after_free_spec());
write_json($benchmarkDir . '/throw-the-book.json', throw_the_book_suite());

echo "Generated random OpenAPI corpus in {$outDir}\n";
echo "Generated benchmark suite {$benchmarkDir}/throw-the-book.json\n";

function write_json(string $path, array $data): void {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

function seeded_title(string $base): string {
    static $words = ['Atlas', 'Beacon', 'Cipher', 'Delta', 'Ember', 'Flux', 'Grove', 'Helix', 'Ion', 'Juno'];
    return $base . ' ' . $words[mt_rand(0, count($words) - 1)] . ' ' . mt_rand(100, 999);
}

function api_spec(string $title, array $paths, array $components = [], array $security = []): array {
    $spec = [
        'openapi' => '3.0.3',
        'info' => ['title' => seeded_title($title), 'version' => '1.0.0'],
        'servers' => [['url' => 'http://127.0.0.1:8089']],
        'paths' => $paths
    ];
    if ($components) $spec['components'] = $components;
    if ($security) $spec['security'] = $security;
    return $spec;
}

function ref_schema(string $name): array {
    return ['$ref' => '#/components/schemas/' . $name];
}

function object_schema(array $required, array $properties, bool $additional = false): array {
    return [
        'type' => 'object',
        'required' => $required,
        'properties' => $properties,
        'additionalProperties' => $additional
    ];
}

function json_response(array $schema, array $headers = [], string $description = 'OK'): array {
    $response = [
        'description' => $description,
        'content' => ['application/json' => ['schema' => $schema]]
    ];
    if ($headers) $response['headers'] = $headers;
    return $response;
}

function text_response(array $schema = ['type' => 'string']): array {
    return ['description' => 'Text', 'content' => ['text/plain' => ['schema' => $schema]]];
}

function binary_response(): array {
    return ['description' => 'Binary', 'content' => ['application/octet-stream' => ['schema' => ['type' => 'string', 'format' => 'binary']]]];
}

function empty_response(string $description = 'No content'): array {
    return ['description' => $description];
}

function error_response(string $description = 'Error'): array {
    return json_response(ref_schema('Error'), [], $description);
}

function common_components(): array {
    return [
        'securitySchemes' => [
            'ApiKeyAuth' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-API-Key']
        ],
        'schemas' => [
            'Error' => object_schema(['error'], ['error' => ['type' => 'string', 'minLength' => 1]]),
            'LineItem' => object_schema(['sku', 'quantity'], [
                'sku' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 24],
                'quantity' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10]
            ]),
            'OrderInput' => object_schema(['customerId', 'amount', 'currency', 'items'], [
                'customerId' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 24],
                'amount' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 1000],
                'currency' => ['type' => 'string', 'enum' => ['USD', 'EUR', 'GBP']],
                'items' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 3, 'items' => ref_schema('LineItem')]
            ]),
            'OrderCore' => object_schema(['customerId', 'amount', 'currency', 'items'], [
                'customerId' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 24],
                'amount' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 1000],
                'currency' => ['type' => 'string', 'enum' => ['USD', 'EUR', 'GBP']],
                'items' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 3, 'items' => ref_schema('LineItem')]
            ], true),
            'Order' => [
                'allOf' => [
                    ref_schema('OrderCore'),
                    object_schema(['id', 'status'], [
                        'id' => ['type' => 'string', 'minLength' => 1],
                        'status' => ['type' => 'string', 'enum' => ['created', 'paid', 'shipped', 'cancelled']]
                    ], true)
                ]
            ],
            'PatchOrderInput' => object_schema(['status'], [
                'status' => ['type' => 'string', 'enum' => ['created', 'paid', 'shipped', 'cancelled']]
            ]),
            'Product' => object_schema(['id', 'name', 'price', 'tags', 'stock'], [
                'id' => ['type' => 'string', 'minLength' => 1],
                'name' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 40],
                'price' => ['type' => 'number', 'minimum' => 0, 'maximum' => 10000],
                'tags' => ['type' => 'array', 'minItems' => 1, 'uniqueItems' => true, 'items' => ['type' => 'string']],
                'stock' => ['type' => 'integer', 'minimum' => 0],
                'discontinued' => ['type' => 'boolean']
            ]),
            'SearchResult' => object_schema(['total', 'results'], [
                'total' => ['type' => 'integer', 'minimum' => 0],
                'cursor' => ['type' => 'string', 'nullable' => true],
                'results' => ['type' => 'array', 'items' => ref_schema('Product')]
            ]),
            'HeaderEcho' => object_schema(['traceId', 'accepted'], [
                'traceId' => ['type' => 'string', 'minLength' => 1],
                'accepted' => ['type' => 'boolean']
            ]),
            'CardVariant' => object_schema(['type', 'cardNumber', 'last4'], [
                'type' => ['type' => 'string', 'enum' => ['card']],
                'cardNumber' => ['type' => 'string', 'minLength' => 12, 'maxLength' => 19],
                'last4' => ['type' => 'string', 'minLength' => 4, 'maxLength' => 4]
            ]),
            'BankVariant' => object_schema(['type', 'routingNumber'], [
                'type' => ['type' => 'string', 'enum' => ['bank']],
                'routingNumber' => ['type' => 'string', 'minLength' => 9, 'maxLength' => 9]
            ]),
            'Profile' => object_schema(['id', 'email', 'role'], [
                'id' => ['type' => 'string', 'minLength' => 1],
                'email' => ['type' => 'string', 'format' => 'email'],
                'role' => ['type' => 'string', 'enum' => ['admin', 'member']]
            ])
        ]
    ];
}

function working_core_spec(): array {
    $components = common_components();
    $orderExample = [
        'customerId' => 'cust_123',
        'amount' => 125,
        'currency' => 'USD',
        'items' => [['sku' => 'sku_1', 'quantity' => 2]]
    ];

    return api_spec('Working Random Core', [
        '/random/status' => [
            'get' => [
                'operationId' => 'getRandomStatus',
                'tags' => ['random', 'working'],
                'responses' => [
                    '200' => json_response(object_schema(['status', 'code', 'active'], [
                        'status' => ['type' => 'string', 'enum' => ['ok']],
                        'code' => ['type' => 'integer', 'minimum' => 200, 'maximum' => 299],
                        'active' => ['type' => 'boolean']
                    ]), ['X-Fixture-Count' => ['required' => true, 'schema' => ['type' => 'integer', 'minimum' => 1]]])
                ]
            ]
        ],
        '/random/search' => [
            'get' => [
                'operationId' => 'searchRandomCatalog',
                'tags' => ['random', 'working'],
                'parameters' => [
                    ['name' => 'q', 'in' => 'query', 'required' => true, 'example' => 'atlas', 'schema' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 20]],
                    ['name' => 'limit', 'in' => 'query', 'required' => true, 'example' => 2, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50]],
                    ['name' => 'tags', 'in' => 'query', 'required' => false, 'style' => 'pipeDelimited', 'explode' => false, 'example' => ['outerwear', 'featured'], 'schema' => ['type' => 'array', 'items' => ['type' => 'string']]]
                ],
                'responses' => ['200' => json_response(ref_schema('SearchResult')), '422' => error_response('Invalid query')]
            ]
        ],
        '/random/products/{productId}' => [
            'parameters' => [
                ['name' => 'productId', 'in' => 'path', 'required' => true, 'example' => 'prod_42', 'schema' => ['type' => 'string', 'minLength' => 1]]
            ],
            'get' => [
                'operationId' => 'getRandomProduct',
                'tags' => ['random', 'working'],
                'responses' => ['200' => json_response(ref_schema('Product')), '404' => error_response('Not found')]
            ]
        ],
        '/random/orders' => [
            'post' => [
                'operationId' => 'createRandomOrder',
                'tags' => ['random', 'working', 'stateful'],
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ref_schema('OrderInput'), 'example' => $orderExample]]],
                'responses' => [
                    '201' => [
                        'description' => 'Created',
                        'headers' => ['Location' => ['required' => true, 'schema' => ['type' => 'string', 'minLength' => 1]]],
                        'content' => ['application/json' => ['schema' => ref_schema('Order')]],
                        'links' => [
                            'GetOrder' => ['operationId' => 'getRandomOrder', 'parameters' => ['orderId' => '$response.body#/id']]
                        ]
                    ],
                    '422' => error_response('Invalid order')
                ]
            ]
        ],
        '/random/orders/{orderId}' => [
            'parameters' => [
                ['name' => 'orderId', 'in' => 'path', 'required' => true, 'example' => 'order_1', 'schema' => ['type' => 'string', 'minLength' => 1]]
            ],
            'get' => [
                'operationId' => 'getRandomOrder',
                'tags' => ['random', 'working', 'stateful'],
                'responses' => ['200' => json_response(ref_schema('Order')), '404' => error_response('Not found')]
            ],
            'patch' => [
                'operationId' => 'patchRandomOrder',
                'tags' => ['random', 'working'],
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ref_schema('PatchOrderInput'), 'example' => ['status' => 'paid']]]],
                'responses' => ['200' => json_response(ref_schema('Order')), '404' => error_response('Not found'), '422' => error_response('Invalid status')]
            ],
            'delete' => [
                'operationId' => 'deleteRandomOrder',
                'tags' => ['random', 'working', 'stateful'],
                'responses' => ['204' => empty_response('Deleted'), '404' => error_response('Not found')]
            ]
        ],
        '/random/text' => [
            'post' => [
                'operationId' => 'postRandomText',
                'tags' => ['random', 'working'],
                'requestBody' => ['required' => true, 'content' => ['text/plain' => ['schema' => ['type' => 'string', 'minLength' => 1], 'example' => 'hello']]],
                'responses' => ['200' => text_response(['type' => 'string', 'minLength' => 1]), '422' => error_response('Empty text')]
            ]
        ],
        '/random/blob' => [
            'get' => [
                'operationId' => 'getRandomBlob',
                'tags' => ['random', 'working'],
                'responses' => ['200' => binary_response()]
            ]
        ],
        '/random/headers' => [
            'get' => [
                'operationId' => 'getRandomHeaders',
                'tags' => ['random', 'working'],
                'parameters' => [
                    ['name' => 'X-Trace-Id', 'in' => 'header', 'required' => true, 'example' => 'trace-123', 'schema' => ['type' => 'string', 'minLength' => 1]]
                ],
                'responses' => [
                    '200' => json_response(ref_schema('HeaderEcho'), [
                        'X-Fixture-Count' => ['required' => true, 'schema' => ['type' => 'integer', 'minimum' => 1]],
                        'X-Fixture-Mode' => ['required' => true, 'schema' => ['type' => 'string', 'enum' => ['pass']]]
                    ]),
                    '400' => error_response('Missing header')
                ]
            ]
        ],
        '/random/polymorphic' => [
            'get' => [
                'operationId' => 'getRandomPolymorphic',
                'tags' => ['random', 'working'],
                'responses' => ['200' => json_response(['oneOf' => [ref_schema('CardVariant'), ref_schema('BankVariant')]])]
            ]
        ],
        '/random/secure/profile' => [
            'get' => [
                'operationId' => 'getRandomSecureProfile',
                'tags' => ['random', 'working', 'auth'],
                'security' => [['ApiKeyAuth' => []]],
                'responses' => ['200' => json_response(ref_schema('Profile')), '401' => error_response('Unauthorized')]
            ]
        ]
    ], $components);
}

function working_fuzz_spec(): array {
    $components = common_components();
    return api_spec('Working Random Fuzz', [
        '/random/search' => [
            'get' => [
                'operationId' => 'searchRandomCatalog',
                'parameters' => [
                    ['name' => 'q', 'in' => 'query', 'required' => true, 'example' => 'atlas', 'schema' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 20]],
                    ['name' => 'limit', 'in' => 'query', 'required' => true, 'example' => 2, 'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50]]
                ],
                'responses' => ['200' => json_response(ref_schema('SearchResult')), '422' => error_response('Invalid query')]
            ]
        ],
        '/random/orders' => [
            'post' => [
                'operationId' => 'createRandomOrder',
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ref_schema('OrderInput')]]],
                'responses' => [
                    '201' => [
                        'description' => 'Created',
                        'headers' => ['Location' => ['required' => true, 'schema' => ['type' => 'string', 'minLength' => 1]]],
                        'content' => ['application/json' => ['schema' => ref_schema('Order')]]
                    ],
                    '422' => error_response('Invalid order')
                ]
            ]
        ]
    ], $components);
}

function working_stateful_spec(): array {
    $components = common_components();
    $orderExample = [
        'customerId' => 'cust_123',
        'amount' => 125,
        'currency' => 'USD',
        'items' => [['sku' => 'sku_1', 'quantity' => 2]]
    ];
    return api_spec('Working Random Stateful', [
        '/random/orders' => [
            'post' => [
                'operationId' => 'createRandomOrder',
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => ref_schema('OrderInput'), 'example' => $orderExample]]],
                'responses' => [
                    '201' => [
                        'description' => 'Created',
                        'headers' => ['Location' => ['required' => true, 'schema' => ['type' => 'string', 'minLength' => 1]]],
                        'content' => ['application/json' => ['schema' => ref_schema('Order')]],
                        'links' => ['GetOrder' => ['operationId' => 'getRandomOrder', 'parameters' => ['orderId' => '$response.body#/id']]]
                    ],
                    '422' => error_response('Invalid order')
                ]
            ]
        ],
        '/random/orders/{orderId}' => [
            'parameters' => [
                ['name' => 'orderId', 'in' => 'path', 'required' => true, 'example' => 'order_1', 'schema' => ['type' => 'string', 'minLength' => 1]]
            ],
            'get' => [
                'operationId' => 'getRandomOrder',
                'responses' => ['200' => json_response(ref_schema('Order')), '404' => error_response('Not found')]
            ],
            'delete' => [
                'operationId' => 'deleteRandomOrder',
                'responses' => ['204' => empty_response('Deleted'), '404' => error_response('Not found')]
            ]
        ]
    ], $components);
}

function drift_spec(string $title, string $path, array $responses): array {
    return api_spec($title, [
        $path => [
            'get' => [
                'operationId' => 'get' . preg_replace('/[^A-Za-z0-9]/', '', ucwords($path, '/-')),
                'responses' => $responses
            ]
        ]
    ], common_components());
}

function broken_auth_spec(): array {
    return api_spec('Broken Auth Declaration', [
        '/random/public-profile' => [
            'get' => [
                'operationId' => 'getPublicProfileWithDeclaredAuth',
                'security' => [['ApiKeyAuth' => []]],
                'responses' => ['200' => json_response(ref_schema('Profile')), '401' => error_response('Unauthorized')]
            ]
        ]
    ], common_components());
}

function broken_negative_rejection_spec(): array {
    return api_spec('Broken Negative Rejection', [
        '/random/lenient' => [
            'post' => [
                'operationId' => 'postLenientPayload',
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => object_schema(['name'], ['name' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 20]])]]],
                'responses' => ['200' => json_response(object_schema(['accepted'], ['accepted' => ['type' => 'boolean']])), '422' => error_response('Invalid payload')]
            ]
        ]
    ], common_components());
}

function broken_positive_acceptance_spec(): array {
    return api_spec('Broken Positive Acceptance', [
        '/random/reject-valid' => [
            'post' => [
                'operationId' => 'postRejectValidPayload',
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => object_schema(['name'], ['name' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 20]]), 'example' => ['name' => 'valid']]]],
                'responses' => ['200' => json_response(object_schema(['accepted'], ['accepted' => ['type' => 'boolean']])), '400' => error_response('Rejected')]
            ]
        ]
    ], common_components());
}

function broken_stateful_availability_spec(): array {
    return api_spec('Broken Stateful Availability', [
        '/random-broken/resources' => [
            'post' => [
                'operationId' => 'createBrokenResource',
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => object_schema(['name'], ['name' => ['type' => 'string', 'minLength' => 1]]), 'example' => ['name' => 'resource']]]],
                'responses' => [
                    '201' => [
                        'description' => 'Created',
                        'headers' => ['Location' => ['required' => true, 'schema' => ['type' => 'string']]],
                        'content' => ['application/json' => ['schema' => object_schema(['id', 'name'], ['id' => ['type' => 'string'], 'name' => ['type' => 'string']])]],
                        'links' => ['GetBrokenResource' => ['operationId' => 'getBrokenResource', 'parameters' => ['resourceId' => '$response.body#/id']]]
                    ]
                ]
            ]
        ],
        '/random-broken/resources/{resourceId}' => [
            'parameters' => [
                ['name' => 'resourceId', 'in' => 'path', 'required' => true, 'example' => 'res_1', 'schema' => ['type' => 'string', 'minLength' => 1]]
            ],
            'get' => [
                'operationId' => 'getBrokenResource',
                'responses' => ['200' => json_response(object_schema(['id', 'name'], ['id' => ['type' => 'string'], 'name' => ['type' => 'string']])), '404' => error_response('Not found')]
            ]
        ]
    ], common_components());
}

function broken_use_after_free_spec(): array {
    $resourceSchema = object_schema(['id', 'name'], ['id' => ['type' => 'string'], 'name' => ['type' => 'string']]);
    return api_spec('Broken Use After Free', [
        '/random-broken/sticky-resources' => [
            'post' => [
                'operationId' => 'createStickyResource',
                'requestBody' => ['required' => true, 'content' => ['application/json' => ['schema' => object_schema(['name'], ['name' => ['type' => 'string', 'minLength' => 1]]), 'example' => ['name' => 'sticky']]]],
                'responses' => [
                    '201' => [
                        'description' => 'Created',
                        'headers' => ['Location' => ['required' => true, 'schema' => ['type' => 'string']]],
                        'content' => ['application/json' => ['schema' => $resourceSchema]],
                        'links' => ['GetStickyResource' => ['operationId' => 'getStickyResource', 'parameters' => ['resourceId' => '$response.body#/id']]]
                    ]
                ]
            ]
        ],
        '/random-broken/sticky-resources/{resourceId}' => [
            'parameters' => [
                ['name' => 'resourceId', 'in' => 'path', 'required' => true, 'example' => 'sticky_1', 'schema' => ['type' => 'string', 'minLength' => 1]]
            ],
            'get' => [
                'operationId' => 'getStickyResource',
                'responses' => ['200' => json_response($resourceSchema), '404' => error_response('Not found')]
            ],
            'delete' => [
                'operationId' => 'deleteStickyResource',
                'responses' => ['204' => empty_response('Deleted'), '404' => error_response('Not found')]
            ]
        ]
    ], common_components());
}

function throw_the_book_suite(): array {
    $defaults = [
        'seed' => 20260721,
        'max-examples' => 2,
        'request-timeout' => 5,
        'report' => 'ndjson,junit,har,vcr',
        'report-dir' => '/tmp/openapi-contract-throw-book-reports',
        'cache-dir' => '/tmp/openapi-contract-throw-book-cache'
    ];
    return [
        'name' => 'throw-the-book-random-openapi-corpus',
        'base-url' => 'http://127.0.0.1:8089',
        'defaults' => $defaults,
        'cases' => [
            pass_case('random-core-all-checks', 'working-random-core.json', ['phases' => 'examples,coverage,fuzzing', 'checks' => 'all', 'auth-api-key' => 'ApiKeyAuth:secret']),
            pass_case('random-negative-fuzzing', 'working-random-fuzz.json', ['mode' => 'all', 'checks' => 'all', 'max-examples' => 3], 'fuzz'),
            pass_case('random-stateful-clean', 'working-random-stateful.json', ['phases' => 'stateful', 'checks' => 'ensure_resource_availability,use_after_free', 'max-examples' => 2]),
            fail_case('broken-undocumented-status', 'broken-status.json', ['phases' => 'examples', 'checks' => 'status_code_conformance']),
            fail_case('broken-content-type', 'broken-content-type.json', ['phases' => 'examples', 'checks' => 'content_type_conformance']),
            fail_case('broken-response-schema', 'broken-schema.json', ['phases' => 'examples', 'checks' => 'response_schema_conformance']),
            fail_case('broken-required-header', 'broken-header.json', ['phases' => 'examples', 'checks' => 'response_headers_conformance']),
            fail_case('broken-ignored-auth', 'broken-auth.json', ['phases' => 'coverage', 'checks' => 'ignored_auth', 'auth-api-key' => 'ApiKeyAuth:secret']),
            fail_case('broken-negative-rejection', 'broken-negative-rejection.json', ['mode' => 'negative', 'checks' => 'negative_data_rejection'], 'fuzz'),
            fail_case('broken-positive-acceptance', 'broken-positive-acceptance.json', ['phases' => 'examples', 'checks' => 'positive_data_acceptance']),
            fail_case('broken-unsupported-method', 'broken-unsupported-method.json', ['phases' => 'coverage', 'checks' => 'unsupported_method']),
            fail_case('broken-resource-availability', 'broken-stateful-availability.json', ['phases' => 'stateful', 'checks' => 'ensure_resource_availability']),
            fail_case('broken-use-after-free', 'broken-use-after-free.json', ['phases' => 'stateful', 'checks' => 'ensure_resource_availability,use_after_free'])
        ]
    ];
}

function pass_case(string $name, string $schema, array $options, string $command = 'run'): array {
    return [
        'name' => $name,
        'command' => $command,
        'schema' => 'tests/fixtures/random-openapi/' . $schema,
        'url' => '{base_url}',
        'options' => $options,
        'expected_exit_code' => 0,
        'expected_failures' => 0
    ];
}

function fail_case(string $name, string $schema, array $options, string $command = 'run'): array {
    $options['max-failures'] = 1;
    return [
        'name' => $name,
        'command' => $command,
        'schema' => 'tests/fixtures/random-openapi/' . $schema,
        'url' => '{base_url}',
        'options' => $options,
        'expected_exit_code' => 1,
        'expected_failures' => 1
    ];
}
