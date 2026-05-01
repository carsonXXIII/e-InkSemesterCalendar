






#!/usr/bin/env python3
import calendar
import json
import logging
import os
import sys
from datetime import date, datetime
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent
DATA_FILE = ROOT / "storage" / "data.json"
LOG_DIR = ROOT / "logs"
OUTPUT_IMAGE = LOG_DIR / "last-render.png"
LOG_FILE = LOG_DIR / "display-update.log"

WIDTH = 800
HEIGHT = 480

USE_WAVESHARE = True
WAVESHARE_CANDIDATES = [
    "epd7in5_V2",
    "epd7in5",
]

logging.basicConfig(
    filename=LOG_FILE,
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s"
)


def load_font(size: int, bold: bool = False):
    candidates = []
    if bold:
        candidates.extend([
            "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
            "/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf",
        ])
    else:
        candidates.extend([
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
            "/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf",
        ])

    for path in candidates:
        if os.path.exists(path):
            return ImageFont.truetype(path, size=size)

    return ImageFont.load_default()


FONT_TITLE = load_font(28, bold=True)
FONT_SUBTITLE = load_font(18, bold=False)
FONT_SECTION = load_font(20, bold=True)
FONT_BODY = load_font(18, bold=False)
FONT_SMALL = load_font(14, bold=False)
FONT_TINY = load_font(12, bold=False)
FONT_DAY = load_font(16, bold=True)
FONT_DATE = load_font(16, bold=False)


