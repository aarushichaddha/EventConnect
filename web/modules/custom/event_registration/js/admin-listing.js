/**
 * @file
 * JavaScript for the admin listing page.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.eventRegistrationAdminListing = {
    attach: function (context, settings) {
      var $dateDropdown = $('#filter-event-date', context);
      var $eventDropdown = $('#filter-event-name', context);
      var $tableWrapper = $('#registrations-table-wrapper', context);
      var $participantCount = $('#participant-count', context);
      var $exportLink = $('#export-csv-link', context);

      // Event date dropdown change handler.
      $dateDropdown.once('event-date-filter').on('change', function () {
        var selectedDate = $(this).val();

        // Reset event dropdown.
        $eventDropdown.html('<option value="">- Select Event Name -</option>');

        if (selectedDate) {
          // Fetch event names for the selected date.
          $.ajax({
            url: drupalSettings.eventRegistration.ajaxEventNamesUrl,
            type: 'GET',
            data: { date: selectedDate },
            dataType: 'json',
            success: function (response) {
              if (response.events) {
                $.each(response.events, function (id, name) {
                  $eventDropdown.append(
                    $('<option></option>').val(id).text(name)
                  );
                });
              }
            },
            error: function () {
              console.error('Error fetching event names.');
            }
          });
        }

        // Filter registrations.
        filterRegistrations(selectedDate, '');

        // Update export link.
        updateExportLink(selectedDate, '');
      });

      // Event name dropdown change handler.
      $eventDropdown.once('event-name-filter').on('change', function () {
        var selectedDate = $dateDropdown.val();
        var selectedEvent = $(this).val();

        // Filter registrations.
        filterRegistrations(selectedDate, selectedEvent);

        // Update export link.
        updateExportLink(selectedDate, selectedEvent);
      });

      /**
       * Filters registrations based on selected date and event.
       */
      function filterRegistrations(date, eventId) {
        $.ajax({
          url: drupalSettings.eventRegistration.ajaxFilterUrl,
          type: 'GET',
          data: {
            event_date: date,
            event_name: eventId
          },
          dataType: 'json',
          success: function (response) {
            // Update participant count.
            $participantCount.html(
              '<strong>' + Drupal.t('Total Participants: @count', { '@count': response.count }) + '</strong>'
            );

            // Update table.
            var tableHtml = buildTableHtml(response.registrations);
            $tableWrapper.html(tableHtml);
          },
          error: function () {
            console.error('Error filtering registrations.');
          }
        });
      }

      /**
       * Builds the HTML for the registrations table.
       */
      function buildTableHtml(registrations) {
        var html = '<table class="registrations-table">';
        html += '<thead><tr>';
        html += '<th>' + Drupal.t('Name') + '</th>';
        html += '<th>' + Drupal.t('Email') + '</th>';
        html += '<th>' + Drupal.t('Event Date') + '</th>';
        html += '<th>' + Drupal.t('College Name') + '</th>';
        html += '<th>' + Drupal.t('Department') + '</th>';
        html += '<th>' + Drupal.t('Submission Date') + '</th>';
        html += '</tr></thead>';
        html += '<tbody>';

        if (registrations.length === 0) {
          html += '<tr><td colspan="6">' + Drupal.t('No registrations found.') + '</td></tr>';
        } else {
          for (var i = 0; i < registrations.length; i++) {
            var reg = registrations[i];
            html += '<tr>';
            html += '<td>' + Drupal.checkPlain(reg.full_name) + '</td>';
            html += '<td>' + Drupal.checkPlain(reg.email) + '</td>';
            html += '<td>' + Drupal.checkPlain(reg.event_date) + '</td>';
            html += '<td>' + Drupal.checkPlain(reg.college_name) + '</td>';
            html += '<td>' + Drupal.checkPlain(reg.department) + '</td>';
            html += '<td>' + Drupal.checkPlain(reg.submission_date) + '</td>';
            html += '</tr>';
          }
        }

        html += '</tbody></table>';
        return html;
      }

      /**
       * Updates the export CSV link with current filters.
       */
      function updateExportLink(date, eventId) {
        var baseUrl = '/admin/event-registration/export-csv';
        var params = [];

        if (date) {
          params.push('event_date=' + encodeURIComponent(date));
        }
        if (eventId) {
          params.push('event_name=' + encodeURIComponent(eventId));
        }

        var newUrl = baseUrl;
        if (params.length > 0) {
          newUrl += '?' + params.join('&');
        }

        $exportLink.attr('href', newUrl);
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
