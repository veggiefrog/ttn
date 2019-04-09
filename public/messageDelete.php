<?php
//
// Description
// -----------
// This method will delete an message.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the message is attached to.
// message_id:            The ID of the message to be removed.
//
// Returns
// -------
//
function va3ned_ttn_messageDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Message'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'checkAccess');
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'ciniki.ttn.messageDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the message
    //
    $strsql = "SELECT id, uuid "
        . "FROM va3ned_ttn_messages "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.ttn', 'message');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['message']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.18', 'msg'=>'Message does not exist.'));
    }
    $message = $rc['message'];

    //
    // Check for any dependencies before deleting
    //

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'va3ned.ttn.message', $args['message_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.19', 'msg'=>'Unable to check if the message is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.20', 'msg'=>'The message is still in use. ' . $rc['msg']));
    }

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
    // Remove the message
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'va3ned.ttn.message',
        $args['message_id'], $message['uuid'], 0x04);
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

    return array('stat'=>'ok');
}
?>
