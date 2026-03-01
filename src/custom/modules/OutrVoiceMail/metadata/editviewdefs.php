<?php
/**
 * S-094-S-095: Voicemail Edit View
 * Edit form with Name, Description, File upload, TTS fields
 */

$viewdefs['OutrVoiceMail']['EditView'] = [
    'templateMeta' => [
        'form' => [
            'enctype' => 'multipart/form-data',
            'hidden' => [],
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
        'useTabs' => true,
        'tabDefs' => [
            'LBL_VOICEMAIL_INFORMATION' => [
                'newTab' => true,
                'panelDefault' => 'expanded',
            ],
            'LBL_RECORDING_DETAILS' => [
                'newTab' => true,
                'panelDefault' => 'expanded',
            ],
            'LBL_TTS_CONFIGURATION' => [
                'newTab' => true,
                'panelDefault' => 'expanded',
            ],
        ],
    ],
    'panels' => [
        'LBL_VOICEMAIL_INFORMATION' => [
            [
                'name',
                'assigned_user_name',
            ],
            [
                [
                    'name' => 'description',
                    'displayParams' => [
                        'rows' => 6,
                        'cols' => 80,
                    ],
                    'span' => 2,
                ],
            ],
            [
                'status',
                'source',
            ],
            [
                'direction',
                'duration',
            ],
        ],
        'LBL_RECORDING_DETAILS' => [
            [
                'twilio_call_sid',
                'twilio_recording_sid',
            ],
            [
                [
                    'name' => 'recording_file',
                    'label' => 'LBL_RECORDING_FILE',
                    'type' => 'file',
                    'customCode' => '
                        {if $fields.id.value}
                            {if $fields.recording_file.value}
                                <audio controls style="width: 100%;">
                                    <source src="index.php?entryPoint=voicemailPlayback&amp;recording_sid={$fields.twilio_recording_sid.value}" type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            {/if}
                        {/if}
                        <input type="file" name="recording_file" id="recording_file" accept="audio/*" />
                    ',
                ],
            ],
            [
                [
                    'name' => 'recording_uri',
                    'label' => 'LBL_RECORDING_URI',
                    'type' => 'url',
                    'readonly' => true,
                ],
            ],
            [
                [
                    'name' => 'recording_duration',
                    'label' => 'LBL_RECORDING_DURATION',
                    'type' => 'int',
                ],
                [
                    'name' => 'recording_format',
                    'label' => 'LBL_RECORDING_FORMAT',
                    'type' => 'varchar',
                ],
            ],
        ],
        'LBL_TTS_CONFIGURATION' => [
            [
                [
                    'name' => 'tts_enabled',
                    'label' => 'LBL_TTS_ENABLED',
                    'type' => 'bool',
                    'default' => '0',
                    'displayParams' => [
                        'required' => false,
                    ],
                ],
                [
                    'name' => 'tts_voice',
                    'label' => 'LBL_TTS_VOICE',
                    'type' => 'enum',
                    'options' => 'outr_tts_voice_list',
                    'default' => 'Polly.Joanna',
                ],
            ],
            [
                [
                    'name' => 'tts_text',
                    'label' => 'LBL_TTS_TEXT',
                    'type' => 'text',
                    'displayParams' => [
                        'rows' => 8,
                        'cols' => 80,
                    ],
                    'span' => 2,
                ],
            ],
            [
                [
                    'name' => 'tts_language',
                    'label' => 'LBL_TTS_LANGUAGE',
                    'type' => 'enum',
                    'options' => 'outr_tts_language_list',
                    'default' => 'en-US',
                ],
                [
                    'name' => 'tts_last_generated',
                    'label' => 'LBL_TTS_LAST_GENERATED',
                    'type' => 'datetime',
                    'readonly' => true,
                ],
            ],
        ],
    ],
];
