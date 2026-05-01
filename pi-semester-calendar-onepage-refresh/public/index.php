<?php
declare(strict_types=1);

require __DIR__ . '/../includes/helpers.php';

$data = load_data();
$semester = $data['semester'];
$courses = $data['courses'];
$events = sort_events($data['events']);
$categories = $data['categories'];
$upcoming = upcoming_events($events, 10);

$editingId = $_GET['edit'] ?? '';
$editing = $editingId !== '' ? find_event_by_id($events, $editingId) : null;
$displayStatus = $_GET['display'] ?? '';
$displayMessage = $_GET['message'] ?? '';

$progress = semester_progress($semester);
$completed = count(array_filter($events, fn(array $event): bool => !empty($event['done'])));
$open = count($events) - $completed;
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title>Semester Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://api.fontshare.com">
    <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell onepage-shell">
    <aside class="sidebar">
        <div>
            <div class="brand">
                <div class="brand-mark">S</div>
                <div>
                    <div class="brand-title">Semester</div>
                    <div class="brand-subtitle">One-page planner</div>
                </div>
            </div>

            <nav class="nav-list" aria-label="Primary">
                <a class="nav-link active" href="#overview">Overview</a>
                <a class="nav-link" href="#planner">Planner</a>
                <a class="nav-link" href="#entries">Entries</a>
            </nav>
        </div>

        <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme">
            Toggle theme
        </button>
    </aside>

    <main class="main">
        <section class="hero-card compact-hero" id="overview">
            <div>
                <p class="eyebrow">Academic planner</p>
                <h1 class="hero-title"><?= h($semester['name'] ?: 'Semester dashboard') ?></h1>
                <p class="hero-copy">
                    <?= h($semester['start_date'] ?: 'No start date') ?> to <?= h($semester['end_date'] ?: 'No end date') ?>
                </p>

                <div class="actions top-actions">
                    <form method="post" action="save.php">
                        <input type="hidden" name="action" value="refresh-display">
                        <input type="hidden" name="redirect" value="index.php">
                        <button class="btn btn-primary" type="submit">Load to e-ink display</button>
                    </form>
                </div>

                <?php if ($displayStatus === 'ok'): ?>
                    <p class="status-note success-note">Display refresh completed: <?= h($displayMessage) ?></p>
                <?php elseif ($displayStatus === 'fail'): ?>
                    <p class="status-note error-note">Display refresh failed: <?= h($displayMessage !== '' ? $displayMessage : 'Unknown error') ?></p>
                <?php endif; ?>
            </div>

            <div class="hero-side">
                <div class="metric-stack">
                    <span class="metric-label">Progress</span>
                    <span class="metric-value"><?= $progress ?>%</span>
                </div>
                <div class="progress-track" aria-label="Semester progress">
                    <span style="width: <?= $progress ?>%"></span>
                </div>
            </div>
        </section>

        <section class="stats-grid">
            <article class="panel stat-card">
                <span class="stat-label">Courses</span>
                <strong class="stat-number"><?= count($courses) ?></strong>
                <p class="muted">Tracked this term.</p>
            </article>

            <article class="panel stat-card">
                <span class="stat-label">Open items</span>
                <strong class="stat-number"><?= $open ?></strong>
                <p class="muted">Still to complete.</p>
            </article>

            <article class="panel stat-card">
                <span class="stat-label">Completed</span>
                <strong class="stat-number"><?= $completed ?></strong>
                <p class="muted">Marked done.</p>
            </article>
        </section>

        <section class="dashboard-grid" id="planner">
            <article class="panel form-panel">
                <div class="section-head">
                    <h2>Semester</h2>
                </div>

                <form class="form-grid" method="post" action="save.php">
                    <input type="hidden" name="action" value="save-semester">
                    <input type="hidden" name="redirect" value="index.php">

                    <div class="form-row">
                        <label for="semester_name">Semester name</label>
                        <input id="semester_name" name="semester_name" value="<?= h($semester['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-split">
                        <div class="form-row">
                            <label for="start_date">Start date</label>
                            <input id="start_date" type="date" name="start_date" value="<?= h($semester['start_date'] ?? '') ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="end_date">End date</label>
                            <input id="end_date" type="date" name="end_date" value="<?= h($semester['end_date'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Save semester</button>
                    </div>
                </form>
            </article>

            <article class="panel form-panel">
                <div class="section-head">
                    <h2>Courses</h2>
                </div>

                <form class="form-grid" method="post" action="save.php">
                    <input type="hidden" name="action" value="add-course">
                    <input type="hidden" name="redirect" value="index.php">

                    <div class="form-split">
                        <div class="form-row">
                            <label for="course_code">Course code</label>
                            <input id="course_code" name="course_code" placeholder="BIO101">
                        </div>

                        <div class="form-row">
                            <label for="course_name">Course name</label>
                            <input id="course_name" name="course_name" placeholder="Biology">
                        </div>
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Add course</button>
                    </div>
                </form>

                <?php if (!empty($courses)): ?>
                    <div class="mini-list">
                        <?php foreach ($courses as $course): ?>
                            <div class="mini-row">
                                <div>
                                    <strong><?= h($course['code'] ?? '') ?></strong>
                                    <div class="muted"><?= h($course['name'] ?? '') ?></div>
                                </div>

                                <form method="post" action="save.php">
                                    <input type="hidden" name="action" value="delete-course">
                                    <input type="hidden" name="redirect" value="index.php">
                                    <input type="hidden" name="course_id" value="<?= h($course['id'] ?? '') ?>">
                                    <button class="btn btn-secondary" type="submit">Delete</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="panel form-panel wide-panel">
                <div class="section-head">
                    <h2><?= $editing ? 'Edit event' : 'Add event' ?></h2>
                </div>

                <form class="form-grid" method="post" action="save.php">
                    <input type="hidden" name="action" value="save-event">
                    <input type="hidden" name="redirect" value="index.php">
                    <input type="hidden" name="event_id" value="<?= h($editing['id'] ?? '') ?>">

                    <div class="form-row">
                        <label for="title">Title</label>
                        <input id="title" name="title" value="<?= h($editing['title'] ?? '') ?>" required>
                    </div>

                    <div class="form-triple">
                        <div class="form-row">
                            <label for="course">Course</label>
                            <input
                                id="course"
                                name="course"
                                list="course-list"
                                value="<?= h($editing['course'] ?? '') ?>"
                                placeholder="BIO101"
                            >
                            <datalist id="course-list">
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= h($course['code'] ?? '') ?>"><?= h($course['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="form-row">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <?php foreach ($categories as $category): ?>
                                    <option
                                        value="<?= h($category) ?>"
                                        <?= (($editing['category'] ?? 'assignment') === $category) ? 'selected' : '' ?>
                                    >
                                        <?= h(ucfirst(str_replace('-', ' ', $category))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label for="date">Date</label>
                            <input id="date" type="date" name="date" value="<?= h($editing['date'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="new_event_category">Or add a new category for this event</label>
                        <input id="new_event_category" name="new_event_category" placeholder="reading, lab, discussion">
                    </div>

                    <div class="form-row checkbox-row">
                        <label>
                            <input type="checkbox" name="done" <?= !empty($editing['done']) ? 'checked' : '' ?>>
                            Mark as done
                        </label>
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="submit">
                            <?= $editing ? 'Update event' : 'Save event' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a class="btn btn-secondary" href="index.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </article>

            <article class="panel form-panel wide-panel">
                <div class="section-head">
                    <h2>Categories</h2>
                </div>

                <form class="form-grid" method="post" action="save.php">
                    <input type="hidden" name="action" value="add-category">
                    <input type="hidden" name="redirect" value="index.php">

                    <div class="form-row">
                        <label for="new_category">Add category</label>
                        <input id="new_category" name="new_category" placeholder="reading, lab, discussion">
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="submit">Add category</button>
                    </div>
                </form>

                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <div class="category-card">
                            <form class="form-grid" method="post" action="save.php">
                                <input type="hidden" name="action" value="rename-category">
                                <input type="hidden" name="redirect" value="index.php">
                                <input type="hidden" name="old_category" value="<?= h($category) ?>">

                                <label for="rename_<?= h($category) ?>">Rename</label>
                                <input id="rename_<?= h($category) ?>" name="renamed_category" value="<?= h($category) ?>">

                                <button class="btn btn-secondary" type="submit">Save name</button>
                            </form>

                            <form method="post" action="save.php">
                                <input type="hidden" name="action" value="delete-category">
                                <input type="hidden" name="redirect" value="index.php">
                                <input type="hidden" name="category" value="<?= h($category) ?>">
                                <button class="btn btn-secondary danger-btn" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="panel form-panel">
                <div class="section-head">
                    <h2>Upcoming</h2>
                </div>

                <?php if (empty($upcoming)): ?>
                    <p class="empty-note">No upcoming items.</p>
                <?php else: ?>
                    <div class="mini-list">
                        <?php foreach ($upcoming as $event): ?>
                            <div class="mini-row">
                                <div>
                                    <strong><?= h($event['title'] ?? '') ?></strong>
                                    <div class="muted">
                                        <?= h($event['course'] ?? '') ?> · <?= h($event['date'] ?? '') ?>
                                    </div>
                                </div>
                                <span class="badge"><?= h($event['category'] ?? '') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="panel table-panel full-span" id="entries">
                <div class="section-head">
                    <h2>All entries</h2>
                </div>

                <?php if (empty($events)): ?>
                    <p class="empty-note">No events yet.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Title</th>
                                <th>Course</th>
                                <th>Category</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?= h($event['title'] ?? '') ?></td>
                                    <td><?= h($event['course'] ?? '') ?></td>
                                    <td><span class="badge"><?= h($event['category'] ?? '') ?></span></td>
                                    <td><?= h($event['date'] ?? '') ?></td>
                                    <td><?= !empty($event['done']) ? 'Done' : 'Open' ?></td>
                                    <td class="table-actions">
                                        <a class="btn btn-secondary" href="?edit=<?= urlencode($event['id'] ?? '') ?>">Edit</a>

                                        <form class="inline-form" method="post" action="save.php">
                                            <input type="hidden" name="action" value="delete-event">
                                            <input type="hidden" name="redirect" value="index.php">
                                            <input type="hidden" name="event_id" value="<?= h($event['id'] ?? '') ?>">
                                            <button class="btn btn-secondary danger-btn" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>
</div>

<script src="assets/theme.js"></script>
</body>
</html>
