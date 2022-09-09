<?php

return [
    'production' => false,
    'baseUrl' => '',
    'title' => '314 Blog Drive',
    'description' => 'Zoltan\'s page',
    'collections' => [
        'posts' => [
            'path' => 'blog/{filename}-{date|Y-m-d}',
            'author' => 'Zoltan',
            'sort' => ['-date'],
        ],
    ],
];
