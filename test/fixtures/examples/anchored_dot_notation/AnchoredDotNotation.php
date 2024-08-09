<?php

class AnchoredDotNotation
{
    public $genres = [
        [
            'name'      => 'Punk',
            'subgenres' => [
                [
                    'name'      => 'Hardcore',
                    'subgenres' => [
                        [
                            'name'      => 'First wave of black metal',
                            'subgenres' => [
                                ['name' => 'Norwegian black metal'],
                                [
                                    'name'      => 'Death metal',
                                    'subgenres' => [
                                        [
                                            'name'      => 'Swedish death metal',
                                            'subgenres' => [
                                                ['name' => 'New wave of American metal'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name'      => 'Thrash metal',
                            'subgenres' => [
                                ['name' => 'Grindcore'],
                                [
                                    'name'      => 'Metalcore',
                                    'subgenres' => [
                                        ['name' => 'Nu metal'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}
