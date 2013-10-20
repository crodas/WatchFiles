<?php

namespace {{$ns}};

function get_list() {
    return array(
        'files' => array(
        @foreach ($files as $path => $ttl)
            @if (!empty($prefix)) 
            "{{{$prefix}}}" . "{{{$path}}}",
            @else
            __DIR__ . "{{{$path}}}",
            @end
        @end
        ),
        'dirs' => array(
        @foreach ($dirs as $path => $ttl)
            @if (!empty($prefix)) 
            "{{{$prefix}}}" . "{{{$path}}}",
            @else
            __DIR__ . "{{{$path}}}",
            @end
        @end
        ),
        'glob' => array(
            @foreach ($globs as $glob)
                "{{{$glob}}}",
            @end
        )
    );
}

function get_watched_files() {
    return {{var_export($input, true) }};
}

function has_changed()
{
    @if (!empty($prefix))
        @set($DIR, var_export($prefix, true))
    @else
        @set($DIR, "__DIR__");
    @end

    @foreach ($dirs as $path => $ts)
    if (!is_dir({{$DIR}} . "{{{$path}}}") || filemtime({{$DIR}} . "{{{$path}}}") > {{$ts}}) {
        return {{$DIR}} . "{{{$path}}}";
    }
    @end

    @foreach ($files as $path => $ts)
    if (!is_file({{$DIR}} . "{{{$path}}}") || filemtime({{$DIR}} . "{{{$path}}}") > {{$ts}}) {
        return {{$DIR}} . "{{{$path}}}";
    }
    @end

    return false;
}
