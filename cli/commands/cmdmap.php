<?php
return [
    "handler" => Albatiqy\Slimlibs\Command\Commands\CommandMapper::class,
    "options" => [
        "help" => "Mapping kelas Slimlibs Cli",
        "args" => [
            [
                "arg" => "bin",
                "help" => "nama perintah",
                "required" => true
                ],
            [
                "arg" => "class",
                "help" => "kelas target",
                "required" => true
                ]
            ],
        "opts" => [
            ],
        "commands" => [
            ]
        ]
];