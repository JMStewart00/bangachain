<?php

namespace Drupal\commerce_loyalty_points\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the LoyaltyPoints entity.
 *
 * @ContentEntityType(
 *   id = "commerce_loyalty_points",
 *   label = @Translation("Loyalty points"),
 *   handlers = {
 *     "storage" = "Drupal\commerce_loyalty_points\LoyaltyPointsStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_loyalty_points\LoyaltyPointsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_loyalty_points\Form\LoyaltyPointsForm",
 *       "edit" = "Drupal\commerce_loyalty_points\Form\LoyaltyPointsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_loyalty_points",
 *   admin_permission = "administer loyalty points entities",
 *   entity_keys = {
 *     "id" = "lpid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/loyalty-points/{commerce_loyalty_points}",
 *     "add-form" = "/loyalty-points/add",
 *     "edit-form" = "/loyalty-points/{commerce_loyalty_points}/edit",
 *     "delete-form" = "/loyalty-points/{commerce_loyalty_points}/delete",
 *     "collection" = "/admin/commerce/loyalty-points",
 *   },
 * )
 */
class LoyaltyPoints extends ContentEntityBase implements LoyaltyPointsInterface {

  /**
   * {@inheritdoc}
   */
  public function getLoyaltyPoints() {
    return $this->get('loyalty_points')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    $uid = $this->get('uid')->target_id;
    return \Drupal::entityTypeManager()
      ->getStorage('user')
      ->load($uid);
  }

  /**
   * {@inheritdoc}
   */
  public function getReason() {
    return $this->get('reason')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['lpid']->setLabel(t('Loyalty point ID'))
      ->setDescription(t('The loyalty point identifier.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Username'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['loyalty_points'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Loyalty points'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'decimal',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'decimal_textfield',
        'weight' => 5,
      ])
      ->setDescription(t('Positive value to add, negative to deduct'));

    $fields['reason'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reason'))
      ->setDescription(t('Reason for loyalty points given to a user.'))
      ->setSettings([
        'max_length' => 256,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

}
