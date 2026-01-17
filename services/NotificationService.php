<?php
// services/NotificationService.php

class NotificationService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Sends an instant acknowledgement message to the student via WhatsApp.
     * In a production environment, this would call the Meta Graph API.
     */
    public function sendInstantAck($phone, $studentName, $counselorName) {
        // 1. Prepare the message content
        $message = "Hi " . $studentName . ",\n\n" .
                   "Thank you for reaching out to UniCore Admissions! We have successfully received your inquiry.\n\n" .
                   "Counselor " . $counselorName . " has been assigned to your file and will contact you shortly to assist with your enrollment.\n\n" .
                   "You can start by uploading your documents here: [Magic Link Placeholder]";

        // 2. Production Logic (Meta API Call)
        // This is where you would use curl to hit: 
        // https://graph.facebook.com/v17.0/{phone-number-id}/messages
        
        // 3. For now, we simulate the success by logging it
        $this->logNotification($phone, 'WhatsApp Ack', $message);

        return true;
    }

    /**
     * Logs the notification attempt in the system for the Chancellor's audit trail.
     */
    private function logNotification($phone, $type, $content) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) 
                VALUES (
                    (SELECT id FROM leads WHERE phone = ? ORDER BY created_at DESC LIMIT 1),
                    1, 
                    'NOTIFICATION', 
                    ?
                )
            ");
            $stmt->execute([$phone, "Sent $type to $phone"]);
        } catch (PDOException $e) {
            // Silently fail if log insertion fails to avoid stopping the webhook
        }
    }
}