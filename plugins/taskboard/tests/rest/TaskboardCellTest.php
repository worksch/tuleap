<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\REST;

use REST_TestDataBuilder;

final class TaskboardCellTest extends \RestBase
{
    /** @var int */
    private static $milestone_id;
    /** @var array<string, int> */
    private static $swimlane_ids;
    /** @var array<string, int> */
    private static $column_ids;

    public function setUp(): void
    {
        parent::setUp();
        if (! self::$milestone_id && ! self::$swimlane_ids && ! self::$column_ids) {
            self::$milestone_id = $this->getMilestoneId();
            self::$swimlane_ids = $this->getSwimlaneIds();
            self::$column_ids   = $this->getColumnIds();
        }
    }

    /**
     * @dataProvider getUserNameAndExpectedStatusCode
     */
    public function testPATCHCellReordersCardsOfASwimlane(string $user_name, int $expected_status_code): void
    {
        $US2_swimlane_id = self::$swimlane_ids['US2'];
        $todo_column_id  = self::$column_ids['Todo'];
        $task_ids        = $this->getChildrenIdsOfSwimlane($US2_swimlane_id);
        $first_task_id   = $task_ids['Task1'];
        $other_task_ids  = [$task_ids['Task2'], $task_ids['Task3']];
        $patch_payload   = [
            "ids"         => $other_task_ids,
            "direction"   => "before",
            "compared_to" => $first_task_id
        ];

        $response = $this->getResponse(
            $this->client->patch(
                'taskboard_cells/' . $US2_swimlane_id . '/column/' . $todo_column_id,
                null,
                json_encode($patch_payload)
            ),
            $user_name
        );
        $this->assertEquals($expected_status_code, $response->getStatusCode());
    }

    /**
     * @return array<string, int>
     */
    public function getUserNameAndExpectedStatusCode(): array
    {
        return [
            'REST API User 1' => [REST_TestDataBuilder::TEST_USER_1_NAME, 200],
            'Read-only bot' => [REST_TestDataBuilder::TEST_BOT_USER_NAME, 404]
        ];
    }

    /**
     * @return int[]
     */
    private function getSwimlaneIds(): array
    {
        $response = $this->getResponse(
            $this->client->get('taskboard/' . self::$milestone_id . '/cards'),
            \TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertSame(200, $response->getStatusCode());
        return $this->indexByLabel($response->json());
    }

    /**
     * @return int[]
     */
    private function getChildrenIdsOfSwimlane(int $swimlane_id): array
    {
        $response = $this->getResponse(
            $this->client->get('taskboard_cards/' . $swimlane_id . '/children?milestone_id=' . self::$milestone_id),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertSame(200, $response->getStatusCode());
        return $this->indexByLabel($response->json());
    }

    /**
     * @return int[]
     */
    private function getColumnIds(): array
    {
        $response = $this->getResponse(
            $this->client->get('taskboard/' . self::$milestone_id . '/columns'),
            \TestDataBuilder::TEST_USER_1_NAME
        );
        $this->assertSame(200, $response->getStatusCode());
        return $this->indexByLabel($response->json());
    }

    private function indexByLabel(array $list): array
    {
        $indexed = [];
        foreach ($list as $item) {
            $indexed[$item['label']] = $item['id'];
        }
        return $indexed;
    }

    private function getMilestoneId(): int
    {
        $project_id = $this->getProjectId('taskboard');

        $response   = $this->getResponse($this->client->get('projects/' . $project_id . '/milestones'));
        $milestones = $response->json();

        $this->assertCount(1, $milestones);

        return (int) $milestones[0]['id'];
    }
}
