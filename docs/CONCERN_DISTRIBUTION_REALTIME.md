# Concern Distribution with Real-time Notification Feature

## Overview
This feature automatically distributes citizen concerns to purok leaders and sends real-time notifications via Pusher when a new concern is submitted.

## Implementation Date
November 6, 2025

## Branch
`feature/concern-distribution-realtime`

---

## Database Schema

### Table: `concern_distribution`
Stores the assignment of concerns to purok leaders.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint (PK) | Primary key |
| `concern_id` | bigint (FK) | Foreign key to `concerns` table |
| `purok_leader_id` | bigint (FK) | Foreign key to `users` table (role_id = 2) |
| `status` | enum | Status: 'assigned', 'acknowledged', 'in_progress', 'resolved' |
| `assigned_at` | timestamp | When the concern was assigned |
| `acknowledged_at` | timestamp (nullable) | When purok leader acknowledged |
| `created_at` | timestamp | Record creation time |
| `updated_at` | timestamp | Record update time |

**Indexes:**
- `concern_id`
- `purok_leader_id`
- `status`

**Migration File:** `database/migrations/2025_11_06_000000_create_concern_distribution_table.php`

---

## Models

### ConcernDistribution Model
**Location:** `app/Models/ConcernDistribution.php`

**Relationships:**
- `concern()` - BelongsTo `App\Models\Citizen\Concern`
- `purokLeader()` - BelongsTo `App\Models\User`

**Fillable Fields:**
- `concern_id`
- `purok_leader_id`
- `status`
- `assigned_at`
- `acknowledged_at`

**Casts:**
- `assigned_at` → datetime
- `acknowledged_at` → datetime

---

## Events

### ConcernAssigned Event
**Location:** `app/Events/ConcernAssigned.php`

**Implements:** `ShouldBroadcast`

**Purpose:** Broadcasts real-time notification to purok leader when a concern is assigned.

**Channel:** `private-purok-leader.{purok_leader_id}`

**Event Name:** `concern.assigned`

**Payload:**
```json
{
  "concern": {
    "id": 1,
    "title": "Concern title",
    "description": "Concern description",
    "category": "infrastructure",
    "severity": "low",
    "status": "pending",
    "latitude": "14.6095",
    "longitude": "120.9742",
    "created_at": "2025-11-06T10:30:00.000000Z",
    "images": ["url1", "url2"]
  },
  "distribution": {
    "id": 1,
    "status": "assigned",
    "assigned_at": "2025-11-06T10:30:00.000000Z"
  },
  "citizen": {
    "name": "Citizen Name"
  }
}
```

---

## Controller Updates

### ManualConcernController
**Location:** `app/Http/Controllers/Api/Citizen/Concern/ManualConcernController.php`

**Modified Method:** `store()`

**New Imports:**
```php
use App\Models\ConcernDistribution;
use App\Events\ConcernAssigned;
```

**Process Flow:**
1. Create concern record
2. Upload files (if any)
3. Save media to database
4. **NEW:** Create distribution record (assign to purok leader)
5. **NEW:** Broadcast real-time notification via Pusher
6. Commit transaction
7. Return response

**Distribution Logic (Step 4):**
```php
// TODO: Replace hardcoded purok_leader_id with actual location-based logic
// For now, all concerns are assigned to purok leader with ID = 2 (Adoracion S. Jumadiao)
$purokLeaderId = 2;

$distribution = ConcernDistribution::create([
    'concern_id' => $concern->id,
    'purok_leader_id' => $purokLeaderId,
    'status' => 'assigned',
    'assigned_at' => now(),
]);
```

**Broadcasting (Step 5):**
```php
event(new ConcernAssigned($concern, $distribution, $uploadedMedia));
```

---

## Broadcasting Configuration

### Channel Authorization
**Location:** `routes/channels.php`

**Private Channel Definition:**
```php
// Purok Leader private channel for receiving concern assignments
Broadcast::channel('purok-leader.{purokLeaderId}', function ($user, $purokLeaderId) {
    // Only allow access if the user is a purok leader (role_id = 2) and the channel matches their ID
    return (int) $user->id === (int) $purokLeaderId && (int) $user->role_id === 2;
});
```

**Security:**
- Only users with `role_id = 2` (purok leaders) can subscribe
- Users can only subscribe to their own channel (ID must match)

---

## Current Configuration

### Hardcoded Purok Leader
**Current Value:** User ID = 2 (Adoracion S. Jumadiao)

**Reason:** For testing/development. Location-based assignment to be implemented later.

**Location:** `ManualConcernController@store()` - Line ~105

**TODO Comment:** 
```php
// TODO: Replace hardcoded purok_leader_id with actual location-based logic
// For now, all concerns are assigned to purok leader with ID = 2 (Adoracion S. Jumadiao)
```

---

## Testing

### Prerequisites
1. Pusher credentials configured in `.env`:
   - `PUSHER_APP_ID`
   - `PUSHER_APP_KEY`
   - `PUSHER_APP_SECRET`
   - `PUSHER_APP_CLUSTER`
