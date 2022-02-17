<?php

namespace Drupal\rules\Context\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rules\Context\ContextConfig;
use Drupal\rules\Context\ContextDefinitionInterface;
use Drupal\rules\Context\DataProcessorManagerTrait;
use Drupal\typed_data\Form\SubformState;
use Drupal\typed_data\Widget\FormWidgetManagerTrait;

/**
 * Provides form logic for handling contexts when configuring an expression.
 */
trait ContextFormTrait {
  use DataProcessorManagerTrait;
  use FormWidgetManagerTrait;

  /**
   * Provides the form part for a context parameter.
   */
  public function buildContextForm(array $form, FormStateInterface $form_state, $context_name, ContextDefinitionInterface $context_definition, array $configuration) {
    // If the form has been submitted already take the mode from the submitted
    // value, otherwise check for restriction setting, then check existing
    // configuration, and if that does not exist default to the "input" mode.
    $mode = $form_state->get('context_' . $context_name);
    if (!$mode) {
      if ($mode = $context_definition->getAssignmentRestriction()) {
        // If there is an assignmentRestriction value, use this for mode.
      }
      elseif (isset($configuration['context_mapping'][$context_name])) {
        $mode = ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR;
      }
      else {
        $mode = ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT;
      }
      $form_state->set('context_' . $context_name, $mode);
    }

    $title = $mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR ? $this->t('Data selector') : $this->t('Value');

    // The default value will be an array if the widget has two or more data
    // input items, for example datespan, or the Rules context definition has
    // 'multiple = TRUE'. Otherwise it will be a scalar value.
    if (isset($configuration['context_values'][$context_name])) {
      $default_value = $configuration['context_values'][$context_name];
    }
    elseif (isset($configuration['context_mapping'][$context_name])) {
      $default_value = $configuration['context_mapping'][$context_name];
    }
    else {
      $default_value = $context_definition->getDefaultValue();
    }

    // Temporary fix: Cast default value to an array if context definition has
    // 'multiple = TRUE' so that the action can be edited without a fatal error.
    // @todo Remove when integrity check prevents the wrong type being saved.
    // @see https://www.drupal.org/project/rules/issues/2723259
    if ($context_definition->isMultiple() && is_scalar($default_value)) {
      $default_value = [$default_value];
    }

    // Derive the widget id using the context definition data type. Take only
    // the first word before : so that entity:user gives entity.
    $dataType = explode(':', $context_definition->getDataType())[0];
    $widget_id = $context_definition->getWidgetId($dataType);

    if ($widget_id === ContextDefinitionInterface::BROKEN_WIDGET_ID) {
      // The datatype is unknown and/or the typed-data widget has not been coded
      // yet, so use the 'broken' widget which by design has no input field.
      \Drupal::messenger()->addError($this->t('No form widget is defined for %label (context name %context_name, data type %dataType). Modules can implement hook_typed_data_widgetlist_alter() to declare which form widget to use. See getWidgetId() and getStandardWidgetList().', [
        '%context_name' => $context_name,
        '%dataType' => $dataType,
        '%label' => is_object($context_definition->getLabel()) ? $context_definition->getLabel()->getUntranslatedString() : '* no label *',
      ]));
    }

    // Create the widget using the widget_id.
    $widget = $this->getFormWidgetManager()->createInstance($widget_id);

    $dataManager = $context_definition->getTypedDataManager();
    $dataDefinition = $context_definition->getDataDefinition();
    $typed_data = $dataManager->create($dataDefinition);

    // Set default before building the form, so that widget->form() can use it.
    $typed_data->setValue($default_value);

    // Create the widget sub-form.
    $sub_form = [];
    $sub_form_state = SubformState::createForSubform($sub_form, $form, $form_state);
    $widget_form = $widget->form($typed_data, $sub_form_state);

    // Add the widget form into the full $form and save the widget_id.
    $form['context_definitions'][$context_name] = $widget_form + [
      '#widget_id' => $widget_id,
    ];
    // If mode is input (not selector) then add a widget class.
    if ($mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT) {
      $form['context_definitions'][$context_name]['#attributes']['class'][] = 'widget-' . str_replace('_', '-', $widget_id);
    }

    // Get the names of the data item input fields in the widget. Mostly there
    // is only one input field and often it will be called 'value', but some
    // datatypes, for example timespan, define two inputs. Remove all array keys
    // that start with # and this will leave just the input field names.
    // @todo Could this information be provided more easily, say by calling
    // $widget->getInputNames() ?
    $widget_input_names = array_keys($widget_form);
    $widget_input_names = array_filter($widget_input_names, function ($k) {
      return substr($k, 0, 1) !== '#';
    });

    if (isset($widget_form['#theme_wrappers']) && $widget_form['#theme_wrappers'][0] == 'fieldset') {
      // @todo Is checking #theme_wrappers = fieldset the best way to do it?
      // Should we count the number of input items? What if the widget had two
      // or more inputs but was badly formed with no fieldset theme_wrapper?
      $input_name = end($widget_input_names);
    }
    else {
      // The widget form is simple, so we need to add more of the form here.
      $form['context_definitions'][$context_name] += [
        '#type' => 'fieldset',
        '#title' => $context_definition->getLabel(),
      ];
      $input_name = reset($widget_input_names);
      $form['context_definitions'][$context_name][$input_name]['#title'] = $title;
    }

    // Extract the (last) element we have just added.
    $element = &$form['context_definitions'][$context_name][$input_name];

    if ($mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR) {
      $element['#type'] = 'textfield';
      $element['#description'] .= ' ' . $this->t("The data selector helps you drill down into the available data. <em>To make entity fields appear in the data selector, you may have to use the condition 'Entity is of bundle'.</em> More useful tips about data selection are available in <a href=':url'>the online documentation</a>.", [
        ':url' => 'https://www.drupal.org/node/1300042',
      ]);

      $url = $this->getRulesUiHandler()->getUrlFromRoute('autocomplete', []);
      $element['#attributes']['class'][] = 'rules-autocomplete';
      $element['#attributes']['data-autocomplete-path'] = $url->toString();
      $element['#attached']['library'][] = 'rules/rules.autocomplete';
    }
    elseif ($context_definition->isMultiple()) {
      $element['#type'] = 'textarea';
      $element['#description'] .= ' ' . $this->t('Enter one value per line for this multi-valued context.');

      // Convert the array of values into a single string split by newlines, to
      // show each item on a separate line in the text area.
      if (is_array($default_value)) {
        $element['#default_value'] = implode("\n", $default_value);
      }
    }

    // If the context is not restricted to one mode or the other, and the widget
    // is ok (not broken) then provide a button to switch between the two modes.
    if (empty($context_definition->getAssignmentRestriction()) && $widget_id != ContextDefinitionInterface::BROKEN_WIDGET_ID) {
      $value = $mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR ? $this->t('Switch to the direct input mode') : $this->t('Switch to data selection');
      $form['context_definitions'][$context_name]['switch_button'] = [
        '#type' => 'submit',
        '#name' => 'context_' . $context_name,
        '#attributes' => ['class' => ['rules-switch-button']],
        '#parameter' => $context_name,
        '#value' => $value,
        '#submit' => [static::class . '::switchContextMode'],
        // Do not validate!
        '#limit_validation_errors' => [],
      ];
    }

    return $form;
  }

