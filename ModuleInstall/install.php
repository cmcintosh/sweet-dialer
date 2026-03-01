<?php
// S-002: Post-install script
function post_install() {
    // Get database instance
    $db = $GLOBALS["db"] ?? null;
    if (!$db) {
        die("Database connection not available");
    }
    
    // Run database schema
    $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . "db_schema.sql";
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        // Split by semicolon but handle empty statements
        $queries = array_filter(array_map("trim", explode(";", $sql)));
        foreach ($queries as $query) {
            if (!empty($query)) {
                $db->query($query);
            }
        }
    }
    
    // Create upload directory
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "upload" . DIRECTORY_SEPARATOR;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Register scheduler jobs
    require_once "modules/Schedulers/Scheduler.php";
    
    $jobs = array(
        array(
            "name" => "Twilio Recording Sync",
            "job" => "function::twilioRecordingSync",
            "date_time_start" => date("Y-m-d H:i:s"),
            "interval" => 900, // 15 minutes
            "status" => "Active"
        ),
    );
    
    foreach ($jobs as $jobData) {
        $scheduler = new Scheduler();
        $scheduler->retrieve_by_string_fields(array("name" => $jobData["name"]));
        if (empty($scheduler->id)) {
            foreach ($jobData as $field => $value) {
                $scheduler->$field = $value;
            }
            $scheduler->save();
        }
    }
    
    // Clear theme cache
    if (file_exists("include/SugarTheme/SugarTheme.php")) {
        require_once "include/SugarTheme/SugarTheme.php";
        if (class_exists("SugarTheme")) {
            SugarTheme::clearAllCache();
        }
    }
    
    return true;
}
