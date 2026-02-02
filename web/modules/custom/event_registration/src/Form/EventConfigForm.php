<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_registration\Service\EventService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an event configuration form.
 */
class EventConfigForm extends FormBase {

  /**
   * The event service.
   *
   * @var \Drupal\event_registration\Service\EventService
   */
  protected $eventService;

  /**
   * Constructs an EventConfigForm object.
   *
   * @param \Drupal\event_registration\Service\EventService $event_service
   *   The event service.
   */
  public function __construct(EventService $event_service) {
    $this->eventService = $event_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.event_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="event-config-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['registration_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration Start Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when event registration opens.'),
    ];

    $form['registration_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration End Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when event registration closes.'),
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when the event takes place.'),
    ];

    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('The name of the event.'),
    ];

    $form['event_category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the Event'),
      '#required' => TRUE,
      '#options' => [
        '' => $this->t('- Select Category -'),
        'Online Workshop' => $this->t('Online Workshop'),
        'Hackathon' => $this->t('Hackathon'),
        'Conference' => $this->t('Conference'),
        'One-day Workshop' => $this->t('One-day Workshop'),
      ],
      '#description' => $this->t('Select the category of the event.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Event Configuration'),
      '#button_type' => 'primary',
    ];

    // Display existing events in a table.
    $events = $this->eventService->getAllEvents();
    if (!empty($events)) {
      $form['existing_events'] = [
        '#type' => 'details',
        '#title' => $this->t('Existing Events'),
        '#open' => TRUE,
        '#weight' => 100,
      ];

      $header = [
        $this->t('ID'),
        $this->t('Event Name'),
        $this->t('Category'),
        $this->t('Event Date'),
        $this->t('Registration Start'),
        $this->t('Registration End'),
        $this->t('Actions'),
      ];

      $rows = [];
      foreach ($events as $event) {
        $rows[] = [
          $event->id,
          $event->event_name,
          $event->event_category,
          $event->event_date,
          $event->registration_start_date,
          $event->registration_end_date,
          $this->t('ID: @id', ['@id' => $event->id]),
        ];
      }

      $form['existing_events']['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No events configured yet.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $registration_start = $form_state->getValue('registration_start_date');
    $registration_end = $form_state->getValue('registration_end_date');
    $event_date = $form_state->getValue('event_date');
    $event_name = $form_state->getValue('event_name');

    // Validate registration dates.
    if (!empty($registration_start) && !empty($registration_end)) {
      $start_timestamp = strtotime($registration_start);
      $end_timestamp = strtotime($registration_end);

      if ($end_timestamp < $start_timestamp) {
        $form_state->setErrorByName('registration_end_date', $this->t('Registration end date must be after or equal to the start date.'));
      }
    }

    // Validate event date is after registration start.
    if (!empty($registration_start) && !empty($event_date)) {
      $start_timestamp = strtotime($registration_start);
      $event_timestamp = strtotime($event_date);

      if ($event_timestamp < $start_timestamp) {
        $form_state->setErrorByName('event_date', $this->t('Event date must be after or equal to the registration start date.'));
      }
    }

    // Validate event name - no special characters.
    if (!empty($event_name) && !preg_match('/^[a-zA-Z0-9\s\-]+$/', $event_name)) {
      $form_state->setErrorByName('event_name', $this->t('Event name should only contain letters, numbers, spaces, and hyphens.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [
      'registration_start_date' => $form_state->getValue('registration_start_date'),
      'registration_end_date' => $form_state->getValue('registration_end_date'),
      'event_date' => $form_state->getValue('event_date'),
      'event_name' => $form_state->getValue('event_name'),
      'event_category' => $form_state->getValue('event_category'),
    ];

    $result = $this->eventService->createEvent($data);

    if ($result) {
      $this->messenger()->addStatus($this->t('Event configuration has been saved successfully.'));
    }
    else {
      $this->messenger()->addError($this->t('An error occurred while saving the event configuration.'));
    }
  }

}
