<?php
// Utility functions for the site
if (!function_exists('getPlaceholderBgColor')) {
    function getPlaceholderBgColor($category)
    {
        switch ($category) {
            case 'Đồ Nam':
                return '87CEEB';
            case 'Đồ Nữ':
                return 'FFB6C1';
            case 'Đồ Bé Trai':
                return '90EE90';
            case 'Đồ Bé Gái':
                return 'FFFFE0';
            default:
                return 'DEDEDE';
        }
    }
}
