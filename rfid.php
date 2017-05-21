<?php

class rfid_api {
	var $fp, $data;

	function rfid_api() {
		$this->status = array(
			0x00 => 'Command successfully completed',
			0x01 => 'General error',
			0x02 => 'Parameter setting failed',
			0x03 => 'Parameter reading failed',
			0x04 => 'No tag',
			0x05 => 'Tag reading failed',
			0x06 => 'Tag writing failed',
			0x07 => 'Tag locking failed',
			0x08 => 'Tag erase failed',
			0x09 => '',
			0x0A => '',
			0xFE => 'Command not supported or parameter out of range',
			0xFF => 'no definition error',
			0x53 => 'Write successfully',
			0x52 => 'Write Error',
			);
	}

	function write() {
		$data_write = substr( uniqid(), 0, -1 );

		$status = $data_write;
		$j = 1;
		for( $i = 0; $i < 11; $i = $i + 2 ) {
			$req = [ 0x0A, 0xFF, 0x0A, 0x89, 0x00, 0x00, 0x00, 0x00, 0x01, ++$j ];
			$req[] = hexdec( substr( $data_write, $i, 1 ) );
			$req[] = hexdec( substr( $data_write, $i + 1, 1 ) );

			$res = $this->get_data( $req );
			if( $res[4] === 0x52 || $res[4] !== 0x00 ) {
				$status = false;
			}
		}

		return $status;		
	}

	function get_status( ) {
		return @$this->status[ $this->data[ 4 ] ]?:'Error ';
	}

	var $ip, $port;
	function connect( $ip = '', $port = 100 ) {
		if( $ip !== '') {
			$this->ip = $ip;
			$this->port = $port;
		} else {
			$ip = $this->ip;
			$port = $this->port;
		}

		$this->fp = fsockopen( $ip, $port, $errno, $errstr, 30 );
		stream_set_blocking($this->fp, 0);
	}

	function get_result( $p = false ) {
		usleep( 100000 );

		$data = fread($this->fp, 128);

		$out = unpack( "C*", $data );

		//checksum
		$sum = '';
		foreach( $out as $k => $v ) {
			if( $k !== count( $out ) )
				$sum += $v;
		}

		$checksum = $sum > 256 ? ( 256 - $sum % 256  ) : ( 256 - $sum );

		if( $checksum !== @$out[ count( $out ) ] ) {
			return false;
		}

        $this->data = $out;

        return $out;
	}


	function get_data( $t ) {

		$sum = 0;
		foreach( $t as $k => $v ) {
			$sum += $v;
		}

		//checksum
		$t[] =  256 - $sum % 256;

		$cmd = '';
		foreach( $t as $k => $v ) {
			$cmd .= chr( $v );
		}

		$res = false;

		if( @ fwrite( $this->fp, $cmd ) ) {
			$res = $this->get_result();		
		} else {
			sleep( 1 );
			unset( $this->fp );
			$this->connect();
		}


		return $res;	
	}

	function get_ip() {
		return $this->get_data( [ 0x0A, 0xFF, 0x02, 0x2B ] );
	}

	function get_version() {
		return $this->get_data( [ 0x0A, 0xFF, 0x02, 0x22 ] );
	}


	function get_frequency() {
		return $this->get_data( [ 0x0A, 0xFF, 0x02, 0x28 ] );
	}


	function query_antenna() {
		return $this->get_data( [ 0x0A, 0xFF, 0x02, 0x2A ] );
	}

	function get_rf_power() {
		return $this->get_data( [ 0x0A, 0xFF, 0x02, 0x26 ] );
	}

	function __destruct() {
		fclose($this->fp);
	}

}


?>