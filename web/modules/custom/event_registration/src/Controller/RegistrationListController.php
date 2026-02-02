<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_registration\Service\EventService;
use Drupal\event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for displaying event registrations list.
 */
class RegistrationListController extends ControllerBase {

  /**
   * The event service.
   *
   * @var \Drupal\event_registration\Service\EventService
   */
  protected $eventService;

  /**
   * The registration service.
   *
   * @var \Drupal\event_registration\Service\RegistrationService
   */
  protected $registrationService;

  /**
   * Constructs a RegistrationListController object.
   *
   * @param \Drupal\event_registration\Service\EventService $event_service
   *   The event service.
   * @param \Drupal\event_registration\Service\RegistrationService $registration_service
   *   The registration service.
   */
  public function __construct(
    EventService $event_service,
    RegistrationService $registration_service
  ) {
    $this->eventService = $event_service;
    $this->registrationService = $registration_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.event_service'),
      $container->get('event_registration.registration_service')
    );
  }

  /**
   * Displays the admin listing page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   */
  public function content(Request $request) {
    $build = [];

    // Get all unique event dates.
    $event_dates = $this->eventService->getAllEventDates();
    $date_options = ['' => $this->t('- Select Event Date -')] + $event_dates;

    // Get selected values from query parameters.
    $selected_date = $request->query->get('event_date', '');
    $selected_event = $request->query->get('event_name', '');

    // Get event names for selected date.
    $event_names = [];
    if (!empty($selected_date)) {
      $event_names = $this->eventService->getEventNamesByDate($selected_date);
    }
    $event_options = ['' => $this->t('- Select Event Name -')] + $event_names;

    // Build filter form.
    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['event-registration-filters']],
    ];

    $build['filters']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => $date_options,
      '#default_value' => $selected_date,
      '#attributes' => [
        'id' => 'filter-event-date',
        'class' => ['filter-dropdown'],
      ],
      '#prefix' => '<div class="filter-item">',
      '#suffix' => '</div>',
    ];

    $build['filters']['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => $event_options,
      '#default_value' => $selected_event,
      '#attributes' => [
        'id' => 'filter-event-name',
        'class' => ['filter-dropdown'],
      ],
      '#prefix' => '<div class="filter-item" id="event-name-filter-wrapper">',
      '#suffix' => '</div>',
    ];

    // Export CSV link.
    $export_url = '/admin/event-registration/export-csv';
    if (!empty($selected_date) || !empty($selected_event)) {
      $export_url .= '?' . http_build_query([
        'event_date' => $selected_date,
        'event_name' => $selected_event,
      ]);
    }

    $build['filters']['export_csv'] = [
      '#type' => 'link',
      '#title' => $this->t('Export as CSV'),
      '#url' => \Drupal\Core\Url::fromUri('internal:' . $export_url),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'id' => 'export-csv-link',
      ],
      '#prefix' => '<div class="filter-item">',
      '#suffix' => '</div>',
    ];

    // Get registrations and count.
    $registrations = [];
    $participant_count = 0;

    if (!empty($selected_date) && !empty($selected_event)) {
      $registrations = $this->registrationService->getRegistrationsByEventId($selected_event);
      $participant_count = count($registrations);
    }
    elseif (!empty($selected_date)) {
      $registrations = $this->registrationService->getRegistrationsByDate($selected_date);
      $participant_count = count($registrations);
    }
    else {
      $registrations = $this->registrationService->getAllRegistrations();
      $participant_count = count($registrations);
    }

    // Display participant count.
    $build['participant_count'] = [
      '#markup' => '<div id="participant-count" class="participant-count"><strong>' .
        $this->t('Total Participants: @count', ['@count' => $participant_count]) .
        '</strong></div>',
    ];

    // Build registrations table.
    $header = [
      $this->t('Name'),
      $this->t('Email'),
      $this->t('Event Date'),
      $this->t('College Name'),
      $this->t('Department'),
      $this->t('Submission Date'),
    ];

    $rows = [];
    foreach ($registrations as $registration) {
      $rows[] = [
        $registration->full_name,
        $registration->email,
        $registration->event_date,
        $registration->college_name,
        $registration->department,
        date('Y-m-d H:i:s', $registration->created),
      ];
    }

    $build['registrations_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No registrations found.'),
      '#prefix' => '<div id="registrations-table-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => ['class' => ['registrations-table']],
    ];

    // Attach the library for AJAX functionality.
    $build['#attached']['library'][] = 'event_registration/admin_listing';
    $build['#attached']['drupalSettings']['eventRegistration'] = [
      'ajaxEventNamesUrl' => '/admin/event-registration/ajax/get-event-names',
      'ajaxFilterUrl' => '/admin/event-registration/ajax/filter-registrations',
    ];

    return $build;
  }

  /**
   * Exports registrations as CSV.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The CSV file response.
   */
  public function exportCsv(Request $request) {
    $selected_date = $request->query->get('event_date', '');
    $selected_event = $request->query->get('event_name', '');

    // Get registrations based on filters.
    if (!empty($selected_event)) {
      $registrations = $this->registrationService->getRegistrationsByEventId($selected_event);
    }
    elseif (!empty($selected_date)) {
      $registrations = $this->registrationService->getRegistrationsByDate($selected_date);
    }
    else {
      $registrations = $this->registrationService->getAllRegistrations();
    }

    // Build CSV content.
    $csv_output = fopen('php://temp', 'r+');

    // Add CSV header.
    fputcsv($csv_output, [
      'Full Name',
      'Email',
      'College Name',
      'Department',
      'Event Category',
      'Event Name',
      'Event Date',
      'Submission Date',
    ]);

    // Add data rows.
    foreach ($registrations as $registration) {
      fputcsv($csv_output, [
        $registration->full_name,
        $registration->email,
        $registration->college_name,
        $registration->department,
        $registration->event_category,
        $registration->event_name,
        $registration->event_date,
        date('Y-m-d H:i:s', $registration->created),
      ]);
    }

    // Get CSV content.
    rewind($csv_output);
    $csv_content = stream_get_contents($csv_output);
    fclose($csv_output);

    // Create response.
    $response = new Response($csv_content);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="event_registrations_' . date('Y-m-d_H-i-s') . '.csv"');

    return $response;
  }

}
