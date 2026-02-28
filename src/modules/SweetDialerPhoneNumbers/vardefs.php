<?php
$dictionary['SweetDialerPhoneNumbers'] = array(
    'table' => 'twiliodialer_phone_numbers',
    'fields' => array(
        'id' => array('name'=>'id','type'=>'id','required'=>true),
        'phone_number' => array('name'=>'phone_number','vname'=>'LBL_PHONE_NUMBER','type'=>'phone','len'=>50,'required'=>true),
        'friendly_name' => array('name'=>'friendly_name','vname'=>'LBL_FRIENDLY_NAME','type'=>'varchar','len'=>255),
        'sid' => array('name'=>'sid','vname'=>'LBL_SID','type'=>'varchar','len'=>255,'required'=>true),
        'active' => array('name'=>'active','vname'=>'LBL_ACTIVE','type'=>'bool','default'=>'1'),
        'date_entered' => array('name'=>'date_entered','type'=>'datetime','required'=>true),
        'date_modified' => array('name'=>'date_modified','type'=>'datetime','required'=>true),
        'deleted' => array('name'=>'deleted','type'=>'bool','default'=>'0'),
    ),
    'indices' => array(
        array('name'=>'idx_phone_pk','type'=>'primary','fields'=>array('id')),
        array('name'=>'idx_phone_number','type'=>'index','fields'=>array('phone_number')),
    ),
);
