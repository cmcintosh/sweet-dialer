<?php
// Epic 4/S-032: Twilio API Client
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");

class TwilioApiClient {
    private $accountSid;
    private $authToken;
    private $baseUrl = "https://api.twilio.com";
    private $maxRetries = 3;
    
    public function __construct($accountSid, $authToken) {
        $this->accountSid = $accountSid;
        $this->authToken = $authToken;
    }
    
    public function get($endpoint, $params = array()) {
        return $this->request("GET", $endpoint, $params);
    }
    
    public function post($endpoint, $data = array()) {
        return $this->request("POST", $endpoint, $data);
    }
    
    private function request($method, $endpoint, $data = array()) {
        $url = $this->baseUrl . $endpoint;
        $attempt = 0;
        
        while ($attempt < $this->maxRetries) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->accountSid . ":" . $this->authToken);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            if ($method === "POST") {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log to twilio_logger if retry needed
            if (($httpCode == 429 || $httpCode >= 500) && $attempt < $this->maxRetries - 1) {
                $attempt++;
                sleep(pow(2, $attempt)); // Exponential backoff: 2^1=2s, 2^2=4s
                continue;
            }
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true);
            }
            
            throw new Exception("Twilio API Error " . $httpCode . ": " . ($curlError ?: $response));
        }
        
        throw new Exception("Max retries exceeded");
    }
    
    public function validateCredentials() {
        try {
            $result = $this->get("/2010-04-01/Accounts/" . $this->accountSid . ".json");
            return array(
                "success" => true,
                "message" => "PASSED ATTEMPT",
                "timestamp" => date("Y-m-d H:i:s"),
                "account_status" => $result["status"] ?? "unknown"
            );
        } catch (Exception $e) {
            return array(
                "success" => false,
                "message" => $e->getMessage(),
                "timestamp" => date("Y-m-d H:i:s")
            );
        }
    }
    
    public function fetchPhoneNumbers() {
        return $this->get("/2010-04-01/Accounts/" . $this->accountSid . "/IncomingPhoneNumbers.json");
    }
}
