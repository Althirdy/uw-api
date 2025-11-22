# API Documentation

**Base URL**: `/api/v1`

This API uses Laravel Sanctum for authentication. All protected endpoints require a valid Bearer Token in the `Authorization` header.

## Authentication & OTP

### 1. Send OTP
Sends a One-Time Password to the specified email address.

*   **Endpoint**: `POST /api/v1/otp/send`
*   **Access**: Public
*   **Request Body**:
    ```json
    {
        "email": "user@example.com", // required, email
        "type": "registration", // required, in: registration, forgot_password, email_verification
        "name": "John Doe" // optional, string
    }
    ```
*   **Response**:
    ```json
    {
        "success": true,
        "message": "OTP sent successfully",
        "data": {
            "email": "user@example.com",
            "expires_in": 10
        }
    }
    ```

### 2. Verify OTP
Verifies the OTP sent to the user.

*   **Endpoint**: `POST /api/v1/otp/verify`
*   **Access**: Public
*   **Request Body**:
    ```json
    {
        "email": "user@example.com", // required, email
        "otp": "123456", // required, string, 6 digits
        "type": "registration" // required, in: registration, forgot_password, email_verification
    }
    ```
*   **Response**:
    ```json
    {
        "success": true,
        "message": "OTP verified successfully",
        "data": {
            "email": "user@example.com",
            "type": "registration"
        }
    }
    ```

### 3. Register Citizen
Registers a new citizen user.

*   **Endpoint**: `POST /api/v1/register`
*   **Access**: Public
*   **Request Body**:
    ```json
    {
        "email": "user@example.com", // required, unique
        "password": "password123", // required, min: 8, confirmed
        "password_confirmation": "password123",
        "first_name": "John", // required
        "middle_name": "D", // optional
        "last_name": "Doe", // required
        "suffix": "Jr", // optional
        "date_of_birth": "1990-01-01", // required, date, before today
        "phone_number": "09123456789", // required
        "address": "123 Main St", // required
        "barangay": "Barangay 1", // required
        "city": "City Name", // required
        "province": "Province Name", // required
        "postal_code": "1234" // required
    }
    ```
*   **Response**:
    ```json
    {
        "success": true,
        "message": "Registration successful",
        "data": {
            "token": "1|...",
            "refreshToken": "2|...",
            "user": {
                "id": 1,
                "firstName": "John",
                "lastName": "Doe",
                "email": "user@example.com",
                "role": "Citizen"
                 // ... other details
            }
        }
    }
    ```

### 4. Login (Citizen/Official)
Logs in a user using email and password.

*   **Endpoint**: `POST /api/v1/login`
*   **Access**: Public
*   **Request Body**:
    ```json
    {
        "email": "user@example.com", // required, email
        "password": "password123" // required
    }
    ```
*   **Response**:
    ```json
    {
        "success": true,
        "data": {
            "token": "1|...",
            "refreshToken": "2|...",
            "user": {
                "id": 1,
                "role": "Citizen"
                // ... user details
            }
        }
    }
    ```

### 5. Login (Purok Leader)
Logs in a Purok Leader using a PIN.

*   **Endpoint**: `POST /api/v1/login/purok-leader`
*   **Access**: Public
*   **Request Body**:
    ```json
    {
        "pin": "123456" // required
    }
    ```

### 6. Get Authenticated User
Retrieves details of the currently authenticated user.

*   **Endpoint**: `GET /api/v1/auth/user`
*   **Access**: Authenticated (Sanctum)
*   **Response**: Returns user details based on role.

### 7. Refresh Token
Refreshes the access token using a refresh token.

*   **Endpoint**: `POST /api/v1/refresh-token`
*   **Access**: Authenticated (Sanctum - requires token with 'refresh-token' ability)
*   **Response**:
    ```json
    {
        "success": true,
        "message": "Token refreshed successfully",
        "data": {
            "token": "new_access_token",
            "refreshToken": "new_refresh_token"
        }
    }
    ```

### 8. Logout
Revokes the current access token.

