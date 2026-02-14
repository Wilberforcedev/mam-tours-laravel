<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $kyc = $user->kyc;
        return view('kyc.verify', compact('kyc'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'id_type' => 'required|in:nin,passport',
            'id_number' => 'required|string|max:50',
            'permit_number' => 'required|string|max:50',
            'id_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'permit_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_original_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'permit_original_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Delete old documents if they exist
        $kyc = $user->kyc;
        if ($kyc) {
            if ($kyc->id_document_path && Storage::disk('public')->exists($kyc->id_document_path)) {
                Storage::disk('public')->delete($kyc->id_document_path);
            }
            if ($kyc->permit_document_path && Storage::disk('public')->exists($kyc->permit_document_path)) {
                Storage::disk('public')->delete($kyc->permit_document_path);
            }
            if ($kyc->id_original_document_path && Storage::disk('public')->exists($kyc->id_original_document_path)) {
                Storage::disk('public')->delete($kyc->id_original_document_path);
            }
            if ($kyc->permit_original_document_path && Storage::disk('public')->exists($kyc->permit_original_document_path)) {
                Storage::disk('public')->delete($kyc->permit_original_document_path);
            }
        }

        // Store new documents
        $idDocPath = $request->file('id_document')->store('kyc/id_documents', 'public');
        $permitDocPath = $request->file('permit_document')->store('kyc/permit_documents', 'public');
        
        $idOriginalDocPath = null;
        $permitOriginalDocPath = null;
        
        if ($request->hasFile('id_original_document')) {
            $idOriginalDocPath = $request->file('id_original_document')->store('kyc/id_original_documents', 'public');
        }
        
        if ($request->hasFile('permit_original_document')) {
            $permitOriginalDocPath = $request->file('permit_original_document')->store('kyc/permit_original_documents', 'public');
        }

        // Create or update KYC
        $kyc = KycVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id_type' => $validated['id_type'],
                'id_number' => $validated['id_number'],
                'permit_number' => $validated['permit_number'],
                'id_document_path' => $idDocPath,
                'permit_document_path' => $permitDocPath,
                'id_original_document_path' => $idOriginalDocPath,
                'permit_original_document_path' => $permitOriginalDocPath,
                'status' => 'pending',
            ]
        );

        return redirect('/kyc')->with('success', 'KYC documents submitted for verification. Please wait for admin approval.');
    }

    // Admin methods
    public function adminIndex()
    {
        return view('kyc.admin');
    }

    public function adminList()
    {
        try {
            $kycVerifications = KycVerification::with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($kycVerifications);
        } catch (\Exception $e) {
            \Log::error('KYC List Error: ' . $e->getMessage());
            // Return empty array if table doesn't exist
            return response()->json([]);
        }
    }

    public function verify($id)
    {
        $kyc = KycVerification::findOrFail($id);
        $kyc->status = 'verified';
        $kyc->verified_at = now();
        $kyc->save();

        return response()->json(['message' => 'KYC verified successfully', 'kyc' => $kyc]);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $kyc = KycVerification::findOrFail($id);
        $kyc->status = 'rejected';
        $kyc->rejection_reason = $request->reason;
        $kyc->save();

        return response()->json(['message' => 'KYC rejected', 'kyc' => $kyc]);
    }

    public function viewDocument($id, $type)
    {
        $kyc = KycVerification::findOrFail($id);
        
        if ($type === 'id') {
            $path = $kyc->id_document_path;
        } elseif ($type === 'permit') {
            $path = $kyc->permit_document_path;
        } elseif ($type === 'id_original') {
            $path = $kyc->id_original_document_path;
        } elseif ($type === 'permit_original') {
            $path = $kyc->permit_original_document_path;
        } else {
            return response()->json(['error' => 'Invalid document type'], 400);
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        return Storage::disk('public')->download($path);
    }
}
