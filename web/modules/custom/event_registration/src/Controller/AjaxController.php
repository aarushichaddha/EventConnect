<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\event_registration\Service\EventService;
use Drupal\event_registration\Service\RegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AJAX callbacks.
 */
class AjaxController extends ControllerBase {

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
   * Constructs an AjaxController object.
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
   * Gets event dates by category.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with event dates.
   */
  public function getEventDates(Request $request) {
    $category = $request->query->get('category', '');

    $dates = [];
    if (!empty($category)) {
      $dates = $this->eventService->getEventDatesByCategory($category);
    }

    return new JsonResponse([
      'dates' => $dates,
    ]);
  }

  /**
   * Gets event names by category and date.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with event names.
   */
  public function getEventNames(Request $request) {
    $category = $request->query->get('category', '');
    $date = $request->query->get('date', '');

    $events = [];
    if (!empty($category) && !empty($date)) {
      $events = $this->eventService->getEventNamesByCategoryAndDate($category, $date);
    }

    return new JsonResponse([
      'events' => $events,
    ]);
  }

  /**
   * Gets event names by date for admin listing.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with event names.
   */
  public function getAdminEventNames(Request $request) {
    $date = $request->query->get('date', '');

    $events = [];
    if (!empty($date)) {
      $events = $this->eventService->getEventNamesByDate($date);
    }

    return new JsonResponse([
      'events' => $events,
    ]);
  }

  /**
   * Filters registrations based on date and event.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response with filtered registrations.
   */
  public function filterRegistrations(Request $request) {
    $date = $request->query->get('event_date', '');
    $event_id = $request->query->get('event_name', '');

    // Get registrations based on filters.
    if (!empty($event_id)) {
      $registrations = $this->registrationService->getRegistrationsByEventId($event_id);
    }
    elseif (!empty($date)) {
      $registrations = $this->registrationService->getRegistrationsByDate($date);
    }
    else {
      $registrations = $this->registrationService->getAllRegistrations();
    }

    // Format registrations for JSON response.
    $formatted_registrations = [];
    foreach ($registrations as $registration) {
      $formatted_registrations[] = [
        'full_name' => $registration->full_name,
        'email' => $registration->email,
        'event_date' => $registration->event_date,
        'college_name' => $registration->college_name,
        'department' => $registration->department,
        'submission_date' => date('Y-m-d H:i:s', $registration->created),
      ];
    }

    return new JsonResponse([
      'registrations' => $formatted_registrations,
      'count' => count($formatted_registrations),
    ]);
  }

}
