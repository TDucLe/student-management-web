<?php

function i18n_init(): void
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'], true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = 'vi';
    }
    $file = APP_ROOT . '/lang/' . $_SESSION['lang'] . '.php';
    $GLOBALS['__translations'] = file_exists($file) ? require $file : require APP_ROOT . '/lang/vi.php';
}

function lang(): string
{
    return $_SESSION['lang'] ?? 'vi';
}

function t(string $key, array $replace = []): string
{
    $text = $GLOBALS['__translations'][$key] ?? $key;
    foreach ($replace as $k => $v) {
        $text = str_replace(':' . $k, (string) $v, $text);
    }
    return $text;
}
