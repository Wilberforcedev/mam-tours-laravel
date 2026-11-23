<?php

namespace App\Services;

use App\Models\KycVerification;
use App\Models\User;
use App\Jobs\ProcessKycDocument;
use App\Jobs\NotifyKycStatusChange;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class KycVerificationService
{
    protected $analyticsService;
    protected $webhookService;

    public function __construct(AnalyticsService $analyticsService, WebhookService $webhookService)
    {
        $this->analyticsService = $analyticsService;
        $this->webhookService = $webhookService;
    }

    /**
     * Submit KYC documents for verification
     */
    public function submitKyc(User $user, array $data, array $files): KycVerification
    {
        DB::beginTransaction();

        try {
            // Delete old documents if resubmitting
            $existingKyc = $user->kyc;
            if ($existingKyc) {
                $this->deleteDocuments($existingKyc);
            }

            // Store documents with encryption
            $documentPaths = $this->storeDocuments($files, $user->id);

            // Create or update KYC record
            $kyc = KycVerification::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_type' => $data['id_type'],
                    'id_number' => $this->encryptSensitiveData($data['id_number']),
                    'permit_number' => $this->encryptSensitiveData($data['permit_number']),
                    'id_document_path' => $documentPaths['id_document'],
                    'permit_document_path' => $documentPaths['permit_document'],
                    'id_original_document_path' => $documentPaths['id_original_document'] ?? null,
                    'permit_original_document_path' => $documentPaths['permit_original_document'] ?? null,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'rejection_reason' => null,
                ]
            );

            // Create audit log
            $this->createAuditLog($kyc, 'submitted', $user->id);

            // Track analytics
            $this->analyticsService->track('kyc', 'KYC Submitted', [
                'kyc_id' => $kyc->id,
                'id_type' => $data['id_type'],
            ]);

            // Dispatch webhook
            $this->webhookService->dispatch('kyc.submitted', [
                'kyc_id' => $kyc->id,
                'user_id' => $user->id,
                'status' => 'pending',
                'submitted_at' => $kyc->submitted_at->toIso8601String(),
            ]);

            // Queue document processing for OCR/validation
            ProcessKycDocument::dispatch($kyc->id);

            DB::commit();

            return $kyc;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verify KYC documents
     */
    public function verifyKyc(KycVerification $kyc, int $verifiedBy, ?string $notes = null): KycVerification
    {
        DB::beginTransaction();

        try {
            $kyc->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $verifiedBy,
                'verification_notes' => $notes,
            ]);

            // Create audit log
            $this->createAuditLog($kyc, 'verified', $verifiedBy, $notes);

            // Track analytics
            $this->analyticsService->track('kyc', 'KYC Verified', [
                'kyc_id' => $kyc->id,
                'user_id' => $kyc->user_id,
            ]);

            // Dispatch webhook
            $this->webhookService->dispatch('kyc.verified', [
                'kyc_id' => $kyc->id,
                'user_id' => $kyc->user_id,
                'verified_at' => $kyc->verified_at->toIso8601String(),
            ]);

            // Notify user
            NotifyKycStatusChange::dispatch($kyc->id, 'verified');

            DB::commit();

            return $kyc;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject KYC documents
     */
    public function rejectKyc(KycVerification $kyc, int $rejectedBy, string $reason): KycVerification
    {
        DB::beginTransaction();

        try {
            $kyc->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => $rejectedBy,
                'rejection_reason' => $reason,
            ]);

            // Create audit log
            $this->createAuditLog($kyc, 'rejected', $rejectedBy, $reason);

            // Track analytics
            $this->analyticsService->track('kyc', 'KYC Rejected', [
                'kyc_id' => $kyc->id,
                'user_id' => $kyc->user_id,
                'reason' => $reason,
            ]);

            // Dispatch webhook
            $this->webhookService->dispatch('kyc.rejected', [
                'kyc_id' => $kyc->id,
                'user_id' => $kyc->user_id,
                'reason' => $reason,
                'rejected_at' => $kyc->rejected_at->toIso8601String(),
            ]);

            // Notify user
            NotifyKycStatusChange::dispatch($kyc->id, 'rejected');

            DB::commit();

            return $kyc;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Request additional documents
     */
    public function requestAdditionalDocuments(KycVerification $kyc, int $requestedBy, string $message): KycVerification
    {
        $kyc->update([
            'status' => 'additional_info_required',
            'additional_info_message' => $message,
        ]);

        // Create audit log
        $this->createAuditLog($kyc, 'additional_info_requested', $requestedBy, $message);

        // Notify user
        NotifyKycStatusChange::dispatch($kyc->id, 'additional_info_required');

        return $kyc;
    }

    /**
     * Perform automated verification checks
     */
    public function performAutomatedChecks(KycVerification $kyc): array
    {
        $checks = [
            'document_quality' => $this->checkDocumentQuality($kyc),
            'data_consistency' => $this->checkDataConsistency($kyc),
            'duplicate_check' => $this->checkForDuplicates($kyc),
            'blacklist_check' => $this->checkBlacklist($kyc),
        ];

        // Store check results
        $kyc->update([
            'automated_checks' => json_encode($checks),
            'automated_checks_at' => now(),
        ]);

        // Create audit log
        $this->createAuditLog($kyc, 'automated_checks_completed', null, json_encode($checks));

        return $checks;
    }

    /**
     * Get KYC statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => KycVerification::count(),
            'pending' => KycVerification::where('status', 'pending')->count(),
            'verified' => KycVerification::where('status', 'verified')->count(),
            'rejected' => KycVerification::where('status', 'rejected')->count(),
            'additional_info_required' => KycVerification::where('status', 'additional_info_required')->count(),
            'avg_verification_time' => $this->getAverageVerificationTime(),
            'verification_rate' => $this->getVerificationRate(),
        ];
    }

    /**
     * Store documents securely
     */
    protected function storeDocuments(array $files, int $userId): array
    {
        $paths = [];

        foreach ($files as $key => $file) {
            if ($file) {
                $filename = sprintf(
                    '%s_%s_%s.%s',
                    $userId,
                    $key,
                    uniqid(),
                    $file->getClientOriginalExtension()
                );

                $path = $file->storeAs(
                    "kyc/{$userId}",
                    $filename,
                    'private' // Use private disk for security
                );

                $paths[$key] = $path;
            }
        }

        return $paths;
    }

    /**
     * Delete old documents
     */
    protected function deleteDocuments(KycVerification $kyc): void
    {
        $documents = [
            $kyc->id_document_path,
            $kyc->permit_document_path,
            $kyc->id_original_document_path,
            $kyc->permit_original_document_path,
        ];

        foreach ($documents as $path) {
            if ($path && Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
            }
        }
    }

    /**
     * Encrypt sensitive data
     */
    protected function encryptSensitiveData(string $data): string
    {
        return encrypt($data);
    }

    /**
     * Create audit log entry
     */
    protected function createAuditLog(KycVerification $kyc, string $action, ?int $performedBy, ?string $notes = null): void
    {
        DB::table('kyc_audit_logs')->insert([
            'kyc_verification_id' => $kyc->id,
            'action' => $action,
            'performed_by' => $performedBy,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Check document quality
     */
    protected function checkDocumentQuality(KycVerification $kyc): array
    {
        // Until document inspection is implemented, require a human reviewer.
        return [
            'passed' => false,
            'score' => 0,
            'issues' => ['Automated document quality checks are not configured; manual review is required.'],
        ];
    }

    /**
     * Check data consistency
     */
    protected function checkDataConsistency(KycVerification $kyc): array
    {
        $issues = [];

        // Check if ID number format is valid
        if ($kyc->id_type === 'nin' && !preg_match('/^[A-Z0-9]{14}$/', decrypt($kyc->id_number))) {
            $issues[] = 'Invalid NIN format';
        }

        return [
            'passed' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check for duplicate submissions
     */
    protected function checkForDuplicates(KycVerification $kyc): array
    {
        // Laravel encryption is randomized, so compare normalized plaintext values
        // after decrypting stored records rather than comparing ciphertext.
        $idNumber = $this->normalizeSensitiveValue($this->decryptStoredSensitiveValue($kyc->id_number));
        $permitNumber = $this->normalizeSensitiveValue($this->decryptStoredSensitiveValue($kyc->permit_number));

        $duplicates = KycVerification::where('id', '!=', $kyc->id)
            ->whereIn('status', ['verified', 'auto_verified'])
            ->get(['id_number', 'permit_number'])
            ->filter(function (KycVerification $existing) use ($idNumber, $permitNumber) {
                $existingId = $this->normalizeSensitiveValue($this->decryptStoredSensitiveValue($existing->id_number));
                $existingPermit = $this->normalizeSensitiveValue($this->decryptStoredSensitiveValue($existing->permit_number));

                return ($idNumber !== null && $existingId !== null && hash_equals($idNumber, $existingId))
                    || ($permitNumber !== null && $existingPermit !== null && hash_equals($permitNumber, $existingPermit));
            })
            ->count();

        return [
            'passed' => $duplicates === 0,
            'duplicates_found' => $duplicates,
        ];
    }

    private function decryptStoredSensitiveValue(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            // Support legacy records saved before KYC values were encrypted.
            return $value;
        }
    }

    private function normalizeSensitiveValue(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return strtoupper(trim($value));
    }

    /**
     * Check against blacklist
     */
    protected function checkBlacklist(KycVerification $kyc): array
    {
        // An unconfigured check must never be treated as a clean result.
        return [
            'passed' => false,
            'blacklisted' => null,
            'issues' => ['Automated blacklist checks are not configured; manual review is required.'],
        ];
    }

    /**
     * Get average verification time in hours
     */
    protected function getAverageVerificationTime(): float
    {
        $avg = KycVerification::whereNotNull('verified_at')
            ->whereNotNull('submitted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, verified_at)) as avg_hours')
            ->value('avg_hours');

        return round($avg ?? 0, 2);
    }

    /**
     * Get verification rate (verified / total submitted)
     */
    protected function getVerificationRate(): float
    {
        $total = KycVerification::count();
        if ($total === 0) return 0;

        $verified = KycVerification::where('status', 'verified')->count();
        return round(($verified / $total) * 100, 2);
    }
}
