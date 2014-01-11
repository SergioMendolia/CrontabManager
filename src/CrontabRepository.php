<?php
/**
 * CrontabRepository
 * Cron jobs manager
 * Manage CrontabJob Objects (add, modify, delete). 
 */

require_once dirname(__FILE__) . '/CrontabJob.php';
require_once dirname(__FILE__) . '/CrontabAdapter.php';

class CrontabRepository{
	
	private $crontabAdapter;
	
	private $crontabJobs = array();
	
	/**
	 * Contain comments on the top of the crontab file. 
	 * @var String
	 */
	public $headerComments;
	
	/**
	 * Instanciate a Crontab repository.
	 * A CrontabAdapter adapter must be provided in order to communicate
	 * with the system "crontab" command line.
	 * @param CrontabAdapter $crontabAdapter
	 */
	public function __construct(CrontabAdapter $crontabAdapter) {
		$this->crontabAdapter = $crontabAdapter;
		$this->readCrontab();
	}
	
	/**
	 * Return the CrontabJob in the "connected" crontab
	 * @return Array of CrontabJobs
	 */
	public function getJobs() {
		return $this->crontabJobs;
	}
		
	/**
	 * Finds jobs by matching theirs task commands with a regex
	 * @param String $regex
	 * @throws InvalidArgumentException
	 * @return Array of CronJobs
	 */
	public function findJobByRegex($regex) {
				
		/* Test if regex is valid */
		set_error_handler(function($severity, $message, $file, $line){
			throw new Exception($message);			
		});
		
		try{
			preg_match($regex, 'test');	
			restore_error_handler();
		}
		catch(Exception $e) {
			restore_error_handler();
			throw new InvalidArgumentException('Not a valid Regex : ' . $e->getMessage());		
			return;
		}

		$crontabJobs = array();
		
		if(!empty($this->crontabJobs)) {
			foreach($this->crontabJobs as $crontabJob) {
				if(preg_match($regex, $crontabJob->taskCommandLine)) {
					array_push($crontabJobs, $crontabJob);
				}
			}
		}
		
		return $crontabJobs;
	}
	
	/**
	 * Add an new CrontabJob in the connected crontab
	 * @param CrontabJob $crontabJob
	 */
	public function addJob(CrontabJob $crontabJob) {
		array_push($this->crontabJobs, $crontabJob);		
	}
	
	/**
	 * Save all operations to the connected crontab.
	 */
	public function persist() {
		
		$crontabRawData = '';
		if(!empty($this->headerComments)) {
			$crontabRawData .= $this->headerComments;
		}
		
		if(!empty($this->crontabJobs)) {
			foreach($this->crontabJobs as $crontabJob) {
				try{
					$crontabLine = $crontabJob->formatCrontabLine();
					$crontabRawData .= ($crontabLine . "\n");
				}
				catch(Exception $e) {
					/* Do nothing here */
				}
			}
		}
		
		$this->crontabAdapter->writeCrontab($crontabRawData);
				
	}
	
	/**
	 * Retrieve the crontab raw data from the system then parse it.
	 */
	private function readCrontab() {
		
		$crontabRawData = $this->crontabAdapter->readCrontab();
		
		if(empty($crontabRawData)) {
			return;
		}
		
		$crontabRawLines = explode("\n", $crontabRawData);
		
		foreach($crontabRawLines as $crontabRawLine) {
			
			try{
				
				/* Use The crontabJob Factory to test if the line is a crontab job line */
				$crontabJob = CrontabJob::createFromCrontabLine($crontabRawLine);
				array_push($this->crontabJobs, $crontabJob);
				
			}
			catch(Exception $e) {
				
				/* if no crontabjobs not already fund, we considers line as header comment */
				if(empty($this->crontabJobs)) {
					if(empty($this->headerComments)) {
						$this->headerComments = $crontabRawLine . "\n";
					}
					else {
						$this->headerComments .= ($crontabRawLine . "\n");
					}
				}
				
			}
		}
				
	}
	
}