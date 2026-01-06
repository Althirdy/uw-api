<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Display a listing of the contacts.
     */
    public function index(Request $request)
    {
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
            if ($request->active === '1') {
                $query->where('active', true);
            } elseif ($request->active === '0') {
                $query->where('active', false);
            }
        }

        $contacts = $query->latest()->paginate(10);

        // Fetch distinct responder types from contacts table
        $responderTypes = Contact::select('responder_type')
            ->distinct()
            ->whereNotNull('responder_type')
            ->orderBy('responder_type')
            ->get()
            ->map(function ($contact, $index) {
                return [
                    'id' => $index + 1,
                    'name' => $contact->responder_type,
                ];
            })
            ->values()
            ->toArray();

        // Fetch distinct branch/unit names from contacts table
        $branchUnitNames = Contact::select('branch_unit_name')
            ->distinct()
            ->whereNotNull('branch_unit_name')
            ->orderBy('branch_unit_name')
            ->get()
            ->map(function ($contact, $index) {
                return [
                    'id' => $index + 1,
                    'name' => $contact->branch_unit_name,
                ];
            })
            ->values()
            ->toArray();

        // Fetch distinct locations from contacts table
        $packageLocations = Contact::select('location')
            ->distinct()
            ->whereNotNull('location')
            ->orderBy('location')
            ->get()
            ->map(function ($contact, $index) {
                return [
                    'id' => $index + 1,
                    'name' => $contact->location,
                ];
            })
            ->values()
            ->toArray();

        // Get distinct active statuses
        $statuses = Contact::select('active')
            ->distinct()
            ->get()
            ->map(function ($contact) {
                return [
                    'value' => $contact->active ? '1' : '0',
                    'label' => $contact->active ? 'Active' : 'Inactive',
                ];
            })
            ->toArray();

        return Inertia::render('contacts', [
            'contacts' => $contacts,
            'filters' => $request->only(['search', 'responder_type', 'active']),
            'responderTypes' => $responderTypes,
            'branchUnitNames' => $branchUnitNames,
            'packageLocations' => $packageLocations,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created contact in storage.
     */
    public function store(ContactRequest $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            $contact = Contact::create($validated);

            DB::commit();

            return redirect()->back()->with('success', 'Contact created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'An error occurred while creating the contact.')
                ->withInput();
        }
    }

    /**
     * Display the specified contact.
     */
    public function show(Contact $contact)
    {
        return Inertia::render('ContactDetails', [
            'contact' => $contact,
        ]);
    }

    /**
     * Update the specified contact in storage.
     */
    public function update(ContactRequest $request, Contact $contact)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();

            $contact->update($validated);

            DB::commit();

            return redirect()->back()->with('success', 'Contact updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'An error occurred while updating the contact.')
                ->withInput();
        }
    }

    /**
     * Remove the specified contact from storage.
     */
    public function destroy(Contact $contact)
    {
        DB::beginTransaction();

        try {
            $contact->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Contact deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'An error occurred while deleting the contact.');
        }
    }

    public function heatMapContacts()
    {
        $contacts = Contact::all();

        return response()->json([
            'success' => true,
            'message' => 'Contacts retrieved successfully',
            'data' => $contacts,
        ], 200);
    }
}
