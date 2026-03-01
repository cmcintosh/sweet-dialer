<?php
/**
 * S-093: Call Tracker Detail View with Recording
 * Adds recording playback subpanel
 */

$viewdefs['OutrTwilioCalls']['DetailView'] = [
    'templateMeta' => [
        'form' => [
            'buttons' => [
                'EDIT',
                'DUPLICATE',
                'DELETE',
                [
                    'customCode' => '{$NEW_RECORD_BUTTON}',
                ],
            ],
        ],
        'maxColumns' => '2',
        'widths' => [
            [
                'label' => '10',
                'field' => '30',
            ],
            [
                'label' => '10',
                'field' => '30',
            ],
        ],
        'useTabs' => false,
        'tabDefs' => [
            'DEFAULT' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
            'LBL_RECORDING_PANEL' => [
                'newTab' => false,
                'panelDefault' => 'expanded',
            ],
        ],
    ],
    'panels' => [
        'default' => [
            [
                'name',
                'direction',
            ],
            [
                'status',
                'duration',
            ],
            [
                'from_number',
                'to_number',
            ],
            [
                'call_sid',
                'parent_name',
            ],
            [
                'assigned_user_name',
                'date_start',
            ],
            [
                [
                    'name' => 'description',
                    'nl2br' => true,
                    'span' => 2,
                ],
            ],
        ],
        'LBL_RECORDING_PANEL' => [
            [
                [
                    'name' => 'recording_player',
                    'label' => 'LBL_RECORDING_PLAYER',
                    'customCode' => '
                        <div id="recording-player-container" class="recording-player-wrapper">
                            {if $fields.twilio_recording_sid.value}
                                <audio controls class="recording-audio-player">
                                    <source src="index.php?entryPoint=voicemailPlayback&amp;recording_sid={$fields.twilio_recording_sid.value}&amp;format=mp3" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                                <div class="recording-details">
                                    <span class="recording-sid">Recording SID: {$fields.twilio_recording_sid.value}</span>
                                    {if $fields.recording_duration.value}
                                        <span class="recording-duration">Duration: {$fields.recording_duration.value}s</span>
                                    {/if}
                                </div>
                                <a class="recording-download" href="index.php?entryPoint=voicemailPlayback&amp;recording_sid={$fields.twilio_recording_sid.value}&amp;format=mp3" download>
                                    Download MP3
                                </a>
                            {else}
                                <span class="no-recording-message">No recording available for this call.</span>
                            {/if}
                        </div>
                    ',
                ],
            ],
        ],
    ],
];
