<?php
// Epic 11: Twilio Logger Service
if (!defined("sugarEntry") || !sugarEntry) die("Not A Valid Entry Point");

class TwilioLogger {
    private static $enabled = true;
    const LEVEL_INFO = "info";
    const LEVEL_WARNING = "warning";
    const LEVEL_ERROR = "error";
    const LEVEL_DEBUG = "debug";
    
    public static function info($message, $context = array()) {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    public static function warning($message, $context = array()) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    public static function error($message, $context = array()) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }
    
    private static function log($level, $message, $context) {
        if (!self::$enabled) return;
        
        $bean = BeanFactory::newBean("outr_twilio_logger");
        if (!$bean) return;
        
        $bean->log_level = $level;
        $bean->name = substr($message, 0, 255);
        $bean->message = $message;
        $bean->context = json_encode($context);
        $bean->date_created = date("Y-m-d H:i:s");
        $bean->save();
    }
    
    public static function errorLog($errorCode, $errorMessage, $callSid = "", $endpoint = "", $requestBody = "", $responseBody = "") {
        $bean = BeanFactory::newBean("outr_twilio_error_logs");
        if (!$bean) return;
        
        $bean->error_code = $errorCode;
        $bean->error_message = $errorMessage;
        $bean->call_sid = $callSid;
        $bean->endpoint = $endpoint;
        $bean->request_body = $requestBody;
        $bean->response_body = $responseBody;
        $bean->date_created = date("Y-m-d H:i:s");
        $bean->save();
    }
}
