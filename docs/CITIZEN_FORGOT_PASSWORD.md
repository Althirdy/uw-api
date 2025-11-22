# Citizen Forgot Password API Integration Guide

## Overview

This document details the API endpoints and integration workflow for the Citizen Forgot Password feature. It is designed to guide frontend developers in implementing a secure and user-friendly password recovery process.

## 🔄 Integration Workflow

The password reset process consists of three distinct steps:

1.  **Request OTP**: The user provides their registered email address, which is then sent via a POST request to initiate the OTP sending process. The system sends a One-Time Password (OTP) to that email.
2.  **Verify OTP**: The user enters the received OTP. The system verifies it and, if valid, returns a temporary **reset token**.
3.  **Reset Password**: The user provides a new password along with the **reset token**. The system updates the password and invalidates the token.

---

## 📡 API Reference

### 1. Initiate Password Reset (Send OTP)

Triggers the generation and dispatch of an OTP to the user's email address.

-   **Endpoint:** `POST /api/v1/otp/send`
-   **Access:** Public
-   **Description:** specific `type` must be set to `"forgot_password"` to ensure the correct email template and logic are used.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | `string` | Yes | The registered email address of the user. |
| `type` | `string` | Yes | Must be exactly `"forgot_password"`. |

#### Example Request
```json
{
  "email": "citizen@example.com",
  "type": "forgot_password"
}
```

#### Responses

**200 OK** - OTP sent successfully
```json
{
  "success": true,
  "message": "OTP sent successfully to your email."
}
```

**422 Unprocessable Entity** - Validation Error (e.g., email not found)
```json
{
  "message": "The selected email is invalid.",
  "errors": {
    "email": ["The selected email is invalid."]
  }
}
```

---

### 2. Verify OTP & Retrieve Token

Verifies the OTP provided by the user. If successful, it returns a secure **reset token** required for the final step.

-   **Endpoint:** `POST /api/v1/otp/verify`
-   **Access:** Public
-   **Description:** This step is critical. The frontend **must** capture the `token` from the response data to authorize the password reset request.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | `string` | Yes | The registered email address. |
| `otp` | `string` | Yes | The 6-digit code received via email. |
| `type` | `string` | Yes | Must be exactly `"forgot_password"`. |

#### Example Request
```json
{
  "email": "citizen@example.com",
  "otp": "123456",
  "type": "forgot_password"
}
```

#### Responses

**200 OK** - Verification Successful
```json
{
  "success": true,
  "message": "OTP verified successfully",
  "data": {
    "email": "citizen@example.com",
    "type": "forgot_password",
    "token": "7382a893-1b2c-4d5e-9f0a-secure_token_string"
  }
}
```

**400 Bad Request** - Invalid or Expired OTP
```json
{
  "success": false,
  "message": "Invalid or expired OTP."
}
```

---

### 3. Set New Password

Finalizes the process by setting the new password. This requires the secure token obtained from the previous step.

-   **Endpoint:** `POST /api/v1/password/reset`
-   **Access:** Public
-   **Description:** Resets the user's password. Existing authentication tokens for the user will be revoked for security.

#### Request Body
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `email` | `string` | Yes | The user's email address. |
| `token` | `string` | Yes | The secure token received from the `/otp/verify` endpoint. |
| `password` | `string` | Yes | The new password (min. 8 characters). |
| `password_confirmation` | `string` | Yes | Must match the `password` field. |

#### Example Request
```json
{
  "email": "citizen@example.com",
  "token": "7382a893-1b2c-4d5e-9f0a-secure_token_string",
  "password": "newSecurePassword123!",
  "password_confirmation": "newSecurePassword123!"
}
```

#### Responses

**200 OK** - Password Reset Successful
```json
{
  "success": true,
  "message": "Password has been successfully reset."
}
```

**400 Bad Request** - Invalid Token or Logic Error
```json
{
  "success": false,
  "message": "Invalid or expired reset token."
}
```

**422 Unprocessable Entity** - Validation Error (e.g., passwords do not match)
```json
{
  "message": "The password field confirmation does not match.",
  "errors": {
    "password": ["The password field confirmation does not match."]
  }
}
```

---

## 🛑 Common Error Codes

| Status Code | Meaning | Description |
| :--- | :--- | :--- |
| **200** | OK | Request processed successfully. |
| **400** | Bad Request | Business logic failure (e.g., invalid OTP, expired token). |
| **422** | Unprocessable Entity | Validation failure (e.g., invalid email format, weak password). |
| **429** | Too Many Requests | Rate limit exceeded. Please implement exponential backoff. |
| **500** | Internal Server Error | Server-side issue. |
