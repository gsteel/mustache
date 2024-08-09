<?php

declare(strict_types=1);

class SectionsNested
{
    public $name = 'Little Mac';

    public function enemies()
    {
        return [
            [
                'name'    => 'Von Kaiser',
                'enemies' => [
                    ['name' => 'Super Macho Man'],
                    ['name' => 'Piston Honda'],
                    ['name' => 'Mr. Sandman'],
                ],
            ],
            [
                'name'    => 'Mike Tyson',
                'enemies' => [
                    ['name' => 'Soda Popinski'],
                    ['name' => 'King Hippo'],
                    ['name' => 'Great Tiger'],
                    ['name' => 'Glass Joe'],
                ],
            ],
            [
                'name'    => 'Don Flamenco',
                'enemies' => [
                    ['name' => 'Bald Bull'],
                ],
            ],
        ];
    }
}
