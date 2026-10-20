<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RemovedPublicEndpointTest extends TestCase
{
    public function test_payment_success_stubs_and_public_test_upload_are_removed()
    {
        $this->postJson('/api/payments/intent')->assertNotFound();
        $this->postJson('/api/payments/capture/1')->assertNotFound();
        $this->postJson('/api/payments/refund/1')->assertNotFound();
        $this->postJson('/api/test-upload', [
            'file' => UploadedFile::fake()->image('test.jpg'),
        ])->assertNotFound();
    }
}
