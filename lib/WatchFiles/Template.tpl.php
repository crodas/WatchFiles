<?php

namespace __ns__;

function get_list() {
    return array(
        'files' => array(
            #* foreach ($files as $file => $ttl)
            __DIR__ . __@file__,
            #* end
        ),
        'dirs' => array(
            #* foreach ($dirs as $dir => $ttl)
            __DIR__ . __@dir__,
            #* end
        ),
        'glob' => array(
            #* foreach ($globs as $glob)
             __@glob__,
            #* end
        )
    );
}

function get_watched_files() {
    return __@input__;
}

function has_changed()
{
    #* foreach ($dirs as $dir => $ts)
    if (!is_dir(__DIR__ . __@dir__) || filemtime(__DIR__ . __@dir__) > __@ts__) {
        return __DIR__ .  __@dir__;
    }
    #* end

    #* foreach ($files as $file => $ts)
    if (!is_file(__DIR__ . __@file__) || filemtime(__DIR__ . __@file__) > __@ts__) {
        return __DIR__ .  __@file__;
    }
    #* end

    return false;
}
