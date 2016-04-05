<?php
namespace Romm\SiteFactory\Duplication\Process;

/*
 * 2016 Romain CANON <romain.hydrocanon@gmail.com>
 *
 * This file is part of the TYPO3 Site Factory project.
 * It is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License, either
 * version 3 of the License, or any later version.
 *
 * For the full copyright and license information, see:
 * http://www.gnu.org/licenses/gpl-3.0.html
 */

use Romm\SiteFactory\Duplication\AbstractDuplicationProcess;

/**
 * Class containing functions called when a site is being duplicated.
 * See function "run" for more information.
 */
class UploadedFilesProcess extends AbstractDuplicationProcess {
	/**
	 * Gets all the fields which contains files, and upload them to the given
	 * file mount.
	 */
	public function run() {
		/** @var \Romm\SiteFactory\Form\Fields\AbstractField[] $filesFields */
		$filesFields = array();
		foreach ($this->getFields() as $field)
			if ($field->getSettings('moveToFileMount') && $field->getValue() != '') {
				if (substr($field->getValue(), 0, 4) == 'new:') {
					$field->setValue(substr($field->getValue(), 4, strlen($field->getValue()) - 4));
					$filesFields[] = $field;
				}
			}

		if (!empty($filesFields)) {
			$fileMountUid = $this->getDuplicationData('fileMountUid');

			if ($fileMountUid) {
				/** @var \TYPO3\CMS\Extbase\Domain\Repository\FileMountRepository $fileMountRepository */
				$fileMountRepository = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Domain\\Repository\\FileMountRepository');

				/** @var \TYPO3\CMS\Extbase\Domain\Model\FileMount $fileMount */
				$fileMount = $fileMountRepository->findByUid($fileMountUid);
				if ($fileMount) {
					$filesMoved = array();

					/** @var \TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory */
					$resourceFactory = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
					$storage = $resourceFactory->getDefaultStorage();

					/** @var \TYPO3\CMS\Core\Resource\Folder $folder */
					$folderPath =  substr($fileMount->getPath(), 1, strlen($fileMount->getPath()));
					$folder = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\Folder', $storage, $folderPath, 'SiteFactory');

					/** @var \TYPO3\CMS\Core\Resource\Driver\LocalDriver $driver */
					$driver = $resourceFactory->getDriverObject($storage->getDriverType(), $storage->getConfiguration());
					$driver->processConfiguration();

					foreach ($filesFields as $field) {
						$name = $field->getName();
						$path = $field->getValue();
						$fileExtension = substr(strrchr($path, '.'), 1);
						$identifier = $folderPath . $name . '.' . $fileExtension;

						if (file_exists($path)) {
							/** @var \TYPO3\CMS\Core\Resource\File $file */
							if ($driver->fileExists($identifier)) {
								$file = $storage->getFile($identifier);
								$storage->replaceFile($file, $path);

								/** @var \TYPO3\CMS\Core\Resource\ProcessedFileRepository $processedFileRepository */
								$processedFileRepository = $this->objectManager->get('TYPO3\\CMS\\Core\\Resource\\ProcessedFileRepository');
								/** @var \TYPO3\CMS\Core\Resource\ProcessedFile[] $processedFiles */
								$processedFiles = $processedFileRepository->findAllByOriginalFile($file);

								foreach($processedFiles as $processedFile)
									$processedFile->delete();
							}
							else
								$file = $storage->addFile($path, $folder, $name . '.' . $fileExtension, 'replace');

							$this->getField($field->getName())->setValue($driver->getPublicUrl($identifier));
							$filesMoved[$name] = $file->getName();
						}
					}

					if (!empty($filesMoved)) {
						$this->addNotice(
							'duplication_process.uploaded_files.notice.success',
							1435421057,
							array($folder->getPublicUrl(), '"' . implode('", ', $filesMoved) . '"')
						);
					}
				}
			}
		}
	}
}
