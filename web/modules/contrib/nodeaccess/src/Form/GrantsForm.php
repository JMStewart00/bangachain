<?php

namespace Drupal\nodeaccess\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Builds the configuration form.
 */
class GrantsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nodeaccess_grants_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL) {
    $db = \Drupal::database();
    $form_values = $form_state->getValues();
    $settings = \Drupal::configFactory()->get('nodeaccess.settings');
    $nid = $node->id();
    $role_alias = $settings->get('role_alias');
    $role_map = $settings->get('role_map');
    $allowed_roles = [];
    $user = $this->currentUser();
    $allowed_grants = $settings->get('grants');
    foreach ($role_alias as $id => $role) {
      if ($role['allow']) {
        $allowed_roles[] = $id;
      }
    }
    if (!$form_values) {
      $form_values = [];
      // Load all roles.
      foreach ($role_alias as $id => $role) {
        $rid = $role_map[$id];
        $query = $db->select('node_access', 'n')
          ->fields('n', ['grant_view', 'grant_update', 'grant_delete'])
          ->condition('n.gid', $rid, '=')
          ->condition('n.realm', 'nodeaccess_rid', '=')
          ->condition('n.nid', $nid)
          ->execute();
        $result = $query->fetchAssoc();
        if (!empty($result)) {
          $form_values['rid'][$rid] = [
            'name' => $role['alias'],
            'grant_view' => (boolean) $result['grant_view'],
            'grant_update' => (boolean) $result['grant_update'],
            'grant_delete' => (boolean) $result['grant_delete'],
          ];
        }
        else {
          $form_values['rid'][$rid] = [
            'name' => $role['alias'],
            'grant_view' => FALSE,
            'grant_update' => FALSE,
            'grant_delete' => FALSE,
          ];
        }
      }

      // Load users from node_access.
      $query = $db->select('node_access', 'n');
      $query->join('users_field_data', 'ufd', 'ufd.uid = n.gid');
      $query->fields('n', ['grant_view', 'grant_update', 'grant_delete', 'nid']);
      $query->fields('ufd', ['name', 'uid']);
      $query->condition('n.nid', $nid, '=');
      $query->condition('n.realm', 'nodeaccess_uid', '=');
      $query->orderBy('ufd.name', 'ASC');
      $results = $query->execute();
      while ($account = $results->fetchObject()) {
        $form_values['uid'][$account->uid] = [
          'name' => $account->name,
          'keep' => 1,
          'grant_view' => $account->grant_view,
          'grant_update' => $account->grant_update,
          'grant_delete' => $account->grant_delete,
        ];
      }
    }
    else {
      // Perform search.
      if ($form_values['keys']) {
        $uids = [];
        $query = $db->select('users_field_data', 'ufd');
        $query->fields('ufd', ['uid', 'name']);
        if (isset($form_values['uid']) && is_array($form_values['uid'])) {
          $uids = array_keys($form_values['uid']);
        }
        if (!in_array($form_values['keys'], $uids)) {
          array_push($uids, $form_values['keys']);
        }
        $query->condition('ufd.uid', $uids, 'IN');
        $results = $query->execute();
        while ($account = $results->fetchObject()) {
          $form_values['uid'][$account->uid] = [
            'name' => $account->name,
            'keep' => 0,
          ];
        }
      }
      // Calculate default grants for found users.
      if (isset($form_values['uid']) && is_array($form_values['uid'])) {
        // set the cast type depending on which database engine is being used.
        if (strstr($db->version(), 'MariaDB') !== FALSE) {
          $cast_type = 'int';
        }
        elseif (strstr($db->clientVersion(), 'PostgreSQL') !== FALSE) {
          $cast_type = 'integer';
        }
        else {
          // assume it's MySQL.
          $cast_type = 'unsigned';
        }
        foreach (array_keys($form_values['uid']) as $uid) {
          if (!$form_values['uid'][$uid]['keep']) {
            foreach (['grant_view', 'grant_update', 'grant_delete'] as $grant_type) {

              $query = $db->select('node_access', 'na');
              $query->join('user__roles', 'r', '(na.gid = CAST(r.roles_target_id as ' . $cast_type . '))');
              $query->condition('na.nid', $nid, '=');
              $query->condition('na.realm', 'nodeaccess_rid', '=');
              $query->condition('r.entity_id', $uid, '=');
              $query->condition($grant_type, '1', '=');
              $query->range(0, 1);
              $query = $query->countQuery();
              $results = $query->execute();
              $count1 = $results->fetchField();

              $query = $db->select('node_access', 'na');
              $query->condition('na.nid', $nid, '=');
              $query->condition('na.realm', 'nodeaccess_uid', '=');
              $query->condition('na.gid', $uid, '=');
              $query->condition($grant_type, '1', '=');
              $query->range(0, 1);
              $query = $query->countQuery();
              $results = $query->execute();
              $count2 = $results->fetchField();

              $form_values['uid'][$uid][$grant_type] = $count1 || $count2;
            }
            $form_values['uid'][$uid]['keep'] = TRUE;
          }
        }
      }
    }

