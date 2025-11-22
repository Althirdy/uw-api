# 📘 API Documentation

**Version:** 1.0
**Base URL:** `/api/v1`

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

*   **Endpoint:** `POST /register`
*   **Access:** Public

#### Request Body
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

#### Success Response
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token": "1|laravel_sanctum_token...",
    "user": { "id": 1, "email": "user@example.com", "role": "Citizen", ... }
  }
}
```

### 2. Login (Standard)
Authenticates Citizens and Officials/Operators.

*   **Endpoint:** `POST /login`
*   **Access:** Public

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | Registered email. |
| `password` | string | Yes | Account password. |

### 3. Login (Purok Leader)
Specialized login for Purok Leaders using a PIN code.

*   **Endpoint:** `POST /login/purok-leader`
*   **Access:** Public
*   **Request Body:** `{ "pin": "123456" }`

### 4. Get Authenticated User
Retrieves the profile of the currently logged-in user.

*   **Endpoint:** `GET /auth/user`
*   **Access:** Authenticated

### 5. Refresh Token
Obtains a new access token using a valid refresh token.

*   **Endpoint:** `POST /refresh-token`
*   **Access:** Authenticated (requires token with `refresh-token` ability)

### 6. Logout
Invalidates the current access token.

*   **Endpoint:** `POST /logout`
*   **Access:** Authenticated

---

## 🛡️ Security & Recovery (OTP)

### 1. Send OTP
Initiates an OTP sequence for registration, password recovery, or verification.

*   **Endpoint:** `POST /otp/send`
*   **Access:** Public

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | Target email. |
| `type` | string | Yes | One of: `registration`, `forgot_password`, `email_verification`. |

### 2. Verify OTP
Validates the code sent to the user.

*   **Endpoint:** `POST /otp/verify`
*   **Access:** Public

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | string | Yes | |
| `otp` | string | Yes | 6-digit code. |
| `type` | string | Yes | Must match the `send` type. |

#### Response (Forgot Password)
Returns a `token` in the data object required for the password reset endpoint.

### 3. Reset Password
Sets a new password using a verified token.

*   **Endpoint:** `POST /password/reset`
*   **Access:** Public

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `token` | string | Yes | Token from `/otp/verify`. |
| `email` | string | Yes | |
| `password` | string | Yes | New password. |
| `password_confirmation` | string | Yes | |

---

## 📢 Citizen Concerns

### 1. List Concerns
Fetches concerns submitted by the authenticated user.

*   **Endpoint:** `GET /concerns`
*   **Access:** Authenticated (Citizen)

### 2. Submit Concern
Uploads a new issue report with optional media.

*   **Endpoint:** `POST /concerns`
*   **Access:** Authenticated (Citizen)
*   **Content-Type:** `multipart/form-data`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `title` | string | Yes | Max 100 chars. |
| `description` | string | Yes | Detailed explanation. |
| `category` | string | Yes | `safety`, `infrastructure`, `environment`, etc. |
| `severity` | string | No | `low`, `medium`, `high`. |
| `latitude` | numeric | No | GPS Latitude. |
| `longitude` | numeric | No | GPS Longitude. |
| `files[]` | file | No | Array of images (max 3). |

### 3. Update Concern
Modifies the text content of a concern.

*   **Endpoint:** `PUT /concerns/{id}`
*   **Access:** Authenticated (Owner)

---

## 🚑 Operator & Emergency Management

### 1. List Contacts
Retrieves emergency responders.

*   **Endpoint:** `GET /contacts`
*   **Access:** Authenticated
*   **Query Params:** `search`, `responder_type`, `active`

### 2. Create Contact
Adds a new responder unit to the system.

*   **Endpoint:** `POST /contacts`
*   **Access:** Authenticated (Operator/Admin)

### 3. List Accidents
Retrieves reported accidents for the operator dashboard.

*   **Endpoint:** `GET /accidents`
*   **Access:** Authenticated (Operator)
*   **Query Params:** `search`, `accident_type`, `status`

### 4. Update Accident Status
Changes the workflow state of an accident record.

*   **Endpoint:** `PATCH /accidents/{id}/status`
*   **Access:** Authenticated (Operator)

#### Request Body
```json
{
  "status": "resolved" // pending, ongoing, resolved, archived
}
```

---

## 🤖 AI Integrations (YOLO)

### 1. Process Snapshot
Ingests data from the CCTV YOLO detection system.

*   **Endpoint:** `POST /yolo/accidents/snapshot`
*   **Access:** Public (API Key recommended for production)
*   **Content-Type:** `multipart/form-data`

#### Request Parameters
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `snapshot` | file | Yes | Image file of the event. |
| `accident_type` | string | No | Classification of accident. |
| `severity` | string | No | Estimated severity. |
| `latitude` | numeric | No | |
| `longitude` | numeric | No | |
