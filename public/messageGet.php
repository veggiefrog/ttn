<?php
//
// Description
// ===========
// This method will return all the information about an message.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the message is attached to.
// message_id:          The ID of the message to get the details for.
//
// Returns
// -------
//
function va3ned_ttn_messageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
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
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.messageGet');
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
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Message
    //
    if( $args['message_id'] == 0 ) {
        $message = array('id'=>0,
            'participant_id'=>'',
            'status'=>'10',
            'number'=>'',
            'precedence'=>'',
            'hx'=>'',
            'station_of_origin'=>'',
            'check_number'=>'',
            'place_of_origin'=>'',
            'time_filed'=>'',
            'date_filed'=>'',
            'to_name_address'=>'',
            'phone_number'=>'',
            'email'=>'',
            'message'=>'',
            'signature'=>'',
        );
    }

    //
    // Get the details for an existing Message
    //
    else {
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
            . "AND va3ned_ttn_messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
            array('container'=>'messages', 'fname'=>'id', 
                'fields'=>array('participant_id', 'status', 'number', 'precedence', 'hx', 'station_of_origin', 'check_number', 'place_of_origin', 'time_filed', 'date_filed', 'to_name_address', 'phone_number', 'email', 'message', 'signature'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.21', 'msg'=>'Message not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['messages'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.22', 'msg'=>'Unable to find Message'));
        }
        $message = $rc['messages'][0];
    }

    return array('stat'=>'ok', 'message'=>$message);
}
?>
