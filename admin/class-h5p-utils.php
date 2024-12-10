<?php
trait H5PUtils {
    /**
     * Filters and sanitizes input, replacing FILTER_SANITIZE_STRING.
     *
     * @param string $var_name Name of the variable to sanitize.
     * @return string Sanitized value.
     */
    public function sanitize_input($var_name): string
    {
        $var_name = filter_input(INPUT_GET, $var_name);
        return htmlspecialchars($var_name ?? '', ENT_QUOTES, 'UTF-8');
    }
}