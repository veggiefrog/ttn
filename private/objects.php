<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function va3ned_ttn_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();

    $objects['net'] = array(
        'name' => 'Net',
        'sync' => 'yes',
        'o_name' => 'net',
        'o_container' => 'nets',
        'table' => 'va3ned_ttn_nets',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'start_utc' => array('name'=>'Start Date Time', 'default'=>''),
            'end_utc' => array('name'=>'End Date Time', 'default'=>''),
            'message_source' => array('name'=>'Message Source File', 'default'=>''),
            'place_of_origin' => array('name'=>'Place of Origin', 'default'=>''),
            ),
        'history_table' => 'va3ned_ttn_history',
        );
    $objects['participant'] = array(
        'name' => 'Participant',
        'sync' => 'yes',
        'o_name' => 'participant',
        'o_container' => 'participants',
        'table' => 'va3ned_ttn_participants',
        'fields' => array(
            'net_id' => array('name'=>'Net', 'ref'=>'va3ned.ttn.net'),
            'callsign' => array('name'=>'Callsign'),
            'flags' => array('name'=>'Options', 'default'=>'0'),
            'name' => array('name'=>'Name', 'default'=>''),
            'email' => array('name'=>'Email', 'default'=>''),
            ),
        'history_table' => 'va3ned_ttn_history',
        );
    $objects['message'] = array(
        'name' => 'Message',
        'sync' => 'yes',
        'o_name' => 'message',
        'o_container' => 'messages',
        'table' => 'va3ned_ttn_messages',
        'fields' => array(
            'participant_id' => array('name'=>'Participant', 'ref'=>'va3ned.ttn.participant'),
            'status' => array('name'=>'Status', 'default'=>'10'),
            'number' => array('name'=>'Message Number', 'default'=>''),
            'precedence' => array('name'=>'Precedence', 'default'=>''),
            'hx' => array('name'=>'Handling', 'default'=>''),
            'station_of_origin' => array('name'=>'Station of Origin', 'default'=>''),
            'check_number' => array('name'=>'Check', 'default'=>''),
            'place_of_origin' => array('name'=>'Place of Origin', 'default'=>''),
            'time_filed' => array('name'=>'Time Filed', 'default'=>''),
            'date_filed' => array('name'=>'Date Filed', 'default'=>''),
            'to_name_address' => array('name'=>'Name/Address', 'default'=>''),
            'phone_number' => array('name'=>'Phone Number', 'default'=>''),
            'email' => array('name'=>'Email', 'default'=>''),
            'message' => array('name'=>'Message', 'default'=>''),
            'signature' => array('name'=>'Signature', 'default'=>''),
            ),
        'history_table' => 'va3ned_ttn_history',
        );


    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
