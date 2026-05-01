# 📚 Pi Semester Calendar One-Page Refresh

A clean one-page semester planner for **Raspberry Pi** with a **PHP dashboard** and a **Python e-ink display renderer**.

Manage your semester dates, courses, categories, and deadlines from a browser, then refresh a Waveshare 7.5" e-paper display with a simplified academic overview. This project is built for a Pi-based setup where the web app and display script live together on the same device.

---

## ✨ Features

- 🖥️ One-page dashboard for quick semester management
- 📅 Semester name, start date, and end date editing
- 📚 Course tracking
- 🏷️ Category management
- 📝 Event and deadline entry
- 🔄 Manual display refresh
- 🧾 E-ink layout with:
  - upcoming deadlines on the left
  - month calendar on the right
  - semester progress bar at the bottom

---

## 🗂️ Project Structure

```text
pi-semester-calendar-onepage-refresh/
├── assets/
│   ├── style.css
│   └── theme.js
├── display/
│   └── update_display.py
├── includes/
│   └── helpers.php
├── logs/
│   ├── display-update.log
│   └── last-render.png
├── public/
│   ├── index.php
│   └── save.php
└── storage/
    └── data.json
```

---

## 🧰 Requirements

### Hardware

- Raspberry Pi 4 or 5
- Waveshare 7.5" e-paper display / HAT

### Software

- PHP
- Python 3
- Pillow (`PIL`)
- Waveshare Python display driver library

---

## 🚀 Run the Web App

From the project root:

```bash
cd /home/sparrow/pi-semester-calendar-onepage-refresh
php -S 127.0.0.1:8000 -t public
```

Then open in your browser:

```text
http://127.0.0.1:8000/index.php
```

---

## 🖼️ Run the Display Renderer

From the project root:

```bash
cd /home/sparrow/pi-semester-calendar-onepage-refresh
python3 display/update_display.py
```

If the script runs correctly, it should generate:

```text
logs/last-render.png
```

---

## 🐍 Optional Virtual Environment

If Pillow is missing or Raspberry Pi OS blocks normal package installs, use a virtual environment:

```bash
cd /home/sparrow/pi-semester-calendar-onepage-refresh
python3 -m venv .venv
source .venv/bin/activate
python -m pip install Pillow
python display/update_display.py
```

---

## 💾 Data Storage

The app stores its live data in:

```text
storage/data.json
```

This file contains semester info, courses, categories, and events used by both the dashboard and the display renderer.

---

## ⚠️ Waveshare Driver Note

The display script currently tries common Waveshare 7.5" driver imports such as:

- `epd7in5_V2`
- `epd7in5`

Depending on your exact display revision, you may need to adjust the driver import in `display/update_display.py`.

---

## 🙈 Suggested .gitignore

A `.gitignore` helps keep local clutter and personal data out of GitHub.

```gitignore
__pycache__/
*.pyc
.venv/
logs/
*.log
storage/data.json
.vscode/
.idea/
.DS_Store
Thumbs.db
```

---

## 🔮 Future Ideas

- ⏰ Daily auto-refresh with cron
- ✏️ Better inline editing for courses and events
- 📊 More dashboard feedback and preview tools
- 🎨 More polished themes and layout options
- 🧩 Driver-specific setup notes for different Waveshare revisions

---

## 📌 Status

This project is currently working as a local Raspberry Pi semester dashboard with a matching Python renderer and generated preview image support.

---

## 📄 License

Add your preferred license here, such as **MIT**.
