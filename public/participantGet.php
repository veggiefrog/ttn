<?php
//
// Description
// ===========
// This method will return all the information about an participant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the participant is attached to.
// participant_id:          The ID of the participant to get the details for.
//
// Returns
// -------
//
function va3ned_ttn_participantGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'participant_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Participant'),
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
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.participantGet');
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
    // Return default for new Participant
    //
    if( $args['participant_id'] == 0 ) {
        $participant = array('id'=>0,
            'net_id'=>'',
            'callsign'=>'',
            'flags'=>'0',
            'name'=>'',
            'email'=>'',
        );
    }

    //
    // Get the details for an existing Participant
    //
    else {
        $strsql = "SELECT va3ned_ttn_participants.id, "
            . "va3ned_ttn_participants.net_id, "
            . "va3ned_ttn_participants.callsign, "
            . "va3ned_ttn_participants.flags, "
            . "va3ned_ttn_participants.name, "
            . "va3ned_ttn_participants.email "
            . "FROM va3ned_ttn_participants "
            . "WHERE va3ned_ttn_participants.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND va3ned_ttn_participants.id = '" . ciniki_core_dbQuote($ciniki, $args['participant_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
            array('container'=>'participants', 'fname'=>'id', 
                'fields'=>array('net_id', 'callsign', 'flags', 'name', 'email'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.16', 'msg'=>'Participant not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['participants'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'va3ned.ttn.17', 'msg'=>'Unable to find Participant'));
        }
        $participant = $rc['participants'][0];
    }

    return array('stat'=>'ok', 'participant'=>$participant);
}
?>