*   **Endpoint**: `POST /api/v1/logout`
*   **Access**: Authenticated (Sanctum)

---

## Citizen Concerns

### 1. List Concerns
Retrieves a list of manual concerns submitted by the authenticated citizen.

*   **Endpoint**: `GET /api/v1/concerns`
*   **Access**: Authenticated (Citizen)
*   **Response**:
    ```json
    {
        "success": true,
        "message": "Manual concerns retrieved successfully",
        "data": {
            "concerns": [
                {
                    "id": 1,
                    "title": "Pothole",
                    "description": "Big pothole on Main St",
                    "category": "infrastructure",
                    "status": "pending",
                    "images": ["url1", "url2"],
                    "distribution": { ... }
                }
            ]
        }
    }
    ```

### 2. Submit Concern
Submits a new manual concern.

*   **Endpoint**: `POST /api/v1/concerns`
*   **Access**: Authenticated (Citizen)
*   **Request Body** (Multipart/Form-Data):
    *   `title` (string, required, max: 100)
    *   `description` (string, required)
    *   `category` (string, required, in: safety, security, infrastructure, environment, noise, other)
    *   `severity` (string, optional, in: low, medium, high)
    *   `latitude` (numeric, optional)
    *   `longitude` (numeric, optional)
    *   `transcript_text` (string, optional)
    *   `files[]` (file array, optional, max 3, images only)
*   **Response**:
    ```json
    {
        "success": true,
        "message": "Concern submitted successfully!",
        "data": {
            "concern": { ... }
        }
    }
    ```

### 3. Update Concern
Updates an existing concern (only title and description).

*   **Endpoint**: `PUT/PATCH /api/v1/concerns/{id}`
*   **Access**: Authenticated (Citizen - Owner only)
*   **Request Body**:
    *   `title` (string, required)
    *   `description` (string, required)

---

## Operator & Contacts

### 1. List Contacts
Retrieves a paginated list of emergency contacts.

*   **Endpoint**: `GET /api/v1/contacts`
*   **Access**: Authenticated
*   **Query Parameters**:
    *   `search` (string)
    *   `responder_type` (string)
    *   `active` (boolean)

### 2. Create Contact
Creates a new emergency contact.

*   **Endpoint**: `POST /api/v1/contacts`
*   **Access**: Authenticated
*   **Request Body**:
    ```json
    {
        "branch_unit_name": "BDRRM", // required, enum
        "responder_type": "Emergency", // required, enum
        "location": "Location String", // required
        "primary_mobile": "09123456789", // required, 11 digits
        "latitude": 14.123, // required
        "longitude": 121.123, // required
        "active": true // boolean
    }
    ```

### 3. Heatmap Contacts
Retrieves all active contacts for heatmap display.

*   **Endpoint**: `GET /api/v1/contacts/heatmap`
*   **Access**: Authenticated

---

## Accidents & Reports (Operator)

### 1. List Accidents
Retrieves a paginated list of accidents/reports.

*   **Endpoint**: `GET /api/v1/accidents`
*   **Access**: Authenticated
*   **Query Parameters**:
    *   `search` (string)
    *   `accident_type` (string)
    *   `status` (string)

### 2. Update Accident Status
Updates the status of an accident.

*   **Endpoint**: `PATCH /api/v1/accidents/{id}/status`
*   **Access**: Authenticated
*   **Request Body**:
    ```json
    {
        "status": "resolved" // required, in: pending, ongoing, resolved, archived
    }
    ```

---

## YOLO Detection

### 1. Process Snapshot
Endpoint for the YOLO system to upload accident snapshots.

*   **Endpoint**: `POST /api/v1/yolo/accidents/snapshot`
*   **Access**: Public (Intended for YOLO script)
*   **Request Body** (Multipart/Form-Data):
    *   `snapshot` (file, required)
    *   `accident_type` (string)
    *   `severity` (string)
    *   `title` (string)
    *   `description` (string)
    *   `latitude` (numeric)
    *   `longitude` (numeric)