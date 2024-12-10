<?php
/**
 * H5P Plugin.
 *
 * @package   H5P
 * @author    Joubel <contact@joubel.com>
 * @license   MIT
 * @link      http://joubel.com
 * @copyright 2018 Joubel
 */
trait H5PUtils {
    /**
     * Filters and sanitize input replacing FILTER_SANITIZE_STRING
     * @param string $var_name Name of the variable to sanitize
     * @return string
     */
    public function sanitize_input($var_name): string
    {
        $var_name = filter_input(INPUT_GET, $var_name);
        return htmlspecialchars($var_name ?? '', ENT_QUOTES, 'UTF-8');
    }
}