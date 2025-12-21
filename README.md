# UrbanWatch 🚨

**UrbanWatch** is an AI-powered real-time accident detection and incident concern reporting platform designed for **Brgy 176 E**.

The system integrates **CCTV cameras** and **AI models** to automatically detect incidents such as car accidents, fires, floods, and medical emergencies. In addition to CCTV-based monitoring, **IoT sensors** (sound, vibration, and environmental) enhance detection by capturing anomalies that may not be visible in camera feeds.

This repository contains the **Laravel (backend)** and **Inertia.js with React (frontend)** codebase. We use **DDEV** for local development to ensure a consistent, containerized environment for all developers.

---

## Project Context

Urban communities face major challenges in ensuring timely responses to accidents and emergencies. Currently, CCTV operators manually monitor camera feeds, which can lead to delayed recognition and slower response times.

**UrbanWatch** addresses this issue by combining **AI-driven video analytics**, **IoT sensors**, and **incident reporting** into a single platform. This allows for **faster detection, quicker validation, and timely response** by local authorities.

---

## Tech Stack

| Component | Technology |
| :--- | :--- |
| **Backend** | Laravel 11 (PHP 8.2+) |
| **Frontend** | React 18 + Inertia.js |
| **Styling** | Tailwind CSS |
| **Database** | MySQL (Managed by DDEV) |
| **Environment** | DDEV (Docker-based) |

---

## Getting Started

Follow these steps to set up the project on your local machine.

### 1. Prerequisites
Ensure you have the following installed:
* [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Make sure WSL2 integration is enabled if on Windows)
* [DDEV](https://ddev.readthedocs.io/en/stable/)
* Git

### 2. Installation
Clone the repository. We recommend renaming the directory to `urbanwatch` to match the project configuration.

```bash
# Clone the repo into a folder named 'urbanwatch'
git clone [https://github.com/Althirdy/inertia-api.git](https://github.com/Althirdy/inertia-api.git) urbanwatch

# Enter the directory
cd urbanwatch
```
### 3. Automated Setup
We have provided a script to automate the entire build process (env file, dependencies, migrations, seeders).

```bash
# Make the script executable
chmod +x setup.sh

# Run the setup script
./setup.sh
```
Note: If you encounter a permission denied error regarding Docker, ensure your Linux user is part of the docker group or that Docker Desktop is running.

```bash
sudo usermod -aG docker $USER && newgrp docker
```
### 3. Running the Application

Once the setup script finishes successfully:

#### 1. Start the Frontend Watcher
To compile assets and listen for changes (Hot Module Replacement):

```bash
ddev npm run dev
```
#### 2. Start the Frontend Watcher
Open a new terminal tab and run:

```bash
ddev describe
```

This will show your local URLs. Usually:

* Web App: https://urbanwatch.ddev.site
* Database GUI: https://urbanwatch.ddev.site:8036 (phpMyAdmin)
