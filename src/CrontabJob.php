<?php
/**
 * CrontabJob
 * Represent a Job in the crontab.
 * 
 * @author FranceProNet
 * @package CrontabManager
 */

class CrontabJob {
	
	/**
	 * min (0 - 59)
	 * @var String/int
	 */
	public $minutes;
	
	/**
	 * hour (0 - 23)
	 * @var String/int
	 */
	public $hours;
	
	/**
	 * day of month (1 - 31)
	 * @var String/int
	 */
	public $dayOfMonth;
	
	/**
	 * month (1 - 12)
	 * @var String/int
	 */
	public $months;
	
	/**
	 * day of week (0 - 6) (0 or 6 are Sunday to Saturday, or use names)
	 * @var String/int
	 */
	public $dayOfWeek;

	/**
	 * the task command line to be executed 
	 * @var String
	 */
	public $taskCommandLine;
	
	/**
	 * Optional comment that will be placed at the end of the crontab line preceded by #
	 * @var unknown_type
	 */
	public $comments;
	
	/**
	 * Predefined scheduling definition
	 * Shorcut définition that replace standard définition (preceded by @)
	 * possibles values : yearly, monthly, weekly, daily, hourly, reboot
	 * When a shortcut is defined, it overwrite stantard définition
	 * @var String
	 */
	public $shortCut;
	
	/**
	 * Factory method to create a CrontabJob from a crontab line.
	 * @param String $crontabLine
	 * @throws InvalidArgumentException
	 * @return CrontabJob
	 */
	public static function createFromCrontabLine($crontabLine) {

		/* Check crontab line format validity */
		if(!preg_match('/^[\s\t]*(([*0-9,-\/]+)[\s\t]+([*0-9,-\/]+)[\s\t]+([*0-9,-\/]+)[\s\t]+([*a-z0-9,-\/]+)[\s\t]+([*a-z0-9,-\/]+)|(@(reboot|yearly|annually|monthly|weekly|daily|midnight|hourly)))[\s\t]+([^#]+)([\s\t]+#(.+))?$/', $crontabLine, $matches)) {
			throw new InvalidArgumentException('Crontab line not well formated then can\'t be parsed');
		}

		/* Create the job from parsed crontab line values */
		$crontabJob = new self();
		
		if(!empty($matches)) {
			$crontabJob->minutes = $matches[2];
			$crontabJob->hours = $matches[3];
			$crontabJob->dayOfMonth = $matches[4];
			$crontabJob->months = $matches[5];
			$crontabJob->dayOfWeek = $matches[6];			
		}
		
		if(!empty($matches[7])) {
			$crontabJob->shortCut = $matches[8];
		}
		
		$crontabJob->taskCommandLine = $matches[9];
		if(!empty($matches[11])) {
			$crontabJob->comments = $matches[11];
		}
		
		return $crontabJob;
		
		
	}
	
	/**
	 * Format the CrontabJob to a crontab line 
	 * @throws InvalidArgumentException
	 */	
	public function formatCrontabLine() {
		
		/* Check if job has a task command line*/
		if(!isset($this->taskCommandLine) || empty($this->taskCommandLine)) {
			throw new InvalidArgumentException('CrontabJob contain\'s no task command line');
		}
		
		$taskPlanningNotation = (isset($this->shortCut) && !empty($this->shortCut))
			? sprintf('@%s', $this->shortCut)
			: sprintf(
				'%s %s %s %s %s', 
				(isset($this->minutes) ? $this->minutes : '*'),
				(isset($this->hours) ? $this->hours : '*'),
				(isset($this->dayOfMonth) ? $this->dayOfMonth : '*'),
				(isset($this->months) ? $this->months : '*'),
				(isset($this->dayOfWeek) ? $this->dayOfWeek : '*')
			)
		;	
		
		return sprintf(
			'%s %s%s', 
			$taskPlanningNotation, 
			$this->taskCommandLine, 
			(isset($this->comments) ? (' #' . $this->comments) : '')
		);	

	}
	
}