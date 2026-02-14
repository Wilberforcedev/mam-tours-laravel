<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send booking confirmation notification
     */
    public function sendBookingConfirmation($booking, $user)
    {
        $subject = 'Booking Confirmed - MAM Tours';
        $paymentMethodText = $this->getPaymentMethodText($booking->payment_method);
        
        $message = "Great news! Your booking has been confirmed.\n\n";
        $message .= "Booking Details:\n";
        $message .= "Booking ID: {$booking->id}\n";
        $message .= "Vehicle: {$booking->car->brand} {$booking->car->model}\n";
        $message .= "From: " . $booking->startDate->format('M d, Y') . "\n";
        $message .= "To: " . $booking->endDate->format('M d, Y') . "\n";
        $message .= "Payment Method: {$paymentMethodText}\n";
        
        if ($booking->mobile_money_number) {
            $message .= "Mobile Money Number: {$booking->mobile_money_number}\n";
        }
        
        $message .= "Total Amount: UGX " . number_format($booking->pricing['total'] ?? 0) . "\n\n";
        $message .= "Please arrive 15 minutes early for vehicle pickup.\n";
        $message .= "Bring your ID and driving permit.\n\n";
        $message .= "Contact us: +256 755-943973\n";
        $message .= "WhatsApp: https://wa.me/256755943973";

        return $this->sendNotification($user, $booking, 'booking_confirmed', $subject, $message);
    }

    /**
     * Send booking reminder notification
     */
    public function sendBookingReminder($booking, $user)
    {
        $subject = 'Booking Reminder - MAM Tours';
        $message = "Reminder: Your booking for {$booking->car->brand} {$booking->car->model} starts on " . $booking->startDate->format('M d, Y') . ".\n";
        $message .= "Booking ID: {$booking->id}\n";
        $message .= "Please arrive 15 minutes early.\n";
        $message .= "Contact: +256 755-943973";

        return $this->sendNotification($user, $booking, 'booking_reminder', $subject, $message);
    }

    /**
     * Send booking completion notification
     */
    public function sendBookingCompletion($booking, $user)
    {
        $subject = 'Booking Completed - MAM Tours';
        $message = "Thank you for renting with MAM Tours!\n";
        $message .= "Your booking for {$booking->car->brand} {$booking->car->model} has been completed.\n";
        $message .= "Booking ID: {$booking->id}\n";
        $message .= "We hope you had a great experience. Please rate us!\n";
        $message .= "Contact: +256 755-943973";

        return $this->sendNotification($user, $booking, 'booking_completed', $subject, $message);
    }

    /**
     * Send booking cancellation notification
     */
    public function sendBookingCancellation($booking, $user)
    {
        $subject = 'Booking Cancelled - MAM Tours';
        $message = "Your booking has been cancelled.\n";
        $message .= "Booking ID: {$booking->id}\n";
        $message .= "Vehicle: {$booking->car->brand} {$booking->car->model}\n";
        $message .= "If you have any questions, contact us at +256 755-943973";

        return $this->sendNotification($user, $booking, 'booking_cancelled', $subject, $message);
    }

    /**
     * Send admin notification for new booking
     */
    public function sendAdminBookingNotification($booking)
    {
        // Get all admin users
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            $subject = 'New Booking Received - MAM Tours';
            $paymentMethodText = $this->getPaymentMethodText($booking->payment_method);
            
            $message = "New booking received!\n\n";
            $message .= "Booking ID: {$booking->id}\n";
            $message .= "Customer: {$booking->customerName}\n";
            $message .= "Vehicle: {$booking->car->brand} {$booking->car->model}\n";
            $message .= "From: " . $booking->startDate->format('M d, Y') . "\n";
            $message .= "To: " . $booking->endDate->format('M d, Y') . "\n";
            $message .= "Payment Method: {$paymentMethodText}\n";
            
            if ($booking->mobile_money_number) {
                $message .= "Mobile Money Number: {$booking->mobile_money_number}\n";
            }
            
            $message .= "Total: UGX " . number_format($booking->pricing['total'] ?? 0) . "\n";
            $message .= "Status: Pending Confirmation\n\n";
            $message .= "Please review and confirm the booking in the admin panel.";

            $this->sendNotification($admin, $booking, 'admin_new_booking', $subject, $message);
        }
    }

    /**
     * Get human-readable payment method text
     */
    private function getPaymentMethodText($paymentMethod)
    {
        $methods = [
            'mtn_mobile_money' => 'MTN Mobile Money',
            'airtel_money' => 'Airtel Money',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash on Pickup'
        ];
        
        return $methods[$paymentMethod] ?? $paymentMethod;
    }
    public function sendNotification($user, $booking, $type, $subject, $message)
    {
        $channels = [];

        // Determine which channels to use
        if ($user->email_notifications) {
            $channels[] = 'email';
        }
        if ($user->sms_notifications && $user->phone) {
            $channels[] = 'sms';
        }

        if (empty($channels)) {
            return false;
        }

        $channel = implode(',', $channels);

        // Create notification record
        $notification = Notification::create([
            'user_id' => $user->id,
            'booking_id' => $booking->id ?? null,
            'type' => $type,
            'subject' => $subject,
            'message' => $message,
            'status' => 'pending',
            'channel' => $channel,
        ]);

        // Send via email
        if (in_array('email', $channels)) {
            $this->sendEmail($user, $subject, $message, $notification);
        }

        // Send via SMS
        if (in_array('sms', $channels)) {
            $this->sendSms($user, $message, $notification);
        }

        return true;
    }

    /**
     * Send email notification
     */
    private function sendEmail($user, $subject, $message, $notification)
    {
        try {
            // For development, just log it
            Log::info("Email to {$user->email}: {$subject}", ['message' => $message]);

            // In production, use:
            // Mail::send('emails.notification', ['subject' => $subject, 'message' => $message], function ($mail) use ($user, $subject) {
            //     $mail->to($user->email)->subject($subject);
            // });

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'response' => 'Email logged (development mode)',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email to {$user->email}: " . $e->getMessage());
            $notification->update([
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send SMS notification
     */
    private function sendSms($user, $message, $notification)
    {
        try {
            $phone = $this->formatPhoneNumber($user->phone);

            // For development, just log it
            Log::info("SMS to {$phone}: {$message}");

            // In production, use Twilio or Africa's Talking:
            // $response = $this->sendViaTwilio($phone, $message);
            // or
            // $response = $this->sendViaAfricasTalking($phone, $message);

            SmsLog::create([
                'user_id' => $user->id,
                'phone_number' => $phone,
                'message' => $message,
                'status' => 'sent',
                'provider' => 'development',
                'provider_id' => null,
                'response' => 'SMS logged (development mode)',
                'sent_at' => now(),
            ]);

            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'response' => 'SMS logged (development mode)',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to {$user->phone}: " . $e->getMessage());
            $notification->update([
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0, replace with 256
        if (substr($phone, 0, 1) === '0') {
            $phone = '256' . substr($phone, 1);
        }

        // If doesn't start with 256, add it
        if (substr($phone, 0, 3) !== '256') {
            $phone = '256' . $phone;
        }

        return '+' . $phone;
    }

    /**
     * Send via Twilio (production)
     */
    public function sendViaTwilio($phone, $message)
    {
        // Implement Twilio integration
        // $twilio = new \Twilio\Rest\Client(env('TWILIO_ACCOUNT_SID'), env('TWILIO_AUTH_TOKEN'));
        // return $twilio->messages->create($phone, ['from' => env('TWILIO_PHONE_NUMBER'), 'body' => $message]);
    }

    /**
     * Send via Africa's Talking (production)
     */
    public function sendViaAfricasTalking($phone, $message)
    {
        // Implement Africa's Talking integration
        // $at = new \AfricasTalking\SDK\AfricasTalking(env('AFRICAS_TALKING_USERNAME'), env('AFRICAS_TALKING_API_KEY'));
        // return $at->sms()->send(['recipients' => [$phone], 'message' => $message]);
    }

    /**
     * Send payment confirmation notification
     */
    public function sendPaymentConfirmation($booking)
    {
        $user = $booking->user;
        $car = $booking->car;
        $amount = $this->calculateTotalAmount($booking);
        
        $subject = "Payment Confirmed - Booking #{$booking->id}";
        $message = "Your payment of UGX " . number_format($amount) . " for {$car->brand} {$car->model} has been confirmed.\n";
        $message .= "Booking dates: " . $booking->startDate->format('M j, Y') . " to " . 
                   $booking->endDate->format('M j, Y') . ".\n";
        $message .= "You will receive pickup instructions 24 hours before your rental date.\n";
        $message .= "Contact us: +256 755-943973";

        $this->sendNotification($user, $booking, 'payment_confirmation', $subject, $message);
        
        // Send admin notification
        $this->sendAdminPaymentNotification($booking);
    }

    /**
     * Send payment reminder notification
     */
    public function sendPaymentReminder($booking)
    {
        $user = $booking->user;
        $car = $booking->car;
        $amount = $this->calculateTotalAmount($booking);
        
        $subject = "Payment Reminder - Booking #{$booking->id}";
        $message = "Reminder: Your booking for {$car->brand} {$car->model} requires payment of UGX " . number_format($amount) . ".\n";
        $message .= "Please complete payment to confirm your reservation.\n";
        $message .= "Booking expires in 24 hours if payment is not received.\n";
        $message .= "Contact us: +256 755-943973";

        $this->sendNotification($user, $booking, 'payment_reminder', $subject, $message);
    }

    /**
     * Send pickup reminder notification
     */
    public function sendPickupReminder($booking)
    {
        $user = $booking->user;
        $car = $booking->car;
        
        $subject = "Vehicle Pickup Tomorrow - Booking #{$booking->id}";
        $message = "Reminder: Your {$car->brand} {$car->model} pickup is scheduled for tomorrow ";
        $message .= "({$booking->startDate->format('M j, Y')}).\n";
        $message .= "Please bring your driver's license and arrive at the scheduled time.\n";
        $message .= "Contact us if you need to reschedule: +256 755-943973";

        $this->sendNotification($user, $booking, 'pickup_reminder', $subject, $message);
    }

    /**
     * Send return reminder notification
     */
    public function sendReturnReminder($booking)
    {
        $user = $booking->user;
        $car = $booking->car;
        
        $subject = "Vehicle Return Tomorrow - Booking #{$booking->id}";
        $message = "Reminder: Your {$car->brand} {$car->model} return is due tomorrow ";
        $message .= "({$booking->endDate->format('M j, Y')}).\n";
        $message .= "Please ensure the vehicle is clean and fuel tank is at the same level as pickup.\n";
        $message .= "Late returns may incur additional charges.\n";
        $message .= "Contact us: +256 755-943973";

        $this->sendNotification($user, $booking, 'return_reminder', $subject, $message);
    }

    /**
     * Send overdue notification
     */
    public function sendOverdueNotification($booking)
    {
        $user = $booking->user;
        $car = $booking->car;
        $daysOverdue = now()->diffInDays($booking->endDate);
        
        $subject = "URGENT: Overdue Vehicle Return - Booking #{$booking->id}";
        $message = "URGENT: Your {$car->brand} {$car->model} return is {$daysOverdue} day(s) overdue.\n";
        $message .= "Please return the vehicle immediately to avoid additional charges.\n";
        $message .= "Contact us immediately at +256 755-943973 if there are any issues.";

        $this->sendNotification($user, $booking, 'overdue_notice', $subject, $message);
        
        // Also send admin notification
        $this->sendAdminOverdueNotification($booking);
    }

    /**
     * Send KYC verification notification
     */
    public function sendKycVerificationNotification($user, $status)
    {
        $subject = $status === 'approved' ? 'KYC Verification Approved' : 'KYC Verification Required';
        
        if ($status === 'approved') {
            $message = "Your identity verification has been approved! You can now make bookings on our platform.\n";
            $message .= "Contact us: +256 755-943973";
        } else {
            $message = "Your KYC verification requires attention. Please log in to your account to complete the verification process.\n";
            $message .= "Contact us for assistance: +256 755-943973";
        }

        $this->sendNotification($user, null, 'kyc_' . $status, $subject, $message);
    }

    /**
     * Send admin payment notification
     */
    private function sendAdminPaymentNotification($booking)
    {
        $car = $booking->car;
        $user = $booking->user;
        $amount = $this->calculateTotalAmount($booking);
        
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            $subject = "Payment Received - Booking #{$booking->id}";
            $message = "Payment of UGX " . number_format($amount) . " received for booking #{$booking->id}.\n";
            $message .= "Customer: {$user->name} ({$user->email})\n";
            $message .= "Vehicle: {$car->brand} {$car->model}\n";
            $message .= "Dates: " . $booking->startDate->format('M j, Y') . " to " . 
                       $booking->endDate->format('M j, Y') . "\n";
            $message .= "Payment Method: " . $this->getPaymentMethodText($booking->payment_method);

            $this->sendNotification($admin, $booking, 'admin_payment_received', $subject, $message);
        }
    }

    /**
     * Send admin overdue notification
     */
    private function sendAdminOverdueNotification($booking)
    {
        $car = $booking->car;
        $user = $booking->user;
        $daysOverdue = now()->diffInDays($booking->endDate);
        
        $admins = User::where('role', 'admin')->get();
        
        foreach ($admins as $admin) {
            $subject = "OVERDUE VEHICLE - Booking #{$booking->id}";
            $message = "Vehicle {$car->brand} {$car->model} (Booking #{$booking->id}) is {$daysOverdue} day(s) overdue.\n";
            $message .= "Customer: {$user->name} ({$user->email}, {$user->phone})\n";
            $message .= "Immediate action required.";

            $this->sendNotification($admin, $booking, 'admin_overdue_alert', $subject, $message);
        }
    }

    /**
     * Calculate total booking amount
     */
    private function calculateTotalAmount($booking)
    {
        $pricing = $booking->pricing ?? [];
        return $pricing['total'] ?? ($pricing['basePrice'] ?? 0) + ($pricing['addOnsTotal'] ?? 0) + ($pricing['tax'] ?? 0);
    }
}
