<?php
return [
    "handler" => Albatiqy\Slimlibs\Command\Commands\Builder::class,
    "options" => [
        "help" => "Builder Playground",
        "args" => [
            ],
        "opts" => [
            ],
        "commands" => [
            "describe" => [
                "help" => "Memproses antrian job background",
                "name" => "tableDescribe",
                "opts" => [
                    ],
                "args" => [
                    [
                        "arg" => "table",
                        "help" => "nama tabel",
                        "required" => true
                        ]
                    ]
                ],
            "service" => [
                "help" => "Service generator",
                "name" => "createService",
                "opts" => [
                    "svcname" => [
                        "name" => "svcname",
                        "help" => "nama kelas",
                        "short" => "s",
                        "arg" => "svcname"
                        ]
                    ],
                "args" => [
                    [
                        "arg" => "tabel",
                        "help" => "tabel db",
                        "required" => true
                        ]
                    ]
                ],
            "restapi" => [
                "help" => "Rest API generator",
                "name" => "createRestApi",
                "opts" => [
                    "primary_key" => [
                        "name" => "primary_key",
                        "help" => "primary key",
                        "short" => "p",
                        "arg" => "primary_key"
                        ]
                    ],
                "args" => [
                    [
                        "arg" => "service",
                        "help" => "kelas Service",
                        "required" => true
                        ]
                    ]
                ]
            ]
        ]
];