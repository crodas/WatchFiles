<?php

@set($ns, "Watcher\\r" . uniqid(true))

namespace {{$ns}};

class Watcher
{

function get_list() {
    return array(
        'files' => array(
        @foreach ($files as $path => $ttl)
            {{@$path}},
        @end
        ),
        'dirs' => array(
        @foreach ($dirs as $path => $ttl)
            {{@$path}},
        @end
        ),
        'glob' => array(
            @foreach ($globs as $glob)
                {{@$glob}},
            @end
        )
    );
}

function get_watched_files() {
    return {{var_export($input, true) }};
}

function has_changed()
{

    @foreach ($dirs as $path => $ts)
    if (!is_dir({{@$path}}) || filemtime({{@$path}}) > {{$ts}}) {
        return {{@$path}};
    }
    @end

    @foreach ($files as $path => $ts)
    if (!is_file({{@$path}}) || filemtime({{@$path}}) > {{$ts}}) {
        return {{@$path}};
    }
    @end

    return false;
}

}

return new Watcher;
