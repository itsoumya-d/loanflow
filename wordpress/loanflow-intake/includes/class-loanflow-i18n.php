<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Loanflow_I18n
{
    public static function t(string $text): string
    {
        return function_exists('esc_html__')
            ? esc_html__($text, 'loanflow-intake')
            : $text;
    }

    public static function esc(string $text): string
    {
        return function_exists('esc_html')
            ? esc_html($text)
            : htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function attr(string $text): string
    {
        return function_exists('esc_attr')
            ? esc_attr($text)
            : htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(string $url): string
    {
        return function_exists('esc_url_raw')
            ? esc_url_raw($url)
            : (string) filter_var($url, FILTER_SANITIZE_URL);
    }
}
