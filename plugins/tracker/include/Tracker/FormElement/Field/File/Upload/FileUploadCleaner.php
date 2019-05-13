<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\PathAllocator;

final class FileUploadCleaner
{
    /**
     * @var PathAllocator
     */
    private $path_allocator;
    /**
     * @var FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var \Logger
     */
    private $logger;

    public function __construct(
        \Logger $logger,
        PathAllocator $path_allocator,
        FileOngoingUploadDao $dao,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->logger               = $logger;
        $this->path_allocator       = $path_allocator;
        $this->dao                  = $dao;
        $this->transaction_executor = $transaction_executor;
    }

    public function deleteDanglingFilesToUpload(\DateTimeImmutable $current_time)
    {
        $this->logger->info('Deleting dangling files to upload.');
        $this->transaction_executor->execute(
            function () use ($current_time): void {
                $unused_current_file_size = 0;
                $current_timestamp        = $current_time->getTimestamp();
                $rows                     = $this->dao->searchUnusableFiles($current_timestamp);
                $this->logger->info('Found ' . count($rows) . ' dangling files.');
                foreach ($rows as $row) {
                    $file_information = new FileBeingUploadedInformation(
                        $row['id'],
                        $row['filename'],
                        $row['filesize'],
                        $unused_current_file_size
                    );

                    $path = $this->path_allocator->getPathForItemBeingUploaded($file_information);
                    if (\is_file($path)) {
                        \unlink($path);
                    }
                }
                $this->dao->deleteUnusableFiles($current_timestamp);
            }
        );
    }
}
