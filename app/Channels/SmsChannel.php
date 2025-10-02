<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

/**
 * SMS Notification Channel
 *
 * To use this channel:
 * 1. Install an SMS provider client (e.g., twilio/sdk, vonage/client, or aws/aws-sdk-php for SNS)
 * 2. Add your API credentials to .env:
 *    SMS_FROM=+1234567890
 *    SMS_API_KEY=your_api_key
 *    SMS_API_SECRET=your_api_secret
 * 3. Update the send() method below with your API client code
 */
class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        // Get the SMS message from the notification
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $to = $notifiable->routeNotificationFor('sms') ?? $notifiable->mobile ?? $notifiable->phone;

        if (!$to) {
            return;
        }

        // TODO: Implement SMS sending logic here
        // Example with Twilio:
        /*
        $twilio = new \Twilio\Rest\Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $twilio->messages->create(
            $to,
            [
                'from' => config('services.twilio.sms_from'),
                'body' => $message
            ]
        );
        */

        // For now, just log that we would send
        \Log::info("SMS notification would be sent to {$to}: {$message}");
    }
}
