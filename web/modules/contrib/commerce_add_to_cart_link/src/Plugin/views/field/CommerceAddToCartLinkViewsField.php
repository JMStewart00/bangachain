<?php

namespace Drupal\commerce_add_to_cart_link\Plugin\views\field;

use Drupal\commerce_add_to_cart_link\AddToCartLink;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Defines a Views field that adds an add to cart link.
 *
 * @ViewsField("commerce_add_to_cart_link")
 */
class CommerceAddToCartLinkViewsField extends LinkBase {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['destination'] = [
      'default' => FALSE,
    ];
    $options['quantity'] = [
      'default' => 1,
    ];
    $options['combine'] = [
      'default' => TRUE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['destination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include destination'),
      '#description' => $this->t('Enforce a <code>destination</code> parameter in the link to return the user to the original view upon completing the link action. Most operations include a destination by default and this setting is no longer needed.'),
      '#default_value' => $this->options['destination'],
    ];

    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#description' => $this->t('Quantity to add to cart.'),
      '#default_value' => $this->options['quantity'],
    ];

    $form['combine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine cart items'),
      '#description' => $this->t('Combine multiple added products (if checked), or make separate cart items (unchecked).'),
      '#default_value' => $this->options['combine'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $id = (int) $this->getValue($row);
    return $this->getUrl($id);
  }

  /**
   * Get URL.
   *
   * @param int $id
   *   The product variation id.
   *
   * @return \Drupal\Core\Url
   *   The URL.
   */
  protected function getUrl(int $id) {
    $url = AddToCartLink::fromVariationId($id)->url();
    $query = (array) $url->getOption('query');
    if ($this->options['destination']) {
      $query += $this->getDestinationArray();
    }
    if ($this->options['quantity'] != 1) {
      $query += ['quantity' => $this->options['quantity']];
    }
    if (!$this->options['combine']) {
      $query += ['combine' => 0];
    }
    $url->setOption('query', $query);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Add to cart');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $build = parent::render($row);
    $metadata = AddToCartLink::fromVariationId($this->getValue($row))->metadata();
    $metadata->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // @see \Drupal\views\Plugin\views\field\FieldPluginBase::query
    $this->ensureMyTable();
    // Add the field.
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];
    $this->field_alias = $this->query->addField($this->tableAlias, $this->realField, NULL, $params);
    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  protected function documentSelfTokens(&$tokens) {
    parent::documentSelfTokens($tokens);

    $tokens['{{ ' . $this->options['id'] . '__url' . ' }}'] = $this->t('The URL of the add to cart link.');
  }

  /**
   * {@inheritdoc}
   */
  protected function addSelfTokens(&$tokens, $item) {
    parent::addSelfTokens($tokens, $item);

    /** @var \Drupal\Core\Url $url */
    $url = $item['url'];
    $tokens['{{ ' . $this->options['id'] . '__url' . ' }}'] = $url->toString();
  }

}