2. Broadcasting driver set to `pusher` in `.env`:
   - `BROADCAST_DRIVER=pusher`
3. User with ID = 2 exists (purok leader - Adoracion S. Jumadiao)

### Test Steps
1. Login as citizen (user_id = 3, Sanger Briones)
2. Submit a manual concern via API: `POST /api/citizen/concerns/manual`
3. Check database: Verify record in `concern_distribution` table
4. Check Pusher dashboard: Verify event was broadcasted
5. Login as purok leader (user_id = 2) in mobile app
6. Verify real-time notification received

### Endpoints Used
- **Submit Concern:** `POST /api/citizen/concerns/manual`
- **Pusher Auth:** `POST /broadcasting/auth` (automatic)

---

## React Native Integration

### Required Packages
```bash
npm install pusher-js @pusher/pusher-websocket-react-native
```

### Basic Implementation
```javascript
import Pusher from 'pusher-js/react-native';

// Initialize Pusher
const pusher = new Pusher('YOUR_PUSHER_APP_KEY', {
  cluster: 'YOUR_PUSHER_CLUSTER',
  encrypted: true,
  authEndpoint: 'https://your-api-url.com/broadcasting/auth',
  auth: {
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Accept': 'application/json',
    }
  }
});

// Subscribe to channel (userId should be 2 for testing)
const channel = pusher.subscribe(`private-purok-leader.${userId}`);

// Listen for events
channel.bind('concern.assigned', (data) => {
  console.log('New concern received:', data);
  // Handle notification
});
```

### Data Structure Received
See **Events > ConcernAssigned Event > Payload** section above.

---

## Future Enhancements

### 1. Location-Based Assignment
Replace hardcoded `purokLeaderId = 2` with logic to:
- Calculate distance from concern location to purok coverage areas
- Assign to nearest purok leader
- Handle edge cases (boundaries, no coverage, etc.)

**Implementation Steps:**
1. Add `coverage_area` (polygon/coordinates) to `officials_details` table
2. Create `PurokService` with `findNearestPurok($latitude, $longitude)` method
3. Replace hardcoded ID in `ManualConcernController@store()`

### 2. Multiple Purok Leaders
- Support for multiple purok leaders per area
- Round-robin or load-based assignment

### 3. Redistribution
- Allow admin to reassign concerns
- Track reassignment history

### 4. Status Updates
- API endpoints for purok leaders to update concern status
- Broadcast status changes back to citizens

### 5. Admin Dashboard
- View all distributions
- Manual assignment/reassignment
- Distribution analytics

---

## API Endpoints (Future)

### For Purok Leaders
```
GET    /api/purok/concerns              - List assigned concerns
GET    /api/purok/concerns/{id}         - View concern details
POST   /api/purok/concerns/{id}/acknowledge - Mark as acknowledged
PUT    /api/purok/concerns/{id}/status  - Update status
POST   /api/purok/concerns/{id}/notes   - Add notes
```

---

## Files Created/Modified

### Created Files
1. `database/migrations/2025_11_06_000000_create_concern_distribution_table.php`
2. `app/Models/ConcernDistribution.php`
3. `app/Events/ConcernAssigned.php`
4. `docs/CONCERN_DISTRIBUTION_REALTIME.md` (this file)

### Modified Files
1. `app/Http/Controllers/Api/Citizen/Concern/ManualConcernController.php`
   - Added imports
   - Modified `store()` method (added Steps 4 & 5)
2. `routes/channels.php`
   - Added `purok-leader.{purokLeaderId}` channel authorization

---

## Troubleshooting

### Issue: Broadcast not received in mobile app
**Possible Causes:**
1. Pusher credentials incorrect
2. User not authenticated properly
3. Channel authorization failing (check role_id)
4. Wrong user ID subscribed

**Debug Steps:**
1. Enable Pusher logging: `Pusher.logToConsole = true;`
2. Check Laravel logs for broadcast events
3. Check Pusher dashboard for event delivery
4. Verify `/broadcasting/auth` endpoint returns 200

### Issue: Channel subscription denied
**Possible Causes:**
1. User's role_id is not 2
2. User ID doesn't match channel ID
3. Authentication token invalid/expired

**Solution:**
- Verify user's `role_id` in database
- Check token validity
- Review `routes/channels.php` authorization logic

### Issue: Event not triggering
**Check:**
1. Database transaction committed successfully
2. `BROADCAST_DRIVER=pusher` in `.env`
3. Queue worker running if using queues
4. Pusher credentials valid

---

## Notes

- All concerns currently go to user_id = 2 (Adoracion S. Jumadiao)
- No admin approval required - automatic distribution
- Status tracking ready for future implementation
- Private channels ensure security
- Event includes citizen name (configurable for privacy)

---

## Related Documentation

- Laravel Broadcasting: https://laravel.com/docs/10.x/broadcasting
- Pusher Channels: https://pusher.com/docs/channels/
- React Native Pusher: https://github.com/pusher/pusher-websocket-react-native

---

## Contact

For questions or issues with this feature, refer to:
- Branch: `feature/concern-distribution-realtime`
- Implemented by: GitHub Copilot
- Date: November 6, 2025
