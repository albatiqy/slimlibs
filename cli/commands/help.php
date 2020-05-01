<?php
return [
    "handler" => Albatiqy\Slimlibs\Command\Commands\Help::class,
    "options" => [
        "help" => "Manual Slimlibs Cli",
        "args" => [
            ],
        "opts" => [
            "path" => [
                "name" => "filter",
                "help" => "filter lokasi scanning nilai \"app\" atau \"libs\"",
                "short" => "p",
                "arg" => "strpath"
                ]
            ],
        "commands" => [
            "class" => [
                "help" => "Menampilkan kelas tersedia untuk dimapping",
                "name" => "listClass",
                "opts" => [
                    ],
                "args" => [
                    ]
                ]
            ]
        ]
];