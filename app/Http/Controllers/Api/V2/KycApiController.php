<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use App\Services\KycVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KycApiController extends Controller
{
    protected $kycService;

    public function __construct(KycVerificationService $kycService)
    {
        $this->kycService = $kycService;
    }

    /**
     * Get current user's KYC status
     */
    public function status(Request $request)
    {
        $user = $request->user();
        $kyc = $user->kyc;

        if (!$kyc) {
            return response()->json([
                'version' => 'v2',
                'data' => [
                    'status' => 'not_submitted',
                    'message' => 'No KYC verification found',
                ],
            ]);
        }

        return response()->json([
            'version' => 'v2',
            'data' => [
                'id' => $kyc->id,
                'status' => $kyc->status,
                'id_type' => $kyc->id_type,
                'submitted_at' => $kyc->submitted_at?->toIso8601String(),
                'verified_at' => $kyc->verified_at?->toIso8601String(),
                'rejected_at' => $kyc->rejected_at?->toIso8601String(),
                'rejection_reason' => $kyc->rejection_reason,
                'additional_info_message' => $kyc->additional_info_message,
                'risk_score' => $kyc->risk_score,
                'automated_checks' => $kyc->automated_checks ? json_decode($kyc->automated_checks, true) : null,
            ],
        ]);
    }

    /**
     * Submit KYC documents
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'id_type' => 'required|in:nin,passport',
            'id_number' => 'required|string|max:50',
            'permit_number' => 'required|string|max:50',
            'id_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'permit_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'id_original_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'permit_original_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $files = [
                'id_document' => $request->file('id_document'),
                'permit_document' => $request->file('permit_document'),
                'id_original_document' => $request->file('id_original_document'),
                'permit_original_document' => $request->file('permit_original_document'),
            ];

            $kyc = $this->kycService->submitKyc($request->user(), $validated, $files);

            return response()->json([
                'version' => 'v2',
                'message' => 'KYC documents submitted successfully',
                'data' => [
                    'id' => $kyc->id,
                    'status' => $kyc->status,
                    'submitted_at' => $kyc->submitted_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('KYC submission failed', [
                'user_id' => $request->user()->id,
                'exception' => $e,
            ]);

            return response()->json([
                'version' => 'v2',
                'error' => [
                    'code' => 'KYC_SUBMISSION_FAILED',
                    'message' => 'KYC submission failed. Please try again later.',
                ],
            ], 500);
        }
    }

    /**
     * Get KYC audit trail
     */
    public function auditTrail(Request $request)
    {
        $user = $request->user();
        $kyc = $user->kyc;

        if (!$kyc) {
            return response()->json([
                'version' => 'v2',
                'error' => [
                    'code' => 'KYC_NOT_FOUND',
                    'message' => 'No KYC verification found',
                ],
            ], 404);
        }

        $auditLogs = \DB::table('kyc_audit_logs')
            ->where('kyc_verification_id', $kyc->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'version' => 'v2',
            'data' => $auditLogs,
        ]);
    }

    /**
     * Admin: List all KYC verifications
     */
    public function adminList(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $status = $request->input('status');
        $search = $request->input('search');

        $query = KycVerification::with('user');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $kycs = $query->orderBy('submitted_at', 'desc')->paginate($perPage);

        return response()->json([
            'version' => 'v2',
            'data' => $kycs->items(),
            'meta' => [
                'current_page' => $kycs->currentPage(),
                'per_page' => $kycs->perPage(),
                'total' => $kycs->total(),
                'last_page' => $kycs->lastPage(),
            ],
        ]);
    }

    /**
     * Admin: Get KYC statistics
     */
    public function adminStatistics()
    {
        $stats = $this->kycService->getStatistics();

        return response()->json([
            'version' => 'v2',
            'data' => $stats,
        ]);
    }

    /**
     * Admin: Verify KYC
     */
    public function adminVerify(Request $request, $id)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $kyc = KycVerification::findOrFail($id);
            $kyc = $this->kycService->verifyKyc($kyc, $request->user()->id, $validated['notes'] ?? null);

            return response()->json([
                'version' => 'v2',
                'message' => 'KYC verified successfully',
                'data' => [
                    'id' => $kyc->id,
                    'status' => $kyc->status,
                    'verified_at' => $kyc->verified_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'version' => 'v2',
                'error' => [
                    'code' => 'KYC_VERIFICATION_FAILED',
                    'message' => 'Failed to verify KYC',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Admin: Reject KYC
     */
    public function adminReject(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $kyc = KycVerification::findOrFail($id);
            $kyc = $this->kycService->rejectKyc($kyc, $request->user()->id, $validated['reason']);

            return response()->json([
                'version' => 'v2',
                'message' => 'KYC rejected',
                'data' => [
                    'id' => $kyc->id,
                    'status' => $kyc->status,
                    'rejected_at' => $kyc->rejected_at->toIso8601String(),
                    'rejection_reason' => $kyc->rejection_reason,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'version' => 'v2',
                'error' => [
                    'code' => 'KYC_REJECTION_FAILED',
                    'message' => 'Failed to reject KYC',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Admin: Request additional documents
     */
    public function adminRequestAdditional(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        try {
            $kyc = KycVerification::findOrFail($id);
            $kyc = $this->kycService->requestAdditionalDocuments($kyc, $request->user()->id, $validated['message']);

            return response()->json([
                'version' => 'v2',
                'message' => 'Additional information requested',
                'data' => [
                    'id' => $kyc->id,
                    'status' => $kyc->status,
                    'additional_info_message' => $kyc->additional_info_message,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'version' => 'v2',
                'error' => [
                    'code' => 'REQUEST_FAILED',
                    'message' => 'Failed to request additional information',
                    'details' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Admin: View document
     */
    public function adminViewDocument($id, $type)
    {
        $kyc = KycVerification::findOrFail($id);
        
        $path = match($type) {
            'id' => $kyc->id_document_path,
            'permit' => $kyc->permit_document_path,
            'id_original' => $kyc->id_original_document_path,
            'permit_original' => $kyc->permit_original_document_path,
            default => null,
        };

        if (!$path || !Storage::disk('private')->exists($path)) {
            return response()->json([
                'version' => 'v2',
                'error' => [
                    'code' => 'DOCUMENT_NOT_FOUND',
                    'message' => 'Document not found',
                ],
            ], 404);
        }

        return Storage::disk('private')->download($path);
    }

    /**
     * Admin: Get audit trail for specific KYC
     */
    public function adminAuditTrail($id)
    {
        $kyc = KycVerification::findOrFail($id);

        $auditLogs = \DB::table('kyc_audit_logs')
            ->leftJoin('users', 'kyc_audit_logs.performed_by', '=', 'users.id')
            ->where('kyc_verification_id', $kyc->id)
            ->select(
                'kyc_audit_logs.*',
                'users.name as performed_by_name',
                'users.email as performed_by_email'
            )
            ->orderBy('kyc_audit_logs.created_at', 'desc')
            ->get();

        return response()->json([
            'version' => 'v2',
            'data' => $auditLogs,
        ]);
    }
}
