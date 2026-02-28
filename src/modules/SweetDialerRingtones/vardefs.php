<?php
$dictionary['SweetDialerRingtones'] = array(
    'table' => 'twiliodialer_dual_ringtone',
    'fields' => array(
        'id' => array('name'=>'id','type'=>'id','required'=>true),
        'name' => array('name'=>'name','vname'=>'LBL_NAME','type'=>'name','dbType'=>'varchar','len'=>255,'required'=>true),
        'file_path' => array('name'=>'file_path','vname'=>'LBL_FILE_PATH','type'=>'text'),
        'file_url' => array('name'=>'file_url','vname'=>'LBL_FILE_URL','type'=>'url'),
        'active' => array('name'=>'active','vname'=>'LBL_ACTIVE','type'=>'bool','default'=>'1'),
        'date_entered' => array('name'=>'date_entered','type'=>'datetime','required'=>true),
        'date_modified' => array('name'=>'date_modified','type'=>'datetime','required'=>true),
        'deleted' => array('name'=>'deleted','type'=>'bool','default'=>'0'),
    ),
    'indices' => array(
        array('name'=>'idx_ringtone_pk','type'=>'primary','fields'=>array('id')),
        array('name'=>'idx_ringtone_active','type'=>'index','fields'=>array('active')),
    ),
);
