<?php
//
// Description
// -----------
// This method will add a new net for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Net to.
//
// Returns
// -------
//
function va3ned_ttn_netAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'start_utc_date'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Start Date'),
        'start_utc_time'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Start Time'),
        'end_utc_date'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'End Date'),
        'end_utc_time'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'End Time'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'checkAccess');
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.netAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $start_utc = (isset($args['start_utc_date']) ? $args['start_utc_date'] : '')
        . ' ' . (isset($args['start_utc_time']) ? $args['start_utc_time'] : '');
    if( trim($start_utc) != '' ) {
        $ts = strtotime($start_utc);
        if( $ts !== false && $ts > 0 ) {
            $dt = new DateTime("@".$ts, new DateTimeZone('UTC'));
            $args['start_utc'] = $dt->format('Y-m-d H:i:s');
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.9', 'msg'=>'Invalid start date & time'));
        }
    } else {
        $args['start_utc'] = '';
    }

    $end_utc = (isset($args['end_utc_date']) ? $args['end_utc_date'] : '')
        . ' ' . (isset($args['end_utc_time']) ? $args['end_utc_time'] : '');
    if( trim($end_utc) != '' ) {
        $ts = strtotime($end_utc);
        if( $ts !== false && $ts > 0 ) {
            $dt = new DateTime("@".$ts, new DateTimeZone('UTC'));
            $args['end_utc'] = $dt->format('Y-m-d H:i:s');
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.35', 'msg'=>'Invalid end date & time'));
        }
    } else {
        $args['end_utc'] = '';
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
    // Add the net to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'va3ned.ttn.net', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'va3ned.ttn');
        return $rc;
    }
    $net_id = $rc['id'];

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'va3ned.ttn.net', 'object_id'=>$net_id));

    return array('stat'=>'ok', 'id'=>$net_id);
}
?>
