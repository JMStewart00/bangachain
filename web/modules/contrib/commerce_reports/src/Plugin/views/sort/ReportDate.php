<?php

namespace Drupal\commerce_reports\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\Date;
/**
 * Extends View basic sort handler for dates to add Week granularity.
 *
 * @ViewsSort("commerce_reports_date")
 */
class ReportDate extends Date {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['granularity']['#options']['week'] = $this->t('Week');
  }

  /**
   * Called to add the sort to a query.
   */
  public function query() {
    $this->ensureMyTable();
    switch ($this->options['granularity']) {
      case 'second':
      default:
        $this->query->addOrderBy($this->tableAlias, $this->realField, $this->options['order']);
        return;
      case 'minute':
        $formula = $this->getDateFormat('YmdHi');
        break;
      case 'hour':
        $formula = $this->getDateFormat('YmdH');
        break;
      case 'day':
        $formula = $this->getDateFormat('Ymd');
        break;
      case 'week':
        $formula = $this->getDateFormat('YW');
        break;
      case 'month':
        $formula = $this->getDateFormat('Ym');
        break;
      case 'year':
        $formula = $this->getDateFormat('Y');
        break;
    }

    // Add the field.
    $this->query->addOrderBy(NULL, $formula, $this->options['order'], $this->tableAlias . '_' . $this->field . '_' . $this->options['granularity']);
  }

}
