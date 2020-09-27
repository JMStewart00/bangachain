<?php

namespace Drupal\commerce_smart_importer\Controller;

use Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\File\FileSystem;
use Drupal\commerce_smart_importer\CommerceSmartImporterConstants;

/**
 * File upload handler.
 */
class SmartImporterFileUploadController extends ControllerBase {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Main Smart Importer service.
   *
   * @var \Drupal\commerce_smart_importer\Plugin\CommerceSmartImporerService
   */
  protected $importerService;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * SmartImporterFileUploadController constructor.
   */
  public function __construct(AccountProxy $user,
                              CommerceSmartImporerService $service,
                              FileSystem $fileSystem) {
    $this->currentUser = $user;
    $this->importerService = $service;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('current_user'),
      $container->get('commerce_smart_importer.service'),
      $container->get('file_system')
    );
  }

  /**
   * Handles uploaded files.
   */
  public function uploadFile(Request $request) {
    $image = $request->files->get('file');
    if (!is_dir(CommerceSmartImporterConstants::TEMP_DIR)) {
      mkdir(CommerceSmartImporterConstants::TEMP_DIR);
    }
    $image->move($this->fileSystem->realpath(CommerceSmartImporterConstants::TEMP_DIR) . '/', $image->getClientOriginalName());
    return new Response('Uploaded successfully');
  }

}
