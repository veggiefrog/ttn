<?php
//
// Description
// -----------
// This method will return the list of Nets for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Net for.
//
// Returns
// -------
//
function va3ned_ttn_netList($ciniki) {
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
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.netList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'va3ned', 'ttn', 'private', 'maps');
    $rc = va3ned_ttn_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of nets
    //
    $strsql = "SELECT va3ned_ttn_nets.id, "
        . "va3ned_ttn_nets.name, "
        . "va3ned_ttn_nets.status, "
        . "va3ned_ttn_nets.status AS status_text, "
        . "va3ned_ttn_nets.start_utc, "
        . "va3ned_ttn_nets.start_utc AS start_utc_text, "
        . "va3ned_ttn_nets.start_utc AS start_time_text, "
        . "va3ned_ttn_nets.end_utc "
        . "FROM va3ned_ttn_nets "
        . "WHERE va3ned_ttn_nets.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY start_utc DESC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
        array('container'=>'nets', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'status_text', 'start_utc', 'start_utc_text', 'end_utc'),
            'maps'=>array('status_text'=>$maps['net']['status']),
            'utctotz'=>array(
                'start_utc_text'=>array('timezone'=>'UTC', 'format'=>$date_format),
                'start_time_text'=>array('timezone'=>'UTC', 'format'=>$time_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['nets']) ) {
        $nets = $rc['nets'];
        $net_ids = array();
        foreach($nets as $iid => $net) {
            $net_ids[] = $net['id'];
        }
    } else {
        $nets = array();
        $net_ids = array();
    }

    return array('stat'=>'ok', 'nets'=>$nets, 'nplist'=>$net_ids);
}
?>
