<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function va3ned_ttn_messageUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'participant_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Participant'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Message Number'),
        'precedence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Precedence'),
        'hx'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Handling'),
        'station_of_origin'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Station of Origin'),
        'check_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Check'),
        'place_of_origin'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Place of Origin'),
        'time_filed'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Time Filed'),
        'date_filed'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Date Filed'),
        'to_name_address'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name/Address'),
        'phone_number'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Phone Number'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'),
        'message'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Message'),
        'signature'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Signature'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'checkAccess');
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.messageUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'va3ned.ttn');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Message in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'va3ned.ttn.message', $args['message_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'va3ned.ttn');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'va3ned.ttn');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'va3ned', 'ttn');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'va3ned.ttn.message', 'object_id'=>$args['message_id']));

    return array('stat'=>'ok');
}
?>
