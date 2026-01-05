# UrbanWatch API Documentation

**Version:** 1.0  
**Base URL:** `https://urbanwatch.me/api/v1`  
**Authentication:** Bearer Token (Laravel Sanctum)

---

## 📋 Table of Contents

- [Authentication](#authentication)
- [Response Format](#response-format)
- [Endpoints](#endpoints)
  - [Auth & User Management](#auth--user-management)
  - [OTP & Security](#otp--security)
  - [Citizen Concerns](#citizen-concerns)
  - [Purok Leader](#purok-leader)
  - [Emergency Contacts](#emergency-contacts)
  - [Accidents](#accidents)
  - [AI Services](#ai-services)
- [Real-time Events](#real-time-events)

---

## Authentication

Most endpoints require a Bearer token obtained from login/register.

**Header:**
```
Authorization: Bearer {your_access_token}
```

---

## Response Format

All responses follow this structure:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

---

## Endpoints

### Auth & User Management

#### `POST /register`
Register a new citizen account.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "first_name": "Juan",
  "last_name": "Dela Cruz",
  "date_of_birth": "1990-01-01",
  "phone_number": "09171234567",
  "address": "123 Main St",
  "barangay": "San Isidro",
  "city": "Makati",
  "province": "Metro Manila",
  "postal_code": "1200"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "token": "1|sanctum_token...",
    "user": { "id": 1, "email": "user@example.com" }
  }
}
```

---

#### `POST /login`
Authenticate citizen or operator.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

---

#### `POST /login/purok-leader`
Authenticate purok leader via PIN.

**Request:**
```json
{
  "pin": "123456"
}
```

---

#### `GET /auth/user`
Get authenticated user profile.

**Headers:** `Authorization: Bearer {token}`

---

#### `POST /logout`
Invalidate access token.

**Headers:** `Authorization: Bearer {token}`

---

### OTP & Security

#### `POST /otp/send`
Send OTP code to email.

**Request:**
```json
{
  "email": "user@example.com",
  "type": "registration" // or "forgot_password", "email_verification"
}
```

---

#### `POST /otp/verify`
Verify OTP code.

**Request:**
```json
{
  "email": "user@example.com",
  "otp": "123456",
  "type": "registration"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token": "reset_token_here"
  }
}
```

---

#### `POST /password/reset`
Reset password using verified token.

**Request:**
```json
{
  "email": "user@example.com",
  "token": "reset_token_here",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

### Citizen Concerns

#### `GET /concerns`
List all concerns submitted by authenticated user.

**Headers:** `Authorization: Bearer {token}`

---

#### `POST /concerns`
Submit a new concern (manual or voice).

**Headers:** 
- `Authorization: Bearer {token}`
- `Content-Type: multipart/form-data`

**Request (Voice Concern):**
```
type: voice
category: safety
severity: medium
latitude: 14.5995
longitude: 120.9842
address: Main Street, Guadalupe
files[0]: [audio file]
```

**Request (Manual Concern):**
```
type: manual
title: Broken Streetlight
description: The streetlight is not working
category: infrastructure
latitude: 14.5995
longitude: 120.9842
files[0]: [image file]
```

**Response:**
```json
{
  "success": true,
  "data": {
    "concern": {
      "id": 123,
      "tracking_code": "CN-20251210-ABCD",
      "title": "Broken Streetlight",
      "status": "pending",
      "transcription_status": "queued" // for voice concerns
    }
  }
}
```

---

#### `PUT /concerns/{id}`
Update concern details.

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "title": "Updated Title",
  "description": "Updated description"
}
```

---

#### `DELETE /concerns/{id}`
Delete a concern.

**Headers:** `Authorization: Bearer {token}`

---

### Purok Leader

#### `GET /purok-leader/concerns`
List concerns assigned to authenticated purok leader.

**Headers:** `Authorization: Bearer {token}`
**Role:** Purok Leader (role_id: 2)

---

#### `GET /purok-leader/concerns/{id}`
Get specific concern details.

**Headers:** `Authorization: Bearer {token}`

---

#### `PUT /purok-leader/concerns/{id}/status`
Update concern status.

**Headers:** `Authorization: Bearer {token}`

**Request:**
```json
{
  "status": "ongoing" // pending, ongoing, escalated, resolved
}
```

---

### Emergency Contacts

#### `GET /contacts`
List emergency responders.

**Query Params:** `search`, `responder_type`, `active`

---

#### `POST /contacts`
Create new contact.

**Request:**
```json
{
  "name": "Police Station 1",
  "contact_number": "911",
  "type": "police",
  "latitude": 14.5995,
  "longitude": 120.9842
}
```

---

### Accidents

#### `GET /accidents`
List reported accidents.

**Headers:** `Authorization: Bearer {token}`

---

#### `PATCH /accidents/{id}/status`
Update accident status.

**Request:**
```json
{
  "status": "resolved" // pending, ongoing, resolved, archived
}
```

---

### AI Services

#### `POST /yolo/accidents/snapshot`
Process CCTV accident detection snapshot.

**Headers:** `Content-Type: multipart/form-data`

**Request:**
```
snapshot: [image file]
accident_type: Collision
severity: high
latitude: 14.5995
longitude: 120.9842
```

---

#### `POST /ocr/national-id`
Extract data from Philippine National ID (front side).

**Headers:** `Content-Type: multipart/form-data`

**Request:**
```
image: [ID image file - jpg, png, heic, heif]
```

**Response:**
```json
{
  "success": true,
  "data": {
    "pcn_number": "1234-5678-9012-3456",
    "last_name": "DELA CRUZ",
    "given_name": "JUAN",
    "middle_name": "SANTOS",
    "date_of_birth": "01/15/1990",
    "address": "123 Sample St",
    "barangay": "Barangay San Isidro",
    "city": "Makati City",
    "region": "NCR",
    "full_address": "123 Sample St, Barangay San Isidro, Makati City, NCR"
  }
}
```

---

## Real-time Events

### WebSocket Integration (Pusher)

**Channel:** `private-purok-leader.{user_id}`  
**Event:** `concern.assigned`

**Setup (React Native):**

```javascript
import Pusher from 'pusher-js/react-native';

const pusher = new Pusher('YOUR_PUSHER_KEY', {
  cluster: 'ap1',
  authEndpoint: 'https://urbanwatch.me/broadcasting/auth',
  auth: {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  }
});

const channel = pusher.subscribe(`private-purok-leader.${userId}`);

channel.bind('.concern.assigned', (data) => {
  const { concern, citizen, distribution } = data;
  // Handle new concern notification
});
```

**Event Payload:**
```json
{
  "concern": {
    "id": 123,
    "title": "Voice Concern - Dec 10, 11:30",
    "category": "safety",
    "status": "pending",
    "audio": "https://storage.url/audio.mp3"
  },
  "citizen": {
    "name": "Juan Dela Cruz"
  },
  "distribution": {
    "status": "assigned"
  }
}
```

---

## Additional Events

- `concern.description.updated` - Triggered when audio transcription completes
- Channels: `citizen.{citizen_id}`, `purok-leader.{purok_leader_id}`

---

**For more details, see:**
- [Audio Transcription Guide](/docs/MOBILE_AUDIO_TRANSCRIPTION_GUIDE.md)
- [Queue Setup Guide](/docs/QUEUE_SETUP_GUIDE.md)
