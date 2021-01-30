<?php

namespace Drupal\commerce_pos_reports\Form;

use Drupal\commerce_pos\Entity\Register;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\commerce_pos_reports\Ajax\PrintEodReport;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Url;
use Drupal\Core\Ajax\InsertCommand;

/**
 * Class EndOfDayForm.
 */
class EndOfDayForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_end_of_day';
  }

  /**
   * Build the end of day report form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Setup form.
    $form['#attached']['library'][] = 'commerce_pos_reports/reports';
    $form['#attached']['library'][] = 'commerce_pos_print/jQuery.print';

    if (!file_exists('libraries/jQuery.print/jQuery.print.js')) {
      $messenger = \Drupal::messenger();
      $messenger->addWarning(t('You don\'t have the jQuery.print library, so printing won\'t work. <a href="@help_link">Learn More</a>', [
        '@help_link' => '/admin/help/commerce_pos_print',
      ]));
    }

    $handler = \Drupal::service('module_handler');
    $path = $handler->getModule('commerce_pos_reports')->getPath();
    $js_settings = [
      'commercePosReports' => [
        'cssUrl' => Url::fromUserInput('/' . $path . '/css/commerce_pos_reports_receipt.css',
          [
            'absolute' => TRUE,
          ])->toString(),
      ],
    ];
    $form['#attached']['drupalSettings'] = $js_settings;

    $form['#prefix'] = '<div id="commerce-pos-report-eod-form-container">';
    $form['#suffix'] = '</div>';

    if (empty($form_state->getValue('results_container_id'))) {
      $form_state->setValue('results_container_id',
        'commerce-pos-report-results-container');
    }

    // Get all the registers.
    $registers = \Drupal::service('commerce_pos.registers')->getRegisters();
    if (empty($registers)) {
      // Return no registers error, link to setup registers.
      $this->messenger()->addError($this->t('POS Orders can not be created until a register has been created. <a href=":url">Add a new register.</a>',
        [
          ':url' => URL::fromRoute('entity.commerce_pos_register.add_form')
            ->toString(),
        ]));

      return $form;
    }

    // Register ID filter.
    /** @var \Drupal\commerce_pos\Entity\Register $current_register */
    $current_register = \Drupal::service('commerce_pos.current_register')
      ->get();

    $register_id = NULL;
    if ($form_state->hasValue('register_id')) {
      $register_id = $form_state->getValue('register_id');
    }
    elseif ($current_register) {
      $register_id = $current_register->id();
    }

    // Our filters.
    $form['filters'] = $this->formFilters($form_state, $register_id, $registers);

    $form['results'] = [
      '#type' => 'container',
      '#id' => $form_state->getValue('results_container_id'),
    ];

    if (empty($register_id)) {
      return $form;
    }

    $register = Register::load($register_id);

    // Get saved data for requested date.
    $date_filter = !empty($form_state->getValue('date')) ? $form_state->getValue('date') : date('Y-m-d', time());
    $report_history = commerce_pos_reports_get_eod_report($date_filter, $register_id);

    if (!$report_history && !$register->isOpen()) {
      $form['results']['error'] = [
        '#markup' => $this->t('There is no already closed report for this day and this register is not currently open.<br />A register must be open for it to be closed and an EOD report generated.'),
      ];

      return $form;
    }

    $can_update = $this->currentUser()
      ->hasPermission('update commerce pos closed reports');

    $form['results']['actions'] = [
      '#type' => 'actions',
    ];

    // Capture the calculate button click and modify the report history
    // values to have the formstate values.
    if (!empty($form_state) && $form_state->getValue('date')) {
      $form_values = $form_state->getValues();
      $report_history['date'] = strtotime($form_state->getValue('date'));
      $report_history['closed'] = isset($report_history['closed']) ? $report_history['closed'] : FALSE;
      $register_id = $report_history['register_id'] = $form_state->getValue('register_id');

      foreach ($form_values as $value) {
        if (is_array($value)) {
          foreach ($value['rows'] as $key => $item) {
            $report_history['data'][$key]['declared'] = $item['declared'][$register_id][$form_state->getValue('date')];
            if ($key === 'pos_cash') {
              $report_history['data'][$key]['cash_deposit'] = $item['cash_deposit'][$register_id][$form_state->getValue('date')];
            }
          }
        }
      }
    }

    $form['results'] = $this->results($date_filter, $register, $report_history);
    $form['results']['#prefix'] = '<div id="commerce-pos-report-results-container">';
    $form['results']['#suffix'] = '</div>';

    $form['results']['actions'] = [
      '#type' => 'actions',
      'calculate' => [
        '#type' => 'button',
        '#ajax' => [
          'callback' => '::ajaxRefresh',
        ],
        '#value' => $this->t('Calculate'),
      ],
    ];

    if ($register->isOpen() && !$report_history['closed']) {
      $form['results']['actions']['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Close Register & Save'),
        '#validate' => ['::saveValidate'],
        '#submit' => ['::saveSubmit'],
      ];
    }
    elseif ($can_update) {
      $form['results']['actions']['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update Report'),
        '#validate' => ['::saveValidate'],
        '#submit' => ['::saveSubmit'],
      ];
    }

    if ($report_history['closed']) {
      $form['results']['actions']['print'] = [
        '#type' => 'submit',
        '#value' => $this->t('Print'),
        '#ajax' => [
          'callback' => '::printJs',
          'wrapper' => 'commerce-pos-report-eod-form-container',
        ],
      ];
    }

    return $form;
  }

  /**
   * Generate the filters for the EOD report.
   */
  private function formFilters($form_state, $register_id, $registers) {
    $form = [];

    $ajax = [
      'callback' => '::ajaxRefresh',
      'wrapper' => $form_state->getValue('results_container_id'),
      'effect' => 'fade',
    ];

    $register_options = ['' => '-'];
    foreach ($registers as $id => $register) {
      $register_options[$id] = $register->getName();
    }

    // Our filters.
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['clearfix']],
    ];

    $form['filters']['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Transaction Date'),
      '#description' => $this->t('The day you wish to view or close a report from.'),
      '#default_value' => date('Y-m-d', time()),
      '#ajax' => $ajax,
    ];

    $form['filters']['register_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Register'),
      '#description' => $this->t('The register you wish to view or close a report on. Defaults to your current register if available.'),
      '#options' => $register_options,
      '#default_value' => $register_id,
      '#ajax' => $ajax,
    ];

    return $form;
  }

  /**
   * Generate the results based on the provided filters and register.
   *
   * @param string $date_filter
   *   The date in string format to filter against.
   * @param \Drupal\commerce_pos\Entity\Register $register
   *   The active register to run the report for.
   * @param array|null $report_history
   *   An array of report data if this is an existing report.
   *
   * @return array
   *   A form array of the results.
   */
  private function results($date_filter, Register $register, $report_history) {
    $form = [];

    $payment_gateway_options = commerce_pos_reports_get_payment_gateway_options($date_filter);

    // Get the totals summary for the selected date and register.
    $totals = commerce_pos_reports_get_totals($date_filter, $register->id(), $payment_gateway_options)[0];

    if (empty($totals)) {
      return $form;
    }

    $can_update = $this->currentUser()
      ->hasPermission('update commerce pos closed reports') || $register->isOpen();

    $currency_formatter = \Drupal::service('commerce_price.currency_formatter');

    $headers = [
      $this->t('Payment Type'),
      $this->t('Declared Amount'),
      $this->t('POS expected Amount'),
      $this->t('Over/Short'),
      $this->t('Cash Deposit'),
    ];

    // Display a textfield to enter the amounts for each currency type and
    // payment method.
    foreach ($totals as $currency_code => $currency_totals) {
      $form['results'][$currency_code] = [
        '#theme' => 'commerce_pos_reports_end_of_day_result_table',
        '#header' => $headers,
        'rows' => [
          '#tree' => TRUE,
        ],
        '#tree' => TRUE,
      ];

      foreach ($currency_totals as $payment_method_id => $amounts) {
        /** @var \Drupal\commerce_price\Entity\Currency $currency */
        $currency = Currency::load($currency_code);

        $expected_amount = $amounts;
        $input_prefix = $currency->getSymbol();

        $row = [
          'title' => [
            '#markup' => $payment_gateway_options[$payment_method_id],
          ],
        ];

        $declared = [];

        if ($payment_method_id == 'pos_cash') {
          $declared['#commerce_pos_keypad'] = [
            'type' => 'cash input',
            'currency_code' => $currency_code,
          ];

          $declared['#attributes']['data-default-float'] = $register->getDefaultFloat()
            ->getNumber();

          $expected_amount += $register->getOpeningFloat()->getNumber();

          $row['cash_deposit'][$register->id()][$date_filter] = [
            '#type' => 'textfield',
            '#size' => 10,
            '#maxlength' => 10,
            '#attributes' => [
              'class' => ['commerce-pos-report-deposit'],
            ],
            '#title' => $this->t('Cash Deposit'),
            '#title_display' => 'invisible',
            '#field_prefix' => $input_prefix,
            '#disabled' => !$can_update,
            '#default_value' => 0,
          ];

          if (isset($report_history['data'][$payment_method_id]['cash_deposit'])) {
            $row['cash_deposit'][$register->id()][$date_filter]['#default_value'] = $report_history['data'][$payment_method_id]['cash_deposit'];
          }
        }
        else {
          $row['cash_deposit'] = [
            '#markup' => '&nbsp;',
          ];
        }

        // Declared amount.
        $declared += [
          '#type' => 'textfield',
          '#size' => 10,
          '#maxlength' => 10,
          '#attributes' => [
            'class' => ['commerce-pos-report-declared-input'],
            'data-currency-code' => $currency_code,
            'data-amount' => 0,
            'data-payment-method-id' => $payment_method_id,
            'data-expected-amount' => $expected_amount,
          ],
          '#element_validate' => ['::validateAmount'],
          '#required' => TRUE,
          '#disabled' => !$can_update,
          '#field_prefix' => $input_prefix,
          '#default_value' => 0,
        ];

        // Adding this element with the register_id and date as the keys
        // because this is a known issue w/ Drupal where default values
        // don't get changed during ajax callbacks. Adding a unique key to
        // the form element fixes the issue.
        $row['declared'][$register->id()][$date_filter] = $declared;

        $over_short_amount = 0;
        if (isset($report_history['data'][$payment_method_id]['declared'])) {
          $over_short_amount = $report_history['data'][$payment_method_id]['declared'];
          $row['declared'][$register->id()][$date_filter]['#default_value'] = $report_history['data'][$payment_method_id]['declared'];
        }

        // Expected amount.
        $markup = '<div class="commerce-pos-report-expected-amount" data-payment-method-id="' . $payment_method_id . '">';
        $markup .= $currency_formatter->format((string) $expected_amount, $currency_code);
        $markup .= '</div>';
        $row['expected'] = [
          '#markup' => $markup,
        ];

        // Over/short.
        $over_short_amount -= $expected_amount;

        $markup = '<div class="commerce-pos-report-balance" data-payment-method-id="' . $payment_method_id . '">';
        if ($over_short_amount >= 0) {
          $markup .= $currency_formatter->format((string) $over_short_amount, $currency_code);
        }
        else {
          $markup .= '<span class="commerce-pos-report-balance commerce-pos-report-negative">(';
          $markup .= $currency_formatter->format((string) abs($over_short_amount), $currency_code);
          $markup .= ')</span>';
        }
        $markup .= '</div>';

        $row['over_short'] = [
          '#markup' => $markup,
        ];

        $order = [
          'title',
          'declared',
          'expected',
          'over_short',
          'cash_deposit',
        ];

        uksort($row, function ($a, $b) use ($order) {
          $pos_a = array_search($a, $order);
          $pos_b = array_search($b, $order);

          return $pos_a - $pos_b;
        });

        $form['results'][$currency_code]['rows'][$payment_method_id] = $row;
      }
    }

    $js_settings['commercePosReportCurrencies'] = commerce_pos_reports_currency_js(array_keys($totals));
    $form['results']['#attached']['drupalSettings'] = $js_settings;

    return $form;
  }

  /**
   * Submit callback for the end of day report form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Used to validate that the declared amount is set if not disabled.
   */
  public function validateAmount(array $element, FormStateInterface $form_state, array &$form) {
    if (!is_numeric($element['#value']) && empty($element['#disabled'])) {
      $form_state->setError($element, $this->t('Amount must be a number.'));
    }
  }

  /**
   * Validation handler for the End of Day report "save" button.
   */
  public function saveValidate(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Submit handler for the End of Day report "save" button.
   */
  public function saveSubmit(array &$form, FormStateInterface $form_state) {
    $date = $form_state->getValue('date');
    // POS register.
    $register_id = $form_state->getValue('register_id');
    $register = Register::load($register_id);
    /** @var \Drupal\commerce_store\Entity\Store $store */
    $store = Store::load($register->getStoreId());

    // Serialize form data.
    $default_currency = $store->getDefaultCurrencyCode();
    $data = $form_state->getValue($default_currency)['rows'];

    // Remove the register_id and date as keys from the declared values before
    // inserting because we don't need it. It was just added due to the default
    // values not changing via ajax callback issue.
    foreach ($data as $payment_id => $values) {
      $data[$payment_id]['declared'] = $values['declared'][$register_id][$date];
      unset($values['declared'][$register_id][$date]);

      if (isset($data[$payment_id]['cash_deposit'])) {
        $data[$payment_id]['cash_deposit'] = $values['cash_deposit'][$register_id][$date];
        unset($values['cash_deposit'][$register_id][$date]);
      }

    }
    $serial_data = serialize($data);

    // Before we insert the values into the db, determine if a report for this
    // date already exists so we know to update or insert.
    $exists = $this->reportExists($date, $register_id);
    if ($exists) {
      $query = \Drupal::database();
      $query = $query->update('commerce_pos_report_declared_data')
        ->condition('register_id', $register_id, '=')
        ->condition('date', strtotime($date), '=')
        ->fields([
          'data' => $serial_data,
        ]);
      $query->execute();
    }
    else {
      $query = \Drupal::database();
      $query = $query->insert('commerce_pos_report_declared_data')
        ->fields([
          'register_id' => $register_id,
          'date' => strtotime($date),
          'data' => $serial_data,
        ]);
      $query->execute();

      // If we're making a new entry, that means we're closing our active
      // register.
      if ($register->isOpen()) {
        $register->close();
        $register->save();
        $this->messenger()->addMessage($this->t('Register @register has been closed.', [
          '@register' => $register->label(),
        ]));
      }
    }

    $this->messenger()->addMessage($this->t('Successfully saved the declared values for register @register.', [
      '@register' => $register->label(),
    ]));
  }

  /**
   * AJAX callback for the report "print" button.
   */
  public function printJs(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Output any status messages first.
    $status_messages = ['#type' => 'status_messages'];
    $output = \Drupal::service('renderer')->renderRoot(($status_messages));
    if (!empty($output)) {
      $response->addCommand(new PrependCommand('.page-content', $output));
    }

    // Now, if we have no errors, let's print the receipt.
    if (!$form_state->getErrors()) {
      $date = $form_state->getValue('date');
      $register_id = $form_state->getValue('register_id');

      $response->addCommand(new PrintEodReport($date, $register_id));
    }

    return $response;
  }

  /**
   * Checks if a report already exists, used to determine update or insert.
   *
   * @param string $date
   *   A strtotime compatible date, will search this date exactly.
   * @param int $register_id
   *   Id of the register to load the report for.
   *
   * @return bool
   *   True if the report exists, false if it doesn't.
   */
  public function reportExists($date, $register_id) {
    $query = \Drupal::database();
    $query = $query->select('commerce_pos_report_declared_data', 't')
      ->fields('t')
      ->condition('register_id', $register_id, '=')
      ->condition('date', strtotime($date), '=');
    $result = $query->execute()->fetchAssoc();

    return !empty($result);
  }

  /**
   * AJAX callback for the report filter elements.
   */
  public function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $response = new AjaxResponse();
    $response->addCommand(new InsertCommand('#commerce-pos-report-results-container', $form['results']));
    return $response;
  }

}
