<?php

use whitemerry\phpkin\AnnotationBlock;
use whitemerry\phpkin\Endpoint;
use whitemerry\phpkin\Identifier\SpanIdentifier;
use whitemerry\phpkin\Identifier\TraceIdentifier;
use whitemerry\phpkin\Logger\SimpleHttpLogger;
use whitemerry\phpkin\Metadata;
use whitemerry\phpkin\Span;
use whitemerry\phpkin\Tracer;

trait ZipkinTrait
{
	/**
	 * @return string
	 */
	abstract function getDSN();

	/**
	 * @return TraceIdentifier
	 */
	abstract public function getBaseTraceId();

	/**
	 * @return SpanIdentifier
	 */
	abstract public function getBaseTraceSpanId();

	/**
	 * @return bool
	 */
	abstract public function getBaseIsSampled();

	/**
	 * @return Endpoint
	 */
	abstract public function getBaseEndpoint();

	/**
	 * @var Endpoint
	 */
	protected $currentEndpoint;

	/**
	 * @var SimpleHttpLogger
	 */
	protected $logger;

	/**
	 * @var Tracer
	 */
	protected $tracer;

	/**
	 * @var int
	 */
	protected $requestStart;

	/**
	 * @return $this
	 */
	public function setRequestStart()
	{
		$this->requestStart = zipkin_timestamp();

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRequestStart()
	{
		if(is_null($this->requestStart))
		{
			$this->setRequestStart();
		}

		return $this->requestStart;
	}

	/**
	 * @return SimpleHttpLogger
	 */
	public function getLogger()
	{
		if(is_null($this->logger))
		{
			$this->logger = new SimpleHttpLogger(
				[
					'host'       => $this->getDSN(),
					'muteErrors' => false,
				]
			);
		}

		return $this->logger;
	}

	/**
	 * Save trace
	 */
	public function trace()
	{
		$this->getTracer()->trace();
	}

	/**
	 * @param string $name
	 * @param string $profile
	 *
	 * @return Tracer
	 */
	protected function getTracer($name = null)
	{
		/**
		 * And create tracer object, if you want to have statically access just initialize TracerProxy
		 * TracerProxy::init($tracer);
		 */
		if(is_null($this->tracer))
		{
			$this->tracer = new Tracer(
				is_null($name)
					? 'Tracert'
					: $name,
				$this->getBaseEndpoint(),
				$this->getLogger(),
				$this->getBaseIsSampled(),
				$this->getBaseTraceId(),
				$this->getBaseTraceSpanId()
			);
		}

		return $this->tracer;
	}

	/**
	 * @return Endpoint
	 *
	 * @throws ZipkinException
	 */
	public function getCurrentEndpoint()
	{
		if(is_null($this->currentEndpoint))
		{
			throw new ZipkinException("Endpoint not created");
		}

		return $this->currentEndpoint;
	}

	/**
	 * Adds Span to trace
	 *
	 * @param string     $name
	 * @param int|null   $startTimestamp
	 * @param array|null $meta
	 */
	public function addSpan($name, Endpoint $endpoint = null, array $meta = null, $startTimestamp = null)
	{
		if(!is_null($meta))
		{
			$metadata = new Metadata();
			foreach($meta as $_key => $_value)
			{
				$metadata->set($_key, is_array($_value) ? \json_encode($_value) : $_value);
			}
		}
		else
		{
			$metadata = null;
		}

		// Add span to Zipkin
		$this->getTracer()->addSpan(
			new Span(
				new SpanIdentifier(),
				$name,
				new AnnotationBlock(
					is_null($endpoint)
						? $this->getCurrentEndpoint()
						: $endpoint,
					is_null($startTimestamp)
						? $this->getRequestStart()
						: $startTimestamp
				),
				$metadata
			)
		);

		if(is_null($startTimestamp))
		{
			$this->setRequestStart();
		}
	}
}