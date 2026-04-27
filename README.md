# 🎧 UnderPass API | Barcelona Underground Electronic Scene

UnderPass is a specialized REST API designed for the management and community-driven curation of electronic music events within the Barcelona local scene. It features a unique trust-based verification system, physical attendance validation via QR/Stamps, and a qualitative feedback loop.

[![Deploy on Railway](https://railway.app/button.svg)](https://underpass-api-production.up.railway.app/api/v1/)

---

## 📚 Table of Contents
* [About](#-about)
* [Tech Stack](#-tech-stack)
* [Core Logic & Features](#-core-logic--features)
* [Testing & QA](#-testing--qa)
* [Setup & Installation](#-setup--installation)
* [Authentication](#-authentication)
* [Roles & Constraints](#-roles--constraints)
* [Docker & Deployment](#-docker--deployment)
* [API Documentation](#-api-documentation)
* [Demo Accounts](#-demo-accounts)

---

## 📖 About
**UnderPass API** acts as the engine for a decentralized electronic music agenda. Unlike traditional platforms, it relies on the community to verify events and uses a gamification loop (Stamps & Points) to ensure that only attendees can provide qualitative feedback ("Vibechecks").

The API follows RESTful conventions, is fully versioned under `/api/v1/`, and uses **OAuth2 (Laravel Passport)** for secure authentication.

---

## 💻 Tech Stack
* **Runtime:** PHP 8.4
* **Framework:** Laravel 13
* **Architecture:** Service Layer Pattern (decoupling business logic from Controllers)
* **Authentication:** Laravel Passport (OAuth2 Personal Access Tokens)
* **Testing:** PEST (Functional & Unit Testing)
* **Database:** MySQL 8.0
* **Documentation:** Scribe + Scalar (Interactive UI)
* **Containerization:** Docker + Docker Compose
* **Deployment:** Railway (FrankenPHP)

---

## 🧠 Core Logic & Features

### 1. Vouch-to-Verify System
To prevent spam, new events enter a **"Waiting Room"** (Pending status).
* **Vouches:** Trusted users can "vouch" for an event.
* **Auto-Publish:** Upon reaching **3 vouches**, the event is automatically verified and promoted to the main feed.

### 2. Role-Based Access Control (RBAC)
The API implements a custom Role system to manage permissions:
* **Admin:** Full moderation and system control.
* **Clubber:** Standard user capable of vouching and collecting stamps.
* **Organizer:** Clubbers with verified event history, granted access to QR management.

### 3. Gamification: QR Stamps & Passport
* **Proof of Attendance:** Organizers of verified events receive a unique QR code.
* **Digital Stamps:** When a user scans the QR URL, they receive a collectible Stamp in their digital Passport.

---

## 🧪 Testing & QA
The project follows a rigorous testing strategy to ensure all endpoints are functional (Happy, Sad, and Edge cases).

* **Framework:** PEST.
* **Coverage:** Authentication, Event Lifecycle, Vibechecks, and Role Permissions.
* **Run Tests Locally:**
    ```bash
    php artisan test
    ```
* **Run Tests on Production (Railway):**
    You can trigger tests directly on the container via Railway CLI:
    ```bash
    railway run php artisan test
    ```

---

## 🛠 Setup & Installation

### Prerequisites
* PHP >= 8.4
* Composer
* Docker

### Local Installation
1. **Clone the repo:**
    ```bash
    git clone [https://github.com/francobrida/UnderPass-API.git](https://github.com/francobrida/UnderPass-API.git)
    cd UnderPass-API
    ```
2. **Install dependencies:**
    ```bash
    composer install
    ```
3. **Environment Setup:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
4. **Migrations & Passport:**
    ```bash
    php artisan migrate --seed
    php artisan passport:keys
    php artisan passport:client --personal
    ```

---

## 🔐 Authentication
The API utilizes **Laravel Passport**. To access protected endpoints, include the token in your request headers:

- **Header:** `Authorization: Bearer {your_token_here}`
- **Accept:** `application/json`

| Method | Endpoint | Action | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/login` | Log in and retrieve Bearer token | No |
| `POST` | `/api/v1/register` | Register a new user | No |
| `POST` | `/api/v1/logout` | Revoke current token and log out | Yes |

---

## 📅 API Endpoints (v1)

### 👤 User Profile & Identity
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/v1/users/{id}` | Get specific user public profile |
| `PATCH` | `/api/v1/profile` | Update currently authenticated user profile |
| `DELETE` | `/api/v1/profile` | Delete currently authenticated user account |
| `GET` | `/api/v1/users` | List all users (Admin) |
| `POST` | `/api/v1/users` | Create a new user (Admin) |
| `PATCH` | `/api/v1/users/{user_id}` | Update any user (Admin) |
| `DELETE` | `/api/v1/users/{user_id}` | Delete any user (Admin) |

### 🎫 Event Management
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/v1/events` | List events (Verified/Unverified) |
| `POST` | `/api/v1/events` | Create a new event (Supports Flyer upload) |
| `GET` | `/api/v1/events/{id}` | Show detailed information of an event |
| `PUT` | `/api/v1/events/{id}` | Edit an event (Owner/Admin only) |
| `DELETE` | `/api/v1/events/{id}` | Delete an event |
| `GET` | `/api/v1/users/{user_id}/events` | List events created by a specific user |

### 🗳️ Vouching System (Reputation)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/v1/events/{id}/vouches` | Cast a "Vouch" for an unverified event |
| `GET` | `/api/v1/events/{id}/vouches` | List all users who vouched for an event |

### 🔊 Vibechecks (Post-Event Feedback)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/v1/events/{id}/vibechecks` | Submit a Vibecheck (Sound & Safety scores + comments) |
| `GET` | `/api/v1/events/{id}/vibechecks` | Get all Vibechecks for a specific event |
| `DELETE` | `/api/v1/vibechecks/{id}` | Remove a Vibecheck |

### 🏅 Gamification: Stamps & Ranking
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/v1/stamps` | Redeem a Stamp using a token |
| `GET` | `/api/v1/stamps` | List all stamps redeemed by the auth user |
| `GET` | `/api/v1/users/{id}/stamps` | List stamps redeemed by a specific user |
| `DELETE` | `/api/v1/stamps/{id}` | Remove a stamp record |
| `GET` | `/api/v1/ranking` | Get the community leaderboard (by points) |

---

## 🛠️ Roles & Constraints
* **CLUBBER:** Limited to **one (1) active event** at a time.
* **ORGANIZER:** Unlocks unlimited slots (requires **3 Vouches** on a previous event).
* **ADMIN:** Full system moderation.

---

## 🐳 Docker & Deployment

### Run Locally with Docker
The project includes a multi-container setup via Docker Compose.
```bash
docker-compose up -d --build
```
### Production Deployment (Railway)
This API is served via **FrankenPHP**.
* **Live API:** `https://underpass-api-production.up.railway.app/api/v1/`
* **Live Docs:** `https://underpass-api-production.up.railway.app/docs`

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