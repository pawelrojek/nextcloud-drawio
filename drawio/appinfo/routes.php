<?php

return [
    "routes" => [
       ["name" => "editor#index", "url" => "/{fileId}", "verb" => "GET"],

       ["name" => "settings#settings", "url" => "/ajax/settings", "verb" => "POST"],
       ["name" => "settings#getsettings", "url" => "/ajax/settings", "verb" => "GET"],
    ]
];
