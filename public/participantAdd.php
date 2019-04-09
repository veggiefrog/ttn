<?php
//
// Description
// -----------
// This method will add a new participant for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Participant to.
//
// Returns
// -------
//
function va3ned_ttn_participantAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'net_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Net'),
        'callsign'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Callsign'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'name'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Name'),
        'email'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Email'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'checkAccess');
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.participantAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args['callsign'] = strtoupper($args['callsign']);

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
    // Add the participant to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'va3ned.ttn.participant', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'va3ned.ttn');
        return $rc;
    }
    $participant_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'va3ned.ttn.participant', 'object_id'=>$participant_id));

    //
    // Return the details for the net
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'public', 'netGet');
    return va3ned_ttn_netGet($ciniki);
}
?>
