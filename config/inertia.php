<?php

return array_replace_recursive(
    require base_path('vendor/inertiajs/inertia-laravel/config/inertia.php'),
    [
        'pages' => [
            'paths' => [
                resource_path('js/Pages'),
            ],
        ],
    ]
);
