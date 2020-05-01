<?php
return [
    "handler" => Albatiqy\Slimlibs\Command\Commands\JobRunner::class,
    "options" => [
        "help" => "Manajemen job",
        "args" => [
            ],
        "opts" => [
            ],
        "commands" => [
            "list" => [
                "help" => "Menampilkan daftar job tersedia",
                "name" => "list",
                "opts" => [
                    ],
                "args" => [
                    ]
                ],
            "remap" => [
                "help" => "Remapping kelas job",
                "name" => "remap",
                "opts" => [
                    ],
                "args" => [
                    ]
                ],
            "serve" => [
                "help" => "Memproses antrian job background",
                "name" => "serve",
                "opts" => [
                    ],
                "args" => [
                    ]
                ],
            "run" => [
                "help" => "Memproses antrian job background",
                "name" => "run",
                "opts" => [
                    "taksname" => [
                        "name" => "jobname",
                        "help" => "nama job yang akan dieksekusi",
                        "short" => "t",
                        "arg" => "jobname"
                        ]
                    ],
                "args" => [
                    [
                        "arg" => "data",
                        "help" => "data berupa JSON",
                        "required" => true
                        ]
                    ]
                ]
            ]
        ]
];