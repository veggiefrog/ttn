<?php
//
// Description
// -----------
// This method will delete an participant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the participant is attached to.
// participant_id:            The ID of the participant to be removed.
//
// Returns
// -------
//
function va3ned_ttn_participantDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'participant_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Participant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'checkAccess');
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'ciniki.ttn.participantDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the participant
    //
    $strsql = "SELECT id, uuid "
        . "FROM va3ned_ttn_participants "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.ttn', 'participant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['participant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.13', 'msg'=>'Participant does not exist.'));
    }
    $participant = $rc['participant'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'va3ned.ttn.participant', $args['participant_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.14', 'msg'=>'Unable to check if the participant is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.15', 'msg'=>'The participant is still in use. ' . $rc['msg']));
    }

    //
    // Get the list of messages for this participant
    //
    $strsql = "SELECT id, uuid "
        . "FROM va3ned_ttn_messages "
        . "WHERE participant_id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'va3ned.ttn', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.23', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $messages = isset($rc['rows']) ? $rc['rows'] : array();
    

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'va3ned.ttn');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the messages
    //
    foreach($messages as $message) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'va3ned.ttn.message',
            $message['id'], $message['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'va3ned.ttn');
            return $rc;
        }
    }

    //
    // Remove the participant
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'va3ned.ttn.participant',
        $args['participant_id'], $participant['uuid'], 0x04);
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
    // Return the details for the net
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'public', 'netGet');
    return va3ned_ttn_netGet($ciniki);
}
?>
