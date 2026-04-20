# 🎧 UnderPass API | Barcelona Underground Electronic Scene

UnderPass is a specialized REST API designed for the management and community-driven curation of electronic music events within the Barcelona local scene. It features a unique trust-based verification system, physical attendance validation via QR/Stamps, and a qualitative feedback loop.

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/)

---

## 📚 Table of Contents
* [About](#-about)
* [Tech Stack](#-tech-stack)
* [Core Logic & Features](#-core-logic--features)
* [Setup & Installation](#-setup--installation)
* [Docker & Deployment](#-docker--deployment)
* [API Documentation](#-api-documentation)
* [Demo Accounts](#-demo-accounts)

---

## 📖 About
**UnderPass API** acts as the engine for a decentralized electronic music agenda. Unlike traditional platforms, it relies on the community to verify events and uses a gamification loop (Stamps & Points) to ensure that only attendees can provide qualitative feedback ("Vibechecks").

The API follows RESTful conventions, is fully versioned under `/api/v1/`, and uses OAuth2 (Laravel Passport) for secure authentication.

---

## 💻 Tech Stack
* **Runtime:** PHP 8.4
* **Framework:** Laravel 12
* **Architecture:** Service Layer Pattern (decoupling business logic from Controllers)
* **Authentication:** Laravel Passport (OAuth2 Personal Access Tokens)
* **Database:** MySQL 8.0
* **Documentation:** Scribe + Scalar (Interactive UI)
* **Containerization:** Docker + Docker Compose
* **Deployment:** Railway

---

## 🧠 Core Logic & Features

### 1. Vouch-to-Verify System
To prevent spam, new events enter a **"Waiting Room"** (Pending status).
* **Vouches:** Trusted users can "vouch" for an event.
* **Auto-Publish:** Upon reaching **3 vouches**, the event is automatically verified and promoted to the main feed.

### 2. Gamification: QR Stamps & Passport
* **Proof of Attendance:** Organizers of verified events receive a unique QR code.
* **Digital Stamps:** When a user "scans" (accesses) the QR URL, they receive a collectible Stamp in their digital Passport.

### 3. Vibechecks (Qualitative Feedback)
* **Exclusive Access:** Only users holding the event's Stamp can submit a "Vibecheck" (Review).
* **Incentive:** Submitting a Vibecheck rewards the user with **+5 points**.

---

## 🛠 Setup & Installation

### Prerequisites
* PHP >= 8.4
* Composer
* Docker (Optional, but recommended)

### Local Installation
1.  **Clone the repo:**
    ```bash
    git clone [https://github.com/francobrida/UnderPass-API.git](https://github.com/francobrida/UnderPass-API.git)
    cd UnderPass-API
    ```
2.  **Install dependencies:**
    ```bash
    composer install
    ```
3.  **Environment Setup:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Update your .env with your local database credentials.*

4.  **Migrations & Passport:**
    ```bash
    php artisan migrate --seed
    php artisan passport:keys
    php artisan passport:client --personal
    ```
5.  **Storage Link:**
    ```bash
    php artisan storage:link
    ```

---

## 🐳 Docker & Deployment

### Run Locally with Docker
The project includes a multi-container setup (App & Web Server) to ensure environment consistency.
```bash
docker-compose up -d --build
```
The API will be available at http://localhost.

### Production Deployment (Railway)
This API is optimized for **Railway** using the provided `Dockerfile`.
* **Live API:** `https://your-app-name.up.railway.app/api/v1/`
* **CI/CD:** Any push to the `main` or `develop` branch triggers an automatic rebuild and deployment.

---

## 📖 API Documentation
Interactive documentation is generated via **Scribe** and rendered with **Scalar**.

* **Live Docs:** [https://your-app-name.up.railway.app/docs](https://your-app-name.up.railway.app/docs)
* **Postman Collection:** You can download the auto-generated Postman collection and OpenAPI spec directly from the "Introduction" section of the Live Docs.

---

## 🔐 Demo Accounts
Use these pre-seeded accounts to test the Role-Based Access Control (RBAC):

| Role | Email | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Admin** | admin@underpass.com | password | Full CRUD & Moderation |
| **Organizer** | organizer@test.com | password | Create Events & QR Management |
| **Clubber** | clubber@test.com | password | Vouching, Stamps & Vibechecks |

---

## 📈 Scalability & Future Improvements
* **Organizer Reputation:** Implementation of an average score based on historical Vibechecks.
* **Points Marketplace:** A dedicated module to exchange accumulated points for exclusive community benefits or partner discounts.
* **Push Notifications:** Real-time alerts when a "Waiting Room" event from a favorite organizer gets verified.

---
Developed by **Franco Bridarolli** - 2026.