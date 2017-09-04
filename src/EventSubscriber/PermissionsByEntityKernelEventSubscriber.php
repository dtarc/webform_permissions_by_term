<?php

namespace Drupal\webform_permissions_by_term\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\webform_permissions_by_term\Service\WebformAccessCheckerInterface;
use Drupal\webform_permissions_by_term\Service\CheckedEntityCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PermissionsByEntityKernelEventSubscriber.
 *
 * @package Drupal\webform_permissions_by_term\EventSubscriber
 */
class PermissionsByEntityKernelEventSubscriber implements EventSubscriberInterface {

  /**
   * The access checker.
   *
   * @var \Drupal\webform_permissions_by_term\Service\WebformAccessCheckerInterface
   */
  private $webformAccessChecker;

  /**
   * The core string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $translation;

  /**
   * The cache for checked entities.
   *
   * @var \Drupal\webform_permissions_by_term\Service\CheckedEntityCache
   */
  private $checkedEntityCache;

  /**
   * PermissionsByEntityKernelEventSubscriber constructor.
   *
   * @param \Drupal\webform_permissions_by_term\Service\WebformAccessCheckerInterface $webform_access_checker
   *   The service to check if the current user is allowed to access an entity.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The core string translator.
   * @param \Drupal\webform_permissions_by_term\Service\CheckedEntityCache $checked_entity_cache
   *   The cache for checked entities.
   */
  public function __construct(
    WebformAccessCheckerInterface $webform_access_checker,
    TranslationInterface $translation,
    CheckedEntityCache $checked_entity_cache
  ) {
    $this->webformAccessChecker = $webform_access_checker;
    $this->translation = $translation;
    $this->checkedEntityCache = $checked_entity_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 5],
    ];
  }

  /**
   * Callback method for the KernelEvents::REQUEST event.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event instance.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    // Get the current request from the event.
    $request = $event->getRequest();

    // Get the entity.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = NULL;

    if ($request->attributes->has('webform_submission')) {
      $entity = $request->attributes->get('webform_submission');
    }

//    if ($request->attributes->has('webform_submission')) {
//      $entity = $request->attributes->get('webform_submission');
//    }

    // If there is no entity abort here.
    if (!$entity) {
      return;
    }

    // If we already checked this entity, we do nothing.
    if ($this->checkedEntityCache->isChecked($entity)) {
      return;
    }
    else {
      // Add this entity to the cache.
      $this->checkedEntityCache->add($entity);
    }



    // Check if the current user is allowed to access this webform submission.
    if (
      $entity &&
      $entity instanceof ContentEntityInterface &&
      $entity->getEntityTypeId() == 'webform_submission' &&
      $entity->getWebform()->getSetting('webform_permissions_by_term')['enable_webform_permissions_by_term'] == TRUE
    ) {
      if (!$this->webformAccessChecker->isWebformAccessAllowed($entity)) {
        // If the current user is not allowed to access this webform submission,
        // we throw an AccessDeniedHttpException.
        throw new AccessDeniedHttpException(
          $this->translation->translate(
            'You are not allowed to view this webform submission.'
          )
        );
      }

    }
  }

}
