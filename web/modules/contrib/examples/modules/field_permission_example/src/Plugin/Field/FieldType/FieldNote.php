<?php

namespace Drupal\field_permission_example\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_permission_example' field type.
 *
 * @FieldType(
 *   id = "field_permission_example_fieldnote",
 *   label = @Translation("Example FieldNote"),
 *   module = "field_permission_example",
 *   description = @Translation("Demonstrates a field simple field note type with permission-based access control."),
 *   default_widget = "field_permission_example_widget",
 *   default_formatter = "field_permission_example_simple_formatter"
 * )
 */
class FieldNote extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Field Note'));

    return $properties;
  }

}
