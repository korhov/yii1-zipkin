<?php

use whitemerry\phpkin\Endpoint;
use whitemerry\phpkin\Identifier\SpanIdentifier;
use whitemerry\phpkin\Identifier\TraceIdentifier;
use whitemerry\phpkin\Tracer;

class Zipkin extends \CComponent
{
	use ZipkinTrait{
		getTracer as traitGetTracer;
	}

	/**
	 * @var string
	 */
	public $dsn = 'http://127.0.0.1:9411';

	public $headerTraceId     = 'HTTP_REQUEST_ID';
	public $headerTraceSpanId = 'HTTP_REQUEST_SPAN_ID';
	public $headerIsSampled   = 'HTTP_REQUEST_SAMPLED';

	/**
	 * @var TraceIdentifier
	 */
	protected $traceId;

	/**
	 * @var SpanIdentifier
	 */
	protected $traceSpanId;

	/**
	 * @var bool
	 */
	protected $isSampled;

	/**
	 * @var Endpoint
	 */
	protected $baseEndpoint;


	public function init()
	{
		$this->getTracer();
		$this->getTracer()->setProfile(Tracer::BACKEND);

		$this->setRequestStart();

		\Yii::app()->attachEventHandler('onEndRequest', array($this, 'trace'));
	}

	public function ping()
	{
	}

	/**
	 * @return string
	 */
	function getDSN()
	{
		return $this->dsn;
	}

	/**
	 * @param string $item
	 *
	 * @return mixed
	 */
	public function getHeaderItem(string $item)
	{
		return $_SERVER[$item];
	}

	/**
	 * @param string $item
	 *
	 * @return bool
	 */
	public function hasHeaderItem(string $item)
	{
		return isset($_SERVER[$item]) && (!empty($_SERVER[$item]) || $_SERVER[$item] == '0');
	}

	/**
	 * @return TraceIdentifier
	 */
	public function getBaseTraceId()
	{
		if(is_null($this->traceId))
		{
			if($this->hasHeaderItem($this->headerTraceId))
			{
				$this->traceId = new TraceIdentifier($this->getHeaderItem($this->headerTraceId));
			}
		}

		return $this->traceId;
	}

	/**
	 * @return SpanIdentifier
	 */
	public function getBaseTraceSpanId()
	{
		if(is_null($this->traceSpanId) && $this->hasHeaderItem($this->headerTraceSpanId))
		{
			$this->traceSpanId = new SpanIdentifier($this->getHeaderItem($this->headerTraceSpanId));
		}

		return $this->traceSpanId;
	}

	/**
	 * @return bool
	 */
	public function getBaseIsSampled()
	{
		if(is_null($this->isSampled) && $this->hasHeaderItem($this->headerIsSampled))
		{
			$this->isSampled = (bool) $this->getHeaderItem($this->headerIsSampled);
		}

		return $this->isSampled;
	}

	/**
	 * @return Endpoint
	 */
	public function getBaseEndpoint()
	{
		if(is_null($this->baseEndpoint))
		{
			$this->baseEndpoint = new Endpoint(\Yii::app()->name, $_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT']);
		}

		return $this->baseEndpoint;
	}

	/**
	 * @param string $serviceName
	 * @param string $ip
	 * @param string $port
	 */
	public function newEndpoint($serviceName = null)
	{
		return $this->currentEndpoint = new Endpoint(
			is_null($serviceName)
				? \Yii::app()->name
				: $serviceName,
			$_SERVER['SERVER_ADDR'],
			$_SERVER['SERVER_PORT']
		);
	}

	/**
	 * @param string $name
	 *
	 * @return Tracer
	 */
	protected function getTracer($name = null)
	{
		return $this->traitGetTracer(
			is_null($name)
				? $_SERVER['REQUEST_METHOD'] . ' ' . \Yii::app()->request->requestUri
				: $name
		);
	}
}
