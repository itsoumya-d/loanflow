<?php

declare(strict_types=1);

return [
    'reference_prefix' => env('LOANFLOW_REFERENCE_PREFIX', 'LF'),

    'limits' => [
        'min_loan_amount_cents' => (int) env('LOANFLOW_MIN_LOAN_AMOUNT_CENTS', 100_000),
        'max_loan_amount_cents' => (int) env('LOANFLOW_MAX_LOAN_AMOUNT_CENTS', 200_000_000),
        'max_annual_income_cents' => (int) env('LOANFLOW_MAX_ANNUAL_INCOME_CENTS', 1_000_000_000),
    ],

    'idempotency' => [
        'ttl_hours' => (int) env('LOANFLOW_IDEMPOTENCY_TTL_HOURS', 72),
        'header' => 'Idempotency-Key',
    ],

    'keap' => [
        'base_url' => env('KEAP_BASE_URL', 'https://api.infusionsoft.com'),
        'token_url' => env('KEAP_TOKEN_URL', 'https://api.infusionsoft.com/token'),
        'client_id' => env('KEAP_CLIENT_ID'),
        'client_secret' => env('KEAP_CLIENT_SECRET'),
        'refresh_token' => env('KEAP_REFRESH_TOKEN'),
        'webhook_secret' => env('KEAP_WEBHOOK_SECRET'),
        'webhook_signature_header' => env('KEAP_WEBHOOK_SIGNATURE_HEADER', 'X-Keap-Signature'),
        'timeout_seconds' => (int) env('KEAP_TIMEOUT_SECONDS', 10),
        'retry_attempts' => (int) env('KEAP_RETRY_ATTEMPTS', 3),
        'retry_base_ms' => (int) env('KEAP_RETRY_BASE_MS', 200),
        'custom_fields' => [
            'loan_reference' => env('KEAP_CUSTOM_FIELD_LOAN_REFERENCE'),
            'loan_amount' => env('KEAP_CUSTOM_FIELD_LOAN_AMOUNT'),
            'loan_status' => env('KEAP_CUSTOM_FIELD_LOAN_STATUS'),
        ],
    ],

    'bigquery' => [
        'project_id' => env('BIGQUERY_PROJECT_ID'),
        'dataset' => env('BIGQUERY_DATASET', 'loanflow_analytics'),
        'table' => env('BIGQUERY_TABLE', 'loan_events'),
        'service_account_json' => env('BIGQUERY_SERVICE_ACCOUNT_JSON'),
        'service_account_path' => env('BIGQUERY_SERVICE_ACCOUNT_PATH'),
        'token_url' => env('BIGQUERY_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'api_url' => env('BIGQUERY_API_URL', 'https://bigquery.googleapis.com/bigquery/v2'),
        'scope' => 'https://www.googleapis.com/auth/bigquery.insertdata',
        'timeout_seconds' => (int) env('BIGQUERY_TIMEOUT_SECONDS', 15),
    ],

    'gtm' => [
        'server_url' => env('GTM_SERVER_URL'),
        'forward_secret' => env('GTM_FORWARD_SECRET'),
        'ingest_secret' => env('GTM_INGEST_SECRET'),
        'signature_header' => 'X-Loanflow-Signature',
        'timeout_seconds' => (int) env('GTM_TIMEOUT_SECONDS', 10),
        'allowed_events' => [
            'loan_application_viewed',
            'loan_application_started',
            'loan_application_submitted',
            'loan_application_validation_failed',
            'loan_application_approved',
            'loan_application_declined',
        ],
    ],

    'metrics' => [
        'token' => env('LOANFLOW_METRICS_TOKEN'),
    ],
];
