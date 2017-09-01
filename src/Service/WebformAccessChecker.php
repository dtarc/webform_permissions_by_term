<?php

namespace Drupal\webform_permissions_by_term\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\webform_permissions_by_term\Event\EntityFieldValueAccessDeniedEvent;
use Drupal\permissions_by_term\Service\AccessCheck;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class WebformAccessChecker.
 *
 * @package Drupal\webform_permissions_by_term\Service
 */
class WebformAccessChecker extends AccessCheck implements WebformAccessCheckerInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * The cache for checked entities.
   *
   * @var \Drupal\webform_permissions_by_term\Service\CheckedEntityCache
   */
  private $checkedEntityCache;

  /**
   * The entity field value access denied event.
   *
   * @var \Drupal\webform_permissions_by_term\Event\EntityFieldValueAccessDeniedEvent
   */
  private $event;

  /**
   * WebformAccessChecker constructor.
   *
   * We override the constructor, because we do not need the entity manager.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\webform_permissions_by_term\Service\CheckedEntityCache $checked_entity_cache
   *   The cache for checked entities.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The core entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EventDispatcherInterface $event_dispatcher,
    CheckedEntityCache $checked_entity_cache,
    EntityManagerInterface $entity_manager,
    Connection $database
  ) {
    parent::__construct($database);
    $this->eventDispatcher = $event_dispatcher;
    $this->checkedEntityCache = $checked_entity_cache;

    $this->event = new EntityFieldValueAccessDeniedEvent();
  }

  /**
   * {@inheritdoc}
   */
  public function isWebformAccessAllowed(ContentEntityInterface $entity, $uid = FALSE) {
    if ($entity->getEntityTypeId() == 'webform_submission') {
      $permissions_by_term = NULL;

      // Get vocab id dynamically.
      // This needs to be the vocab designated for use with permissions_by_term module.
      $chosen_perms_vocab = get_webform_secured_by_term_vocab_machine_name();
      foreach ($entity->getWebform()->getElementsDecoded() as $element_key => $element_value) {

        if (array_key_exists('#vocabulary', $element_value)) {
          // Check if this vocab is infact the selected one in settings form.
          if ($chosen_perms_vocab == $element_value['#vocabulary']) {
            $permissions_by_term = $entity->getData($element_key);
          }
        }
      }

      if ($permissions_by_term != NULL) {
        if (!$this->isAccessAllowedByDatabase($permissions_by_term, $uid)) {
          // Return that the user is not allowed to access this entity.
          return FALSE;
        }
        return TRUE;
      }

    }
  }

}
