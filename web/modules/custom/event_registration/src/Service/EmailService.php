<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Service for handling email notifications.
 */
class EmailService {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an EmailService object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->logger = $logger_factory->get('event_registration');
  }

  /**
   * Sends a confirmation email to the user.
   *
   * @param string $to
   *   The recipient email address.
   * @param array $params
   *   The email parameters.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendUserConfirmation($to, array $params) {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    try {
      $result = $this->mailManager->mail(
        'event_registration',
        'registration_confirmation',
        $to,
        $langcode,
        $params,
        NULL,
        TRUE
      );

      if ($result['result'] !== TRUE) {
        $this->logger->error('Failed to send confirmation email to @email', [
          '@email' => $to,
        ]);
        return FALSE;
      }

      $this->logger->info('Confirmation email sent to @email', [
        '@email' => $to,
      ]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error sending confirmation email: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Sends a notification email to the administrator.
   *
   * @param array $params
   *   The email parameters.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   */
  public function sendAdminNotification(array $params) {
    $config = $this->configFactory->get('event_registration.settings');

    // Check if admin notifications are enabled.
    if (!$config->get('enable_admin_notifications')) {
      return FALSE;
    }

    $admin_email = $config->get('admin_email');
    if (empty($admin_email)) {
      $this->logger->warning('Admin notification email is not configured.');
      return FALSE;
    }

    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    try {
      $result = $this->mailManager->mail(
        'event_registration',
        'admin_notification',
        $admin_email,
        $langcode,
        $params,
        NULL,
        TRUE
      );

      if ($result['result'] !== TRUE) {
        $this->logger->error('Failed to send admin notification email to @email', [
          '@email' => $admin_email,
        ]);
        return FALSE;
      }

      $this->logger->info('Admin notification email sent to @email', [
        '@email' => $admin_email,
      ]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error sending admin notification email: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
