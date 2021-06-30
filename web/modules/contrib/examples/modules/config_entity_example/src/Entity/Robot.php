<?php

namespace Drupal\config_entity_example\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the robot entity.
 *
 * The lines below, starting with '@ConfigEntityType,' are a plugin annotation.
 * These define the entity type to the entity type manager.
 *
 * The properties in the annotation are as follows:
 *  - id: The machine name of the entity type.
 *  - label: The human-readable label of the entity type. We pass this through
 *    the "@Translation" wrapper so that the multilingual system may
 *    translate it in the user interface.
 *  - handlers: An array of entity handler classes, keyed by handler type.
 *    - access: The class that is used for access checks.
 *    - list_builder: The class that provides listings of the entity.
 *    - form: An array of entity form classes keyed by their operation.
 *  - entity_keys: Specifies the class properties in which unique keys are
 *    stored for this entity type. Unique keys are properties which you know
 *    will be unique, and which the entity manager can use as unique in database
 *    queries.
 *  - links: entity URL definitions. These are mostly used for Field UI.
 *    Arbitrary keys can set here. For example, User sets cancel-form, while
 *    Node uses delete-form.
 *
 * @see http://previousnext.com.au/blog/understanding-drupal-8s-config-entities
 * @see annotation
 * @see Drupal\Core\Annotation\Translation
 *
 * @ingroup config_entity_example
 *
 * @ConfigEntityType(
 *   id = "robot",
 *   label = @Translation("Robot"),
 *   admin_permission = "administer robots",
 *   handlers = {
 *     "access" = "Drupal\config_entity_example\RobotAccessController",
 *     "list_builder" = "Drupal\config_entity_example\Controller\RobotListBuilder",
 *     "form" = {
 *       "add" = "Drupal\config_entity_example\Form\RobotAddForm",
 *       "edit" = "Drupal\config_entity_example\Form\RobotEditForm",
 *       "delete" = "Drupal\config_entity_example\Form\RobotDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/examples/config_entity_example/manage/{robot}",
 *     "delete-form" = "/examples/config_entity_example/manage/{robot}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "floopy"
 *   }
 * )
 */
class Robot extends ConfigEntityBase {

  /**
   * The robot ID.
   *
   * @var string
   */
  public $id;

  /**
   * The robot UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The robot label.
   *
   * @var string
   */
  public $label;

  /**
   * The robot floopy flag.
   *
   * @var string
   */
  public $floopy;

}
