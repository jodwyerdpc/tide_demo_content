<?php

namespace Drupal\tide_demo_content\Plugin\yaml_content\process;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\File\FileSystemInterface;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\yaml_content\process\File;

/**
 * Plugin for processing and loading a file attachment.
 *
 * @YamlContentProcess(
 *   id = "file_path_rename",
 *   title = @Translation("File Processor"),
 *   description = @Translation("Processing and loading a file attachment.")
 * )
 */
class ProcessFile extends File {

  /**
   * {@inheritdoc}
   */
  public function process(ProcessingContext $context, array &$field_data) {
    [$entity_type, $filter_params] = $this->configuration;

    $filename = $filter_params['filename'];
    $directory = '/data_files/';
    // If the entity type is an image, look in to the /images directory.
    if ($entity_type === 'image') {
      $directory = '/images/';
    }
    // Path set tide_demo_content directory.
    $output = file_get_contents($context->getContentLoader()->getContentPath() . $directory . $filename);
    if ($output !== FALSE) {
      $destination = 'public://';
      // Look-up the field's directory configuration.
      $directory = $context->getField()->getSetting('file_directory');
      if ($directory) {
        $directory = trim($directory, '/');
        $directory = PlainTextOutput::renderFromHtml('tide_demo_content');
        if ($directory) {
          $destination .= $directory . '/';
        }
      }

      // Create the destination directory if it does not already exist.
      \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);

      // Save the file data or return an existing file.
      $file = file_save_data($output, $destination . $filename, FileSystemInterface::EXISTS_REPLACE);

      // Use the newly created file id as the value.
      $field_data['target_id'] = $file->id();

      // Remove process data to avoid issues when setting the value.
      unset($field_data['#process']);

      return $file->id();
    }
    $this->throwParamError('Unable to process file content', $entity_type, $filter_params);
    return NULL;
  }

  /**
   * Return the file system service.
   *
   * @return \Drupal\Core\File\FileSystemInterface
   *   The file system service.
   */
  protected static function getFileSystem() : FileSystemInterface {
    // @todo Fix by using proper dependency injection here.
    return \Drupal::service('file_system');
  }

}
