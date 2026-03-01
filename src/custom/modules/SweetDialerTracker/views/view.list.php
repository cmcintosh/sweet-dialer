<?php
/**
 * S-096: Voicemail playback in Call Tracker
 * Show badge and audio player for voicemails
 */

require_once 'include/MVC/View/views/view.list.php';

class SweetDialerTrackerViewList extends ViewList
{
    public function preDisplay()
    {
        parent::preDisplay();
        
        // Add custom field formatting for voicemail playback
        $this->lv->xTemplate->assign('SHOW_VOICEMAIL_BADGE', true);
    }
    
    public function display()
    {
        // Inject voicemail audio player JavaScript
        echo '<script src="custom/modules/SweetDialerTracker/js/voicemail_player.js"></script>';
        parent::display();
    }
    
    public function listViewProcess()
    {
        parent::listViewProcess();
        
        // Add voicemail badge to rows with recordings
        if (!empty($this->lv->data)) {
            foreach ($this->lv->data as $key => $row) {
                if (!empty($row['recording_url'])) {
                    $this->lv->data[$key]['VOICEMAIL_BADGE'] = $this->getVoicemailBadge($row);
                    $this->lv->data[$key]['AUDIO_PLAYER'] = $this->getAudioPlayer($row);
                }
            }
        }
    }
    
    private function getVoicemailBadge($row)
    {
        return '<span class="voicemail-badge"><i class="fa fa-envelope"></i> Voicemail</span>';
    }
    
    private function getAudioPlayer($row)
    {
        $url = htmlspecialchars($row['recording_url']);
        return '<audio controls class="voicemail-player" data-sid="' . $row['call_sid'] . '" preload="none">
            <source src="' . $url . '" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>';
    }
}
