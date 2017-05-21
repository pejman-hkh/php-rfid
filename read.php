<?php
ini_set( "display_errors", 'On' );

require_once( 'rfid.php' );

$p = new rfid_api();
$p->connect( "192.168.1.200", 100 );


$start_read = time();
$tag = '';
while( true ) {
	//start reading
	$res = $p->get_data( [ 0x0A, 0xFF, 0x03, 0x80, 0x00 ] );

	$res = $p->get_data( [ 0x0A, 0xFF, 0x03, 0x40, $res[6] ] );

	if( $res[5] > 0 ) {
		$out = array();
		for( $j = 8; $j < 20; $j++ ) {
			$dec = dechex( $res[ $j ] );
			$out[] = $dec;
		}

		$tag = implode("", $out );
		break;
	}

	if( time() - $start_read > 10 ) {
		break;
	}
}

//stop reading
$res = $p->get_data( [ 0x0A, 0xFF, 0x03, 0x81, 0x01 ] );

echo ( json_encode( array('tag' => $tag ) ) );


?>