<?php
return [
    "handler" => Albatiqy\Slimlibs\Command\Commands\Telegram::class,
    "options" => [
        "help" => "Telegram tools",
        "args" => [
            ],
        "opts" => [
            ],
        "commands" => [
            "initbotcommands" => [
                "help" => "Inisialisasi bot commands",
                "name" => "initCommands",
                "opts" => [
                    ],
                "args" => [
                    ]
                ],
            "users" => [
                "help" => "Menampilkan user tersedia",
                "name" => "listUsers",
                "opts" => [
                    ],
                "args" => [
                    ]
                ],
            "msgchannel" => [
                "help" => "Mengirim pesan ke channel",
                "name" => "sendChannel",
                "opts" => [
                    ],
                "args" => [
                    [
                        "arg" => "text",
                        "help" => "teks yang akan dikirim",
                        "required" => true
                        ]
                    ]
                ],
            "msguser" => [
                "help" => "Mengirim pesan ke user",
                "name" => "sendUser",
                "opts" => [
                    "username" => [
                        "name" => "username",
                        "help" => "username tujuan",
                        "short" => "u",
                        "arg" => "username"
                        ]
                    ],
                "args" => [
                    [
                        "arg" => "text",
                        "help" => "teks yang akan dikirim",
                        "required" => true
                        ]
                    ]
                ]
            ]
        ]
];