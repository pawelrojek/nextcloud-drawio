<?php

return [
    "routes" => [
       ["name" => "editor#index", "url" => "/{fileId}", "verb" => "GET"],
       ["name" => "editor#load", "url" => "/ajax/load/{fileId}", "verb" => "GET"],
       ["name" => "editor#save", "url" => "/ajax/save/{fileId}", "verb" => "POST"],
       ["name" => "editor#create", "url" => "/ajax/new", "verb" => "POST"],

       ["name" => "settings#settings", "url" => "/ajax/settings", "verb" => "POST"],
       ["name" => "settings#formats", "url" => "/ajax/settings", "verb" => "GET"],
    ]
];
