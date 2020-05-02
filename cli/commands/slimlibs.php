<?php
return [
    "handler" => Albatiqy\Slimlibs\Command\Commands\Slimlibs::class,
    "options" => [
        "help" => "Slimlibs tools",
        "args" => [
            ],
        "opts" => [
            ],
        "commands" => [
            "initvar" => [
                "help" => "Inisialisasi var directory",
                "name" => "initVar",
                "opts" => [
                    ],
                "args" => [
                    ]
                ],
            "initauth" => [
                "help" => "Inisialisasi autentikasi dan otorisasi",
                "name" => "initAuth",
                "opts" => [
                    ],
                "args" => [
                    ]
                ],
            "initschedules" => [
                "help" => "Inisialisasi schedule",
                "name" => "initSchedules",
                "opts" => [
                    ],
                "args" => [
                    ]
                ]
            ]
        ]
];