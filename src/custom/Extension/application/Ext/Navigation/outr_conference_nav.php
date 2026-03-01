<?php
/**
 * Conference Navigation Extension
 * S-107-S-109: Add Conference to sidebar
 */

\$navigation['OutrConference'] = array(
    'type' => 'module',
    'order' => 120,
    'module' => 'OutrConference',
    'label' => 'LBL_OUTR_CONFERENCE',
    'icon' => 'fa-users',
    'parent' => 'SweetDialerCTI'
);
