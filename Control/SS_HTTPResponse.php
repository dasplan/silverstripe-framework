<?php

namespace SilverStripe\Control;

use InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Requirements;

/**
 * Represents a response returned by a controller.
 */
class SS_HTTPResponse {

	/**
	 * @var array
	 */
	protected static $status_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Request Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		429 => 'Too Many Requests',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
	);

	/**
	 * @var array
	 */
	protected static $redirect_codes = array(
		301,
		302,
		303,
		304,
		305,
		307
	);

	/**
	 * @var int
	 */
	protected $statusCode = 200;

	/**
	 * @var string
	 */
	protected $statusDescription = "OK";

	/**
	 * HTTP Headers like "Content-Type: text/xml"
	 *
	 * @see http://en.wikipedia.org/wiki/List_of_HTTP_headers
	 * @var array
	 */
	protected $headers = array(
		"Content-Type" => "text/html; charset=utf-8",
	);

	/**
	 * @var string
	 */
	protected $body = null;

	/**
	 * Create a new HTTP response
	 *
	 * @param string $body The body of the response
	 * @param int $statusCode The numeric status code - 200, 404, etc
	 * @param string $statusDescription The text to be given alongside the status code.
	 *  See {@link setStatusCode()} for more information.
	 */
	public function __construct($body = null, $statusCode = null, $statusDescription = null) {
		$this->setBody($body);
		if($statusCode) {
			$this->setStatusCode($statusCode, $statusDescription);
		}
	}

	/**
	 * @param string $code
	 * @param string $description Optional. See {@link setStatusDescription()}.
	 *  No newlines are allowed in the description.
	 *  If omitted, will default to the standard HTTP description
	 *  for the given $code value (see {@link $status_codes}).
	 * @return $this
	 */
	public function setStatusCode($code, $description = null) {
		if(isset(self::$status_codes[$code])) {
			$this->statusCode = $code;
		} else {
			throw new InvalidArgumentException("Unrecognised HTTP status code '$code'");
		}

		if($description) {
			$this->statusDescription = $description;
		} else {
			$this->statusDescription = self::$status_codes[$code];
		}
		return $this;
	}

	/**
	 * The text to be given alongside the status code ("reason phrase").
	 * Caution: Will be overwritten by {@link setStatusCode()}.
	 *
	 * @param string $description
	 * @return $this
	 */
	public function setStatusDescription($description) {
		$this->statusDescription = $description;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * @return string Description for a HTTP status code
	 */
	public function getStatusDescription() {
		return str_replace(array("\r","\n"), '', $this->statusDescription);
	}

	/**
	 * Returns true if this HTTP response is in error
	 *
	 * @return bool
	 */
	public function isError() {
		return $this->statusCode && ($this->statusCode < 200 || $this->statusCode > 399);
	}

	/**
	 * @param string $body
	 * @return $this
	 */
	public function setBody($body) {
		$this->body = $body ? (string) $body : $body; // Don't type-cast false-ish values, eg null is null not ''
		return $this;
	}

	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * Add a HTTP header to the response, replacing any header of the same name.
	 *
	 * @param string $header Example: "Content-Type"
	 * @param string $value Example: "text/xml"
	 * @return $this
	 */
	public function addHeader($header, $value) {
		$this->headers[$header] = $value;
		return $this;
	}

	/**
	 * Return the HTTP header of the given name.
	 *
	 * @param string $header
	 * @returns string
	 */
	public function getHeader($header) {
		if(isset($this->headers[$header])) {
			return $this->headers[$header];
		}
		return null;
	}

	/**
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Remove an existing HTTP header by its name,
	 * e.g. "Content-Type".
	 *
	 * @param string $header
	 * @return $this
	 */
	public function removeHeader($header) {
		unset($this->headers[$header]);
		return $this;
	}

	/**
	 * @param string $dest
	 * @param int $code
	 * @return $this
	 */
	public function redirect($dest, $code = 302) {
		if(!in_array($code, self::$redirect_codes)) {
			trigger_error("Invalid HTTP redirect code {$code}", E_USER_WARNING);
			$code = 302;
		}
		$this->setStatusCode($code);
		$this->headers['Location'] = $dest;
		return $this;
	}

	/**
	 * Send this HTTPReponse to the browser
	 */
	public function output() {
		// Attach appropriate X-Include-JavaScript and X-Include-CSS headers
		if(Director::is_ajax()) {
			Requirements::include_in_response($this);
		}

		if(in_array($this->statusCode, self::$redirect_codes) && headers_sent($file, $line)) {
			$url = Director::absoluteURL($this->headers['Location'], true);
			$urlATT = Convert::raw2htmlatt($url);
			$urlJS = Convert::raw2js($url);
			$title = Director::isDev()
				? "{$urlATT}... (output started on {$file}, line {$line})"
				: "{$urlATT}...";
			echo <<<EOT
<p>Redirecting to <a href="{$urlATT}" title="Click this link if your browser does not redirect you">{$title}</a></p>
<meta http-equiv="refresh" content="1; url={$urlATT}" />
<script type="application/javascript">setTimeout(function(){
	window.location.href = "{$urlJS}";
}, 50);</script>
EOT
			;
		} else {
			$line = $file = null;
			if(!headers_sent($file, $line)) {
				header($_SERVER['SERVER_PROTOCOL'] . " $this->statusCode " . $this->getStatusDescription());
				foreach($this->headers as $header => $value) {
					//etags need to be quoted
					if (strcasecmp('etag', $header) === 0 && 0 !== strpos($value, '"')) {
						$value = sprintf('"%s"', $value);
					}
					header("$header: $value", true, $this->statusCode);
				}
			} else {
				// It's critical that these status codes are sent; we need to report a failure if not.
				if($this->statusCode >= 300) {
					user_error(
						"Couldn't set response type to $this->statusCode because " .
						"of output on line $line of $file",
						E_USER_WARNING
					);
				}
			}

			// Only show error pages or generic "friendly" errors if the status code signifies
			// an error, and the response doesn't have any body yet that might contain
			// a more specific error description.
			if(Director::isLive() && $this->isError() && !$this->body) {
				$formatter = Injector::inst()->get('FriendlyErrorFormatter');
				echo $formatter->format(array(
					'code' => $this->statusCode
				));

			} else {
				echo $this->body;
			}

		}
	}

	/**
	 * Returns true if this response is "finished", that is, no more script execution should be done.
	 * Specifically, returns true if a redirect has already been requested
	 *
	 * @return bool
	 */
	public function isFinished() {
		return in_array($this->statusCode, array(301, 302, 303, 304, 305, 307, 401, 403));
	}

}