def load_data() -> dict:
    if not DATA_FILE.exists():
        return {
            "semester": {"name": "", "start_date": "", "end_date": ""},
            "courses": [],
            "events": [],
            "categories": ["assignment", "quiz", "project", "exam"],
        }

    with open(DATA_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


def parse_date(value: str):
    try:
        return datetime.strptime(value, "%Y-%m-%d").date()
    except Exception:
        return None


def semester_progress(semester: dict) -> int:
    start = parse_date(semester.get("start_date", ""))
    end = parse_date(semester.get("end_date", ""))
    today = date.today()

    if not start or not end or end <= start:
        return 0
    if today <= start:
        return 0
    if today >= end:
        return 100

    total_days = (end - start).days
    elapsed_days = (today - start).days
    return round((elapsed_days / total_days) * 100)


def sort_events(events: list) -> list:
    return sorted(events, key=lambda e: e.get("date", ""))


def upcoming_events(events: list, limit: int = 6) -> list:
    today = date.today()
    filtered = []

    for event in events:
        event_date = parse_date(event.get("date", ""))
        if not event_date:
            continue
        if event_date >= today and not event.get("done", False):
            filtered.append(event)

    return sort_events(filtered)[:limit]


def draw_text(draw, xy, text, font, fill=0):
    draw.text(xy, text, font=font, fill=fill)


def truncate(text: str, max_len: int) -> str:
    text = text.strip()
    if len(text) <= max_len:
        return text
    return text[: max_len - 1].rstrip() + "…"


def render_calendar(draw, x0, y0, width, height, today: date):
    cal = calendar.Calendar(firstweekday=6)
    month_weeks = cal.monthdayscalendar(today.year, today.month)

    month_title = today.strftime("%B %Y")
    draw_text(draw, (x0, y0), month_title, FONT_SECTION)
    y = y0 + 34

    day_names = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
    col_w = width // 7
    row_h = 40

    for i, day_name in enumerate(day_names):
        dx = x0 + i * col_w + 8
        draw_text(draw, (dx, y), day_name, FONT_SMALL)

    y += 28

    for week in month_weeks:
        for i, day_num in enumerate(week):
            cell_x = x0 + i * col_w
            cell_y = y
            cell_right = cell_x + col_w - 6
            cell_bottom = cell_y + row_h - 6

            draw.rounded_rectangle(
                (cell_x + 2, cell_y + 2, cell_right, cell_bottom),
                radius=8,
                outline=0,
                width=1,
            )

            if day_num:
                number_x = cell_x + 10
                number_y = cell_y + 8
                draw_text(draw, (number_x, number_y), str(day_num), FONT_DAY)

                if day_num == today.day:
                    tri = [
                        (cell_right - 14, cell_y + 8),
                        (cell_right - 6, cell_y + 8),
                        (cell_right - 6, cell_y + 16),
                    ]
                    draw.polygon(tri, fill=0)
        y += row_h


def render_to_image(data: dict) -> Image.Image:
    semester = data.get("semester", {})
    events = data.get("events", [])
    progress = semester_progress(semester)
    upcoming = upcoming_events(events, limit=6)
    today = date.today()

    image = Image.new("1", (WIDTH, HEIGHT), 255)
    draw = ImageDraw.Draw(image)

    outer_margin = 18
    gap = 18

    draw.rounded_rectangle(
        (8, 8, WIDTH - 8, HEIGHT - 8),
        radius=18,
        outline=0,
        width=2,
    )

    title = semester.get("name", "").strip() or "Semester Calendar"
    subtitle_parts = []

    if semester.get("start_date"):
        subtitle_parts.append(semester["start_date"])
    if semester.get("end_date"):
        subtitle_parts.append(semester["end_date"])

    subtitle = "  to  ".join(subtitle_parts) if subtitle_parts else today.strftime("%A, %B %d, %Y")

    draw_text(draw, (outer_margin, 18), title, FONT_TITLE)
    draw_text(draw, (outer_margin, 54), subtitle, FONT_SUBTITLE)

    top_y = 92
    left_x = outer_margin
    right_x = 430
    left_w = 360
    right_w = WIDTH - right_x - outer_margin

    draw.rounded_rectangle(
        (left_x, top_y, left_x + left_w, HEIGHT - 78),
        radius=16,
        outline=0,
        width=2,
    )
    draw.rounded_rectangle(
        (right_x, top_y, WIDTH - outer_margin, HEIGHT - 78),
        radius=16,
        outline=0,
        width=2,
    )

    draw_text(draw, (left_x + 14, top_y + 12), "Upcoming deadlines", FONT_SECTION)

    if upcoming:
        row_y = top_y + 48
        for event in upcoming:
            title_text = truncate(event.get("title", "Untitled"), 26)
            course_text = event.get("course", "").strip()
            category_text = event.get("category", "").strip()
            date_text = event.get("date", "").strip()

            meta = " · ".join(part for part in [course_text, category_text] if part)
            draw_text(draw, (left_x + 16, row_y), title_text, FONT_BODY)
            if meta:
                draw_text(draw, (left_x + 16, row_y + 20), truncate(meta, 32), FONT_SMALL)
            draw_text(draw, (left_x + 16, row_y + 38), date_text or "No date", FONT_DATE)

            draw.line(
                (left_x + 14, row_y + 60, left_x + left_w - 14, row_y + 60),
                fill=0,
                width=1,
            )
            row_y += 68
            if row_y > HEIGHT - 170:
                break
    else:
        draw_text(draw, (left_x + 16, top_y + 50), "No upcoming items.", FONT_BODY)

    render_calendar(draw, right_x + 14, top_y + 12, right_w - 28, HEIGHT - 170, today)

    footer_y = HEIGHT - 58
    label_y = footer_y - 20
    bar_y = footer_y + 10
    bar_x = outer_margin
    bar_w = WIDTH - (outer_margin * 2)
    bar_h = 14

    draw_text(draw, (bar_x, label_y), f"Semester Progress: {progress}%", FONT_SECTION)

    draw.rounded_rectangle(
        (bar_x, bar_y, bar_x + bar_w, bar_y + bar_h),
        radius=7,
        outline=0,
        width=2,
        fill=255,
    )

    fill_w = int((bar_w * progress) / 100)
    if fill_w > 0:
        draw.rounded_rectangle(
            (bar_x, bar_y, bar_x + fill_w, bar_y + bar_h),
            radius=7,
            outline=0,
            fill=0,
        )

    return image


def import_waveshare_driver():
    home = str(Path.home())
    extra_paths = [
        os.path.join(home, "e-Paper", "RaspberryPi_JetsonNano", "python", "lib"),
        os.path.join(home, "e-Paper", "RaspberryPi_JetsonNano", "python", "examples"),
    ]

    for extra in extra_paths:
        if os.path.isdir(extra) and extra not in sys.path:
            sys.path.append(extra)

    try:
        from waveshare_epd import epd7in5_V2 as driver
        return driver
    except Exception:
        pass

    try:
        from waveshare_epd import epd7in5 as driver
        return driver
    except Exception:
        pass

    raise ImportError("Could not import a Waveshare 7.5-inch driver (epd7in5_V2 or epd7in5).")


def send_to_waveshare(image: Image.Image):
    driver = import_waveshare_driver()
    epd = driver.EPD()
    epd.init()
    epd.Clear()

    if hasattr(epd, "getbuffer"):
        epd.display(epd.getbuffer(image))
    else:
        epd.display(image)

    try:
        epd.sleep()
    except Exception:
        pass


def main():
    LOG_DIR.mkdir(parents=True, exist_ok=True)

    try:
        data = load_data()
        image = render_to_image(data)
        image.save(OUTPUT_IMAGE)
        logging.info("Saved preview image to %s", OUTPUT_IMAGE)

        if USE_WAVESHARE:
            send_to_waveshare(image)
            logging.info("Sent render to Waveshare display.")
        else:
            logging.info("USE_WAVESHARE is False; preview image only.")

        print(f"Render complete: {OUTPUT_IMAGE}")
    except Exception as exc:
        logging.exception("Update failed: %s", exc)
        print(f"Update failed: {exc}", file=sys.stderr)
        raise


if __name__ == "__main__":
    main()
