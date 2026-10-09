<?php
declare(strict_types=1);

/** Escape anything a user typed before printing it. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Escape, then keep the member's line breaks. */
function e_multiline(?string $value): string
{
    return nl2br(e($value), false);
}

function redirect(string $to): void
{
    header("Location: {$to}");
    exit;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Session::csrfToken()) . '">';
}

/** Stops POST handlers that were reached without a valid form token. */
function require_post_with_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    if (!Session::verifyCsrf($_POST['csrf'] ?? null)) {
        http_response_code(419);
        exit('Your form expired. Go back, refresh the page, and try again.');
    }
}

/** Positive integer from an array (GET/POST), or 0. */
function int_param(array $source, string $key): int
{
    $value = filter_var($source[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? 0 : $value;
}

function str_param(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function format_date(string $datetime): string
{
    return date('M j, Y · g:i A', strtotime($datetime));
}

function time_label(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h . ' hr' . ($m ? ' ' . $m . ' min' : '');
}

function abort(int $code, string $message): void
{
    http_response_code($code);
    $pageTitle = (string) $code;
    require __DIR__ . '/../partials/header.php';
    echo '<section class="panel narrow center"><p class="eyebrow">Error ' . $code . '</p>'
       . '<h1>' . e($message) . '</h1>'
       . '<a class="btn" href="' . 'index.php' . '">Back to recipes</a></section>';
    require __DIR__ . '/../partials/footer.php';
    exit;
}

/** Keeps the current filters when building pagination links. */
function query_with(array $changes): string
{
    $params = array_merge($_GET, $changes);
    $params = array_filter($params, static fn ($v) => $v !== '' && $v !== null && $v !== 0);
    return $params ? '?' . http_build_query($params) : '';
}
