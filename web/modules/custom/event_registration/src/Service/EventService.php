<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Service for managing events.
 */
class EventService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs an EventService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Creates a new event configuration.
   *
   * @param array $data
   *   The event data.
   *
   * @return int|bool
   *   The ID of the created event, or FALSE on failure.
   */
  public function createEvent(array $data) {
    try {
      $result = $this->database->insert('event_registration_config')
        ->fields([
          'registration_start_date' => $data['registration_start_date'],
          'registration_end_date' => $data['registration_end_date'],
          'event_date' => $data['event_date'],
          'event_name' => $data['event_name'],
          'event_category' => $data['event_category'],
          'created' => $this->time->getRequestTime(),
        ])
        ->execute();

      return $result;
    }
    catch (\Exception $e) {
      \Drupal::logger('event_registration')->error('Error creating event: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets all events.
   *
   * @return array
   *   An array of event objects.
   */
  public function getAllEvents() {
    return $this->database->select('event_registration_config', 'e')
      ->fields('e')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets an event by ID.
   *
   * @param int $id
   *   The event ID.
   *
   * @return object|null
   *   The event object, or NULL if not found.
   */
  public function getEventById($id) {
    return $this->database->select('event_registration_config', 'e')
      ->fields('e')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * Gets available categories for events open for registration.
   *
   * @return array
   *   An array of categories keyed by category name.
   */
  public function getAvailableCategories() {
    $today = date('Y-m-d');

    $query = $this->database->select('event_registration_config', 'e')
      ->fields('e', ['event_category'])
      ->condition('registration_start_date', $today, '<=')
      ->condition('registration_end_date', $today, '>=')
      ->distinct();

    $results = $query->execute()->fetchAll();

    $categories = [];
    foreach ($results as $result) {
      $categories[$result->event_category] = $result->event_category;
    }

    return $categories;
  }

  /**
   * Gets event dates by category for events open for registration.
   *
   * @param string $category
   *   The event category.
   *
   * @return array
   *   An array of event dates keyed by date.
   */
  public function getEventDatesByCategory($category) {
    $today = date('Y-m-d');

    $results = $this->database->select('event_registration_config', 'e')
      ->fields('e', ['event_date'])
      ->condition('event_category', $category)
      ->condition('registration_start_date', $today, '<=')
      ->condition('registration_end_date', $today, '>=')
      ->distinct()
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll();

    $dates = [];
    foreach ($results as $result) {
      $dates[$result->event_date] = $result->event_date;
    }

    return $dates;
  }

  /**
   * Gets event names by category and date for events open for registration.
   *
   * @param string $category
   *   The event category.
   * @param string $date
   *   The event date.
   *
   * @return array
   *   An array of event names keyed by event ID.
   */
  public function getEventNamesByCategoryAndDate($category, $date) {
    $today = date('Y-m-d');

    $results = $this->database->select('event_registration_config', 'e')
      ->fields('e', ['id', 'event_name'])
      ->condition('event_category', $category)
      ->condition('event_date', $date)
      ->condition('registration_start_date', $today, '<=')
      ->condition('registration_end_date', $today, '>=')
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAll();

    $events = [];
    foreach ($results as $result) {
      $events[$result->id] = $result->event_name;
    }

    return $events;
  }

  /**
   * Gets all unique event dates.
   *
   * @return array
   *   An array of event dates keyed by date.
   */
  public function getAllEventDates() {
    $results = $this->database->select('event_registration_config', 'e')
      ->fields('e', ['event_date'])
      ->distinct()
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll();

    $dates = [];
    foreach ($results as $result) {
      $dates[$result->event_date] = $result->event_date;
    }

    return $dates;
  }

  /**
   * Gets event names by date.
   *
   * @param string $date
   *   The event date.
   *
   * @return array
   *   An array of event names keyed by event ID.
   */
  public function getEventNamesByDate($date) {
    $results = $this->database->select('event_registration_config', 'e')
      ->fields('e', ['id', 'event_name'])
      ->condition('event_date', $date)
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAll();

    $events = [];
    foreach ($results as $result) {
      $events[$result->id] = $result->event_name;
    }

    return $events;
  }

  /**
   * Checks if an event is open for registration.
   *
   * @param object $event
   *   The event object.
   *
   * @return bool
   *   TRUE if the event is open for registration, FALSE otherwise.
   */
  public function isEventOpenForRegistration($event) {
    $today = date('Y-m-d');
    return ($event->registration_start_date <= $today && $event->registration_end_date >= $today);
  }

}
