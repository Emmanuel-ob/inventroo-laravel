<?php

return  [

    // Base url to api
    'api' => [
        'base' => config('app.url').'/api',
    ],
    // hours before activation expires
    'activation_ttl' => 2,
    'frontend_base_url' => 'https://inventroo.com/',
    'mail_from' => 'transactions@inventroo.com',
    
     //Transaction status
    'transaction' => [
        'statusArray' => ['Canceled' => 0, 'Pending' => 1, 'In Progress' => 2, 'Completed' => 3, 'Disputed' => 4, 'Confirmed' => 5, 'Shipped' => 6, 'Paid' => 7, 'Delivered' => 8],
        'status' => ['Canceled', 'Pending', 'In Progress', 'Completed', 'Disputed', 'Confirmed', 'Shipped', 'Paid', 'Delivered'],
    ],

    // Time to live for cache.
    'cache' => [
        'ttl' => 60*24,
    ],

    'backend' => [
        'perpage' => 50,
        'concat'  => '_pepuser',
    ],
    'image' => [
        'quality' => 75,
        'allowed_extensions' => '.jpg , .jpeg',

        /**
         * Region that resize or fit function will focus on when cropping
         *
         * Possible values: top-left, top, top-right, left, center, right,
         * bottom-left, bottom, bottom-right
         */
        'focus' => 'top',
        'sizes' => [
            'mini_' => [25, 25],
            'small_' => [48, 48],
            'normal_' => [100, 100],
            'medium_' => [200, 200],
            'profile_' => [300, 300],
            'big_' => [400, 400],
        ],
        'max' => 1024,
        // convert the above kilobytes to MB. Will be displayed to users
        'MB' => 1,
    ],
    // avatar name and sizes
    'avatar' => [
        'sizes' => [
            'mini_' => [25, 25],
            'small_' => [48, 48],
            'normal_' => [100, 100],
            'medium_' => [200, 200],
            'profile_' => [300, 300],
            'big_' => [400, 400],
        ],
        'max' => 1024,
        // convert the above kilobytes to MB. Will be displayed to users
        'MB' => 1,
    ],

    'services' => [
        'phrase'  => 'dCNmzV]&B6[A',
        'song' => [
            'price' => 100,
        ],
        'subscription' => [
            'price' => 20,
        ],
        'api' => [
            'song_upload_code' => 5155,
            'vote_song_code'   => 54002,
            'short_code'   => 3133,
        ],

    ],

];
