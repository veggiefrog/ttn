<?php
//
// Description
// ===========
// This method will return all the information about an net.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the net is attached to.
// net_id:          The ID of the net to get the details for.
//
// Returns
// -------
//
function va3ned_ttn_netGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'net_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Net'),
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
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.netGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

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
    // Return default for new Net
    //
    if( $args['net_id'] == 0 ) {
        $net = array('id'=>0,
            'name'=>'',
            'status'=>'10',
            'start_utc'=>'',
            'end_utc'=>'',
        );
    }

    //
    // Get the details for an existing Net
    //
    else {
        $strsql = "SELECT va3ned_ttn_nets.id, "
            . "va3ned_ttn_nets.name, "
            . "va3ned_ttn_nets.status, "
            . "va3ned_ttn_nets.status AS status_text, "
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
                'fields'=>array('name', 'status', 'status_text', 'start_utc_date', 'start_utc_time', 'end_utc_date', 'end_utc_time',),
                'maps'=>array('status_text'=>$maps['net']['status']),
                'utctotz'=>array(
                    'start_utc_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'start_utc_time'=>array('timezone'=>'UTC', 'format'=>$time_format),
                    'end_utc_date'=>array('timezone'=>'UTC', 'format'=>$date_format),
                    'end_utc_time'=>array('timezone'=>'UTC', 'format'=>$time_format),
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.32', 'msg'=>'Net not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['nets'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.33', 'msg'=>'Unable to find Net'));
        }
        $net = $rc['nets'][0];

        //
        // Get the list of participants
        //
        $strsql = "SELECT va3ned_ttn_participants.id, "
            . "va3ned_ttn_participants.net_id, "
            . "va3ned_ttn_participants.callsign, "
            . "va3ned_ttn_participants.flags, "
            . "va3ned_ttn_participants.name, "
            . "va3ned_ttn_participants.email "
            . "FROM va3ned_ttn_participants "
            . "WHERE va3ned_ttn_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND va3ned_ttn_participants.net_id = '" . ciniki_core_dbQuote($ciniki, $args['net_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
            array('container'=>'participants', 'fname'=>'id', 
                'fields'=>array('id', 'net_id', 'callsign', 'flags', 'name', 'email')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $net['participants'] = isset($rc['participants']) ? $rc['participants'] : array();

        $participant_ids = array();
        foreach($net['participants'] as $p) {
            $participant_ids[] = $p['id'];
        }

        //
        // Get the list of messages
        //
        if( count($participant_ids) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
            $strsql = "SELECT va3ned_ttn_messages.id, "
                . "va3ned_ttn_messages.participant_id, "
                . "va3ned_ttn_participants.callsign, "
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
                . "LEFT JOIN va3ned_ttn_participants ON ("
                    . "va3ned_ttn_messages.participant_id = va3ned_ttn_participants.id "
                    . "AND va3ned_ttn_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE va3ned_ttn_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND va3ned_ttn_messages.participant_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $participant_ids) . ") "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
                array('container'=>'messages', 'fname'=>'id', 
                    'fields'=>array('id', 'participant_id', 'callsign', 'status', 'number', 'precedence', 'hx', 
                        'station_of_origin', 'check_number', 'place_of_origin', 'time_filed', 'date_filed', 
                        'to_name_address', 'phone_number', 'email', 'message', 'signature')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $net['messages'] = isset($rc['messages']) ? $rc['messages'] : array();
        } else {
            $net['messages'] = array();
        }
    }

    return array('stat'=>'ok', 'net'=>$net);
}
?>
