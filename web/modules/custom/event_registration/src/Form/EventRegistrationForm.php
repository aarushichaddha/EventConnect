<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\event_registration\Service\EventService;
use Drupal\event_registration\Service\RegistrationService;
use Drupal\event_registration\Service\EmailService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the event registration form for users.
 */
class EventRegistrationForm extends FormBase {

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
   * The email service.
   *
   * @var \Drupal\event_registration\Service\EmailService
   */
  protected $emailService;

  /**
   * Constructs an EventRegistrationForm object.
   *
   * @param \Drupal\event_registration\Service\EventService $event_service
   *   The event service.
   * @param \Drupal\event_registration\Service\RegistrationService $registration_service
   *   The registration service.
   * @param \Drupal\event_registration\Service\EmailService $email_service
   *   The email service.
   */
  public function __construct(
    EventService $event_service,
    RegistrationService $registration_service,
    EmailService $email_service
  ) {
    $this->eventService = $event_service;
    $this->registrationService = $registration_service;
    $this->emailService = $email_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.event_service'),
      $container->get('event_registration.registration_service'),
      $container->get('event_registration.email_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if any events are available for registration.
    $available_categories = $this->eventService->getAvailableCategories();

    if (empty($available_categories)) {
      $form['no_events'] = [
        '#markup' => '<div class="messages messages--warning">' .
          $this->t('No events are currently open for registration. Please check back later.') .
          '</div>',
      ];
      return $form;
    }

    $form['#prefix'] = '<div id="event-registration-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your full name (letters, spaces, and hyphens only).'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter a valid email address.'),
    ];

    $form['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your college name (letters, numbers, spaces, and hyphens only).'),
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your department name (letters, numbers, spaces, and hyphens only).'),
    ];

    // Category dropdown with available categories.
    $category_options = ['' => $this->t('- Select Category -')] + $available_categories;
    $form['event_category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the Event'),
      '#required' => TRUE,
      '#options' => $category_options,
      '#description' => $this->t('Select the category of the event you want to register for.'),
      '#ajax' => [
        'callback' => '::updateEventDatesCallback',
        'wrapper' => 'event-date-wrapper',
        'event' => 'change',
      ],
    ];

    // Event date dropdown - depends on category selection.
    $selected_category = $form_state->getValue('event_category');
    $event_dates = [];
    if (!empty($selected_category)) {
      $event_dates = $this->eventService->getEventDatesByCategory($selected_category);
    }

    $date_options = ['' => $this->t('- Select Event Date -')] + $event_dates;
    $form['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#options' => $date_options,
      '#description' => $this->t('Select the date of the event.'),
      '#prefix' => '<div id="event-date-wrapper">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => '::updateEventNamesCallback',
        'wrapper' => 'event-name-wrapper',
        'event' => 'change',
      ],
    ];

    // Event name dropdown - depends on category and date selection.
    $selected_date = $form_state->getValue('event_date');
    $event_names = [];
    if (!empty($selected_category) && !empty($selected_date)) {
      $event_names = $this->eventService->getEventNamesByCategoryAndDate($selected_category, $selected_date);
    }

    $name_options = ['' => $this->t('- Select Event Name -')] + $event_names;
    $form['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#options' => $name_options,
      '#description' => $this->t('Select the event you want to register for.'),
      '#prefix' => '<div id="event-name-wrapper">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * AJAX callback to update event dates based on selected category.
   */
  public function updateEventDatesCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#event-date-wrapper', $form['event_date']));
    $response->addCommand(new ReplaceCommand('#event-name-wrapper', $form['event_name']));
    return $response;
  }

  /**
   * AJAX callback to update event names based on selected category and date.
   */
  public function updateEventNamesCallback(array &$form, FormStateInterface $form_state) {
    return $form['event_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $full_name = $form_state->getValue('full_name');
    $email = $form_state->getValue('email');
    $college_name = $form_state->getValue('college_name');
    $department = $form_state->getValue('department');
    $event_name_id = $form_state->getValue('event_name');

    // Validate full name - no special characters.
    if (!empty($full_name) && !preg_match('/^[a-zA-Z\s\-]+$/', $full_name)) {
      $form_state->setErrorByName('full_name', $this->t('Full name should only contain letters, spaces, and hyphens.'));
    }

    // Validate email format.
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }

    // Validate college name - no special characters.
    if (!empty($college_name) && !preg_match('/^[a-zA-Z0-9\s\-]+$/', $college_name)) {
      $form_state->setErrorByName('college_name', $this->t('College name should only contain letters, numbers, spaces, and hyphens.'));
    }

    // Validate department - no special characters.
    if (!empty($department) && !preg_match('/^[a-zA-Z0-9\s\-]+$/', $department)) {
      $form_state->setErrorByName('department', $this->t('Department should only contain letters, numbers, spaces, and hyphens.'));
    }

    // Check for duplicate registration (email + event).
    if (!empty($email) && !empty($event_name_id)) {
      if ($this->registrationService->isDuplicateRegistration($email, $event_name_id)) {
        $form_state->setErrorByName('email', $this->t('You have already registered for this event with this email address.'));
      }
    }

    // Validate that the selected event is still open for registration.
    if (!empty($event_name_id)) {
      $event = $this->eventService->getEventById($event_name_id);
      if (!$event || !$this->eventService->isEventOpenForRegistration($event)) {
        $form_state->setErrorByName('event_name', $this->t('The selected event is no longer open for registration.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_config_id = $form_state->getValue('event_name');
    $event = $this->eventService->getEventById($event_config_id);

    if (!$event) {
      $this->messenger()->addError($this->t('An error occurred. The selected event could not be found.'));
      return;
    }

    $data = [
      'full_name' => $form_state->getValue('full_name'),
      'email' => $form_state->getValue('email'),
      'college_name' => $form_state->getValue('college_name'),
      'department' => $form_state->getValue('department'),
      'event_category' => $form_state->getValue('event_category'),
      'event_config_id' => $event_config_id,
    ];

    $result = $this->registrationService->createRegistration($data);

    if ($result) {
      // Send confirmation emails.
      $email_params = [
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'college_name' => $data['college_name'],
        'department' => $data['department'],
        'event_name' => $event->event_name,
        'event_date' => $event->event_date,
        'event_category' => $data['event_category'],
      ];

      // Send user confirmation email.
      $this->emailService->sendUserConfirmation($data['email'], $email_params);

      // Send admin notification if enabled.
      $this->emailService->sendAdminNotification($email_params);

      $this->messenger()->addStatus($this->t('Thank you for registering! A confirmation email has been sent to @email.', [
        '@email' => $data['email'],
      ]));
    }
    else {
      $this->messenger()->addError($this->t('An error occurred while processing your registration. Please try again.'));
    }
  }

}
