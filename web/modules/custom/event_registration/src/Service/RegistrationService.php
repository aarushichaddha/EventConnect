<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Service for managing event registrations.
 */
class RegistrationService {

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
   * Constructs a RegistrationService object.
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
   * Creates a new registration.
   *
   * @param array $data
   *   The registration data.
   *
   * @return int|bool
   *   The ID of the created registration, or FALSE on failure.
   */
  public function createRegistration(array $data) {
    try {
      $result = $this->database->insert('event_registration_submissions')
        ->fields([
          'full_name' => $data['full_name'],
          'email' => $data['email'],
          'college_name' => $data['college_name'],
          'department' => $data['department'],
          'event_category' => $data['event_category'],
          'event_config_id' => $data['event_config_id'],
          'created' => $this->time->getRequestTime(),
        ])
        ->execute();

      return $result;
    }
    catch (\Exception $e) {
      \Drupal::logger('event_registration')->error('Error creating registration: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if a duplicate registration exists.
   *
   * @param string $email
   *   The email address.
   * @param int $event_config_id
   *   The event configuration ID.
   *
   * @return bool
   *   TRUE if a duplicate exists, FALSE otherwise.
   */
  public function isDuplicateRegistration($email, $event_config_id) {
    $count = $this->database->select('event_registration_submissions', 'r')
      ->condition('email', $email)
      ->condition('event_config_id', $event_config_id)
      ->countQuery()
      ->execute()
      ->fetchField();

    return $count > 0;
  }

  /**
   * Gets all registrations.
   *
   * @return array
   *   An array of registration objects with event details.
   */
  public function getAllRegistrations() {
    $query = $this->database->select('event_registration_submissions', 'r');
    $query->join('event_registration_config', 'e', 'r.event_config_id = e.id');
    $query->fields('r', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'event_category',
      'event_config_id',
      'created',
    ]);
    $query->fields('e', ['event_name', 'event_date']);
    $query->orderBy('r.created', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * Gets registrations by event ID.
   *
   * @param int $event_config_id
   *   The event configuration ID.
   *
   * @return array
   *   An array of registration objects with event details.
   */
  public function getRegistrationsByEventId($event_config_id) {
    $query = $this->database->select('event_registration_submissions', 'r');
    $query->join('event_registration_config', 'e', 'r.event_config_id = e.id');
    $query->fields('r', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'event_category',
      'event_config_id',
      'created',
    ]);
    $query->fields('e', ['event_name', 'event_date']);
    $query->condition('r.event_config_id', $event_config_id);
    $query->orderBy('r.created', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * Gets registrations by event date.
   *
   * @param string $date
   *   The event date.
   *
   * @return array
   *   An array of registration objects with event details.
   */
  public function getRegistrationsByDate($date) {
    $query = $this->database->select('event_registration_submissions', 'r');
    $query->join('event_registration_config', 'e', 'r.event_config_id = e.id');
    $query->fields('r', [
      'id',
      'full_name',
      'email',
      'college_name',
      'department',
      'event_category',
      'event_config_id',
      'created',
    ]);
    $query->fields('e', ['event_name', 'event_date']);
    $query->condition('e.event_date', $date);
    $query->orderBy('r.created', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * Gets the count of registrations by event ID.
   *
   * @param int $event_config_id
   *   The event configuration ID.
   *
   * @return int
   *   The count of registrations.
   */
  public function getRegistrationCountByEventId($event_config_id) {
    return $this->database->select('event_registration_submissions', 'r')
      ->condition('event_config_id', $event_config_id)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
