<?php

namespace Drupal\events_example\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a incident report event for event subscribers.
 *
 * Whenever there is additional contextual data that you want to provide to the
 * event subscribers when dispatching an event you should create a new class
 * that extends \Symfony\Component\EventDispatcher\Event.
 *
 * See \Drupal\Core\Config\ConfigCrudEvent for an example of this in core.
 *
 * @see \Drupal\Core\Config\ConfigCrudEvent
 *
 * @ingroup events_example
 */
class IncidentReportEvent extends Event {

  /**
   * Incident type.
   *
   * @var string
   */
  protected $type;

  /**
   * Detailed incident report.
   *
   * @var string
   */
  protected $report;

  /**
   * Constructs an incident report event object.
   *
   * @param string $type
   *   The incident report type.
   * @param string $report
   *   A detailed description of the incident provided by the reporter.
   */
  public function __construct($type, $report) {
    $this->type = $type;
    $this->report = $report;
  }

  /**
   * Get the incident type.
   *
   * @return string
   *   The type of report.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get the detailed incident report.
   *
   * @return string
   *   The text of the report.
   */
  public function getReport() {
    return $this->report;
  }

}
