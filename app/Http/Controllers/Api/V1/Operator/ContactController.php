<?php

namespace App\Http\Controllers\Api\V1\Operator;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Operator\StoreContactRequest;
use App\Http\Requests\Api\V1\Operator\UpdateContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactController extends BaseApiController
{
    /**
     * List Emergency Contacts
     * 
     * Retrieve a paginated list of emergency responder contacts.
     * Supports searching and filtering by responder type and active status.
     *
     * @group Operator
     * @authenticated
     * 
     * @queryParam search string optional Search term for contact information. Example: Fire
     * @queryParam responder_type string optional Filter by responder type (Fire, Emergency, Crime, Traffic, Barangay, Others). Example: Fire
     * @queryParam active boolean optional Filter by active status (true/false). Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "contacts": [
     *       {
     *         "id": 1,
     *         "branch_unit_name": "BFP",
     *         "contact_person": "Juan Dela Cruz",
     *         "responder_type": "Fire",
     *         "location": "Baguio City Hall",
     *         "primary_mobile": "09171234567",
     *         "backup_mobile": "09181234567",
     *         "latitude": 16.4023,
     *         "longitude": 120.5960,
     *         "active": true
     *       }
     *     ],
     *     "meta": {
     *       "current_page": 1,
     *       "last_page": 3,
     *       "per_page": 10,
     *       "total": 25
     *     }
     *   },
     *   "message": "Contacts retrieved successfully"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving contacts: error details"
     * }
     */
    public function index(Request $request)
    {
        try {
            $query = Contact::query();

            // Search functionality
            if ($request->filled('search')) {
                $query->search($request->search);
            }

            // Filter by responder type
            if ($request->filled('responder_type')) {
                $query->byResponderType($request->responder_type);
            }

            // Filter by status
            if ($request->filled('active')) {
                $isActive = filter_var($request->active, FILTER_VALIDATE_BOOLEAN);
                $query->where('active', $isActive);
            }

            $contacts = $query->latest()->paginate(10);

            return $this->sendResponse([
                'contacts' => ContactResource::collection($contacts),
                'meta' => [
                    'current_page' => $contacts->currentPage(),
                    'last_page' => $contacts->lastPage(),
                    'per_page' => $contacts->perPage(),
                    'total' => $contacts->total(),
                ],
            ], 'Contacts retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving contacts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving contacts: '.$e->getMessage());
        }
    }

    /**
     * Create Emergency Contact
     * 
     * Create a new emergency responder contact with location and contact details.
     *
     * @group Operator
     * @authenticated
     * 
     * @bodyParam branch_unit_name string required Branch/Unit name (BEST, BCCM, BCPC, BDRRM, BHERT, BHW, BPSO, BTMO, VAWC). Example: BCPC
     * @bodyParam contact_person string optional Contact person's name. Example: Juan Dela Cruz
     * @bodyParam responder_type string required Responder type (Fire, Emergency, Crime, Traffic, Barangay, Others). Example: Fire
     * @bodyParam location string required Location address. Example: Baguio City Hall
     * @bodyParam primary_mobile string required Primary mobile number (11 digits). Example: 09171234567
     * @bodyParam backup_mobile string optional Backup mobile number (11 digits). Example: 09181234567
     * @bodyParam latitude number required Latitude coordinate (-90 to 90). Example: 16.4023
     * @bodyParam longitude number required Longitude coordinate (-180 to 180). Example: 120.5960
     * @bodyParam active boolean optional Active status (default: true). Example: true
     * 
     * @response 201 {
     *   "success": true,
     *   "data": {
     *     "contact": {
     *       "id": 1,
     *       "branch_unit_name": "BCPC",
     *       "contact_person": "Juan Dela Cruz",
     *       "responder_type": "Fire",
     *       "location": "Baguio City Hall",
     *       "primary_mobile": "09171234567",
     *       "backup_mobile": "09181234567",
     *       "latitude": 16.4023,
     *       "longitude": 120.5960,
     *       "active": true,
     *       "created_at": "2023-12-27T10:00:00.000000Z"
     *     }
     *   },
     *   "message": "Contact created successfully!"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "primary_mobile": ["Primary mobile number must be exactly 11 digits."]
     *   }
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while creating the contact: error details"
     * }
     */
    public function store(StoreContactRequest $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            $contact = Contact::create($validated);

            DB::commit();

            return $this->sendResponse([
                'contact' => new ContactResource($contact),
            ], 'Contact created successfully!', 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating contact', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while creating the contact: '.$e->getMessage());
        }
    }

    /**
     * Get Contact Details
     * 
     * Retrieve detailed information about a specific emergency contact.
     *
     * @group Operator
     * @authenticated
     * 
     * @urlParam contact integer required The ID of the contact. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "contact": {
     *       "id": 1,
     *       "branch_unit_name": "BCPC",
     *       "contact_person": "Juan Dela Cruz",
     *       "responder_type": "Fire",
     *       "location": "Baguio City Hall",
     *       "primary_mobile": "09171234567",
     *       "backup_mobile": "09181234567",
     *       "latitude": 16.4023,
     *       "longitude": 120.5960,
     *       "active": true
     *     }
     *   },
     *   "message": "Contact retrieved successfully"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Contact not found"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving the contact: error details"
     * }
     */
    public function show(Contact $contact)
    {
        try {
            return $this->sendResponse([
                'contact' => new ContactResource($contact),
            ], 'Contact retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error retrieving contact', [
                'error' => $e->getMessage(),
                'contact_id' => $contact->id,
            ]);

            return $this->sendError('An error occurred while retrieving the contact: '.$e->getMessage());
        }
    }

    /**
     * Update Emergency Contact
     * 
     * Update an existing emergency responder contact's information.
     *
     * @group Operator
     * @authenticated
     * 
     * @urlParam contact integer required The ID of the contact. Example: 1
     * @bodyParam branch_unit_name string required Branch/Unit name (BEST, BCCM, BCPC, BDRRM, BHERT, BHW, BPSO, BTMO, VAWC). Example: BCPC
     * @bodyParam contact_person string optional Contact person's name. Example: Juan Dela Cruz
     * @bodyParam responder_type string required Responder type (Fire, Emergency, Crime, Traffic, Barangay, Others). Example: Fire
     * @bodyParam location string required Location address. Example: Baguio City Hall
     * @bodyParam primary_mobile string required Primary mobile number (11 digits). Example: 09171234567
     * @bodyParam backup_mobile string optional Backup mobile number (11 digits). Example: 09181234567
     * @bodyParam latitude number required Latitude coordinate (-90 to 90). Example: 16.4023
     * @bodyParam longitude number required Longitude coordinate (-180 to 180). Example: 120.5960
     * @bodyParam active boolean optional Active status. Example: true
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "contact": {
     *       "id": 1,
     *       "branch_unit_name": "BCPC",
     *       "contact_person": "Juan Dela Cruz",
     *       "responder_type": "Fire",
     *       "location": "Updated Location",
     *       "updated_at": "2023-12-27T11:00:00.000000Z"
     *     }
     *   },
     *   "message": "Contact updated successfully!"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {}
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while updating the contact: error details"
     * }
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            $contact->update($validated);

            DB::commit();

            return $this->sendResponse([
                'contact' => new ContactResource($contact),
            ], 'Contact updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating contact', [
                'error' => $e->getMessage(),
                'contact_id' => $contact->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while updating the contact: '.$e->getMessage());
        }
    }

    /**
     * Delete Emergency Contact
     * 
     * Permanently delete an emergency responder contact from the system.
     *
     * @group Operator
     * @authenticated
     * 
     * @urlParam contact integer required The ID of the contact to delete. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "contact_id": 1
     *   },
     *   "message": "Contact deleted successfully!"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Contact not found"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while deleting the contact: error details"
     * }
     */
    public function destroy(Contact $contact)
    {
        DB::beginTransaction();

        try {
            $contact->delete();

            DB::commit();

            return $this->sendResponse([
                'contact_id' => $contact->id,
            ], 'Contact deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting contact', [
                'error' => $e->getMessage(),
                'contact_id' => $contact->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while deleting the contact: '.$e->getMessage());
        }
    }

    /**
     * Get Contacts for Heatmap
     * 
     * Retrieve all active emergency contacts for displaying on a heatmap visualization.
     *
     * @group Operator
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "contacts": [
     *       {
     *         "id": 1,
     *         "branch_unit_name": "BCPC",
     *         "responder_type": "Fire",
     *         "location": "Baguio City Hall",
     *         "latitude": 16.4023,
     *         "longitude": 120.5960,
     *         "active": true
     *       },
     *       {
     *         "id": 2,
     *         "branch_unit_name": "BDRRM",
     *         "responder_type": "Emergency",
     *         "location": "Emergency Center",
     *         "latitude": 16.4100,
     *         "longitude": 120.5980,
     *         "active": true
     *       }
     *     ]
     *   },
     *   "message": "Contacts retrieved successfully for heatmap"
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An error occurred while retrieving contacts: error details"
     * }
     */
    public function heatmap()
    {
        try {
            $contacts = Contact::where('active', true)->get();

            return $this->sendResponse([
                'contacts' => ContactResource::collection($contacts),
            ], 'Contacts retrieved successfully for heatmap');

        } catch (\Exception $e) {
            Log::error('Error retrieving heatmap contacts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError('An error occurred while retrieving contacts: '.$e->getMessage());
        }
    }
}
