<?php

namespace Drupal\webform_permissions_by_term\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_permissions_by_term_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webform_permissions_by_term.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_permissions_by_term.settings');

    $form = parent::buildForm($form, $form_state);

    $description = "";

    $form['webform_permissions_by_term_vocab'] = [
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()
      ->getEditable('webform_permissions_by_term.settings.webform_permissions_by_term_vocab')
      ->set('value', $form_state->getValue('webform_permissions_by_term_vocab'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
