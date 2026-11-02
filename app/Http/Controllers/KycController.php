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

        // Store new documents on the private disk before touching the old ones,
        // so a failed upload never destroys previously submitted evidence.
        $idDocPath = $request->file('id_document')->store('kyc/id_documents', 'private');
        $permitDocPath = $request->file('permit_document')->store('kyc/permit_documents', 'private');

        $idOriginalDocPath = null;
        $permitOriginalDocPath = null;

        if ($request->hasFile('id_original_document')) {
            $idOriginalDocPath = $request->file('id_original_document')->store('kyc/id_original_documents', 'private');
        }

        if ($request->hasFile('permit_original_document')) {
            $permitOriginalDocPath = $request->file('permit_original_document')->store('kyc/permit_original_documents', 'private');
        }

        $oldKyc = $user->kyc;

        // Create or update KYC
        $kyc = KycVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id_type' => $validated['id_type'],
                'id_number' => $validated['id_number'],
                'permit_number' => $validated['permit_number'],
                'id_document_path' => $idDocPath,
                'permit_document_path' => $permitDocPath,
                'id_original_document_path' => $idOriginalDocPath ?? $oldKyc->id_original_document_path ?? null,
                'permit_original_document_path' => $permitOriginalDocPath ?? $oldKyc->permit_original_document_path ?? null,
                'status' => 'pending',
                'verified_at' => null,
                'rejection_reason' => null,
            ]
        );

        if ($oldKyc) {
            $this->deleteDocument($oldKyc->id_document_path, $kyc->id_document_path);
            $this->deleteDocument($oldKyc->permit_document_path, $kyc->permit_document_path);
            $this->deleteDocument($oldKyc->id_original_document_path, $kyc->id_original_document_path);
            $this->deleteDocument($oldKyc->permit_original_document_path, $kyc->permit_original_document_path);
        }

        return redirect('/kyc')->with('success', 'KYC documents submitted for verification. Please wait for admin approval.');
    }

    private function deleteDocument(?string $oldPath, ?string $newPath): void
    {
        if (!$oldPath || $oldPath === $newPath) {
            return;
        }

        Storage::disk('private')->delete($oldPath);
        Storage::disk('public')->delete($oldPath);
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

        if (!$path) {
            return response()->json(['error' => 'Document not found'], 404);
        }

        $disk = Storage::disk('private');
        $legacyDisk = Storage::disk('public');

        if ($disk->exists($path)) {
            return $disk->response($path, null, [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        // Documents uploaded before the move to private storage.
        if ($legacyDisk->exists($path)) {
            return $legacyDisk->response($path, null, [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return response()->json(['error' => 'Document not found'], 404);
    }
}