    $form_values['rid'] = isset($form_values['rid']) ? $form_values['rid'] : [];
    $form_values['uid'] = isset($form_values['uid']) ? $form_values['uid'] : [];
    $roles = $form_values['rid'];
    $users = $form_values['uid'];
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    // If $preserve is TRUE, the fields the user is not allowed to view or
    // edit are included in the form as hidden fields to preserve them.
    $preserve = $settings->get('preserve');
    // Roles table.
    if (count($allowed_roles)) {
      $header = [];
      $header[] = $this->t('Role');
      if ($allowed_grants['view']) {
        $header[] = $this->t('View');
      }
      if ($allowed_grants['edit']) {
        $header[] = $this->t('Edit');
      }
      if ($allowed_grants['delete']) {
        $header[] = $this->t('Delete');
      }
      $form['rid'] = [
        '#type' => 'table',
        '#header' => $header,
        '#tree' => TRUE,
      ];
      foreach ($allowed_roles as $id) {
        $rid = $role_map[$id];
        $form['rid'][$rid]['name'] = [
          '#markup' => $role_alias[$id]['alias'],
        ];
        if ($allowed_grants['view']) {
          $form['rid'][$rid]['grant_view'] = [
            '#type' => 'checkbox',
            '#default_value' => $roles[$rid]['grant_view'],
          ];
        }
        if ($allowed_grants['edit']) {
          $form['rid'][$rid]['grant_update'] = [
            '#type' => 'checkbox',
            '#default_value' => $roles[$rid]['grant_update'],
          ];
        }
        if ($allowed_grants['delete']) {
          $form['rid'][$rid]['grant_delete'] = [
            '#type' => 'checkbox',
            '#default_value' => $roles[$rid]['grant_delete'],
          ];
        }
      }
    }

    // Autocomplete returns errors if users don't have access to profiles.
    if ($user->hasPermission('access user profiles')) {
      $form['keys'] = [
        '#type' => 'entity_autocomplete',
        '#default_value' => isset($form_values['keys']) ? $form_values['keys'] : '',
        '#size' => 40,
        '#target_type' => 'user',
        '#title' => $this->t('Enter names to search for users'),
      ];
    }
    else {
      $form['keys'] = [
        '#type' => 'textfield',
        '#default_value' => isset($form_values['keys']) ? $form_values['keys'] : '',
        '#size' => 40,
      ];
    }
    $form['keys']['#prefix'] = '<p><div class="container-inline">';
    $form['search'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#submit' => ['::searchUser'],
      '#suffix' => '</div></p>',
    ];
    // Users table.
    if (count($users)) {
      $header = [];
      $header[] = $this->t('User');
      $header[] = $this->t('Keep?');
      if ($allowed_grants['view']) {
        $header[] = $this->t('View');
      }
      if ($allowed_grants['edit']) {
        $header[] = $this->t('Edit');
      }
      if ($allowed_grants['delete']) {
        $header[] = $this->t('Delete');
      }
      $form['uid'] = [
        '#type' => 'table',
        '#header' => $header,
      ];
      foreach ($users as $uid => $account) {
        $form['uid'][$uid]['name'] = [
          '#markup' => $account['name'],
        ];
        $form['uid'][$uid]['keep'] = [
          '#type' => 'checkbox',
          '#default_value' => $account['keep'],
        ];
        if ($allowed_grants['view']) {
          $form['uid'][$uid]['grant_view'] = [
            '#type' => 'checkbox',
            '#default_value' => $account['grant_view'],
          ];
        }
        if ($allowed_grants['edit']) {
          $form['uid'][$uid]['grant_update'] = [
            '#type' => 'checkbox',
            '#default_value' => $account['grant_update'],
          ];
        }
        if ($allowed_grants['delete']) {
          $form['uid'][$uid]['grant_delete'] = [
            '#type' => 'checkbox',
            '#default_value' => $account['grant_delete'],
          ];
        }
      }
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Grants'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uids = $form_state->getValue('uid');
    // Delete unkept users.
    if (!empty($uids) && is_array($uids)) {
      foreach ($uids as $uid => $row) {
        if (!$row['keep']) {
          unset($uids[$uid]);
        }
      }
      $form_state->setValue('uid', $uids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db = \Drupal::database();
    // Update configuration.
    $values = $form_state->getValues();
    $nid = $values['nid'];
    $grants = [];
    $node = Node::load($nid);

    foreach (['uid', 'rid'] as $type) {
      $realm = 'nodeaccess_' . $type;
      if (isset($values[$type]) && is_array($values[$type])) {
        foreach ($values[$type] as $gid => $line) {
          $grant = [
            'gid' => $gid,
            'realm' => $realm,
            'grant_view' => empty($line['grant_view']) ? 0 : $line['grant_view'],
            'grant_update' => empty($line['grant_update']) ? 0 : $line['grant_update'],
            'grant_delete' => empty($line['grant_delete']) ? 0 : $line['grant_delete'],
          ];
          if ($grant['grant_view'] || $grant['grant_update'] || $grant['grant_delete']) {
            $grants[] = $grant;
          }
        }
      }
    }
    // Save role and user grants to our own table.
    $db->delete('nodeaccess')
      ->condition('nid', $nid)
      ->execute();
    foreach ($grants as $grant) {
      $id = $db->insert('nodeaccess')
        ->fields([
          'nid' => $nid,
          'gid' => $grant['gid'],
          'realm' => $grant['realm'],
          'grant_view' => $grant['grant_view'],
          'grant_update' => $grant['grant_update'],
          'grant_delete' => $grant['grant_delete'],
        ])
        ->execute();
    }
    \Drupal::entityTypeManager()->getAccessControlHandler('node')->acquireGrants($node);
    \Drupal::service('node.grant_storage')->write($node, $grants);
    \Drupal::messenger()->addMessage($this->t('Grants saved.'));

    $tags = ['node:' . $node->id()];
    Cache::invalidateTags($tags);
  }

  /**
   * Helper function to search usernames.
   */
  public function searchUser(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setRebuild();
    $form_state->setStorage($values);
  }

}
