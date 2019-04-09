<?php
//
// Description
// -----------
// This method will return the list of Messages for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Message for.
//
// Returns
// -------
//
function va3ned_ttn_messageList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'checkAccess');
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.messageList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of messages
    //
    $strsql = "SELECT va3ned_ttn_messages.id, "
        . "va3ned_ttn_messages.participant_id, "
        . "va3ned_ttn_messages.status, "
        . "va3ned_ttn_messages.number, "
        . "va3ned_ttn_messages.precedence, "
        . "va3ned_ttn_messages.hx, "
        . "va3ned_ttn_messages.station_of_origin, "
        . "va3ned_ttn_messages.check_number, "
        . "va3ned_ttn_messages.place_of_origin, "
        . "va3ned_ttn_messages.time_filed, "
        . "va3ned_ttn_messages.date_filed, "
        . "va3ned_ttn_messages.to_name_address, "
        . "va3ned_ttn_messages.phone_number, "
        . "va3ned_ttn_messages.email, "
        . "va3ned_ttn_messages.message, "
        . "va3ned_ttn_messages.signature "
        . "FROM va3ned_ttn_messages "
        . "WHERE va3ned_ttn_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'participant_id', 'status', 'number', 'precedence', 'hx', 'station_of_origin', 'check_number', 'place_of_origin', 'time_filed', 'date_filed', 'to_name_address', 'phone_number', 'email', 'message', 'signature')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['messages']) ) {
        $messages = $rc['messages'];
        $message_ids = array();
        foreach($messages as $iid => $message) {
            $message_ids[] = $message['id'];
        }
    } else {
        $messages = array();
        $message_ids = array();
    }

    return array('stat'=>'ok', 'messages'=>$messages, 'nplist'=>$message_ids);
}
?>
