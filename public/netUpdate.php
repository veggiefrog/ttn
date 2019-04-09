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
function va3ned_ttn_netUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'net_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Net'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
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
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'checkAccess');
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.netUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current net
    //
    $strsql = "SELECT va3ned_ttn_nets.id, "
        . "va3ned_ttn_nets.name, "
        . "va3ned_ttn_nets.status, "
        . "va3ned_ttn_nets.start_utc AS start_utc_date, "
        . "va3ned_ttn_nets.start_utc AS start_utc_time, "
        . "va3ned_ttn_nets.end_utc AS end_utc_date, "
        . "va3ned_ttn_nets.end_utc AS end_utc_time "
        . "FROM va3ned_ttn_nets "
        . "WHERE va3ned_ttn_nets.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND va3ned_ttn_nets.id = '" . ciniki_core_dbQuote($ciniki, $args['net_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
        array('container'=>'nets', 'fname'=>'id', 
            'fields'=>array('name', 'status', 'start_utc_date', 'start_utc_time', 'end_utc_date', 'end_utc_time',),
            'utctotz'=>array(
                'start_utc_date'=>array('timezone'=>'UTC', 'Y-m-d'),
                'start_utc_time'=>array('timezone'=>'UTC', 'H:i:s'),
                'end_utc_date'=>array('timezone'=>'UTC', 'Y-m-d'),
                'end_utc_time'=>array('timezone'=>'UTC', 'H:i:s'),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.7', 'msg'=>'Net not found', 'err'=>$rc['err']));
    }
    if( !isset($rc['nets'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.8', 'msg'=>'Unable to find Net'));
    }
    $net = $rc['nets'][0];

    //
    // Check for updates to datetimes
    //
    if( isset($args['start_utc_date']) || isset($args['start_utc_time']) ) {
        $start_utc = (isset($args['start_utc_date']) ? $args['start_utc_date'] : $net['start_utc_date'])
            . ' ' . (isset($args['start_utc_time']) ? $args['start_utc_time'] : $net['start_utc_date']);
        if( trim($start_utc) != '' ) {
            $ts = strtotime($start_utc);
            if( $ts !== false && $ts > 0 ) {
                $dt = new DateTime("@".$ts, new DateTimeZone('UTC'));
                $args['start_utc'] = $dt->format('Y-m-d H:i:s');
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.34', 'msg'=>'Invalid start date & time'));
            }
        } else {
            $args['start_utc'] = '';
        }
    }

    if( isset($args['start_utc_date']) || isset($args['start_utc_time']) ) {
        $end_utc = (isset($args['end_utc_date']) ? $args['end_utc_date'] : $net['end_utc_date'])
            . ' ' . (isset($args['end_utc_time']) ? $args['end_utc_time'] : $net['end_utc_time']);
        if( trim($end_utc) != '' ) {
            $ts = strtotime($end_utc);
            if( $ts !== false && $ts > 0 ) {
                $dt = new DateTime("@".$ts, new DateTimeZone('UTC'));
                $args['end_utc'] = $dt->format('Y-m-d H:i:s');
            } else {
                return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.12', 'msg'=>'Invalid end date & time'));
            }
        } else {
            $args['end_utc'] = '';
        }
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
    // Update the Net in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'va3ned.ttn.net', $args['net_id'], $args, 0x04);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'va3ned.ttn.net', 'object_id'=>$args['net_id']));

    return array('stat'=>'ok');
}
?>
