<?php
// S-045: Webhook Entrypoints
$entry_point_registry["twilioVoiceInbound"] = array(
    "file" => "custom/include/TwilioDialer/webhooks/inbound.php",
    "auth" => false
);
$entry_point_registry["twilioVoiceOutbound"] = array(
    "file" => "custom/include/TwilioDialer/webhooks/outbound.php",
    "auth" => false
);
$entry_point_registry["twilioVoiceStatus"] = array(
    "file" => "custom/include/TwilioDialer/webhooks/status.php",
    "auth" => false
);
$entry_point_registry["twilioVoiceRecording"] = array(
    "file" => "custom/include/TwilioDialer/webhooks/recording.php",
    "auth" => false
);
$entry_point_registry["twilioVoiceVoicemail"] = array(
    "file" => "custom/include/TwilioDialer/webhooks/voicemail.php",
    "auth" => false
);
$entry_point_registry["twilioVoiceHold"] = array(
    "file" => "custom/include/TwilioDialer/webhooks/hold.php",
    "auth" => false
);
$entry_point_registry["twilioVoiceTransfer"] = array(
    "file" => "custom/include/TwilioDialer/webhooks/transfer.php",
    "auth" => false
);
$entry_point_registry["twilioToken"] = array(
    "file" => "custom/include/TwilioDialer/tokenEndpoint.php",
    "auth" => true
);
