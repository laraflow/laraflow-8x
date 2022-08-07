<?php

if (! function_exists('resource')) {
    /**
     * Generate an asset path for the application.
     *
     * @param  string  $path
     * @param  bool|null  $secure
     * @return string
     */
    function resource(string $path, $secure = null)
    {
        if (preg_match('/^(http|https|\/\/)/', $path) > 0) {
            return $path;
        }

        return app('url')->asset($path, $secure);
    }
}
