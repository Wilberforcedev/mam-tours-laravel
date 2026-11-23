<?php

namespace App\Jobs;

use App\Models\KycVerification;
use App\Services\KycVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessKycDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes for OCR processing

    protected $kycId;

    public function __construct(int $kycId)
    {
        $this->kycId = $kycId;
    }

    public function handle(KycVerificationService $kycService): void
    {
        $kyc = KycVerification::find($this->kycId);
        
        if (!$kyc) {
            logger()->warning('KYC verification not found', ['kyc_id' => $this->kycId]);
            return;
        }

        try {
            // Perform automated verification checks
            $checks = $kycService->performAutomatedChecks($kyc);

            // Calculate risk score based on checks
            $riskScore = $this->calculateRiskScore($checks);
            $kyc->update(['risk_score' => $riskScore]);

            logger()->info('KYC document processed successfully', [
                'kyc_id' => $this->kycId,
                'risk_score' => $riskScore,
                'checks' => $checks,
            ]);
        } catch (\Exception $e) {
            logger()->error('KYC document processing failed', [
                'kyc_id' => $this->kycId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function calculateRiskScore(array $checks): int
    {
        $score = 0;

        // Document quality check
        if (!$checks['document_quality']['passed']) {
            $score += 30;
        } else {
            $score += max(0, 100 - $checks['document_quality']['score']);
        }

        // Data consistency check
        if (!$checks['data_consistency']['passed']) {
            $score += 25;
        }

        // Duplicate check
        if (!$checks['duplicate_check']['passed']) {
            $score += 40;
        }

        // Blacklist check
        if (!$checks['blacklist_check']['passed']) {
            $score += 100; // Maximum risk
        }

        return min(100, $score);
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('KYC document processing job failed permanently', [
            'kyc_id' => $this->kycId,
            'error' => $exception->getMessage(),
        ]);

        // Update KYC status to indicate processing failure
        $kyc = KycVerification::find($this->kycId);
        if ($kyc) {
            $kyc->update([
                'status' => 'processing_failed',
            ]);
        }
    }
}
