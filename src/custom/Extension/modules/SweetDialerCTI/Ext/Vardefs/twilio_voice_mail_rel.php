<?php
/**
 * S-013: CTI Settings -> Voicemail Relationship
 */

$dictionary['SweetDialerCTI']['fields']['twilio_voice_mail'] = array(
    'name' => 'twilio_voice_mail',
    'vname' => 'LBL_TWILIO_VOICE_MAIL',
    'type' => 'relate',
    'rname' => 'name',
    'id_name' => 'twilio_voice_mail_id',
    'module' => 'SweetDialerVoicemail',
    'source' => 'non-db',
    'massupdate' => true,
);

$dictionary['SweetDialerCTI']['fields']['twilio_voice_mail_id'] = array(
    'name' => 'twilio_voice_mail_id',
    'vname' => 'LBL_TWILIO_VOICE_MAIL_ID',
    'type' => 'id',
);
