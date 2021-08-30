<?php

return [
    'modules'    => [

    ],

    // searchable
    'searchable' => [
        'modules' => [

        ],
        'driver'  => 'spatie',
        'limit'   => '1000',
    ],

    // daily, weekly, monthly
    'clear_time' => [
        'reports'       => 'monthly',
        'attachments'   => 'monthly',
        'notifications' => 'monthly',
    ]
];
