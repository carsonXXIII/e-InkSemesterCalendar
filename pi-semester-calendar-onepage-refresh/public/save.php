<?php
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$data = load_data();
$action = $_POST['action'] ?? '';
$redirect = trim($_POST['redirect'] ?? 'index.php');

switch ($action) {
    case 'save-semester':
        $data['semester'] = [
            'name' => trim($_POST['semester_name'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? ''),
        ];
        break;

    case 'add-course':
        $code = trim($_POST['course_code'] ?? '');
        $name = trim($_POST['course_name'] ?? '');

        if ($code !== '' || $name !== '') {
            $data['courses'][] = [
                'id' => uniqid('crs-', true),
                'code' => $code,
                'name' => $name,
            ];
        }
        break;

    case 'delete-course':
        $courseId = $_POST['course_id'] ?? '';
        if ($courseId !== '') {
            $data['courses'] = array_values(array_filter(
                $data['courses'],
                fn(array $course): bool => ($course['id'] ?? '') !== $courseId
            ));
        }
        break;

    case 'add-category':
        $new = normalize_slug($_POST['new_category'] ?? '');
        if ($new !== '' && !in_array($new, $data['categories'], true)) {
            $data['categories'][] = $new;
        }
        break;

    case 'rename-category':
        $old = normalize_slug($_POST['old_category'] ?? '');
        $new = normalize_slug($_POST['renamed_category'] ?? '');

        if ($old !== '' && $new !== '') {
            foreach ($data['categories'] as $i => $category) {
                if ($category === $old) {
                    $data['categories'][$i] = $new;
                }
            }

            foreach ($data['events'] as $i => $event) {
                if (($event['category'] ?? '') === $old) {
                    $data['events'][$i]['category'] = $new;
                }
            }

            $data['categories'] = array_values(array_unique($data['categories']));
        }
        break;

    case 'delete-category':
        $category = normalize_slug($_POST['category'] ?? '');

        if ($category !== '') {
            $fallback = 'assignment';

            $data['categories'] = array_values(array_filter(
                $data['categories'],
                fn(string $item): bool => $item !== $category
            ));

            if (empty($data['categories'])) {
                $data['categories'][] = $fallback;
            }

            foreach ($data['events'] as $i => $event) {
                if (($event['category'] ?? '') === $category) {
                    $data['events'][$i]['category'] = $fallback;
                }
            }
        }
        break;

    case 'save-event':
        $typedCategory = normalize_slug($_POST['new_event_category'] ?? '');
        $selectedCategory = normalize_slug($_POST['category'] ?? '');
        $finalCategory = $typedCategory !== '' ? $typedCategory : $selectedCategory;

        if ($finalCategory !== '' && !in_array($finalCategory, $data['categories'], true)) {
            $data['categories'][] = $finalCategory;
        }

        $id = $_POST['event_id'] ?? '';

        $event = [
            'id' => $id !== '' ? $id : uniqid('evt-', true),
            'title' => trim($_POST['title'] ?? ''),
            'course' => trim($_POST['course'] ?? ''),
            'category' => $finalCategory !== '' ? $finalCategory : 'assignment',
            'date' => trim($_POST['date'] ?? ''),
            'done' => isset($_POST['done']),
        ];

        $found = false;

        foreach ($data['events'] as $index => $existing) {
            if (($existing['id'] ?? '') === $event['id']) {
                $data['events'][$index] = $event;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $data['events'][] = $event;
        }
        break;

    case 'delete-event':
        $deleteId = $_POST['event_id'] ?? '';
        if ($deleteId !== '') {
            $data['events'] = array_values(array_filter(
                $data['events'],
                fn(array $event): bool => ($event['id'] ?? '') !== $deleteId
            ));
        }
        break;

    case 'refresh-display':
        $result = run_display_refresh();
        $suffix = $result['ok']
            ? '?display=ok&message=' . urlencode($result['message'])
            : '?display=fail&message=' . urlencode($result['message']);
        redirect('index.php' . $suffix);
        break;

    default:
        redirect($redirect);
}

save_data($data);
redirect($redirect);
