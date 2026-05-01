<?php
declare(strict_types=1);

function project_root(): string
{
    return dirname(__DIR__);
}

function storage_path(): string
{
    return project_root() . '/storage/data.json';
}

function default_data(): array
{
    return [
        'semester' => [
            'name' => '',
            'start_date' => '',
            'end_date' => '',
        ],
        'courses' => [],
        'events' => [],
        'categories' => [
            'assignment',
            'quiz',
            'project',
            'exam',
        ],
    ];
}

function load_data(): array
{
    $path = storage_path();

    if (!file_exists($path)) {
        return default_data();
    }

    $json = file_get_contents($path);
    if ($json === false) {
        return default_data();
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return default_data();
    }

    return array_replace_recursive(default_data(), $data);
}

function save_data(array $data): bool
{
    $path = storage_path();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return false;
    }

    return file_put_contents($path, $json) !== false;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function normalize_slug(string $value): string
{
    $value = trim($value);

    if ($value === '') {
        return '';
    }

    $value = strtolower($value);
    $value = preg_replace('/\s+/', '-', $value);
    $value = preg_replace('/[^a-z0-9\-]/', '', $value);

    return trim((string) $value, '-');
}

function semester_progress(array $semester): int
{
    $start = strtotime($semester['start_date'] ?? '');
    $end = strtotime($semester['end_date'] ?? '');
    $today = strtotime(date('Y-m-d'));

    if (!$start || !$end || $end <= $start) {
        return 0;
    }

    if ($today <= $start) {
        return 0;
    }

    if ($today >= $end) {
        return 100;
    }

    return (int) round((($today - $start) / ($end - $start)) * 100);
}

function sort_events(array $events): array
{
    usort($events, function (array $a, array $b): int {
        return strcmp($a['date'] ?? '', $b['date'] ?? '');
    });

    return $events;
}

function upcoming_events(array $events, int $limit = 8): array
{
    $today = date('Y-m-d');

    $filtered = array_values(array_filter($events, function (array $event) use ($today): bool {
        return ($event['date'] ?? '') >= $today && empty($event['done']);
    }));

    return array_slice(sort_events($filtered), 0, $limit);
}

function find_event_by_id(array $events, string $id): ?array
{
    foreach ($events as $event) {
        if (($event['id'] ?? '') === $id) {
            return $event;
        }
    }

    return null;
}

function run_display_refresh(): array
{
    $root = project_root();
   $candidates = [
    $root . '/display/update_display.py',
    $root . '/display_calendar.py',
    $root . '/display/display_calendar.py',
];

    $script = '';
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $script = $candidate;
            break;
        }
    }

    if ($script === '') {
        return [
            'ok' => false,
            'message' => 'No display script found. Checked: ' . implode(', ', $candidates),
        ];
    }

    $python = trim((string) @shell_exec('command -v python3 2>/dev/null'));
    if ($python === '') {
        $python = '/usr/bin/python3';
    }

    if (!file_exists($python)) {
        return [
            'ok' => false,
            'message' => 'python3 not found at: ' . $python,
        ];
    }

    if (!function_exists('exec')) {
        return [
            'ok' => false,
            'message' => 'PHP exec() is unavailable.',
        ];
    }

    $command = escapeshellarg($python) . ' ' . escapeshellarg($script) . ' 2>&1';
    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);

    $message = trim(implode("\n", $output));
    if ($message === '') {
        $message = 'Exit code: ' . $exitCode;
    }

    return [
        'ok' => $exitCode === 0,
        'message' => $message,
        'script' => $script,
        'python' => $python,
    ];
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}
