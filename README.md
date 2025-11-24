# 📘 API Documentation

**Version:** 1.0
**Base URL:** `https://urbanwatch.me/api/v1`

## Table of Contents
- [🚀 Introduction](#-introduction)
- [🔐 Authentication & User Management](#-authentication--user-management)
  - [1. Register Citizen](#1-register-citizen)
  - [2. Login (Standard)](#2-login-standard)
  - [3. Login (Purok Leader)](#3-login-purok-leader)
  - [4. Get Authenticated User](#4-get-authenticated-user)
  - [5. Logout](#5-logout)
- [🛡️ Security & Recovery (OTP)](#-security--recovery-otp)
  - [1. Send OTP](#1-send-otp)
  - [2. Verify OTP](#2-verify-otp)
  - [3. Reset Password](#3-reset-password)
- [📢 Citizen Concerns](#-citizen-concerns)
  - [1. List Concerns](#1-list-concerns)
  - [2. Submit Concern (Manual or Voice)](#2-submit-concern-manual-or-voice)
  - [3. Update Concern](#3-update-concern)
- [🚑 Operator & Emergency Management](#-operator--emergency-management)
  - [1. List Contacts](#1-list-contacts)
  - [2. Create Contact](#2-create-contact)
  - [3. List Accidents](#3-list-accidents)
  - [4. Update Accident Status](#4-update-accident-status)
- [🤖 AI Integrations (YOLO)](#-ai-integrations-yolo)
  - [1. Process Snapshot](#1-process-snapshot)
- [📚 Appendix A: Real-time Distribution Architecture](#-appendix-a-real-time-distribution-architecture)

---

## 🚀 Introduction

Welcome to the API documentation. This API provides backend services for the Citizen App, Operator Dashboard, and AI integration systems.

### Authentication
The API uses **Laravel Sanctum** for authentication.
*   **Header:** `Authorization: Bearer <your_access_token>`
*   Most endpoints require a valid access token unless marked as **Public**.

### Response Format
All responses generally follow this standard JSON structure:
```json
{
  "success": boolean,
  "message": string,
  "data": object | array, // Optional
  "errors": object        // Optional, for validation errors
}
```

---

## 🔐 Authentication & User Management

### 1. Register Citizen
Registers a new citizen account in the system.

*   **Endpoint:** `https://urbanwatch.me/api/v1/register`
*   **Method:** `POST`
*   **Access:** Public
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | Unique email address. |
| `password` | string | Yes | Min 8 chars, must match confirmation. |
| `password_confirmation` | string | Yes | |
| `first_name` | string | Yes | |
| `last_name` | string | Yes | |
| `date_of_birth` | date | Yes | Format: `YYYY-MM-DD`. |
| `phone_number` | string | Yes | 11-digit mobile number. |
| `address` | string | Yes | |
| `barangay` | string | Yes | |
| `city` | string | Yes | |
| `province` | string | Yes | |
| `postal_code` | string | Yes | |

#### JSON Response
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token": "1|laravel_sanctum_token...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "role_id": 1,
      "first_name": "Juan",
      "last_name": "Dela Cruz"
    }
  }
}
```

### 2. Login (Standard)
Authenticates Citizens and Officials/Operators.

*   **Endpoint:** `https://urbanwatch.me/api/v1/login`
*   **Method:** `POST`
*   **Access:** Public
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | Registered email. |
| `password` | string | Yes | Account password. |

#### JSON Response
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "2|laravel_sanctum_token...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "role_id": 1
    }
  }
}
```

### 3. Login (Purok Leader)
Specialized login for Purok Leaders using a PIN code.

*   **Endpoint:** `https://urbanwatch.me/api/v1/login/purok-leader`
*   **Method:** `POST`
*   **Access:** Public
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `pin` | string | Yes | Assigned PIN code. |

### 4. Get Authenticated User
Retrieves the profile of the currently logged-in user.

*   **Endpoint:** `https://urbanwatch.me/api/v1/auth/user`
*   **Method:** `GET`
*   **Access:** Authenticated
*   **Header:** `Authorization: Bearer <token>`

#### JSON Response
```json
{
  "id": 1,
  "name": "Juan Dela Cruz",
  "email": "user@example.com",
  "role_id": 1,
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

### 5. Logout
Invalidates the current access token.

*   **Endpoint:** `https://urbanwatch.me/api/v1/logout`
*   **Method:** `POST`
*   **Access:** Authenticated
*   **Header:** `Authorization: Bearer <token>`

#### JSON Response
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 🛡️ Security & Recovery (OTP)

### 1. Send OTP
Initiates an OTP sequence for registration, password recovery, or verification.

*   **Endpoint:** `https://urbanwatch.me/api/v1/otp/send`
*   **Method:** `POST`
*   **Access:** Public
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | Target email. |
| `type` | string | Yes | One of: `registration`, `forgot_password`, `email_verification`. |

#### JSON Response
```json
{
  "success": true,
  "message": "OTP sent successfully"
}
```

### 2. Verify OTP
Validates the code sent to the user.

*   **Endpoint:** `https://urbanwatch.me/api/v1/otp/verify`
*   **Method:** `POST`
*   **Access:** Public
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | |
| `otp` | string | Yes | 6-digit code. |
| `type` | string | Yes | Must match the `send` type. |

#### JSON Response
```json
{
  "success": true,
  "message": "OTP verified successfully",
  "data": {
    "token": "7382a893-1b2c-4d5e..." // Required for password reset
  }
}
```

### 3. Reset Password
Sets a new password using a verified token.

*   **Endpoint:** `https://urbanwatch.me/api/v1/password/reset`
*   **Method:** `POST`
*   **Access:** Public
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | |
| `token` | string | Yes | Token from `/otp/verify`. |
| `password` | string | Yes | New password. |
| `password_confirmation` | string | Yes | |

#### JSON Response
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

---

## 📢 Citizen Concerns

### 1. List Concerns
Fetches concerns submitted by the authenticated user.

*   **Endpoint:** `https://urbanwatch.me/api/v1/concerns`
*   **Method:** `GET`
*   **Access:** Authenticated (Citizen)
*   **Header:** `Authorization: Bearer <token>`

#### JSON Response
```json
{
  "success": true,
  "message": "Manual concerns retrieved successfully",
  "data": {
    "concerns": [
      {
        "id": 101,
        "title": "Voice Concern - Nov 24, 08:30",
        "description": "Audio recording received...",
        "category": "safety",
        "severity": "low",
        "latitude": "14.123",
        "longitude": "121.987",
        "status": "pending",
        "created_at": "2025-11-24T08:30:00.000000Z",
        "images": []
      }
    ]
  }
}
```

### 2. Submit Concern (Manual or Voice)
Uploads a new issue report. This single endpoint handles both text-based ("manual") and audio-based ("voice") concerns.

*   **Endpoint:** `https://urbanwatch.me/api/v1/concerns`
*   **Method:** `POST`
*   **Access:** Authenticated (Citizen)
*   **Header:** `Content-Type: multipart/form-data`

#### Request Parameters
The API enforces **conditional validation** based on the `type` field.

| Field | Type | Required? | Description |
| :--- | :--- | :--- | :--- |
| `type` | `enum` | **Yes** | Must be `manual` or `voice`. |
| `category` | `enum` | **Yes** | `safety`, `security`, `infrastructure`, `environment`, `noise`, `other`. |
| `files[]` | `file` | **Conditional** | **Required if `type=voice`**. <br>Max 3 files. <br>Allowed: `mp3`, `wav`, `m4a`, `aac` (Audio) OR `jpg`, `png` (Images). <br>Max size: 10MB per file. |
| `title` | `string` | **Conditional** | **Required if `type=manual`**. <br>Ignored/Optional for `voice`. Max 100 chars. |
| `description` | `string` | **Conditional** | **Required if `type=manual`**. <br>Ignored/Optional for `voice`. Detailed explanation. |
| `severity` | `enum` | No | `low`, `medium`, `high`. Defaults to `low`. |
| `latitude` | `numeric` | Yes | GPS Latitude. |
| `longitude` | `numeric` | Yes | GPS Longitude. |
| `address` | `string` | No | Human-readable address. |
| `custom_location` | `string` | No | Specific landmark details. |

#### Example Requests & Responses

##### Scenario A: Voice Concern (Audio)
**Request Body (`multipart/form-data`):**
```json
{
  "type": "voice",
  "category": "safety",
  "latitude": "14.12345",
  "longitude": "121.98765",
  "files[0]": (binary_audio_file.mp3)
}
```

**JSON Response (Voice):**
```json
{
  "success": true,
  "message": "Concern submitted successfully!",
  "data": {
    "concern": {
      "id": 101,
      "title": "Voice Concern - Nov 24, 08:30",
      "description": "Audio recording received. Transcription pending...",
      "category": "safety",
      "severity": "low",
      "status": "pending",
      "created_at": "2025-11-24T08:30:00.000000Z",
      "images": [],
      "audio": "https://r2.cloudflarestorage.com/bucket/concerns/audio/12345.mp3",
      "summary": null,
      "transcript": null
    }
  }
}
```

##### Scenario B: Manual Concern (Text)
**Request Body (`multipart/form-data`):**
```json
{
  "type": "manual",
  "title": "Broken Streetlight",
  "description": "The light at the corner of 5th street is flickering.",
  "category": "infrastructure",
  "latitude": "14.12345",
  "longitude": "121.98765",
  "files[0]": (binary_image_file.jpg)
}
```

**JSON Response (Manual):**
```json
{
  "success": true,
  "message": "Concern submitted successfully!",
  "data": {
    "concern": {
      "id": 102,
      "title": "Broken Streetlight",
      "description": "The light at the corner of 5th street is flickering.",
      "category": "infrastructure",
      "severity": "low",
      "status": "pending",
      "created_at": "2025-11-24T08:35:00.000000Z",
      "images": [
        "https://r2.cloudflarestorage.com/bucket/concerns/images/67890.jpg"
      ],
      "audio": null,
      "summary": null,
      "transcript": null
    }
  }
}
```

### 3. Update Concern
Modifies the text content of a concern.

*   **Endpoint:** `https://urbanwatch.me/api/v1/concerns/{id}`
*   **Method:** `PUT` or `PATCH`
*   **Access:** Authenticated (Owner)
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `title` | string | No | New title. |
| `description` | string | No | New description. |

#### JSON Response
```json
{
  "success": true,
  "message": "Manual concern updated successfully",
  "data": {
    "concern": {
      "id": 102,
      "title": "Updated Title",
      "description": "Updated description...",
      "updated_at": "2025-11-24T09:00:00.000000Z"
    }
  }
}
```

---

## 🚑 Operator & Emergency Management

### 1. List Contacts
Retrieves emergency responders.

*   **Endpoint:** `https://urbanwatch.me/api/v1/contacts`
*   **Method:** `GET`
*   **Access:** Authenticated
*   **Header:** `Authorization: Bearer <token>`

#### Query Parameters
| Field | Type | Description |
| :--- | :--- | :--- |
| `search` | string | Search by name or details. |
| `responder_type` | string | Filter by type (e.g., police, fire). |
| `active` | boolean | `true` for active only. |

#### JSON Response
```json
{
  "success": true,
  "message": "Contacts retrieved successfully",
  "data": {
    "contacts": [
      {
        "id": 1,
        "name": "Central Fire Station",
        "contact_number": "911",
        "type": "fire",
        "active": true
      }
    ],
    "meta": {
      "current_page": 1,
      "total": 50
    }
  }
}
```

### 2. Create Contact
Adds a new responder unit to the system.

*   **Endpoint:** `https://urbanwatch.me/api/v1/contacts`
*   **Method:** `POST`
*   **Access:** Authenticated (Operator/Admin)
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `name` | string | Yes | Name of the contact/agency. |
| `contact_number` | string | Yes | Phone number. |
| `type` | string | Yes | e.g., `police`, `ambulance`. |
| `latitude` | numeric | Yes | |
| `longitude` | numeric | Yes | |

#### JSON Response
```json
{
  "success": true,
  "message": "Contact created successfully!",
  "data": {
    "contact": { "id": 2, "name": "Police Station 1", ... }
  }
}
```

### 3. List Accidents
Retrieves reported accidents for the operator dashboard.

*   **Endpoint:** `https://urbanwatch.me/api/v1/accidents`
*   **Method:** `GET`
*   **Access:** Authenticated (Operator)
*   **Header:** `Authorization: Bearer <token>`

#### JSON Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "Collision",
      "status": "ongoing",
      "location": "Main St."
    }
  ]
}
```

### 4. Update Accident Status
Changes the workflow state of an accident record.

*   **Endpoint:** `https://urbanwatch.me/api/v1/accidents/{id}/status`
*   **Method:** `PATCH`
*   **Access:** Authenticated (Operator)
*   **Header:** `Content-Type: application/json`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `status` | string | Yes | `pending`, `ongoing`, `resolved`, `archived`. |

#### JSON Response
```json
{
  "success": true,
  "message": "Status updated",
  "data": { "id": 1, "status": "resolved" }
}
```

---

## 🤖 AI Integrations (YOLO)

### 1. Process Snapshot
Ingests data from the CCTV YOLO detection system.

*   **Endpoint:** `https://urbanwatch.me/api/v1/yolo/accidents/snapshot`
*   **Method:** `POST`
*   **Access:** Public (API Key recommended)
*   **Header:** `Content-Type: multipart/form-data`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `snapshot` | file | Yes | Image file of the event. |
| `accident_type` | string | No | Classification of accident. |
| `severity` | string | No | Estimated severity. |
| `latitude` | numeric | No | |
| `longitude` | numeric | No | |

#### JSON Response
```json
{
  "success": true,
  "message": "Snapshot processed"
}
```

---

## 📚 Appendix A: Real-time Distribution Architecture

### Overview
This feature automatically distributes citizen concerns to purok leaders and sends real-time notifications via Pusher when a new concern is submitted.

### Events (ConcernAssigned Event)
**Channel:** `private-purok-leader.{purok_leader_id}` (Laravel automatically prepends `private-` for private channels)
**Event:** `concern.assigned`

**Important Note on `purok_leader_id`:** Currently, the backend logic for assigning concerns to a Purok Leader is hardcoded to `purok_leader_id = 2`. This means that only frontend applications authenticated as `User ID 2` (with `role_id = 2`) will receive these broadcasted events.

**React Native Implementation Guide:**

1.  **Install Dependencies:**
    ```bash
    npm install pusher-js @pusher/pusher-websocket-react-native
    ```

2.  **Initialize Pusher:**
    ```javascript
    import Pusher from 'pusher-js/react-native';

    // Initialize with your config and Auth Endpoint
    // Ensure these environment variables are available in your frontend build process
    const pusher = new Pusher(process.env.VITE_PUSHER_APP_KEY || 'YOUR_PUSHER_APP_KEY', {
      cluster: process.env.VITE_PUSHER_APP_CLUSTER || 'YOUR_APP_CLUSTER', // e.g., 'ap1'
      encrypted: true,
      authEndpoint: 'https://urbanwatch.me/broadcasting/auth', // Laravel's default broadcasting authentication route
      auth: {
        headers: {
          'Authorization': `Bearer ${userToken}`, // Pass the logged-in user's Sanctum token
          'Accept': 'application/json',
        }
      }
    });
    ```

3.  **Subscribe & Listen:**
    ```javascript
    // The user ID (purok leader) comes from your auth state
    // For now, this should typically be hardcoded to `2` on the client-side for testing purposes
    // if the backend `purok_leader_id` is still hardcoded.
    const userId = user.id; 
    const channelName = `private-purok-leader.${userId}`;
    
    const channel = pusher.subscribe(channelName);

    channel.bind('.concern.assigned', (data) => { // Note the leading dot for client-named events
      console.log('New Concern Received:', data);
      
      // Data structure matches the payload below
      const { concern, citizen, distribution } = data;

      // Example: Play audio if available
      if (concern.audio) {
        playAudio(concern.audio); 
      }
      
      // Show notification
      Alert.alert('New Concern', `${citizen.name} reported: ${concern.title}`);
    });
    ```

**Payload Example (Voice Concern):**
```json
{
  "concern": {
    "id": 42,
    "title": "Voice Concern - Nov 24, 08:30",
    "description": "Audio recording received. Transcription pending...",
    "category": "safety",
    "severity": "low",
    "status": "pending",
    "created_at": "2025-11-24T08:30:00.000000Z",
    "images": [],
    "audio": "https://r2.cloudflarestorage.com/bucket/concerns/audio/12345.mp3",
    "summary": null,
    "transcript": null
  },
  "citizen": {
    "name": "Juan Dela Cruz"
  },
  "distribution": {
    "id": 1,
    "status": "assigned",
    "assigned_at": "2025-11-24T08:30:00.000000Z"
  }
}
```