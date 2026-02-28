<?php
/**
 * S-017: Logger Menu
 */
$module_menu[] = array('Twilio Error Logs', 'Logs', 'LBL_TWILIO_ERROR_LOGS', 'SweetDialerLogger', 'ListView', '', false, 'twilio-settings-module', 'sidebar');
$module_menu[] = array('Logger', 'Logger', 'LBL_LOGGER', 'SweetDialerLogger', 'index','',false,'twilio-settings-module','sidebar');
$module_menu[] = array('Clean All App', 'Clean', 'LBL_CLEAN_ALL_APP', 'SweetDialerLogger', 'clean','',false,'twilio-settings-module','sidebar');
$module_menu[] = array('Stop Logging', 'Stop', 'LBL_STOP_LOGGING', 'SweetDialerLogger', 'stopLogging','',false,'twilio-settings-module','sidebar');
