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

namespace Tuleap\Taskboard\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\AgileDashboard\REST\v1\IdsFromBodyAreNotUniqueException;
use Tuleap\AgileDashboard\REST\v1\OrderIdOutOfBoundException;
use Tuleap\AgileDashboard\REST\v1\OrderRepresentation;
use Tuleap\AgileDashboard\REST\v1\OrderValidator;
use Tuleap\AgileDashboard\REST\v1\Rank\ArtifactsRankOrderer;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\UserManager;
use Tuleap\Taskboard\Swimlane\SwimlaneChildrenRetriever;

class CellPatcher
{
    /** @var UserManager */
    private $user_manager;
    /** @var \Tracker_ArtifactFactory */
    private $artifact_factory;
    /** @var SwimlaneChildrenRetriever */
    private $children_retriever;
    /** @var ArtifactsRankOrderer */
    private $rank_orderer;

    public function __construct(
        UserManager $user_manager,
        \Tracker_ArtifactFactory $artifact_factory,
        SwimlaneChildrenRetriever $children_retriever,
        ArtifactsRankOrderer $rank_orderer
    ) {
        $this->user_manager       = $user_manager;
        $this->artifact_factory   = $artifact_factory;
        $this->children_retriever = $children_retriever;
        $this->rank_orderer       = $rank_orderer;
    }

    public static function build(): self
    {
        $artifact_factory = \Tracker_ArtifactFactory::instance();
        return new CellPatcher(
            UserManager::build(),
            $artifact_factory,
            new SwimlaneChildrenRetriever(),
            ArtifactsRankOrderer::build()
        );
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function patchCell(int $swimlane_id, ?OrderRepresentation $order = null): void
    {
        $current_user      = $this->user_manager->getCurrentUser();
        $swimlane_artifact = $this->getSwimlaneArtifact($current_user, $swimlane_id);
        $project           = $swimlane_artifact->getTracker()->getProject();
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        if ($order !== null) {
            $order->checkFormat();
            $this->validateOrder($order, $current_user, $swimlane_artifact);
            $this->rank_orderer->reorder($order, \Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, $project);
        }
    }

    /**
     * @throws RestException
     */
    private function getSwimlaneArtifact(PFUser $current_user, int $id): \Tracker_Artifact
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if (! $artifact || ! $artifact->userCanView($current_user)) {
            throw new RestException(404);
        }

        return $artifact;
    }

    /**
     * @throws RestException
     */
    private function validateOrder(
        OrderRepresentation $order,
        PFUser $current_user,
        \Tracker_Artifact $swimlane_artifact
    ): void {
        $children_artifact_ids          = $this->children_retriever->getSwimlaneArtifactIds(
            $swimlane_artifact,
            $current_user
        );
        $index_of_swimlane_children_ids = array_fill_keys($children_artifact_ids, true);
        $order_validator = new OrderValidator($index_of_swimlane_children_ids);
        try {
            $order_validator->validate($order);
        } catch (IdsFromBodyAreNotUniqueException | OrderIdOutOfBoundException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }
}