  /**
   * Provides the form part for a 'provided' context parameter.
   */
  public function buildProvidedContextForm(array $form, FormStateInterface $form_state, $provides_name, ContextDefinitionInterface $provides_definition, array $configuration) {
    if (isset($configuration['provides_mapping'][$provides_name])) {
      $default_name = $configuration['provides_mapping'][$provides_name];
    }
    else {
      $default_name = $provides_name;
    }

    $form['provides'][$provides_name] = [
      '#type' => 'fieldset',
      '#title' => $provides_definition->getLabel(),
    ];

    $form['provides'][$provides_name]['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Variable name'),
      '#description' => $this->t('The variable name must contain only lowercase letters, numbers, and underscores and must be unique in the current scope.'),
      '#required' => TRUE,
      '#default_value' => $default_name,
    ];

    return $form;
  }

  /**
   * Creates a context config object from the submitted form values.
   *
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state containing the submitted values.
   * @param \Drupal\Core\Plugin\Context\ContextDefinitionInterface[] $context_definitions
   *   The context definitions of the plugin.
   *
   * @return \Drupal\rules\Context\ContextConfig
   *   The context config object populated with context mappings/values.
   */
  protected function getContextConfigFromFormValues(array $form, FormStateInterface $form_state, array $context_definitions) {
    $context_config = ContextConfig::create();
    if ($form_state->hasValue('context_definitions')) {
      foreach ($form_state->getValue('context_definitions') as $context_name => $value) {
        $context_definition = $context_definitions[$context_name];

        if ($context_definition->isMultiple()) {
          // Remove the switch button then get the input directly. This is not
          // the right way. The problem is that Rules uses 'multiple = TRUE' for
          // textarea to allow multiple entry items. However, they have to be
          // in an array for the TypedData setValue to work without throwing
          // InvalidArgumentException: Cannot set a list with a non-array value.
          // @todo Fix this.
          // @see https://www.drupal.org/project/typed_data/issues/2847804
          unset($value['switch_button']);
          $input = reset($value);
        }
        else {
          // Get the input the 'proper' way.
          // Create an instance of the widget that was used in this context so
          // that we can use extractFormValues() to get the entered data.
          $widget_id = $form['context_definitions'][$context_name]['#widget_id'];
          $widget = $this->getFormWidgetManager()->createInstance($widget_id);
          $data = $context_definition->getTypedDataManager()
            ->create($context_definition->getDataDefinition());
          $subform_state = SubformState::createWithParents(['context_definitions', $context_name], $form, $form_state);
          $widget->extractFormValues($data, $subform_state);
          $input = $data->getValue();
        }

        if ($form_state->get("context_$context_name") == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR) {
          $context_config->map($context_name, $input);
        }
        else {
          // Not selector, so it must be input.
          if ($context_definition->isMultiple()) {
            // Textareas should always have \r\n line breaks, but for more
            // robust parsing we should also accommodate just \n or just \r.
            // Additionally, we want to remove leading and trailing whitespace
            // from each line, and discard any empty lines.
            $values = preg_split('/\s*\R\s*/', $input, 0, PREG_SPLIT_NO_EMPTY);
            $context_config->setValue($context_name, $values);
          }
          else {
            $context_config->setValue($context_name, $input);
          }

          // For now, always add in the token context processor if it's present.
          // @todo Improve this in https://www.drupal.org/node/2804035.
          if ($this->getDataProcessorManager()->getDefinition('rules_tokens')) {
            $context_config->process($context_name, 'rules_tokens');
          }
        }
      }
    }

    return $context_config;
  }

  /**
   * Submit callback: switch a context to data selector or direct input mode.
   */
  public static function switchContextMode(array &$form, FormStateInterface $form_state) {
    $element_name = $form_state->getTriggeringElement()['#name'];
    $mode = $form_state->get($element_name);
    $switched_mode = $mode == ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR ? ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_INPUT : ContextDefinitionInterface::ASSIGNMENT_RESTRICTION_SELECTOR;
    $form_state->set($element_name, $switched_mode);

    $form_state->setRebuild();
  }

}
