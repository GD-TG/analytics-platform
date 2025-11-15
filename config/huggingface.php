<?php

return [
    'huggingface' => [
        'api_key' => env('HUGGINGFACE_API_KEY', ''),
        'api_url' => env('HUGGINGFACE_API_URL', 'https://api-inference.huggingface.co/models'),
        'models' => [
            'text_generation' => 'gpt2', // Small model for local or lite inference
            'zero_shot_classification' => 'facebook/bart-large-mnli',
            'qa' => 'distilbert-base-cased-distilled-squad',
        ],
        'timeout' => 30,
    ],
];
