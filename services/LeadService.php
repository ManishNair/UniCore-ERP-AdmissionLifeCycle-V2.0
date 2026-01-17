<?php
// services/LeadService.php

class LeadService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Ingests a lead from various sources (WhatsApp, FB, IG, etc.)
     * and handles assignment and token generation.
     */
    public function ingestLead($data) {
        try {
            // 1. DATA SANITIZATION
            $name       = trim($data['full_name'] ?? 'Unknown Applicant');
            $phone      = trim($data['phone'] ?? '');
            $email_raw  = trim($data['email'] ?? '');
            $college_id = (int)($data['college_id'] ?? 1);
            $source     = $data['source'] ?? 'Direct';

            // Convert empty email to NULL to avoid UNIQUE constraint violations
            $email = (empty($email_raw)) ? null : $email_raw;

            // 2. DUPLICATE CHECK
            if ($this->isDuplicate($phone, $email)) {
                return [
                    "status" => "error", 
                    "message" => "Duplicate lead found (Email/Phone already exists)."
                ];
            }

            // 3. TOKEN GENERATION (For Public Upload portal) 
            $access_token = bin2hex(random_bytes(16));

            // 4. AUTOMATED ASSIGNMENT (Simplistic Round-Robin or Fixed Logic)
            // We assign the lead to the first available Counselor for that college
            $counselor_id = $this->getAutoAssignee($college_id);

            // 5. DATABASE INSERTION
            // Corrected: Removed 'assigned_by' and used 'counselor_id' to match schema 
            $sql = "INSERT INTO leads (
                        full_name, email, phone, college_id, counselor_id, 
                        source, current_stage, access_token, conversion_probability, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'Compliance Gate', ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $name, 
                $email, 
                $phone, 
                $college_id, 
                $counselor_id, 
                $source, 
                $access_token,
                45 // Base probability for fresh automated leads
            ]);

            $lead_id = $this->pdo->lastInsertId();

            // 6. LOGGING FOR AUDIT TRAIL
            $this->logActivity($lead_id, $counselor_id, 'INGESTION', "Lead ingested via $source");

            return [
                "status" => "success", 
                "message" => "Lead $lead_id ingested and assigned successfully.",
                "lead_id" => $lead_id
            ];

        } catch (PDOException $e) {
            // Log full error to wa_log.txt for developer review 
            return [
                "status" => "error", 
                "message" => "Database Sync Error: " . $e->getMessage()
            ];
        }
    }

    private function isDuplicate($phone, $email) {
        if (!$phone && !$email) return false;

        $sql = "SELECT COUNT(*) FROM leads WHERE phone = ?";
        $params = [$phone];

        if ($email) {
            $sql .= " OR email = ?";
            $params[] = $email;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    private function getAutoAssignee($college_id) {
        // Find a counselor assigned to this college 
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE role = 'Counselor' AND FIND_IN_SET(?, college_id) LIMIT 1");
        $stmt->execute([$college_id]);
        return $stmt->fetchColumn() ?: null;
    }

    private function logActivity($lead_id, $user_id, $type, $desc) {
        $stmt = $this->pdo->prepare("INSERT INTO lead_activity_log (lead_id, user_id, action_type, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$lead_id, $user_id, $type, $desc]);
    }
}