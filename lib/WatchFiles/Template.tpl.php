<?php

namespace __ns__;

function get_list() {
    return array(
        'files' => array(
        #* foreach ($files as $path => $ttl)
            #* if (!empty($prefix)) 
            __@prefix__ . __@path__,
            #* else
            __DIR__ . __@path__,
            #* end
        #* end
        ),
        'dirs' => array(
        #* foreach ($dirs as $path => $ttl)
            #* if (!empty($prefix)) 
            __@prefix__ . __@path__,
            #* else
            __DIR__ . __@path__,
            #* end
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
    #* if (!empty($prefix)) 
    # $DIR = @$prefix
    #end

    #* foreach ($dirs as $path => $ts)
    if (!is_dir(__DIR__ . __@path__) || filemtime(__DIR__ . __@path__) > __@ts__) {
        return __DIR__ . __@path__;
    }
    #* end

    #* foreach ($files as $path => $ts)
    if (!is_file(__DIR__ . __@path__) || filemtime(__DIR__ . __@path__) > __@ts__) {
        return __DIR__ . __@path__;
    }
    #* end

    return false;
}
