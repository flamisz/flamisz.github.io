<?php

return [
    'production' => false,
    'baseUrl' => '',
    'title' => '314 Blog Drive',
    'description' => 'Zoltan\'s page',
    'collections' => [
        'posts' => [
            'path' => '{filename}-{date|Y-m-d}',
            'author' => 'Zoltan',
            'sort' => ['-date'],
            'excerpt' => function ($page, $characters = 50) {
                return substr(strip_tags($page->getContent()), 0, $characters);
            },
        ],
    ],
];
