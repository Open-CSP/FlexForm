<?php
/**
 * Created by  : Designburo.nl
 * Project     : devrvs
 * Filename    : logger.class.php
 * Description :
 * Date        : 7-4-2021
 * Time        : 13:44
 */

/**
 * Class wsLogger
 * For letting WSForm write content to a LOG
 */
class wsLogger {

	/**
	 * Stored parameters from the parser
	 * @var array
	 */
	private $options = [];

	/**
	 * WM Tag and their named versions
	 * @var array
	 */
	private $allowedTags = [
		'log-publiceren'   => 'gepubliceerd',
		'log-depubliceren' => 'gedepubliceerd'
	];

	/**
	 * wsLogger constructor.
	 * Store the options for the logger
	 */
	public function __construct() {

		$field     = getFormValues( 'wslogger' );
		if ( $field === false ) {
			return false;
		}
		$options = array();
		if( is_array( $field ) ) {
			foreach( $field as $single ) {
				$options[] = $this->handleWsLogger( $single );
			}
		} else $options[] = $this->handleWsLogger( $field );

		$this->options = $options;
	}

	/**
	 * @param string $field
	 *
	 * @return array
	 */
	private function handleWsLogger( $field ){
		$separator = ';';
		$options  = [];
		$exploded = explode( $separator, $field );
		foreach ( $exploded as $single ) {
			$split         = explode( '=', $single );
			$k             = $split[0];
			$v             = $split[1];
			$options[ $k ] = $v;
		}
		return $options;
	}

	public function getCount(){
		return count( $this->options );
	}

	/**
	 * @param string $name
	 * @param int $k
	 *
	 * @return false|mixed
	 */
	private function getOption( string $name, int $k ) {
		if ( isset( $this->options[$k][ $name ] ) && $this->options[$k][ $name ] !== '' ) {
			return $this->options[$k][ $name ];
		} else {
			return false;
		}
	}

	/**
	 * @param int $i
	 * @return false|mixed
	 */
	public function getLogType( int $i ) {
		return $this->getOption( 'logtype', $i );
	}

	/**
	 * @param int $i
	 * @return false|mixed
	 */
	public function getTitle( int $i ) {
		return $this->getOption( 'title', $i  );
	}

	/**
	 * @param int $i
	 * @return false|mixed
	 */
	public function getTag( int $i ) {
		return $this->getOption( 'tag', $i  );
	}

	/**
	 * @param int $i
	 * @return false|mixed
	 */
	public function getSummary( int $i ) {
		return $this->getOption( 'summary', $i  );
	}

	/**
	 * @param int $i
	 * @return false|mixed
	 */
	public function getOption1( int $i ) {
		return $this->getOption( 'option', $i  );
	}

	/**
	 * @param int $i
	 * @return false|mixed
	 */
	public function getUser( int $i) {
		return $this->getOption( 'user', $i );

	}

	/**
	 * Check if it is an existing tag
	 * @param string $tag
	 *
	 * @return bool
	 */
	public function isAllowedTag( string $tag ): bool {
		if ( array_key_exists( $tag, $this->allowedTags ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Create the data for the API
	 * @param string $log
	 * @param string $title
	 * @param string $user
	 * @param string $tag
	 * @param string $option
	 * @param string|bool $summary
	 *
	 * @return string
	 */
	public function createDate(
		string $log,
		string $title,
		string $user,
		string $tag,
		string $option,
			   $summary
	): string {
			global $api;
			$data     = [];
			$postdata = http_build_query( [
				"action" => "query",
				"format" => "json",
				"meta"   => 'tokens',
			] );
			$result   = $api->apiPost( $postdata );
			if ( $result['error'] ) {
				echo $result['error'];
				exit;
			}
			$result          = $result['received'];
			$data['token']   = $result['query']['tokens']['csrftoken'];
			$data['action']  = 'customlogswrite';
			$data['logtype'] = $log;
			$data['title']   = $title;
			if ( $summary !== false ) {
				$data['summary'] = $summary;
			}
			$data['tags']     = $tag;
			$data['publish']  = 1;
			$data['custom-1'] = $this->allowedTags[ $tag ];
			$data['euser']    = $user;
			if ( $option !== false ) {
				$data['custom-2'] = $option;
			}
			$data['format'] = "json";

			return http_build_query( $data );

	}

}