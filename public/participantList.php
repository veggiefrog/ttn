<?php
//
// Description
// -----------
// This method will return the list of Participants for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Participant for.
//
// Returns
// -------
//
function va3ned_ttn_participantList($ciniki) {
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
    $rc = va3ned_ttn_checkAccess($ciniki, $args['tnid'], 'va3ned.ttn.participantList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'va3ned.ttn', array(
        array('container'=>'participants', 'fname'=>'id', 
            'fields'=>array('id', 'net_id', 'callsign', 'flags', 'name', 'email')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['participants']) ) {
        $participants = $rc['participants'];
        $participant_ids = array();
        foreach($participants as $iid => $participant) {
            $participant_ids[] = $participant['id'];
        }
    } else {
        $participants = array();
        $participant_ids = array();
    }

    return array('stat'=>'ok', 'participants'=>$participants, 'nplist'=>$participant_ids);
}
?>
